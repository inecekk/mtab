<?php

namespace app\controller;

use app\BaseController;
use app\model\ConfigModel;
use app\model\HistoryModel;
use app\model\LinkModel;
use app\model\TabbarModel;
use app\model\UserSearchEngineModel;
use think\facade\Cache;

class Link extends BaseController
{
    private function saveUserLinks($user, array $link): void
    {
        $is = LinkModel::where("user_id", $user['user_id'])->find();
        if ($is) {
            HistoryModel::create(['user_id' => $user['user_id'], 'link' => $is['link'], 'create_time' => date("Y-m-d H:i:s")]);
            $ids = HistoryModel::where("user_id", $user['user_id'])->order("id", 'desc')->limit(50)->select()->toArray();
            $ids = array_column($ids, "id");
            HistoryModel::where("user_id", $user['user_id'])->whereNotIn("id", $ids)->delete();
            $is->link = $link;
            $is->save();
        } else {
            LinkModel::create(["user_id" => $user['user_id'], "link" => $link]);
        }
        Cache::delete("Link.{$user['user_id']}");
    }

    public function update(): \think\response\Json
    {
        $user = $this->getUser(true);
        $error = "";
        try {
            if ($user) {
                $link = $this->request->post("link", []);
                if (is_array($link)) {
                    $is = LinkModel::where("user_id", $user['user_id'])->find();
                    if ($is) {
                        HistoryModel::create(['user_id' => $user['user_id'], 'link' => $is['link'], 'create_time' => date("Y-m-d H:i:s")]); //历史记录备份,用于用户误操作恢复用途
                        $ids = HistoryModel::where("user_id", $user['user_id'])->order("id", 'desc')->limit(50)->select()->toArray();
                        $ids = array_column($ids, "id");
                        HistoryModel::where("user_id", $user['user_id'])->whereNotIn("id", $ids)->delete();
                        $is->link = $link;
                        $is->save();
                    } else {
                        LinkModel::create(["user_id" => $user['user_id'], "link" => $link]);
                    }
                    Cache::delete("Link.{$user['user_id']}");
                    return $this->success('ok');
                }
            }
        } catch (\Throwable $th) {
            $error = $th->getMessage();
        }
        return $this->error('保存失败'.$error);
    }

    public function get(): \think\response\Json
    {

        $user = $this->getUser();
        if ($user) {
            $c = Cache::get("Link.{$user['user_id']}");
            if ($c) {
                return $this->success('ok', $c);
            }
            $data = LinkModel::where('user_id', $user['user_id'])->find();
            if ($data) {
                $c = $data['link'];
                Cache::tag("linkCache")->set("Link.{$user['user_id']}", $c, 60 * 60);
                return $this->success('ok', $c);
            }
        }
        $config = $this->systemSetting("defaultTab", 'static/defaultTab.json', true);
        if ($config) {
            $fp = public_path() . $config;
            if (!file_exists($fp)) {
                $fp = public_path() . "static/defaultTab.json";
            }
            if (file_exists($fp)) {
                $file = file_get_contents($fp);
                $json = json_decode($file, true);
                return $this->success('ok', $json['link'] ?? []);
            }
        }
        return $this->success('ok', []);
    }

    public function exportBookmarks(): \think\response\Json
    {
        $user = $this->getUser(true);
        $link = $this->getCurrentUserLinks($user);

        return $this->success('ok', [
            'format' => 'mtab-bookmarks',
            'version' => 1,
            'exported_at' => date('c'),
            'count' => count($link),
            'link' => $link,
        ]);
    }

    public function exportBookmarksFile(): \think\Response
    {
        $user = $this->getUser(true);
        $link = $this->getCurrentUserLinks($user);
        $format = strtolower((string)$this->request->get('format', 'json'));

        if ($format === 'html') {
            $content = $this->renderBookmarksHtml($link);
            $filename = 'mtab-bookmarks-' . date('Ymd-His') . '.html';
            return response($content, 200, [
                'Content-Type' => 'text/html; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $content = json_encode([
            'format' => 'mtab-bookmarks',
            'version' => 1,
            'exported_at' => date('c'),
            'count' => count($link),
            'link' => $link,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $filename = 'mtab-bookmarks-' . date('Ymd-His') . '.json';

        return response($content, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function importBookmarks(): \think\response\Json
    {
        $user = $this->getUser(true);
        $mode = strtolower((string)$this->request->post('mode', 'merge'));
        if (!in_array($mode, ['merge', 'replace'], true)) {
            return $this->error('导入模式不合法');
        }

        $content = '';
        $file = $this->request->file('file');
        if ($file) {
            if ($file->getSize() > 1024 * 1024 * 5) {
                return $this->error('书签文件最大支持5MB');
            }
            $content = file_get_contents($file->getPathname());
        } else {
            $content = (string)$this->request->post('data', '');
            if ($content === '') {
                $link = $this->request->post('link', []);
                if (is_array($link)) {
                    $content = json_encode(['link' => $link], JSON_UNESCAPED_UNICODE);
                }
            }
        }

        if (trim($content) === '') {
            return $this->error('没有读取到书签内容');
        }

        $imported = $this->parseBookmarks($content);
        if (empty($imported)) {
            return $this->error('没有解析到可导入的书签');
        }

        $current = $this->getCurrentUserLinks($user);
        $link = $mode === 'replace' ? $imported : $this->mergeBookmarks($current, $imported);
        $this->saveUserLinks($user, $link);

        return $this->success('导入成功', [
            'mode' => $mode,
            'imported' => count($imported),
            'total' => count($link),
        ]);
    }

    function refreshWebAppCache(): \think\response\Json
    {
        $this->getAdmin();
        Cache::tag('linkCache')->clear();
        return $this->success('刷新完毕');
    }

    public function history(): \think\response\Json
    {
        $user = $this->getUser(true);
        $history = HistoryModel::where("user_id", $user['user_id'])->whereNotNull("create_time")->field('id,user_id,create_time')->limit(100)->order("id", "desc")->select();
        return $this->success('ok', $history);
    }

    public function delBack(): \think\response\Json
    {
        $user = $this->getUser(true);
        $id = $this->request->post('id');
        if ($id) {
            $res = HistoryModel::where('id', $id)->where('user_id', $user['user_id'])->delete();
            if ($res) {
                return $this->success('ok');
            }
        }
        return $this->error('备份节点不存在');
    }

    public function rollBack(): \think\response\Json
    {
        $user = $this->getUser(true);
        $id = $this->request->post("id");
        if ($id) {
            $res = HistoryModel::where('id', $id)->where("user_id", $user['user_id'])->find();
            if ($res) {
                $link = $res['link'];
                Cache::delete("Link.{$user['user_id']}");
                LinkModel::update(["user_id" => $user['user_id'], "link" => $link]);
                return $this->success('ok');
            }
        }
        return $this->error("备份节点不存在");
    }

    private function getCurrentUserLinks($user): array
    {
        $data = LinkModel::where('user_id', $user['user_id'])->find();
        if ($data) {
            return (array)$data['link'];
        }
        return [];
    }

    private function parseBookmarks(string $content): array
    {
        $json = json_decode($content, true);
        if (is_array($json)) {
            if (isset($json['link']) && is_array($json['link'])) {
                return $this->normalizeBookmarks($json['link']);
            }
            if ($this->isListArray($json)) {
                return $this->normalizeBookmarks($json);
            }
        }

        return $this->parseBookmarksHtml($content);
    }

    private function isListArray(array $array): bool
    {
        if ($array === []) {
            return true;
        }
        return array_keys($array) === range(0, count($array) - 1);
    }

    private function normalizeBookmarks(array $items): array
    {
        $result = [];
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $url = trim((string)($item['url'] ?? ''));
            $name = trim((string)($item['name'] ?? ''));
            if ($url === '' || $name === '') {
                continue;
            }
            $item['id'] = (string)($item['id'] ?? uuid());
            $item['app'] = (int)($item['app'] ?? 0);
            $item['pid'] = $item['pid'] ?? null;
            $item['src'] = (string)($item['src'] ?? '');
            $item['url'] = $url;
            $item['name'] = mb_substr($name, 0, 100);
            $item['size'] = (string)($item['size'] ?? '1x1');
            $item['sort'] = isset($item['sort']) ? (int)$item['sort'] : 99999 - $index;
            $item['type'] = (string)($item['type'] ?? 'icon');
            $item['bgColor'] = $item['bgColor'] ?? '#fff';
            $item['pageGroup'] = $item['pageGroup'] ?? '';
            $item['form'] = $item['form'] ?? 'link';
            $result[] = $item;
        }
        return $result;
    }

    private function parseBookmarksHtml(string $html): array
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        if (!$loaded) {
            return [];
        }

        $items = [];
        foreach ($dom->getElementsByTagName('a') as $index => $node) {
            $url = trim($node->getAttribute('href'));
            if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
                continue;
            }
            $name = trim($node->textContent);
            if ($name === '') {
                $name = parse_url($url, PHP_URL_HOST) ?: $url;
            }
            $items[] = [
                'id' => uuid(),
                'app' => 0,
                'pid' => null,
                'src' => '',
                'url' => $url,
                'name' => mb_substr($name, 0, 100),
                'size' => '1x1',
                'sort' => 99999 - $index,
                'type' => 'icon',
                'bgColor' => '#fff',
                'pageGroup' => '',
                'form' => 'link',
            ];
        }
        return $items;
    }

    private function mergeBookmarks(array $current, array $imported): array
    {
        $seen = [];
        foreach ($current as $item) {
            if (isset($item['url'])) {
                $seen[mb_strtolower((string)$item['url'])] = true;
            }
        }
        foreach ($imported as $item) {
            $key = mb_strtolower((string)$item['url']);
            if (!isset($seen[$key])) {
                $current[] = $item;
                $seen[$key] = true;
            }
        }
        return $current;
    }

    private function renderBookmarksHtml(array $link): string
    {
        $html = "<!DOCTYPE NETSCAPE-Bookmark-file-1>\n";
        $html .= "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=UTF-8\">\n";
        $html .= "<TITLE>Bookmarks</TITLE>\n<H1>Bookmarks</H1>\n<DL><p>\n";
        foreach ($link as $item) {
            if (!is_array($item) || empty($item['url']) || empty($item['name'])) {
                continue;
            }
            $url = htmlspecialchars((string)$item['url'], ENT_QUOTES, 'UTF-8');
            $name = htmlspecialchars((string)$item['name'], ENT_QUOTES, 'UTF-8');
            $html .= "    <DT><A HREF=\"{$url}\">{$name}</A>\n";
        }
        $html .= "</DL><p>\n";
        return $html;
    }

    public function reset(): \think\response\Json
    {
        $user = $this->getUser();
        if ($user) {
            $data = LinkModel::find($user['user_id']);
            if ($data) {
                Cache::delete("Link.{$user['user_id']}");
                $data->delete();
            }
            $data = TabbarModel::find($user['user_id']);
            if ($data) {
                $data->delete();
            }
            $data = ConfigModel::find($user['user_id']);
            if ($data) {
                $data->delete();
            }
            $data = UserSearchEngineModel::find($user['user_id']);
            if ($data) {
                $data->delete();
            }
        }
        return $this->success('ok');
    }
}
