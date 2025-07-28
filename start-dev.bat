@echo off
chcp 65001
echo 🚀 启动 PHP 内置开发服务器
echo.

echo 📝 设置环境配置...
if not exist ".env" copy ".env.local" ".env"

echo 📁 检查运行时目录...
if not exist "runtime" mkdir runtime
if not exist "runtime\cache" mkdir runtime\cache
if not exist "runtime\log" mkdir runtime\log

echo 🌍 启动服务器...
echo 访问地址: http://localhost:8000
echo 按 Ctrl+C 停止服务器
echo.

cd public
php -S localhost:8000