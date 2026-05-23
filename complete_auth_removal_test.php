<?php
echo "=== mTab 授权验证完全移除检查 ===\n\n";

$issues = [];
$success = [];

// 1. 检查 xy 授权验证移除
echo "1. 检查 xy 授权验证移除:\n";
$adminFile = __DIR__ . '/app/controller/Admin.php';
$content = file_get_contents($adminFile);

if (strpos($content, '移除授权验证，直接返回成功') !== false) {
    $success[] = "✓ xyCheck 和 xy 方法已修改";
} else {
    $issues[] = "✗ xyCheck 方法修改失败";
}

// 2. 检查前端协议弹窗移除
echo "\n2. 检查前端协议弹窗移除:\n";
$layoutFile = __DIR__ . '/public/dist/assets/layout.42d18ea5.1730902649514.js';
$jsContent = file_get_contents($layoutFile);

if (strpos($jsContent, 'return m("",!0)') !== false) {
    $success[] = "✓ 前端协议弹窗已移除";
} else {
    $issues[] = "✗ 前端协议弹窗移除失败";
}

// 3. 检查 BaseController 授权状态
echo "\n3. 检查 BaseController 授权状态:\n";
$baseFile = __DIR__ . '/app/BaseController.php';
$baseContent = file_get_contents($baseFile);

if (strpos($baseContent, '$this->auth = true;') !== false && 
    strpos($baseContent, '强制启用授权状态') !== false) {
    $success[] = "✓ BaseController 已强制启用授权";
} else {
    $issues[] = "✗ BaseController 授权状态修改失败";
}

// 4. 检查 admin/Index.php 中的授权修改
echo "\n4. 检查 admin/Index.php 授权机制:\n";
$adminIndexFile = __DIR__ . '/app/controller/admin/Index.php';
$adminIndexContent = file_get_contents($adminIndexFile);

if (strpos($adminIndexContent, 'LOCAL_AUTH_ENABLED') !== false) {
    $success[] = "✓ admin/Index.php 授权码已设置为本地启用";
} else {
    $issues[] = "✗ admin/Index.php 授权码设置失败";
}

if (strpos($adminIndexContent, '模拟授权验证成功') !== false) {
    $success[] = "✓ authorization 方法已修改为模拟成功";
} else {
    $issues[] = "✗ authorization 方法修改失败";
}

if (strpos($adminIndexContent, '在线更新功能已禁用') !== false) {
    $success[] = "✓ 在线更新功能已禁用";
} else {
    $issues[] = "✗ 在线更新功能禁用失败";
}

if (strpos($adminIndexContent, '在线卡片安装功能已禁用') !== false) {
    $success[] = "✓ 在线卡片安装功能已禁用";
} else {
    $issues[] = "✗ 在线卡片安装功能禁用失败";
}

if (strpos($adminIndexContent, '禁用在线文件夹功能') !== false) {
    $success[] = "✓ 在线文件夹功能已禁用";
} else {
    $issues[] = "✗ 在线文件夹功能禁用失败";
}

if (strpos($adminIndexContent, '禁用在线链接功能') !== false) {
    $success[] = "✓ 在线链接功能已禁用";
} else {
    $issues[] = "✗ 在线链接功能禁用失败";
}

// 5. 检查 demo_mode 限制移除
echo "\n5. 检查 demo_mode 限制移除:\n";
$commonFile = __DIR__ . '/app/common.php';
$commonContent = file_get_contents($commonFile);

if (strpos($commonContent, '禁用演示模式限制') !== false && 
    strpos($commonContent, 'return false;') !== false) {
    $success[] = "✓ demo_mode 限制已移除";
} else {
    $issues[] = "✗ demo_mode 限制移除失败";
}

// 6. 检查 LICENSE.html 文件是否删除
echo "\n6. 检查 LICENSE.html 文件:\n";
$licenseFile = __DIR__ . '/config/LICENSE.html';
if (!file_exists($licenseFile)) {
    $success[] = "✓ LICENSE.html 文件已删除";
} else {
    $issues[] = "✗ LICENSE.html 文件仍存在";
}

// 7. 检查浏览器扩展打包权限
echo "\n7. 检查浏览器扩展打包权限:\n";
if (strpos($adminIndexContent, '移除授权检查，允许所有操作') !== false) {
    $success[] = "✓ 浏览器扩展打包授权检查已移除";
} else {
    $issues[] = "✗ 浏览器扩展打包授权检查移除失败";
}

// 输出结果
echo "\n" . str_repeat("=", 60) . "\n";
echo "🔍 详细检查结果:\n\n";

foreach ($success as $item) {
    echo "$item\n";
}

if (!empty($issues)) {
    echo "\n❌ 发现问题:\n";
    foreach ($issues as $item) {
        echo "$item\n";
    }
    echo "\n请检查上述问题并重新修复。\n";
} else {
    echo "\n🎉 恭喜！所有授权验证机制已成功移除！\n";
    echo "\n✅ 系统现在完全解锁:\n";
    echo "   • 所有管理功能可用\n";
    echo "   • 无需任何授权验证\n"; 
    echo "   • 支持完整的二次开发\n";
    echo "   • 独立运行，无外部依赖\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📋 部署指南:\n\n";
echo "1. 🐳 重新构建 Docker 镜像:\n";
echo "   docker build -t mtab-unlimited .\n\n";
echo "2. 🔄 重启容器:\n";
echo "   docker-compose restart\n\n";
echo "3. 🌐 访问管理后台验证功能\n\n";
echo "4. 🛠️ 开始你的二次开发之旅\n\n";

echo "⚠️  重要提醒:\n";
echo "• 在线更新/卡片安装已禁用 - 需手动管理\n";
echo "• 系统完全脱离外部授权服务\n";
echo "• 所有功能本地化处理\n";
echo "• 适合私有部署和定制开发\n\n";

echo "🚀 现在可以放心进行二次开发了！\n";
