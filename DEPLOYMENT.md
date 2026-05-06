# Docker 部署到 VPS

本项目按 Docker Compose 部署，不要求 VPS 宿主机安装 PHP、Composer、Supervisor 或宝塔。

部署目录示例：`/var/www/zxaihub-sms`。

## 1. GitHub 仓库 Secrets

在 GitHub 仓库：`Settings -> Secrets and variables -> Actions -> New repository secret` 添加：

- `VPS_HOST`：VPS IP 或域名
- `VPS_PORT`：SSH 端口，通常 `22`
- `VPS_USER`：SSH 用户，例如 `root`
- `VPS_SSH_KEY`：SSH 私钥内容
- `VPS_DEPLOY_PATH`：部署目录，例如 `/var/www/zxaihub-sms`

## 2. VPS 前置要求

VPS 宿主机只需要 Docker 和 Docker Compose：

```bash
docker --version
docker compose version
```

如果没有 Docker，可以先安装 Docker 后再部署。宿主机不需要 `php` 命令。

## 3. VPS 首次初始化

```bash
mkdir -p /var/www/zxaihub-sms
cd /var/www/zxaihub-sms
nano .env
```

`.env` 不会提交到 GitHub，也不会被 Action 覆盖。至少需要：

```env
APP_NAME="ZXAIHUB SMS"
APP_ENV=production
APP_KEY=base64:请填生成值
APP_DEBUG=false
APP_URL=https://sms.zxaihub.com

# Docker 端口，默认只监听本机 8011，适合前面接 Nginx/Caddy/NPM 反代
COMPOSE_PROJECT_NAME=zxsms
SMS_DOCKER_HTTP_BIND=127.0.0.1
SMS_DOCKER_HTTP_PORT=18081

DB_CONNECTION=mysql
DB_HOST=host.docker.internal
DB_PORT=3306
DB_DATABASE=你的数据库
DB_USERNAME=你的用户名
DB_PASSWORD=你的密码

HEROSMS_API_KEY=你的HeroSMS_API_KEY
HEROSMS_BASE_URL=https://hero-sms.com/stubs/handler_api.php

SMS_ADMIN_USER=admin
SMS_ADMIN_PASSWORD=后台强密码

SMS_YIPAY_PID=
SMS_YIPAY_GATEWAY=
SMS_YIPAY_KEY=
SMS_YIPAY_ALIPAY_ENABLED=true
SMS_YIPAY_ALIPAY_PAY_CHECK=alipay
SMS_YIPAY_WXPAY_ENABLED=true
SMS_YIPAY_WXPAY_PAY_CHECK=wxpay

SMS_EPUSDT_API_KEY=
SMS_EPUSDT_BASE_URL=
SMS_EPUSDT_BEP20_ENABLED=true
SMS_EPUSDT_BEP20_PAY_CHECK=usdt.bep20
SMS_EPUSDT_TRC20_ENABLED=true
SMS_EPUSDT_TRC20_PAY_CHECK=usdt.trc20
```

生成 `APP_KEY` 不需要 PHP，可以在 VPS 执行：

```bash
echo "base64:$(openssl rand -base64 32)"
```

然后把输出填到：

```env
APP_KEY=base64:xxxx
```

### 数据库连接说明

- 如果 MySQL 在宿主机或通过宿主机端口暴露，Docker 容器里用：

```env
DB_HOST=host.docker.internal
```

- 如果 MySQL 是另一个 Docker Compose 项目里的容器，推荐用 `docker-compose.override.yml` 把本项目接入同一个外部网络，然后 `DB_HOST` 填那个 MySQL 容器名。`docker-compose.override.yml` 已被 `.gitignore` 忽略，不会提交。

## 4. 域名访问

本项目自带一个 Nginx 容器，默认暴露到宿主机本地：

```text
127.0.0.1:18081
```

如果你已有 Docker 反代服务，比如 Nginx Proxy Manager / Caddy / Traefik，把 `sms.zxaihub.com` 反代到：

```text
http://127.0.0.1:18081
```

如果这台 VPS 没有其它服务占用 80 端口，也可以直接公开 HTTP：

```env
SMS_DOCKER_HTTP_BIND=0.0.0.0
SMS_DOCKER_HTTP_PORT=80
```

HTTPS 证书建议交给前置反代或 Cloudflare 处理。

## 5. 后台和守护任务

后台地址：

```text
https://sms.zxaihub.com/sms-admin
```

Docker 里会启动 3 个服务：

- `app`：Laravel PHP-FPM
- `nginx`：Web 入口
- `scheduler`：Laravel 定时任务守护，负责 HeroSMS 价格同步、验证码轮询、超时退款

因此不需要宝塔 Supervisor，也不需要宿主机 cron。

## 6. 部署流程

推送到 `main` 分支后，GitHub Actions 会自动：

1. rsync 上传代码到 VPS
2. 在 VPS 上 `docker compose up -d --build`
3. 容器内执行：
   - `php artisan package:discover`
   - `php artisan migrate --force`
   - `php artisan storage:link`
   - `php artisan config:cache`
   - `php artisan view:cache`
4. 启动/重启 `scheduler` 守护容器

手动在 VPS 查看状态：

```bash
cd /var/www/zxaihub-sms
docker compose ps
docker compose logs -f app
docker compose logs -f scheduler
```
