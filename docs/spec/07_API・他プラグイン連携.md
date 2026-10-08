# 07 公開API・AJAX・他プラグイン連携

> 対象: 車両管理プラグイン v1.0.0。実装元: `vehicle-manager.php`（公開関数）、`admin/class-admin-menu.php`（AJAX登録）、`includes/class-vehicle.php`（AJAX処理）

## 1. 他プラグインから呼べる公開 PHP 関数

プラグインが有効化されていれば、WordPress の他のプラグイン/テーマから直接呼び出せる（グローバル関数）。いずれも**読み取り専用**で、権限チェックは行わない（呼び出し側で制御する）。「車番」＝`serial_number`（一連指定番号）。

### `vm_get_vehicle_numbers()`
| 項目 | 内容 |
|---|---|
| 戻り値 | `array` 車番（一連指定番号）の配列。重複なし・昇順（文字列順）。空の車番は除外 |
| 用途 | 運行日報などの車番プルダウンの選択肢 |
| SQL | `SELECT DISTINCT serial_number ... WHERE serial_number != '' ORDER BY serial_number ASC` |

```php
$numbers = vm_get_vehicle_numbers(); // ['10','35','36', ...]
```

### `vm_get_transport_bureau_map()`
| 項目 | 内容 |
|---|---|
| 戻り値 | `array` `[ 車番 => 運輸支局 ]` の連想配列 |
| 用途 | 車番から運輸支局を一括で引く |

```php
$map = vm_get_transport_bureau_map(); // ['35' => '青森', '36' => '熊本']
```

### `vm_get_transport_bureau( $serial_number )`
| 項目 | 内容 |
|---|---|
| 引数 | `string $serial_number` 車番 |
| 戻り値 | `string` 運輸支局。未登録・空入力は空文字 `''` |
| 備考 | 引数は `sanitize_text_field` され、`prepare` でバインドされる |

### `vm_vehicle_exists( $serial_number )`
| 項目 | 内容 |
|---|---|
| 引数 | `string $serial_number` 車番 |
| 戻り値 | `bool` 登録済みなら true。空入力は false |

```php
if ( vm_vehicle_exists( '1234' ) ) { /* ... */ }
```

### 連携上の注意
- 車番（`serial_number`）は DB の UNIQUE 制約で一意が保証されるため、他プラグインの外部参照キーとして使える。
- 車両を削除すると、他プラグイン側に残った車番との整合性は保証されない（外部キー制約なし）。
- 公開関数は `vehicle-manager.php` がロードされた時点で定義される。呼び出し側は `function_exists( 'vm_get_vehicle_numbers' )` で存在確認をすると安全。
- クラス（`VM_Vehicle`, `VM_Master`）も呼べるが、公開APIとして保証されているのは上記4関数のみ。

## 2. AJAX エンドポイント（管理画面内部用）

すべて `wp-admin/admin-ajax.php`（`vmData.ajaxUrl`）へ POST。ログイン済みユーザー向け（`wp_ajax_` のみ、`wp_ajax_nopriv_` は無し）。権限は `edit_custom_plugins`。

| action | ハンドラ | nonce 名（パラメータ `nonce`） | 入力 | 成功レスポンス |
|---|---|---|---|---|
| `vm_save` | `VM_Vehicle::ajax_save` | `vm_form_nonce` | 全項目＋`id`（0=新規、>0=更新） | `{success:true, data:{id}}` |
| `vm_delete` | `VM_Vehicle::ajax_delete` | `vm_list_nonce` | `id` | `{success:true}` |
| `vm_csv_import` | `VM_Vehicle::ajax_csv_import` | `vm_csv_nonce` | `csv_file`, `duplicate_mode`, `preview` | `06_CSV一括登録仕様.md` 参照 |

エラー時は `{success:false, data:"メッセージ"}`。主なメッセージ:

| メッセージ | 原因 |
|---|---|
| 権限がありません。 | `edit_custom_plugins` が無い |
| （検証メッセージを `<br>` 連結） | 必須・形式エラー（`05_業務ルール.md`） |
| 一連指定番号「○○」はすでに登録されています。 | UNIQUE 重複（DBエラー文に "Duplicate" を含む場合） |
| DB エラー: … | 上記以外のDB失敗 |
| 無効なID です。 | `id` が 0 以下 |
| 削除に失敗しました。 | DELETE が false |
| ファイルのアップロードに失敗しました。 | CSV 未受信/エラー |

- `vm_save` は `id` が正の数なら **更新**、そうでなければ **新規挿入**。更新対象IDの存在確認は行わない（存在しないIDなら 0 行更新で成功扱い）。
- nonce の不一致は WordPress 標準の `check_ajax_referer` により処理が拒否される（HTTP 403 等）。
- 削除は物理削除（ゴミ箱・論理削除なし）。

## 3. マスタ管理の更新（AJAX ではない）

マスタ管理画面は通常のフォーム POST。`vm_master_action` = `add` / `delete` / `move`、`master_type` = `bureau` / `class_number` / `purpose`、`item_name`、`dir`（`up`/`down`）。nonce は `check_admin_referer('vm_master_nonce', '_vm_master_nonce')`。

## 4. WordPress フック一覧

| フック | 登録内容 |
|---|---|
| `register_activation_hook` | `VM_DB_Install::install`（テーブル作成、マスタ初期化、タグ初期化） |
| `admin_menu` | `VM_Admin_Menu::register_menus` |
| `admin_enqueue_scripts` | `VM_Admin_Menu::enqueue_assets` |
| `wp_ajax_vm_save` / `wp_ajax_vm_delete` / `wp_ajax_vm_csv_import` | 上記AJAX |

プラグイン独自の `do_action` / `apply_filters`（拡張ポイント）は**提供していない**。

## 5. 定数

| 定数 | 値 | 用途 |
|---|---|---|
| `VM_VERSION` | `1.0.0` | アセットのバージョンクエリ |
| `VM_PLUGIN_DIR` | プラグインの絶対パス | require 用 |
| `VM_PLUGIN_URL` | プラグインのURL | CSS/JS 読込 |
| `VM_TABLE` | `vehicle_manager` | テーブル名（`$wpdb->prefix` を前置） |
