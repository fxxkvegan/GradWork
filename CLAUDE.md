# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## プロジェクト概要

Laravel 12 (PHP 8.2) で構築されたアプリケーションカタログ API バックエンド。
モバイル・デスクトップアプリケーションのレビュープラットフォーム用 RESTful API を提供。

## セットアップコマンド

```bash
# 依存関係のインストール
composer install

# 環境ファイルのセットアップ (初回のみ)
cp .env.example .env

# アプリケーションキーの生成 (初回のみ)
php artisan key:generate

# Dockerコンテナの起動 (データベース)
docker compose up -d

# マイグレーション実行
php artisan migrate
```

## 開発コマンド

```bash
# 開発サーバー起動
composer run dev
# または
php artisan serve

# テスト実行
composer run test
# または
php artisan test

# コードフォーマット
./vendor/bin/pint

# 設定キャッシュクリア
php artisan config:clear
```

## アーキテクチャ

### 認証システム

- **Laravel Sanctum** を使用したトークンベース認証
- Personal Access Tokens (PAT) による API 認証
- `auth:api` ミドルウェアで保護されたエンドポイント
- 実装: `app/Models/User.php` (HasApiTokens トレイト使用)
- 認証コントローラー: `app/Http/Controllers/Api/AuthController.php`

### ルーティング構造

すべての API ルートは `routes/api.php` に集約:
- `/api/health-check`: ヘルスチェック
- `/api/products`: プロダクト管理 (CRUD)
- `/api/categories`: カテゴリ管理
- `/api/rankings`: ランキング取得
- `/api/reviews`: レビュー管理
- `/api/auth`: 認証 (login, signup, logout)
- `/api/users/me`: ユーザープロフィール管理
- `/api/home`: ホームページ用データ

### データモデル

主要なモデルは `app/Models/` に配置:
- `Product`: アプリケーション製品
- `Category`: カテゴリ (製品との多対多リレーション)
- `Review`: レビュー
- `Response`: レビューへの返信
- `User`: ユーザー (Sanctum 認証対応)
- `Version`: 製品バージョン履歴
- `ProductStatus`: 製品のステータス (online/maintenance/deprecated)

### コントローラー構成

すべての API コントローラーは `app/Http/Controllers/Api/` に配置:
- RESTful 設計に準拠
- 認証が必要なアクションには `auth:api` ミドルウェアを適用
- OpenAPI 仕様書 (`swagger.yml`) と連携

## API 仕様

- OpenAPI 仕様書: `swagger.yml`
- Bearer トークン認証: `Authorization: Bearer {token}`
- RESTful 設計原則に準拠
- 標準的な HTTP レスポンスコードを使用

## データベース

- マイグレーションファイル: `database/migrations/`
- Docker Compose でデータベースコンテナを管理
- マイグレーション変更時は必ず `php artisan migrate` を実行すること
