<?php

namespace app\controller;

use app\BaseController;
use app\model\CardModel;
use think\facade\Cache;

class Card extends BaseController
{
    function index(): \think\response\Json
    {
        // 使用缓存机制，与管理员后台保持一致
        $cacheKey = 'cardList';
        $apps = Cache::get($cacheKey);
        
        if ($apps === null) {
            // 获取本地已安装并启用的卡片
            $apps = CardModel::where('status', 1)->select()->toArray();
            
            // 如果没有找到官方卡片，则添加默认的官方卡片
            $officialCards = ['weather', 'todo', 'poetry', 'food', 'topSearch'];
            $existingCards = array_column($apps, 'name_en');
            
            foreach ($officialCards as $cardId) {
                if (!in_array($cardId, $existingCards)) {
                    // 添加缺失的官方卡片到数据库
                    $cardData = $this->getOfficialCardData($cardId);
                    if ($cardData) {
                        CardModel::create($cardData);
                        $apps[] = $cardData;
                    }
                }
            }
            
            // 缓存结果，过期时间1小时
            Cache::set($cacheKey, $apps, 3600);
        }
        
        return $this->success('ok', $apps);
    }
    
    private function getOfficialCardData($cardId): ?array
    {
        $officialCards = [
            'weather' => [
                'name' => '天气预报',
                'name_en' => 'weather',
                'tips' => '实时天气预报小组件',
                'version' => '1.0.0',
                'src' => '/static/cards/weather.png',
                'url' => '/plugins/weather',
                'window' => 0,
                'status' => 1,
                'install_num' => 0
            ],
            'todo' => [
                'name' => '待办事项',
                'name_en' => 'todo',
                'tips' => '简单的待办事项管理',
                'version' => '1.0.0',
                'src' => '/static/cards/todo.png',
                'url' => '/plugins/todo',
                'window' => 0,
                'status' => 1,
                'install_num' => 0
            ],
            'poetry' => [
                'name' => '每日一诗',
                'name_en' => 'poetry',
                'tips' => '中国古诗词欣赏',
                'version' => '1.0.0',
                'src' => '/static/cards/poetry.png',
                'url' => '/plugins/poetry',
                'window' => 0,
                'status' => 1,
                'install_num' => 0
            ],
            'food' => [
                'name' => '今天吃什么',
                'name_en' => 'food',
                'tips' => '随机推荐今日美食',
                'version' => '1.0.0',
                'src' => '/static/cards/food.png',
                'url' => '/plugins/food',
                'window' => 0,
                'status' => 1,
                'install_num' => 0
            ],
            'topSearch' => [
                'name' => '热搜榜',
                'name_en' => 'topSearch',
                'tips' => '实时热搜排行榜',
                'version' => '1.0.0',
                'src' => '/static/cards/topsearch.png',
                'url' => '/plugins/topSearch',
                'window' => 0,
                'status' => 1,
                'install_num' => 0
            ]
        ];
        
        return $officialCards[$cardId] ?? null;
    }

    function install_num(): \think\response\Json
    {
        $id = $this->request->post('id', 0);
        if ($id) {
            $find = CardModel::where("id", $id)->find();
            if ($find) {
                $find->install_num += 1;
                $find->save();
            }
        }
        return $this->success('ok');
    }
}