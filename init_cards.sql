-- 初始化官方卡片数据
INSERT IGNORE INTO `card` (`name`, `name_en`, `tips`, `version`, `src`, `url`, `window`, `status`, `install_num`, `create_time`) VALUES
('天气预报', 'weather', '实时天气预报小组件', '1.0.0', '/static/cards/weather.png', '/plugins/weather', 0, 1, 0, NOW()),
('待办事项', 'todo', '简单的待办事项管理', '1.0.0', '/static/cards/todo.png', '/plugins/todo', 0, 1, 0, NOW()),
('每日一诗', 'poetry', '中国古诗词欣赏', '1.0.0', '/static/cards/poetry.png', '/plugins/poetry', 0, 1, 0, NOW()),
('今天吃什么', 'food', '随机推荐今日美食', '1.0.0', '/static/cards/food.png', '/plugins/food', 0, 1, 0, NOW()),
('热搜榜', 'topSearch', '实时热搜排行榜', '1.0.0', '/static/cards/topsearch.png', '/plugins/topSearch', 0, 1, 0, NOW());