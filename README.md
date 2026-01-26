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

---

※ 一部UIのために JavaScript を使用（外部ライブラリ/フレームワーク不使用）

## 環境構築

### 1. Dockerビルド

```bash
git clone https://github.com/MurakiXi/SIM-PJ1.git
cd SIM-PJ1
docker compose up -d --build
```

### 2. Laravel 初期化（php コンテナ内）

```bash
docker compose exec php bash
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan optimize:clear
```

### 3. .env 設定（重要）

src/.env（コンテナ内では /var/www/.env）を以下に合わせてください。

```env
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

MAIL_HOST=mailhog
MAIL_PORT=1025
```

### 4. マイグレーション・シーディング(phpコンテナ内)

```bash
php artisan migrate --seed
```

### 動作確認用ダミーユーザー（Seeder）

---

出品者

email: seller@example.com

password: testpass1

購入者

email: buyer@example.com

password: testpass2

### 6.URL一覧

アプリ: http://localhost

phpMyAdmin: http://localhost:8080

Mailhog: http://localhost:8025

### 7. Stripe / Webhook（購入機能のローカル検証：任意）

（閲覧・出品・いいね等の確認だけなら、この章は飛ばして構いません）

本アプリは Stripe を利用します。購入機能をローカルで検証する場合のみ、.env にStripeのテストキーを設定してください。

**7-1. Stripe テストキー（STRIPE_KEY / STRIPE_SECRET）を取得して設定**

Stripe ダッシュボード（テストモード）の Developers → API keys から取得します。

- 公開可能キー（pk*test*...）→ STRIPE_KEY

- シークレットキー（sk*test*...）→ STRIPE_SECRET

```env
STRIPE_KEY=pk_test_xxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxx
```

**7-2. Webhook（コンビニ決済の paid 反映）をローカルで受信する**

コンビニ決済は非同期のため、Webhook 受信がないと注文が paid になりません。

ローカル環境（APP_URL=http://localhost）では Stripe が localhost へ直接Webhook送信できないため、Stripe CLI で転送します

1. Stripe CLIを起動（別ターミナル）

以下のコマンド実行後に表示される whsec\_... を .env の STRIPE_WEBHOOK_SECRET に設定してください。

（CLIでローカル検証する場合、この whsec\_... がエンドポイントのsecretになります）

```bash
stripe login
stripe listen --forward-to http://localhost/stripe/webhook
```

```env
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx
```

※ローカル検証中は stripe listen を起動し続けてください。止めるとWebhookが届かず、コンビニ決済が pending のままになります。

**7-3. 設定反映（phpコンテナ内）**

```bash
docker compose exec php php artisan optimize:clear
```

※　コンビニ決済は非同期のため、Webhook未受信だと「paid」になりません（ローカル環境ではStripe CLI転送が必要／本番環境は公開URLにWebhook設定）。

**7-4. 補足：**

- Webhook受信エンドポイント

Webhook受信エンドポイント: POST /stripe/webhook（route name: stripe.webhook）

- 支払い完了後、Webhook受信により注文が paid になり、画面上のステータスが Sold に変化します。反映まで少し時間がかかる場合があります。

- 本番環境ではコンビニ決済完了後にWebhookが送受信され、statusがSoldに変化します(CLI転送不要)

---

## 設計上の補足（要件外の拡張）：Processing（購入手続き中）について

本アプリでは二重購入（同一商品に対する同時購入操作）や、決済途中離脱による状態不整合を避けるため、
要件にはない拡張として「Processing（購入手続き中）＝一時確保」の状態を追加しています。

- 購入手続き開始時に注文を作成し、一定時間だけ商品を確保（processing）します
- 確保中は他ユーザーの購入をブロックします（active order がある商品は購入不可）
- カード決済はリダイレクト後に確定、コンビニ決済は非同期のため Webhook 受信で paid 反映します
- 期限切れ/キャンセル時は確保を解除し、再購入可能に戻します

※要件通りの最小実装であれば必須ではありませんが、実運用で起こり得る並行操作・非同期確定を考慮し、
整合性（同一商品が同時に複数人へ販売されないこと）を強める目的で導入しています。


## テスト実行（重要：laravel_test DB）

phpunit.xml は laravel_test を参照します。DBを作成し、権限を付与してください。

docker compose exec mysql mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS laravel_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

docker compose exec mysql mysql -uroot -proot -e "GRANT ALL PRIVILEGES ON laravel_test.\* TO 'laravel_user'@'%'; FLUSH PRIVILEGES;"

テスト実行：

```bash
docker compose exec php vendor/bin/phpunit
```

または

```bash
docker compose exec php php artisan test
```

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

1. Stripe CLI の転送が起動しているか確認（別ターミナル）

```bash
stripe listen --forward-to http://localhost/stripe/webhook
```

2. .env の STRIPE*WEBHOOK_SECRET が stripe listen に表示された whsec*... と一致しているか確認

   (変更した場合は設定反映)

```bash
docker compose exec php php artisan config:clear
```

Webhookがアプリに届いているかログ確認

```bash
docker compose exec php tail -n 200 storage/logs/laravel.log
```

補足：本番（公開URLの環境）では Stripe CLI は不要です。Stripe DashboardでWebhook送信先を公開URLに設定し、発行された whsec\_... を STRIPE_WEBHOOK_SECRET に設定してください。
