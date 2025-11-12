# GitHub Actions デプロイ設定ガイド

このドキュメントは、GitHub ActionsでLaravelアプリケーションをLightsailにデプロイするために必要な設定を説明します。

## GitHub Secretsの設定

GitHubリポジトリの Settings > Secrets and variables > Actions から以下のシークレットを追加してください。

### 必須のシークレット

| シークレット名 | 説明 | 例 |
|-------------|------|-----|
| `LIGHTSAIL_SSH_KEY` | Lightsail SSH秘密鍵の内容 | `-----BEGIN RSA PRIVATE KEY-----\n...` |
| `LIGHTSAIL_HOST` | Lightsailのホスト名またはIPアドレス | `app.nice-dig.com` |
| `LIGHTSAIL_USER` | SSH接続ユーザー名 | `bitnami` |
| `DB_HOST` | データベースホスト | `localhost` |
| `DB_PORT` | データベースポート | `3306` |
| `DB_DATABASE` | データベース名 | `your_database_name` |
| `DB_USERNAME` | データベースユーザー名 | `your_database_user` |
| `DB_PASSWORD` | データベースパスワード | `your_secure_password` |
| `APP_ENV` | アプリケーション環境 | `production` |
| `APP_DEBUG` | デバッグモード | `false` |
| `APP_URL` | アプリケーションURL | `https://app.nice-dig.com` |

### SSH鍵の設定手順

1. **AWS Lightsailコンソールから秘密鍵をダウンロード**
   - Lightsailコンソールにログイン
   - 対象のインスタンスを選択
   - 「アカウントページのSSHキー」からSSH鍵をダウンロード

2. **SSH鍵の内容をコピー**
   ```bash
   # macOS/Linuxの場合
   cat ~/Downloads/LightsailDefaultKey-ap-northeast-1.pem

   # Windowsの場合（PowerShell）
   Get-Content $env:USERPROFILE\Downloads\LightsailDefaultKey-ap-northeast-1.pem
   ```

3. **GitHubに`LIGHTSAIL_SSH_KEY`として登録**
   - コピーした内容全体（`-----BEGIN RSA PRIVATE KEY-----`から`-----END RSA PRIVATE KEY-----`まで）をそのまま貼り付け

### その他のシークレット設定値

以下の値は、Lightsailサーバーの`.env`ファイルと同じ値を設定してください：

- **データベース情報**: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- **アプリケーション設定**: `APP_ENV`, `APP_DEBUG`, `APP_URL`

## デプロイの実行

### 自動デプロイ

`main`ブランチにpushすると、自動的にデプロイが開始されます。

```bash
git add .
git commit -m "Deploy to Lightsail"
git push origin main
```

### 手動デプロイ

GitHub ActionsのUIから手動実行も可能です：

1. GitHubリポジトリの「Actions」タブを開く
2. 「Deploy to Lightsail」ワークフローを選択
3. 「Run workflow」→ ブランチを選択 → 「Run workflow」をクリック

## デプロイフロー

デプロイ時に実行される処理：

1. メンテナンスモード有効化（60秒リトライ）
2. 最新コード取得（`git reset --hard origin/main`）
3. Composer依存関係インストール（本番用）
4. キャッシュクリア
5. データベースマイグレーション実行
6. ビューキャッシュ化
7. ストレージリンク作成
8. パーミッション設定
9. メンテナンスモード解除
10. Apache再起動

## トラブルシューティング

### デプロイが失敗する場合

GitHub Actionsのログを確認してください：

- リポジトリの「Actions」タブ → 失敗したワークフローを選択
- エラーメッセージを確認して対処

よくあるエラー：

| エラー内容 | 原因 | 解決方法 |
|----------|------|---------|
| `Permission denied (publickey)` | SSH鍵が正しく設定されていない | `LIGHTSAIL_SSH_KEY`の内容を確認 |
| `Could not resolve host` | ホスト名が間違っている | `LIGHTSAIL_HOST`の値を確認 |
| `composer: command not found` | Composerがインストールされていない | Lightsailサーバーにログインして`composer --version`で確認 |
| `php artisan migrate failed` | データベース接続エラー | `DB_*`シークレットの値を確認 |
