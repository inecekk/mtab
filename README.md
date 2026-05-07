# mTab新标签页 - 无限制版本

![logo](https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/192.png)

### 🎉 **完全免费无限制版本** - 已移除所有商业授权限制

这是基于原版 mTab 的无限制修改版本，移除了所有付费功能的限制，所有功能完全免费使用。

### [原版mTab书签官网](https://mtab.cc) | [原版安装文档](https://mtab.cc/document.html) | [原作者Blog](https://blog.mcecy.com)

![](https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/1.png?x-image-process=image/resize,m_lfit,w_900)


### 主要有以下特点

跨设备同步：不再为了在不同设备上找不到书签或笔记而苦恼。Mtab书签让你的收藏网址和重要笔记在所有设备上同步。

跨浏览器支持：Mtab书签支持所有主流浏览器。Chrome、Firefox、Edge、Safari，无论你的选择是什么，都能在一应俱全的工具箱中找到你的书签和笔记。

多功能一体：Mtab书签不仅仅是一个书签工具，它还提供了一个实用的记事本功能，让你随时随地记录想法、灵感和待办事项。此外，它还内置了一些在线小工具，解决您的日常工作问题。

私有部署：如果部你对数据安全性有更高要求，Mtab书签也支持私有部署。你可以将它部署在自己的服务器上，完全掌控你的数据，不受任何干扰。

免费无广告：Mtab书签坚守“免费无广告”的原则，为用户提供清爽的使用体验，没有任何干扰。

Mtab书签的界面设计美观简洁，操作简单直观，让你可以专注于你的网络活动，而不是应用本身。它是你高效、无忧的网络生活的理想伴侣。
高效流畅的操作体验：超级简约却强大的操作逻辑，没有繁琐的操作流程即可处理复杂的事情。

## Demo演示站

#### **[演示站Demo入口](https://demo.mtab.cc)**

演示账号：admin

演示密码：123456


## 🚀 快速部署方式

### Docker 部署（推荐）

**无限制版镜像：** `ghcr.io/inecekk/mtab:latest`

#### 一键部署命令：
```bash
docker run -itd --name mtab-unlimited -p 9200:80 -v /opt/mtab:/app ghcr.io/inecekk/mtab:latest
```

#### 使用 docker-compose 部署：
```bash
# 克隆项目
git clone https://github.com/inecekk/mtab.git
cd mtab

# 启动服务
docker-compose up -d
```

docker-compose.yml 配置：
```yml
version: '3'
services:
  mtab:
    image: ghcr.io/inecekk/mtab:latest
    container_name: mtab-unlimited
    ports:
      - "9200:80"
    volumes:
      - ./:/app
    restart: always
```

**部署后初始化：**
1. 访问 `http://localhost:9200` 
2. 程序会自动引导你进行数据库配置
3. 输入你的 MySQL 连接信息完成安装

### 本地开发

**Windows 环境：**
```bash
# 使用 PHP 内置服务器
start-dev.bat
# 或手动启动
cd public && php -S localhost:8000
```

**其他环境：**
- 配置 Web 服务器指向 `public` 目录
- 通过 Web 界面配置数据库连接

### 🎯 重要说明

- ✅ **无需授权码** - 所有功能完全免费
- ✅ **无付费限制** - 移除了所有商业限制
- ✅ **完整功能** - 包含文件夹管理、卡片安装、链接管理等
- ✅ **自动构建** - GitHub Actions 自动构建 Docker 镜像
## 预览图

![](https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/1.png)
<img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/2.png" width="50%"><img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/3.png" width="50%">
<img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/4.png" width="33.3%"><img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/5.png" width="33.3%"><img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/6.png" width="33.3%">
<img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/8.png" width="50%"><img src="https://raw.githubusercontent.com/tsxcw/imagesHouse/itushan/mTabReadme/7.png" width="50%">

