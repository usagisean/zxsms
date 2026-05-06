# GitHub Actions 部署到 VPS

本项目建议部署目录示例：`/var/www/zxaihub-sms`，Nginx 站点根目录指向 `public`。

## 1. GitHub 仓库 Secrets

在 GitHub 仓库：`Settings -> Secrets and variables -> Actions -> New repository secret` 添加：

- `VPS_HOST`：VPS IP 或域名
- `VPS_PORT`：SSH 端口，通常 `22`
- `VPS_USER`：SSH 用户，例如 `root` 或 `deploy`
- `VPS_SSH_KEY`：私钥内容，建议使用专门的 deploy key
- `VPS_DEPLOY_PATH`：部署目录，例如 `/var/www/zxaihub-sms`

## 2. VPS 首次初始化

```bash
mkdir -p /var/www/zxaihub-sms
cd /var/www/zxaihub-sms
# 上传或手动创建 .env，不能提交到 GitHub
nano .env
mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data /var/www/zxaihub-sms
chmod -R ug+rw storage bootstrap/cache
```

`.env` 至少需要配置：

```env
APP_NAME="ZXAIHUB SMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://zxaihub.com
APP_KEY=base64:请在本地或服务器运行 php artisan key:generate 生成

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=你的数据库
DB_USERNAME=你的用户名
DB_PASSWORD=你的密码

HEROSMS_API_KEY=
SMS_ADMIN_USER=admin
SMS_ADMIN_PASSWORD=强密码

SMS_YIPAY_PID=
SMS_YIPAY_GATEWAY=
SMS_YIPAY_KEY=
SMS_EPUSDT_API_KEY=
SMS_EPUSDT_BASE_URL=
```

生成 APP_KEY：

```bash
php artisan key:generate --show
```

## 3. Nginx 示例

```nginx
server {
    listen 80;
    server_name zxaihub.com www.zxaihub.com;
    root /var/www/zxaihub-sms/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    }

    location ~ /\. {
        deny all;
    }
}
```

## 4. 定时任务

HeroSMS 价格同步、订单轮询、无码自动退款依赖 Laravel schedule：

```cron
* * * * * cd /var/www/zxaihub-sms && php artisan schedule:run >> /dev/null 2>&1
```

## 5. 部署流程

推送到 `main` 分支后，GitHub Actions 会自动：

1. 安装 PHP 依赖
2. rsync 上传项目文件到 VPS
3. 执行数据库迁移
4. 生成 Laravel 缓存

注意：`.env` 不会上传，必须在 VPS 上维护。
