<?php

namespace app\controller;

use app\BaseController;
use app\model\FileModel;
use app\model\LinkModel;
use app\model\SettingModel;
use DOMDocument;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use think\facade\Cache;
use think\facade\Filesystem;
use think\facade\View;
use think\helper\Str;

class Api extends BaseController
{
    public function site(): \think\response\Json
    {
        return $this->success("ok", [
            'email' => $this->systemSetting('email', ''),
            'qqGroup' => $this->systemSetting("qqGroup", ''),
            'beianMps' => $this->systemSetting("beianMps", ''),
            'copyright' => $this->systemSetting("copyright", ''),
            "recordNumber" => $this->systemSetting("recordNumber", ''),
            "mobileRecordNumber" => $this->systemSetting('mobileRecordNumber', '0'),
            "auth" => $this->auth,
            "logo" => $this->systemSetting('logo', ''),
            "qq_login" => $this->systemSetting('qq_login', '0'),
            "loginCloseRecordNumber" => $this->systemSetting('loginCloseRecordNumber', '0'),
            "is_push_link_store" => $this->auth ? $this->systemSetting('is_push_link_store', '0') : '0',
            "is_push_link_store_tips" => $this->systemSetting('is_push_link_store_tips', '0'),
            "is_push_link_status" => $this->systemSetting("is_push_link_status", '0'),
            'google_ext_link' => $this->systemSetting("google_ext_link", ''),
            'edge_ext_link' => $this->systemSetting("edge_ext_link", ''),
            'local_ext_link' => $this->systemSetting("local_ext_link", ''),
            "customAbout" => $this->systemSetting("customAbout", ''),
            "user_register" => $this->systemSetting("user_register", '0', true),
            "tip" => [
                "ds_status" => $this->systemSetting('ds_status', '0', true),
                "ds_template" => $this->systemSetting('ds_template', 'org', true),
                "ds_alipay_img" => $this->systemSetting('ds_alipay_img', '', true),
                "ds_wx_img" => $this->systemSetting('ds_wx_img', '', true),
                "ds_custom_url" => $this->systemSetting("ds_custom_url", '', true),
                'ds_title' => $this->systemSetting('ds_title', '', true),
                'ds_tips' => $this->systemSetting('ds_tips', '', true)
            ]
        ]);
    }

    public function background(): \think\response\File
    {
        return download('static/background.jpeg', 'background.jpeg')->mimeType(\PluginStaticSystem::mimeType('static/background.jpeg'))->force(false)->expire(60 * 60 * 24 * 3);
    }

    //获取默认壁纸
    function DefBg(): \think\response\Json
    {
        $config = $this->systemSetting('defaultTab', 'static/defaultTab.json', true);
        if ($config) {
            $fp = public_path() . $config;
            if (file_exists($fp)) {
                $file = file_get_contents($fp);
                $json = json_decode($file, true);
                if (isset($json['config']['theme']['backgroundImage'])) {
                    $bg = $json['config']['theme']['backgroundImage'];
                    $bgMime = $json['config']['theme']['backgroundMime'] ?? 0;
                    return $this->success("ok", ['background' => $bg, "mime" => $bgMime]);
                }
            }
        }
        return $this->success("ok", ['background' => "static/background.jpeg", "mime" => 0]);
    }

    function globalNotify(): \think\response\Json
    {
        $info = SettingModel::Config("globalNotify", false);
        if ($info) {
            $info = json_decode($info, true);
            $info['html'] = modifyImageUrls($info['html'], request()->root(true));
            if (isset($info['status']) && $info['status'] == 1) {
                return $this->success('ok', json_encode($info, JSON_UNESCAPED_UNICODE));
            }
        }
        return $this->error('empty');
    }

    //获取邮件验证码
    function getMailCode(): \think\response\Json
    {
        $mail = $this->request->post("mail", false);
        $code = rand(100000, 999999);
        if ($mail) {
            if (Cache::get('code' . $mail)) {
                return $this->success("请勿频繁获取验证码");
            }
            $k = SettingModel::Config('smtp_code_template', false);
            if ($k === false || mb_strlen(trim($k)) == 0) {
                $k = '
                        <div style="border:1px #DEDEDE solid;border-top:3px #009944 solid;padding:25px;background-color:#FFF;">
                            <div style="font-size:17px;font-weight:bold;">邮箱验证码</div>
                            <div style="font-size:14px;line-height:36px;padding-top:15px;padding-bottom:15px;">
                                尊敬的用户，您好！<br>
                                您的验证码是：<b style="color: #1e9fff">{$code}</b>。5分钟内有效，请尽快验证。
                            </div>
                            <div style="line-height:15px;">
                                此致
                            </div>
                        </div>
                ';
            }
            $html = View::display($k, ['time' => date('Y-m-d H:i:s'), 'code' => $code]);
            try {
                $status = \Mail::send($mail, $html);
                if ($status) {
                    Cache::set('code' . $mail, $code, 300);
                    return $this->success('发送成功');
                }
            } catch (\Exception $e) {
                return $this->error($e->getMessage());
            }
        }
        return $this->error('发送失败');
    }

    private function addHttpProtocolRemovePath($url): string
    {
        // 解析URL
        $parsedUrl = parse_url($url);
        // 检查是否已经有协议，如果没有则添加http://
        if (!isset($parsedUrl['scheme'])) {
            // 检查是否以 // 开头，如果是，则转换为相对协议
            if (isset($parsedUrl['host']) && strpos($url, '//') === 0) {
                $url = 'http:' . $url;
            } else {
                $url = 'http://' . $url;
            }
        } else {
            // 如果有协议但没有路径，保留原样
            $url = $parsedUrl['scheme'] . '://';
            // 如果有主机，则添加主机部分
            if (isset($parsedUrl['host'])) {
                $url .= $parsedUrl['host'];
                // 如果有端口号，则添加端口号
                if (isset($parsedUrl['port'])) {
                    $url .= ':' . $parsedUrl['port'];
                }
            }
        }
        return $url;
    }

    private function addHttpProtocol($url)
    {
        // 检查是否已经有协议，如果没有则添加http://
        if (!parse_url($url, PHP_URL_SCHEME)) {
            // 检查是否以 // 开头，如果是，则转换为相对协议
            if (strpos($url, '//') === 0) {
                $url = 'https:' . $url;
            } else {
                $url = 'http://' . $url;
            }
        }
        return $url;
    }

    private function hasOnlyPath($url): bool
    {
        if (!$url) {
            return false;
        }
        $parsedUrl = parse_url($url);
        // 检查是否存在路径但不存在域名和协议
        if (isset($parsedUrl['path']) && !isset($parsedUrl['host']) && !isset($parsedUrl['scheme'])) {
            return true;
        }
        return false;
    }

    function getIcon(): \think\response\Json
    {
        $avatar = $this->request->post('avatar');
        if ($avatar) {
            $remote_avatar = $this->systemSetting('remote_avatar', 'https://avatar.mtab.cc/6.x/icons/png?seed=', true);
            $str = $this->downloadFile($remote_avatar . $avatar, md5($avatar) . '.png');
            return $this->success(['src' => $str]);
        }

        $url = $this->request->post('url', false);
        if (!$url) {
            return $this->error('没有抓取到图标');
        }

        $realUrl = $this->addHttpProtocolRemovePath($url);
        $cdn = $this->systemSetting('assets_host', '');
        $icon = '';
        $title = '';

        try {
            $client = \Axios::http();
            $response = $client->get($realUrl, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ]
            ]);
            $status = $response->getStatusCode();

            if ($status === 200) {
                $contentType = $response->getHeaderLine('Content-Type');
                if (stripos($contentType, 'text/html') === false) {
                    return $this->error('没有抓取到图标');
                }

                $body = $response->getBody()->getContents();
                $dom = new DOMDocument();
                @$dom->loadHTML($body);

                // 获取页面标题
                $titles = $dom->getElementsByTagName('title');
                if ($titles->length > 0) {
                    $title = $titles->item(0)->textContent;
                }

                // 查找常见的图标类型
                $iconTags = $this->findIcons($dom, $realUrl);

                if (!empty($iconTags)) {
                    // 处理第一个找到的图标
                    $iconHref = $iconTags[0]['href'];
                    $icon = $this->processIcon($iconHref, $realUrl, $cdn);
                }
            }

            // 如果没有找到图标或抓取失败，则尝试获取 favicon.ico
            if (empty($icon)) {
                $icon = $this->fetchFavicon($realUrl, $cdn);
            }

            if ($icon) {
                return $this->success(['src' => $icon, 'name' => $title]);
            }
        } catch (\Exception $e) {
            return $this->error('没有抓取到图标');
        }

        return $this->error('没有抓取到图标');
    }

    private function findIcons($dom, $baseUrl): array
    {
        $icons = [];
        $iconSelectors = [
            'link[rel=icon]',
            'link[rel=shortcut icon]',
            'link[rel=apple-touch-icon]',
            'link[rel=apple-touch-icon-precomposed]',
            'link[rel=mask-icon]'
        ];

        foreach ($iconSelectors as $selector) {
            foreach ($dom->getElementsByTagName('link') as $icon) {
                if (in_array($icon->getAttribute('rel'), array_map('trim', $iconSelectors))) {
                    $href = $icon->getAttribute('href');
                    if ($this->hasOnlyPath($href)) {
                        $href = rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
                    }
                    $icons[] = ['href' => $href];
                }
            }
        }

        return $icons;
    }

    private function processIcon($iconHref, $realUrl, $cdn): string
    {
        try {
            $client = \Axios::http();
            $response = $client->get($iconHref, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ]
            ]);
            $contentType = $response->getHeaderLine('Content-Type');

            // 根据 content-type 确定文件格式
            if (preg_match('/(png|jpg|jpeg|x-icon|svg\+xml)$/i', $contentType, $matches)) {
                $fileFormats = [
                    'png' => 'png',
                    'jpg' => 'jpg',
                    'jpeg' => 'jpeg',
                    'x-icon' => 'ico',
                    'svg+xml' => 'svg',
                ];
                $fileFormat = strtolower($matches[1]);
                $iconPath = $this->downloadFile($iconHref, md5($realUrl) . '.' . $fileFormats[$fileFormat]);
                return $cdn . $iconPath;
            }
        } catch (\Exception $e) {
            // 直接返回失败
            return '';
        }

        return '';
    }

    private function fetchFavicon($realUrl, $cdn): string
    {
        try {
            $client = \Axios::http();
            $faviconUrl = rtrim($realUrl, '/') . '/favicon.ico';
            $response = $client->get($faviconUrl, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ]
            ]);
            $status = $response->getStatusCode();
            if ($status === 200) {
                $iconPath = $this->downloadFile($faviconUrl, md5($realUrl) . '.ico');
                return $cdn . $iconPath;
            }
        } catch (\Exception $e) {
            // 直接返回失败
            return '';
        }

        return '';
    }

    private function downloadFile($url, $name)
    {
        $user = $this->getUser();
        $client = \Axios::http();
        $path = '/images/' . date('Y/m/d/');
        $remotePath = public_path() . $path;
        $downloadPath = $remotePath . $name;
        if (!is_dir($remotePath)) {
            mkdir($remotePath, 0755, true);
        }
        try {
            $client->request('GET', $url, [
                'sink' => $downloadPath,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ]
            ]);
            return FileModel::addFile($path . $name, $user['user_id'] ?? null);
        } catch (RequestException $e) {
        }
        return false;
    }

    function renderIco(): \think\Response
    {
        $send = $this->request->get('seed');
        $client = new Client();
        $remote_avatar = $this->systemSetting('remote_avatar', 'https://avatar.mtab.cc/6.x/icons/png?seed=', true);
        $response = $client->get($remote_avatar . urlencode($send), [
            'stream' => true,
            'timeout' => 10,
        ]);
        return response($response->getBody(), 200, ['Content-Type' => $response->getHeader("content-type")[0]]);
    }

    function upload(): \think\response\Json
    {
        $user = $this->getUser();
        if (!$user) {
            if ($this->systemSetting('touristUpload') !== '1') {
                //如果没有开启游客上传
                return $this->error('管理员已关闭游客上传！请登录后使用');
            }
        }
        $type = $this->request->header("Up-Type", '');
        $file = $this->request->file('file');
        if (empty($file)) {
            return $this->error('not File');
        }
        $calc = $this->request->header("Calc");
        $maxSize = (float)$this->systemSetting('upload_size', '2');
        if ($file->getSize() > 1024 * 1024 * $maxSize) {
            $limit = $maxSize < 1 ? ($maxSize * 1000) . 'KB' : ($maxSize) . 'MB';
            return $this->error("文件最大$limit,请压缩后再试");
        }
        $extension = strtolower($file->getOriginalExtension());
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'ico', 'svg'];
        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml'];
        $mimeType = $file->getMime();
        if (in_array($extension, $allowedExtensions, true) && in_array($mimeType, $allowedMimeTypes, true)) {
            if ($extension === 'svg') {
                $svg = file_get_contents($file->getPathname());
                if (preg_match('/<\s*script|on\w+\s*=|javascript:/i', $svg)) {
                    return $this->error('SVG 文件包含不安全内容');
                }
            }
            // 验证文件并保存
            try {
                // 构建保存路径
                $savePath = '/images/' . date('Y/m/d');
                $hash = Str::random(32);
                $fileName = $hash . '.' . $extension;
                $filePath = Filesystem::disk('images')->putFileAs($savePath, $file, $fileName);
                $minPath = '';
                if ($type == 'icon' || $type == 'avatar') {
                    $fp = joinPath(public_path(), $filePath);
                    $image = new \ImageBack($fp);
                    $image->resize(144, 0)->save($fp);
                } else if ($type == 'AdminBackground') {
                    $minPath = joinPath($savePath, "/min_$fileName");
                    $fp = joinPath(public_path(), $filePath);
                    $image = new \ImageBack($fp);
                    $image->resize(400, 0)->save(joinPath(public_path(), $minPath));
                    $minPath = FileModel::addFile($minPath, $user['user_id'] ?? null);
                }
                //如果包含裁剪头就裁剪图片
                if ($calc) {
                    $w = (int)explode("x", $calc)[0];
                    $h = (int)explode("x", $calc)[1];
                    $fp = joinPath(public_path(), $filePath);
                    $image = new \ImageBack($fp);
                    $image->resize($w, $h)->save($fp);
                }
                $filePath = FileModel::addFile($filePath, $user['user_id'] ?? null);
                return $this->success(['url' => $filePath, "minUrl" => $minPath, 'filename' => $fileName]);
            } catch (\think\exception\ValidateException $e) {
                return $this->error($e->getMessage());
                // 验证失败，给出错误提示
                // ...
            }
        }
        return $this->error('上传失败');
    }

    function AdminUpload(): \think\response\Json
    {
        $user = $this->getAdmin();
        $file = $this->request->file('file');
        if (empty($file)) {
            return $this->error('not File');
        }
        if ($file->getSize() > 1024 * 1024 * 8) {
            return $this->error('文件最大8MB,请压缩后再试');
        }
        $extension = strtolower($file->getOriginalExtension());
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'webp', 'ico', 'svg'];
        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml'];
        if (!in_array($extension, $allowedExtensions, true) || !in_array($file->getMime(), $allowedMimeTypes, true)) {
            return $this->error('不支持的文件类型');
        }
        if ($extension === 'svg') {
            $svg = file_get_contents($file->getPathname());
            if (preg_match('/<\s*script|on\w+\s*=|javascript:/i', $svg)) {
                return $this->error('SVG 文件包含不安全内容');
            }
        }
        // 验证文件并保存
        try {
            // 构建保存路径
            $savePath = '/images/' . date('Y/m/d');
            $hash = Str::random(32);
            $fileName = $hash . '.' . $extension;
            $filePath = Filesystem::disk('images')->putFileAs($savePath, $file, $fileName);
            $cdn = $this->systemSetting('assets_host', '/', true);
            $path = FileModel::addFile($filePath, $user['user_id'] ?? null);
            return $this->success(['url' => $cdn . $path]);
        } catch (\think\exception\ValidateException $e) {
            // 验证失败，给出错误提示
            // ...
        }
        return $this->error('上传失败');
    }

    function refresh(): \think\response\Json
    {
        $user = $this->getUser();
        if ($user) {
            $data = [];
            $data['link_update_time'] = LinkModel::where("user_id", $user['user_id'])->value("update_time");
            return $this->success("ok", $data);
        }
        return $this->error("not login");
    }

    function cardImages(): \think\response\Json
    {
        $list = [];
        $webPath = '/static/CardBackground/bg/';
        $dirPath = public_path($webPath);
        $dir = opendir($dirPath);
        while (($file = readdir($dir)) !== false) {
            if ($file != "." && $file != "..") {
                if (is_file(joinPath($dirPath, $file))) {
                    $list[] = [
                        "thumbor" => joinPath($webPath, $file),
                        "url" => joinPath($webPath, $file),
                    ];
                }
            }
        }
        //针对文件的创建时间排序
        usort($list, function ($a, $b) {
            return filemtime(joinPath(public_path(), $b['thumbor'])) - filemtime(joinPath(public_path(), $a['thumbor']));
        });
        return $this->success($list);
    }

    function moveFile(): \think\response\Json
    {
        $this->getAdmin();
        $old = $this->request->post("old");
        $new = $this->request->post("new");
        FileModel::moveFile($old, $new);
        return $this->success("文件移动成功");
    }

    function delImages(): \think\response\Json
    {
        $this->getAdmin();
        $url = $this->request->post('url');
        $p = joinPath(public_path(), $url);
        if (file_exists($p)) {
            FileModel::delFile($url);
        }
        return $this->success("删除完毕");
    }
}
