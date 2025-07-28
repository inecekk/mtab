#!/bin/bash
# mTab 服务器一键部署脚本

echo "🚀 mTab 服务器部署脚本"
echo "=========================="

# 检查系统
if [[ "$EUID" -ne 0 ]]; then
    echo "❌ 请使用 root 权限运行此脚本"
    exit 1
fi

# 更新系统
echo "📦 更新系统包..."
apt update && apt upgrade -y

# 安装基础环境
echo "🔧 安装基础环境..."
apt install -y nginx mysql-server php8.1 php8.1-fpm php8.1-mysql \
    php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl \
    php8.1-xml php8.1-bcmath php8.1-redis composer git

# 创建项目目录
PROJECT_DIR="/var/www/mtab"
echo "📁 创建项目目录: $PROJECT_DIR"
mkdir -p $PROJECT_DIR

# 下载/复制项目文件（假设当前目录是项目根目录）
echo "📋 复制项目文件..."
cp -r . $PROJECT_DIR/
cd $PROJECT_DIR

# 安装 PHP 依赖
echo "📦 安装 PHP 依赖..."
composer install --no-dev --optimize-autoloader

# 设置权限
echo "🔐 设置文件权限..."
chown -R www-data:www-data $PROJECT_DIR
chmod -R 755 $PROJECT_DIR
chmod -R 777 $PROJECT_DIR/runtime/
chmod -R 777 $PROJECT_DIR/public/static/

# 配置 Nginx
echo "🌍 配置 Nginx..."
cat > /etc/nginx/sites-available/mtab << 'EOF'
server {
    listen 80;
    server_name _;
    root /var/www/mtab/public;
    index index.php index.html;

    # 安全设置
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # 隐藏敏感文件
    location ~ /\.(htaccess|htpasswd|env) {
        deny all;
    }
}
EOF

# 启用站点
ln -sf /etc/nginx/sites-available/mtab /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# 测试 Nginx 配置
nginx -t

# 配置 MySQL
echo "🗄️ 配置 MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS mtab_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'mtab'@'localhost' IDENTIFIED BY 'mtab_password_2024';"
mysql -e "GRANT ALL PRIVILEGES ON mtab_prod.* TO 'mtab'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# 导入数据库结构
mysql mtab_prod < $PROJECT_DIR/install.sql
mysql mtab_prod < $PROJECT_DIR/defaultData.sql

# 创建生产环境配置
echo "⚙️ 创建生产环境配置..."
cat > $PROJECT_DIR/.env << 'EOF'
APP_DEBUG = false

[APP]

[DATABASE]
TYPE = mysql
HOSTNAME = localhost  
DATABASE = mtab_prod
USERNAME = mtab
PASSWORD = mtab_password_2024
HOSTPORT = 3306
CHARSET = utf8mb4
DEBUG = false

[CACHE]
DRIVER = file

[LANG]
default_lang = zh-cn
EOF

# 重启服务
echo "🔄 重启服务..."
systemctl restart nginx php8.1-fpm mysql
systemctl enable nginx php8.1-fpm mysql

# 防火墙设置
echo "🔥 配置防火墙..."
ufw allow 22
ufw allow 80
ufw allow 443
ufw --force enable

# 完成提示
PUBLIC_IP=$(curl -s ifconfig.me)
echo ""
echo "🎉 部署完成！"
echo "=========================="
echo "📍 访问地址: http://$PUBLIC_IP"
echo "🗄️ 数据库信息:"
echo "   - 数据库: mtab_prod"
echo "   - 用户名: mtab"  
echo "   - 密码: mtab_password_2024"
echo ""
echo "🔧 下一步："
echo "1. 访问网站进行最终配置"
echo "2. 设置管理员账号"
echo "3. 配置 SSL 证书（建议）"
echo ""
echo "🛡️ 安全建议："
echo "- 修改默认数据库密码"
echo "- 配置 HTTPS"
echo "- 定期备份数据"
echo ""