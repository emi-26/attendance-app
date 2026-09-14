cat > README.md <<'EOF'
# coachtech 勤怠管理アプリ

ユーザーの出勤・退勤・休憩の打刻、勤怠確認、修正申請と、
管理者による勤怠管理・修正申請の承認を行う勤怠管理アプリです。

## 環境構築

### 1. リポジトリをクローン

~~~bash
git clone <リポジトリURL>
cd attendance-app
~~~

### 2. Composerパッケージをインストール

~~~bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
  laravelsail/php82-composer:latest \
  composer install
~~~

### 3. .envファイルを作成

~~~bash
cp .env.example .env
~~~

`.env` の以下の項目を確認・設定します。

~~~env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
~~~

### 4. Dockerコンテナを起動

~~~bash
./vendor/bin/sail up -d
~~~

### 5. アプリケーションキーを生成

~~~bash
./vendor/bin/sail artisan key:generate
~~~

### 6. マイグレーション・シーディングを実行

~~~bash
./vendor/bin/sail artisan migrate --seed
~~~

### 7. npmパッケージをインストール

~~~bash
./vendor/bin/sail npm install
~~~

### 8. Viteを起動

~~~bash
./vendor/bin/sail npm run dev
~~~

## ログイン情報

### 一般ユーザー1

- メールアドレス：user1@example.com
- パスワード：password

### 一般ユーザー2

- メールアドレス：user2@example.com
- パスワード：password

### 管理者

- メールアドレス：user3@example.com
- パスワード：password

## 使用技術

- PHP 8.2
- Laravel 10.x
- Laravel Fortify
- Laravel Sail
- MySQL 8.4
- phpMyAdmin
- Mailpit
- Vite

## ER図

~~~mermaid
erDiagram
    users ||--o{ attendance_records : has
    users ||--o{ applications : submits
    attendance_records ||--o{ breaks : has
    attendance_records ||--o{ applications : has
    applications ||--o{ application_breaks : has

    users {
        bigint id PK
        string name
        string email
        timestamp email_verified_at
        string password
        boolean admin_status
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    attendance_records {
        bigint id PK
        bigint user_id FK
        date date
        time clock_in
        time clock_out
        string comment
        timestamp created_at
        timestamp updated_at
    }

    breaks {
        bigint id PK
        bigint attendance_record_id FK
        time break_in
        time break_out
        timestamp created_at
        timestamp updated_at
    }

    applications {
        bigint id PK
        bigint user_id FK
        bigint attendance_record_id FK
        time clock_in
        time clock_out
        string comment
        string status
        timestamp created_at
        timestamp updated_at
    }

    application_breaks {
        bigint id PK
        bigint application_id FK
        time break_in
        time break_out
        timestamp created_at
        timestamp updated_at
    }
~~~

## URL

- アプリ：http://localhost
- 一般ユーザーログイン：http://localhost/login
- 一般ユーザー会員登録：http://localhost/register
- 管理者ログイン：http://localhost/admin/login
- phpMyAdmin：http://localhost:8080
- Mailpit：http://localhost:8025
EOF