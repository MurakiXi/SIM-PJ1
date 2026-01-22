# SIM-PJ1（coachtechフリマ）
Laravel 8 を用いたフリマアプリ（Docker環境）。

---

## 構成（使用コンテナ / ポート）
- nginx: http://localhost（80）
- phpMyAdmin: http://localhost:8080
- Mailhog（メールUI）: http://localhost:8025
  - SMTP: 1025
- MySQL: mysql:3306（コンテナ内）

---

## 使用技術
- PHP 8.1（Docker / php:8.1-fpm）
- Laravel 8.x
- MySQL 8.0.26
- nginx 1.21.1
- Stripe（決済）
- Mailhog（開発用メール受信）

※ 一部UIのために JavaScript を使用（外部ライブラリ/フレームワーク不使用）

---

## 環境構築

### 1) Dockerビルド
```bash
git clone https://github.com/MurakiXi/SIM-PJ1.git
cd SIM-PJ1
docker compose up -d --build
# ※ docker-compose の環境では `docker-compose up -d --build` でも可
```
### 2) Laravel 初期化（php コンテナ内）
```bash
コードをコピーする
docker compose exec php bash
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
```

### 3) .env 設定（重要）
```bash
src/.env（コンテナ内では /var/www/.env）を以下に合わせてください。

env
コードをコピーする
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

MAIL_HOST=mailhog
MAIL_PORT=1025

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

### 動作確認用ダミーユーザー（Seeder）
---
出品者

email: seller@example.com

password: testpass1

購入者

email: buyer@example.com

password: testpass2

---

### URL一覧

アプリ: http://localhost

phpMyAdmin: http://localhost:8080

Mailhog: http://localhost:8025

---

### Stripe / Webhook（購入機能）

本アプリは Stripe を利用します。ローカルで決済フローを確認する場合は .env に Stripe のテストキーを設定してください。
支払い結果（成功/キャンセル等）の反映に Webhook を利用します。

Webhook受信エンドポイント: POST /stripe/webhook（route name: stripe.webhook）

---

### ローカルでWebhookを受信する（Stripe CLI）

APP_URL が http://localhost の場合：

bash
コードをコピーする
stripe listen --forward-to http://localhost/stripe/webhook
コマンド実行後に表示される whsec_... を .env の STRIPE_WEBHOOK_SECRET に設定してください。

※ コンビニ決済は非同期のため、Webhookが動いていないと購入確定（paid反映）しません。

---

### テスト実行（重要：laravel_test DB）

phpunit.xml は laravel_test を参照します。DBを作成し、権限を付与してください。

docker compose exec mysql mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS laravel_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
docker compose exec mysql mysql -uroot -proot -e "GRANT ALL PRIVILEGES ON laravel_test.* TO 'laravel_user'@'%'; FLUSH PRIVILEGES;"
テスト実行：

docker compose exec php vendor/bin/phpunit

または

docker compose exec php php artisan test

---

### ※テーブル数

users / items / orders / categories / category_item / likes / comments / addresses / stripe_events

総テーブル数9（migrations等のLaravel標準テーブルは除外）
