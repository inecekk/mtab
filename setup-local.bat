@echo off
chcp 65001
echo 🚀 mTab 本地开发环境设置
echo.

echo 📁 创建运行时目录...
if not exist "runtime" mkdir runtime
if not exist "runtime\cache" mkdir runtime\cache  
if not exist "runtime\log" mkdir runtime\log
if not exist "runtime\temp" mkdir runtime\temp

echo 📝 设置环境配置...
copy ".env.local" ".env"

echo 🔧 设置目录权限...
attrib -R runtime\* /S /D
attrib -R public\static\* /S /D

echo 📋 接下来需要手动操作：
echo.
echo 1. 🌐 启动 PhpStudy，确保 Apache + MySQL 运行
echo 2. 🗄️  创建数据库 'mtab_dev'
echo 3. 📊 导入数据：
echo    - install.sql 
echo    - defaultData.sql
echo 4. 🌍 添加网站：
echo    - 域名: mtab.local
echo    - 目录: %cd%\public
echo    - PHP: 8.1
echo 5. 📝 修改 hosts 文件添加：
echo    127.0.0.1 mtab.local
echo.
echo ✅ 配置完成后访问: http://mtab.local
echo.
pause