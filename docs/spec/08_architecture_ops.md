# 08 アーキテクチャ・ソース構成・運用

> 対象: 車両管理プラグイン v1.0.0（エンジニア・システム管理者向け）

## 1. リポジトリ構成

```
vehicle-manager/
├── vehicle-manager.php          # プラグイン本体。ヘッダ、定数、require、有効化フック、公開API関数4つ（約100行）
├── uninstall.php                # アンインストール時にテーブルと option を削除
├── includes/
│   ├── class-db-install.php     # VM_DB_Install … テーブル作成(dbDelta)、初期マスタ・初期タグ投入
│   ├── class-vehicle.php        # VM_Vehicle … CRUD、検証、和暦変換、一覧/件数、期限条件、タグ再計算、AJAXハンドラ3種
│   └── class-master.php         # VM_Master … マスタ3種の定義と追加・削除・並べ替え・使用数
├── admin/
│   ├── class-admin-menu.php     # VM_Admin_Menu … メニュー、アセット読込、AJAXフック登録、描画メソッド
│   ├── views/
│   │   ├── vehicle-list.php     # 車両一覧（PHPで描画、GETで絞り込み/並べ替え/ページング）
│   │   ├── vehicle-form.php     # 登録/編集フォーム
│   │   ├── vehicle-csv.php      # CSV取込画面（HTMLのみ。動作は admin.js）
│   │   └── vehicle-master.php   # マスタ管理（POST処理と描画が同一ファイル）
│   └── assets/
│       ├── admin.js             # jQuery。タグ、車台番号検証、和暦連携、フォーム送信、削除モーダル、CSV画面
│       └── admin.css            # `vm-` 接頭辞のスタイル
└── .github/workflows/
    ├── deploy.yml               # main へ push → 本番へ FTP デプロイ
    └── staging-deploy.yml       # staging へ push → 検証環境へ FTP デプロイ
```

テスト・ビルド設定・Composer/npm 設定・README は存在しない。

## 2. クラスと責務

| クラス | ファイル | 主なメソッド |
|---|---|---|
| `VM_DB_Install` | `includes/class-db-install.php` | `install()`（有効化時）, `init_default_tags()` |
| `VM_Vehicle` | `includes/class-vehicle.php` | `wareki_to_date`, `wareki_to_ym`, `date_to_wareki`, `ym_to_wareki`, `validate`, `sanitize_post`, `insert`, `update`, `get`, `delete`, `get_list`, `count`, `expiry_status_where`, `rebuild_tags`, `ajax_save`, `ajax_delete`, `ajax_csv_import` |
| `VM_Master` | `includes/class-master.php` | `types`, `type`, `get_items`, `get_bureaus`, `init_defaults`, `usage_counts`, `add_item`, `delete_item`, `move_item` |
| `VM_Admin_Menu` | `admin/class-admin-menu.php` | `init`, `register_menus`, `enqueue_assets`, `render_list/form/master/csv` |

全クラスのメソッドは静的（`static`）。ファイル末尾で `VM_Admin_Menu::init()` を呼んでフックを登録する。

## 3. 主要な処理フロー

### 3.1 有効化
`register_activation_hook` → `VM_DB_Install::install()`:
1. `dbDelta` でテーブル `{prefix}vehicle_manager` 作成/更新
2. option `vm_db_version` = 1.0.0
3. `VM_Master::init_defaults()`（未登録のマスタ option のみ初期値投入）
4. `vm_tag_data` 未設定なら初期タグ投入

### 3.2 車両の登録/更新（AJAX）
フォーム送信（JS）→ 車台番号のクライアント検証 → `$.post(vm_save)` → `ajax_save`: nonce確認 → 権限確認(`edit_custom_plugins`) → `sanitize_post`（和暦変換含む）→ `validate` → `id>0 ? update : insert` → DBエラー内に "Duplicate" があれば重複メッセージ → 成功で一覧へ遷移。

### 3.3 一覧表示
`vehicle-list.php` が GET を読み、`VM_Vehicle::get_list/count` を呼び、PHP で行を描画（サーバー描画。JSは削除モーダルのみ）。有効期限の色分けは PHP が判定。

### 3.4 CSV取込
`06_csv_import.md` のフロー参照。

### 3.5 マスタ管理
`vehicle-master.php` の冒頭で POST を処理 → `VM_Master` の各メソッド → `update_option`。画面再描画。

## 4. 権限モデル

| 用途 | ケイパビリティ | 使用箇所 |
|---|---|---|
| メニュー表示・画面描画 | `access_custom_plugins` | `add_menu_page`/`add_submenu_page`、`render_*` の `current_user_can` |
| 保存・削除・CSV取込（AJAX） | `edit_custom_plugins` | `ajax_save`, `ajax_delete`, `ajax_csv_import` |
| マスタ変更（POST） | （画面と同じ `access_custom_plugins` + nonce のみ） | `vehicle-master.php` |

- 両ケイパビリティはこのプラグインでは**付与も定義もしていない**。既存の別プラグイン（社内の権限管理）が付与する前提と思われるが、ソースからは断定できない。未付与の環境ではメニューが表示されない。
- 公開PHP関数（`vm_*`）には権限チェックがない。

## 5. セキュリティ実装の要点

| 観点 | 実装 |
|---|---|
| CSRF | AJAX は `check_ajax_referer`（3種の nonce）。マスタ管理は `check_admin_referer` |
| SQL | 値は `$wpdb->prepare` / `$wpdb->insert/update/delete` を使用。並べ替えカラムは許可リスト。マスタ使用数のカラム名は固定定義のみ |
| XSS | 画面出力は `esc_html` / `esc_attr` / `esc_url`。JS側のプレビュー描画はテキスト→HTMLエスケープ関数で処理 |
| 入力 | `sanitize_text_field`、整数は `intval`、車台番号は正規表現 |
| 例外 | 公開関数の `vm_get_vehicle_numbers`/`vm_get_transport_bureau_map` は固定SQL（外部入力なし） |

## 6. 依存関係

| 依存 | 内容 |
|---|---|
| WordPress | `dbDelta`, `$wpdb`, option API, nonce, `wp_send_json_*`, dashicons, jQuery |
| PHP | `mb_*` 関数（`mb_strlen`, `mb_detect_encoding`, `mb_convert_encoding`）、`str_getcsv`、null合体演算子 `??`（PHP 7以上） |
| 任意 | `employee-manager` プラグインの `admin/assets/admin.css`（存在すれば読み込み。見た目の統一用） |

## 7. デプロイ（GitHub Actions → FTP）

| 環境 | トリガー | ワークフロー | 使用 Secrets | 配置先（プラグインディレクトリ） |
|---|---|---|---|---|
| 本番 | `main` ブランチへ push | `.github/workflows/deploy.yml`（FTP-Deploy-Action v4.3.4） | `FTP_HOST_TANPOPO`, `FTP_USER_TANPOPO`, `FTP_PASS_TANPOPO` | XSERVER 上 `/xs969605.xsrv.jp/public_html/inhouse/wp-content/plugins/vehicle-manager/` |
| 検証 | `staging` ブランチへ push | `.github/workflows/staging-deploy.yml`（v4.3.0） | `FTP_HOST_STAGING`, `FTP_USER_STAGING`, `FTP_PASS_STAGING` | XSERVER 上 `/labs-ims.com/public_html/tanpoposub-test.labs-ims.com/wp-content/plugins/vehicle-manager/` |

- `local-dir: ./` のためリポジトリ全体（`.github` を含む）が配置される。
- 運用フロー（Git履歴より）: 機能ブランチ → PR → `main`（本番）/ `staging`（検証）へマージ。
- プラグインのバージョン（1.0.0）は更新されていないため、アセットのキャッシュバスターは変わらない（ブラウザキャッシュで古いJS/CSSが残る場合は強制リロード）。
- DBスキーマ変更用のマイグレーション機構は無い。カラム追加時は `dbDelta` が再実行される有効化（無効化→有効化）が必要。

## 8. アンインストール（`uninstall.php`）
WordPress の管理画面でプラグインを「削除」すると実行される。
- `DROP TABLE IF EXISTS {prefix}vehicle_manager` — **全車両データが消える**
- option 削除: `vm_tag_data`, `vm_transport_bureaus`, `vm_class_numbers`, `vm_purpose_categories`, `vm_db_version`
- 「無効化」だけではデータは残る。

## 9. 運用の勧め（ソースから導かれる実務上の留意）
- 削除は物理削除で復元不可。定期的に DB（テーブル `{prefix}vehicle_manager`）のバックアップを取る。
- CSV 取込は上書きモードで空欄が消去される（`06_csv_import.md` 注意点2）。取込前に現行データをバックアップする。
- 有効期限判定はサーバー日付基準。サーバーのタイムゾーンは PHP の `date()` 設定に従う（WordPress の設定タイムゾーンとは必ずしも一致しない）。
- 機能拡張時の主な変更箇所:
  - 項目追加: `class-db-install.php`（カラム）、`class-vehicle.php`（`sanitize_post`・CSV `$col_map`）、`vehicle-form.php`（入力欄）、必要なら一覧
  - 固定選択肢の変更: `vehicle-form.php` 冒頭の配列
  - 期限間近の日数変更: `class-vehicle.php`（`expiry_status_where`）と `vehicle-list.php`・`vehicle-form.php`（`+30 days`）の3箇所
