# 01 システム概要

> 対象: 車両管理プラグイン v1.0.0（有限会社たんぽぽ運送 社内用 WordPress プラグイン）

## 1. 目的

運送会社が保有する車両の車検証情報を一元管理し、次のことを実現する。

1. 車両情報（車検証項目）の登録・編集・削除・一覧表示
2. 有効期限満了日の色分け表示と絞り込みによる車検切れ防止
3. 車検証データ CSV の一括取り込み（Shift-JIS / UTF-8 対応、和暦対応）
4. 選択肢（運輸支局・分類番号・用途区別）のマスタ管理
5. 他の WordPress プラグイン（例: 運行管理系）への車番・運輸支局情報の提供（公開PHP関数）

## 2. 機能一覧

| # | 機能 | メニュー（内部ページ名） | 概要 |
|---|---|---|---|
| F1 | 車両一覧 | 車両管理 > 車両一覧（`vehicle-manager`） | 20件/ページ。運輸支局・有効期限で絞り込み、並べ替え、編集・削除 |
| F2 | 車両登録/編集 | 車両管理 > 車両登録（`vm-vehicle-form`） | 34項目のフォーム。和暦入力、タグ入力補助、車台番号リアルタイム検証 |
| F3 | CSV一括登録 | 車両管理 > CSV一括登録（`vm-vehicle-csv`） | ドラッグ&ドロップ、プレビュー、重複時スキップ/上書き、エラー行表示 |
| F4 | マスタ管理 | 車両管理 > マスタ管理（`vm-master`） | 運輸支局・分類番号・用途区別の追加・削除・並べ替え |
| F5 | 有効期限警告 | 一覧・編集画面内 | 期限切れ=赤、30日以内=黄 |
| F6 | タグ入力補助 | 登録フォーム | 車名・型式・原動機型式・車台番号接頭辞・リーフスプリングの頻出値ボタン |
| F7 | 公開API | PHP関数 `vm_*` | 車番一覧、車番→運輸支局マップ等を他プラグインへ提供 |

## 3. 全体構成（概念図）

```
[ブラウザ（WordPress管理画面）]
   ├─ 車両一覧 ──(GET, サーバー描画)──────────────▶ VM_Vehicle::get_list / count
   ├─ 車両登録/編集 ─(AJAX: vm_save)──────────────▶ VM_Vehicle::ajax_save
   ├─ 一覧の削除 ───(AJAX: vm_delete)─────────────▶ VM_Vehicle::ajax_delete
   ├─ CSV一括登録 ──(AJAX: vm_csv_import)─────────▶ VM_Vehicle::ajax_csv_import
   └─ マスタ管理 ───(通常のPOSTフォーム)──────────▶ VM_Master::add_item / delete_item / move_item

[データ保存]
   ├─ テーブル {prefix}vehicle_manager   … 車両データ本体（1車両=1行）
   ├─ option vm_transport_bureaus        … 運輸支局マスタ
   ├─ option vm_class_numbers            … 分類番号マスタ
   ├─ option vm_purpose_categories       … 用途区別マスタ
   ├─ option vm_tag_data                 … タグ（JSON）
   └─ option vm_db_version               … DBバージョン

[他プラグイン] ──(PHP関数呼び出し)──▶ vm_get_vehicle_numbers() 等
```

## 4. 利用者と権限

| 利用者 | できること | 必要な権限（WordPress ケイパビリティ） |
|---|---|---|
| 閲覧・操作画面を開く人 | メニュー表示、各画面の表示 | `access_custom_plugins` |
| 登録・更新・削除・CSV取込を行う人 | データの保存、削除、取り込み（AJAX処理） | `edit_custom_plugins` |

- 権限名はこのプラグインでは**定義しておらず**、別の仕組み（権限管理系プラグイン等）で付与される前提。どのロールに付与されているかはソースからは確認できない。
- マスタ管理画面の POST 処理（追加・削除・並べ替え）は、画面表示権限（`access_custom_plugins`）と nonce のみで判定され、`edit_custom_plugins` のチェックは行われない（詳細は `09_faq_known_issues.md`）。

## 5. 技術スタック

| 項目 | 内容 |
|---|---|
| プラットフォーム | WordPress プラグイン（管理画面のみ、フロント画面なし） |
| 言語 | PHP（クラスは静的メソッド中心）、JavaScript（jQuery、WordPress同梱） |
| DB | MySQL（`dbDelta` でテーブル作成） |
| UI | 独自CSS（`vm-` 接頭辞）。WordPress の dashicons を使用。可能なら `employee-manager` プラグインの共通CSSも読込 |
| ビルド | なし（ソースをそのまま配置） |
| デプロイ | GitHub Actions → FTP（本番: main、検証: staging） |
| 依存プラグイン | 必須の依存はなし。`employee-manager` が入っていれば共通CSSを流用（任意） |

## 6. バージョン履歴（Git履歴から判明する範囲）

| 時期の順 | 変更 |
|---|---|
| 初期 | 車両登録・一覧・CSV一括登録の基本機能 |
| PR #3 | 運輸支局をマスタ管理化（選択肢を画面から追加・削除） |
| PR #4 | 分類番号・用途区別もマスタ管理に対応 |
| PR #5 | 車両一覧に有効期限での絞り込み（期限切れを除く／期限間近／期限切れのみ） |
| PR #6 | 運輸支局取得の公開関数を追加（`vm_get_transport_bureau_map`, `vm_get_transport_bureau`） |

※ プラグインのバージョン定数は 1.0.0 のまま変更されていない。
