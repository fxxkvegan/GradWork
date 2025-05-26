# GradWork

## Getting Started

install

```bash
composer install
```

env 作成（初回だけ）

```bash
cp .env.example .env
```

Key 生成（初回だけ）

```bash
php artisan key:generate
```

Dev

```bash
composer run dev
```

ヘルスチェックエンドポイント

```
http://127.0.0.1:8000/api/health-check

```

Docker 起動

```bash
docker compose up -d
```

Migration

```bash
php artisan migrate
```
