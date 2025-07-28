@echo off
echo 🚀 mTab 开发环境设置脚本
echo.

echo 📁 创建必要目录...
if not exist "runtime" mkdir runtime
if not exist "runtime\cache" mkdir runtime\cache
if not exist "runtime\log" mkdir runtime\log
if not exist "runtime\temp" mkdir runtime\temp

echo 📝 复制开发环境配置...
copy ".env.dev" ".env" >nul 2>&1

echo 🔧 设置文件权限...
icacls runtime /grant Everyone:F /T >nul 2>&1
icacls public\static /grant Everyone:F /T >nul 2>&1

echo ✅ 开发环境设置完成！
echo.
echo 📋 接下来的步骤:
echo 1. 在 VS Code 中打开项目
echo 2. 按 Ctrl+Shift+P，选择 "Dev Containers: Reopen in Container"
echo 3. 等待容器构建完成
echo 4. 访问 http://localhost:8080
echo 5. 数据库管理: http://localhost:8081 (root/root)
echo.
pause