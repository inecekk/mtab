<?php
// 初始化官方卡片数据到数据库
require_once __DIR__ . '/vendor/autoload.php';

use think\facade\Db;
use app\model\CardModel;

// 官方卡片数据
$officialCards = [
    [
        'name' => '天气预报',
        'name_en' => 'weather',
        'tips' => '实时天气预报小组件',
        'version' => '1.0.0',
        'src' => '/static/cards/weather.png',
        'url' => '/plugins/weather',
        'window' => 0,
        'status' => 1,
        'install_num' => 0,
        'create_time' => date('Y-m-d H:i:s')
    ],
    [
        'name' => '待办事项',
        'name_en' => 'todo',
        'tips' => '简单的待办事项管理',
        'version' => '1.0.0',
        'src' => '/static/cards/todo.png',
        'url' => '/plugins/todo',
        'window' => 0,
        'status' => 1,
        'install_num' => 0,
        'create_time' => date('Y-m-d H:i:s')
    ],
    [
        'name' => '每日一诗',
        'name_en' => 'poetry',
        'tips' => '中国古诗词欣赏',
        'version' => '1.0.0',
        'src' => '/static/cards/poetry.png',
        'url' => '/plugins/poetry',
        'window' => 0,
        'status' => 1,
        'install_num' => 0,
        'create_time' => date('Y-m-d H:i:s')
    ],
    [
        'name' => '今天吃什么',
        'name_en' => 'food',
        'tips' => '随机推荐今日美食',
        'version' => '1.0.0',
        'src' => '/static/cards/food.png',
        'url' => '/plugins/food',
        'window' => 0,
        'status' => 1,
        'install_num' => 0,
        'create_time' => date('Y-m-d H:i:s')
    ],
    [
        'name' => '热搜榜',
        'name_en' => 'topSearch',
        'tips' => '实时热搜排行榜',
        'version' => '1.0.0',
        'src' => '/static/cards/topsearch.png',
        'url' => '/plugins/topSearch',
        'window' => 0,
        'status' => 1,
        'install_num' => 0,
        'create_time' => date('Y-m-d H:i:s')
    ]
];

try {
    echo "开始初始化官方卡片...\n";
    
    foreach ($officialCards as $card) {
        // 检查是否已存在
        $exists = Db::table('card')->where('name_en', $card['name_en'])->find();
        
        if ($exists) {
            // 更新现有卡片
            Db::table('card')->where('name_en', $card['name_en'])->update($card);
            echo "更新卡片: {$card['name']}\n";
        } else {
            // 插入新卡片
            Db::table('card')->insert($card);
            echo "添加卡片: {$card['name']}\n";
        }
    }
    
    echo "官方卡片初始化完成！\n";
    
} catch (Exception $e) {
    echo "初始化失败: " . $e->getMessage() . "\n";
}
?>