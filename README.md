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

### 1. Dockerビルド

```bash
git clone https://github.com/MurakiXi/SIM-PJ1.git
cd SIM-PJ1
docker compose up -d --build
# ※ docker-compose の環境では `docker-compose up -d --build` でも可
```
### 2. Laravel 初期化（php コンテナ内）

```bash
docker compose exec php bash
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
```

### 3. .env 設定（重要）
src/.env（コンテナ内では /var/www/.env）を以下に合わせてください。

```bash
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

本アプリは Stripe を利用します。ローカルで決済フローを確認する場合は `.env` に Stripe のテストキーを設定してください。  
支払い結果（成功/キャンセル等）の反映に Webhook を利用します。

Webhook受信エンドポイント: `POST /stripe/webhook`（route name: `stripe.webhook`）

### ローカルでWebhookを受信する（Stripe CLI必須：APP_URL=http://localhost の場合）

Stripeは `localhost` へ直接Webhookを送信できないため、ローカルで決済結果（特にコンビニ決済）を反映するには  
Stripe CLIでWebhookを転送してください。

1) Stripe CLIを起動（別ターミナル）

```bash
stripe login
stripe listen --forward-to http://localhost/stripe/webhook
```

コマンド実行後に表示される whsec_... を .env の STRIPE_WEBHOOK_SECRET に設定してください。


2) stripe listen 実行後に表示される whsec_... を .env の STRIPE_WEBHOOK_SECRET に設定

3) 設定反映（phpコンテナ内）

```bash
docker compose exec php php artisan config:clear
```

※ stripe listen は起動し続けてください。停止するとWebhookが届かず、注文が pending のままになります。

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


要件の「テーブル数9個以内」は、本アプリで利用する主要テーブル（users / items / orders / categories / category_item / likes / comments / addresses / stripe_events
）を対象として解釈し、合計9個。

なお、Laravel標準の補助テーブル(password_resets等)はフレームワーク機能のため、要件の対象外として解釈。

---

## トラブルシューティング

- 支払い後も pending / 商品が processing のままで変わらない

→コンビニ決済は非同期のため、支払い確定（paid 反映）は Webhook受信により行われます。

ローカル環境（APP_URL=http://localhost）では Stripe が localhost に直接Webhookを送れないため、Stripe CLIの転送が必須です。

- ローカルで反映されない場合（5分以上 pending のまま）

1) Stripe CLI の転送が起動しているか確認（別ターミナル）

```bash
stripe listen --forward-to http://localhost/stripe/webhook
```

2) .env の STRIPE_WEBHOOK_SECRET が stripe listen に表示された whsec_... と一致しているか確認

    (変更した場合は設定反映)

```bash
docker compose exec php php artisan config:clear
```

Webhookがアプリに届いているかログ確認

```bash
docker compose exec php tail -n 200 storage/logs/laravel.log
```

補足：本番（公開URLの環境）では Stripe CLI は不要です。Stripe DashboardでWebhook送信先を公開URLに設定し、発行された whsec_... を STRIPE_WEBHOOK_SECRET に設定してください。