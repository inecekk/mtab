<?php

namespace app\controller\admin;

use app\BaseController;
use app\model\CardModel;
use app\model\LinkStoreModel;
use app\model\SettingModel;
use think\facade\Cache;
use think\facade\Db;


class Index extends BaseController
{
    public $authService = "https://auth.mtab.cc";
    public $authCode = '';


    function setSubscription(): \think\response\Json
    {
        $this->getAdmin();
        $code = $this->request->post("code", "");
        if (trim($code)) {
            Db::table('setting')->replace()->insert(['keys' => 'authCode', 'value' => $code]);
            SettingModel::refreshSetting();
        }
        return $this->success("ok");
    }

    private function initAuth()
    {
        // 强制设置授权码为有效状态，跳过在线验证
        $this->authCode = 'LOCAL_AUTH_ENABLED';
        $this->authService = $this->systemSetting('authServer', 'https://auth.mtab.cc', true);
    }


    function updateApp($n = 0): \think\response\Json
    {
        $this->getAdmin();
        // 禁用在线更新功能
        return $this->error("在线更新功能已禁用，请手动下载最新版本更新");
    }

    function authorization(): \think\response\Json
    {
        $this->getAdmin();
        $this->initAuth();
        $info = [];
        $info['version'] = app_version;
        $info['version_code'] = app_version_code;
        $info['php_version'] = phpversion();
        // 模拟授权验证成功，跳过在线验证
        $info['remote'] = [
            "auth" => true,
            "status" => "active",
            "message" => "本地授权已启用"
        ];
        return $this->success($info);
    }


    function cardList(): \think\response\Json
    {
        $this->getAdmin();
        // 移除授权限制，返回完整的卡片列表
        $localCards = CardModel::select()->toArray();
        
        // 模拟官方卡片商店数据，移除付费限制
        $officialCards = [
            [
                'id' => 'weather',
                'name' => '天气预报',
                'name_en' => 'weather',
                'tips' => '实时天气预报小组件',
                'version' => '1.0.0',
                'src' => '/static/cards/weather.png',
                'url' => '',
                'window' => 0,
                'status' => 1,
                'install' => !in_array('weather', array_column($localCards, 'name_en')),
                'official' => true
            ],
            [
                'id' => 'todo',
                'name' => '待办事项',
                'name_en' => 'todo',
                'tips' => '简单的待办事项管理',
                'version' => '1.0.0',
                'src' => '/static/cards/todo.png',
                'url' => '',
                'window' => 0,
                'status' => 1,
                'install' => !in_array('todo', array_column($localCards, 'name_en')),
                'official' => true
            ],
            [
                'id' => 'poetry',
                'name' => '每日一诗',
                'name_en' => 'poetry',
                'tips' => '中国古诗词欣赏',
                'version' => '1.0.0',
                'src' => '/static/cards/poetry.png',
                'url' => '',
                'window' => 0,
                'status' => 1,
                'install' => !in_array('poetry', array_column($localCards, 'name_en')),
                'official' => true
            ]
        ];
        
        // 合并本地卡片和官方卡片
        $allCards = array_merge($localCards, $officialCards);
        
        return $this->success('ok', $allCards);
    }

    //获取本地应用
    function localCard(): \think\response\Json
    {
        $this->getAdmin();
        $apps = CardModel::select();
        return $this->success('ok', $apps);
    }

    function stopCard(): \think\response\Json
    {
        $this->getAdmin();
        is_demo_mode(true);
        $name_en = $this->request->post('name_en', '');
        CardModel::where('name_en', $name_en)->update(['status' => 0]);
        Cache::delete('cardList');
        return $this->success('设置成功');
    }

    function startCard(): \think\response\Json
    {
        $this->getAdmin();
        $name_en = $this->request->post('name_en', '');
        CardModel::where('name_en', $name_en)->update(['status' => 1]);
        Cache::delete('cardList');
        return $this->success('设置成功');
    }

    function installCard(): \think\response\Json
    {
        $this->getAdmin();
        is_demo_mode(true);
        
        // 支持本地卡片安装，移除授权限制
        $name_en = $this->request->post("name_en", "");
        $url = $this->request->post("url", "");
        
        if (empty($name_en)) {
            return $this->error("卡片名称不能为空");
        }
        
        // 定义官方卡片的基础数据
        $officialCards = [
            'weather' => [
                'name' => '天气预报',
                'name_en' => 'weather',
                'tips' => '实时天气预报小组件',
                'version' => '1.0.0',
                'src' => '/static/cards/weather.png',
                'url' => '/plugins/weather',
                'window' => 0
            ],
            'todo' => [
                'name' => '待办事项',
                'name_en' => 'todo',
                'tips' => '简单的待办事项管理',
                'version' => '1.0.0',
                'src' => '/static/cards/todo.png',
                'url' => '/plugins/todo',
                'window' => 0
            ],
            'poetry' => [
                'name' => '每日一诗',
                'name_en' => 'poetry',
                'tips' => '中国古诗词欣赏',
                'version' => '1.0.0',
                'src' => '/static/cards/poetry.png',
                'url' => '/plugins/poetry',
                'window' => 0
            ]
        ];
        
        // 如果是官方卡片，直接安装
        if (isset($officialCards[$name_en])) {
            $data = $officialCards[$name_en];
            $find = CardModel::where('name_en', $name_en)->find();
            if ($find) {
                $find->force()->save($data);
            } else {
                CardModel::create($data);
            }
            Cache::delete('cardList');
            return $this->success("官方卡片安装成功");
        }
        
        // 如果提供了URL，则从URL安装
        if (!empty($url)) {
            $info = [
                'name_en' => $name_en,
                'download' => $url
            ];
            return $this->installCardTask($info);
        }
        
        // 检查本地 plugins 目录中是否存在该卡片
        $pluginPath = root_path() . 'plugins/' . $name_en;
        if (is_dir($pluginPath)) {
            $config = $this->readCardInfo($name_en);
            if ($config) {
                $data = [
                    'name' => $config['name'],
                    'name_en' => $config['name_en'],
                    'version' => $config['version'],
                    'tips' => $config['tips'],
                    'src' => $config['src'],
                    'url' => $config['url'],
                    'window' => $config['window'],
                ];
                if (isset($config['setting'])) {
                    $data['setting'] = $config['setting'];
                }
                
                $find = CardModel::where('name_en', $name_en)->find();
                if ($find) {
                    $find->force()->save($data);
                } else {
                    CardModel::create($data);
                }
                Cache::delete('cardList');
                return $this->success("卡片安装成功");
            }
        }
        
        return $this->error("未找到卡片文件，请确保卡片已上传至 plugins/{$name_en} 目录");
    }

    function uninstallCard(): \think\response\Json
    {
        $this->getAdmin();
        is_demo_mode(true);
        $name_en = $this->request->post("name_en");
        if ($name_en) {
            $this->deleteDirectory(root_path() . 'plugins/' . $name_en);
            CardModel::where('name_en', $name_en)->delete();
            Cache::delete('cardList');
        }
        return $this->success('卸载完毕！');
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                if (is_dir("$dir/$file")) {
                    $this->deleteDirectory("$dir/$file");
                } else {
                    unlink("$dir/$file");
                }
            }
        }
        rmdir($dir);
    }

    private function readCardInfo($name_en)
    {
        $file = root_path() . 'plugins/' . $name_en . '/info.json';
        $info = file_get_contents($file);
        try {
            return json_decode($info, true);
        } catch (\Exception $e) {
        }
        return false;
    }

    private function installCardTask($info): \think\response\Json
    {
        if ($info['download']) {
            $task = new \PluginsInstall($info);
            $state = $task->run();
            if ($state === true) {
                $config = $this->readCardInfo($info['name_en']);
                $data = [
                    'name' => $config['name'],
                    'name_en' => $config['name_en'],
                    'version' => $config['version'],
                    'tips' => $config['tips'],
                    'src' => $config['src'],
                    'url' => $config['url'],
                    'window' => $config['window'],
                ];
                if (isset($config['setting'])) {
                    $data['setting'] = $config['setting'];
                }
                $find = CardModel::where('name_en', $info['name_en'])->find();
                if ($find) {
                    $find->force()->save($data);
                } else {
                    CardModel::create($data);
                }
                Cache::delete('cardList');
                return $this->success("安装成功");
            }
            return $this->error($state);
        }
        abort(0, "新版本没有提供下载地址！");
    }

    //打包扩展
    function build(): \think\response\Json
    {
        $this->getAdmin();
        is_demo_mode(true);
        if (!extension_loaded('zip')) {
            return $this->error("系统未安装或开启zip扩展，请安装后重试！");
        }
        // 移除授权检查，允许所有操作
        // if (!$this->auth) {
        //     return $this->error("请获取授权后进行操作");
        // }
        $ExtInfo = $this->request->post("extInfo", []);
        $build = new \BrowserExtBuild($ExtInfo);
        try {
            $status = $build->runBuild();
            if ($status) {
                return $this->success('打包完毕', ['url' => '/browserExt.zip']);
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        return $this->success('打包失败');
    }


    function folders(): \think\response\Json
    {
        $this->getAdmin();
        // 启用文件夹功能，移除授权限制
        $folders = \app\model\LinkFolderModel::order('sort', 'desc')->select();
        return $this->success('ok', $folders);
    }

    function links(): \think\response\Json
    {
        $this->getAdmin();
        // 启用本地链接功能，移除授权限制
        $folders = $this->request->param('folders', 0);
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 18);
        
        $sql = ['status' => 1];
        if ($folders && $folders > 0) {
            $sql[] = ['area', 'like', "%$folders%"];
        }
        
        $linkStore = new \app\model\LinkStoreModel();
        $list = $linkStore->where($sql)
            ->order('hot', 'desc')
            ->page($page, $limit)
            ->select();
            
        $total = $linkStore->where($sql)->count();
        
        $data = [
            'data' => $list,
            'total' => $total,
            'current_page' => $page,
            'per_page' => $limit
        ];
        
        return json(['code' => 1, 'msg' => 'ok', 'data' => $data, 'local' => []]);
    }
}
