<?php
// 测试授权验证移除是否有效

// 模拟 ThinkPHP 环境
require_once __DIR__ . '/vendor/autoload.php';

echo "测试授权验证移除修改...\n\n";

// 1. 检查 Admin 控制器的授权方法
echo "1. 检查 xyCheck 方法修改:\n";
$adminFile = __DIR__ . '/app/controller/Admin.php';
$content = file_get_contents($adminFile);

if (strpos($content, '移除授权验证，直接返回成功') !== false) {
    echo "✓ xyCheck 方法已修改，将直接返回成功\n";
} else {
    echo "✗ xyCheck 方法修改失败\n";
}

// 2. 检查前端协议弹窗是否已移除
echo "\n2. 检查前端协议弹窗修改:\n";
$layoutFile = __DIR__ . '/public/dist/assets/layout.42d18ea5.1730902649514.js';
$jsContent = file_get_contents($layoutFile);

if (strpos($jsContent, 'return m("",!0)') !== false) {
    echo "✓ 前端协议弹窗已移除\n";
} else {
    echo "✗ 前端协议弹窗移除失败\n";
}

// 3. 检查 LICENSE.html 文件是否已删除
echo "\n3. 检查 LICENSE.html 文件:\n";
$licenseFile = __DIR__ . '/config/LICENSE.html';
if (!file_exists($licenseFile)) {
    echo "✓ LICENSE.html 文件已删除\n";
} else {
    echo "✗ LICENSE.html 文件仍存在\n";
}

echo "\n修改完成! 现在可以部署并测试系统功能.\n";
echo "建议重新构建 Docker 镜像或重启服务以使修改生效.\n";