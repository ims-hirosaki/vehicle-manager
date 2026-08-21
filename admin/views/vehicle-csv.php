<?php
if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="vm-wrap">
    <div class="vm-page-header">
        <h1 class="vm-page-title">
            <span class="dashicons dashicons-upload"></span>
            CSV一括登録
        </h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=vehicle-manager' ) ); ?>"
           class="vm-back-link">← 車両一覧に戻る</a>
    </div>

    <!-- 説明カード -->
    <div class="vm-card vm-info-card">
        <div class="vm-card-title">
            <span class="dashicons dashicons-info-outline"></span> CSV フォーマット
        </div>
        <ul class="vm-info-list">
            <li>文字コード: <strong>Shift-JIS（CP932）</strong> または <strong>UTF-8（BOM あり/なし）</strong> — 自動判定</li>
            <li>1行目はヘッダー行（運輸支局,分類番号,用途区別,一連指定番号,車台番号,…）</li>
            <li>車台番号が空の行は自動的にスキップされます</li>
            <li>日付は「令和○年○月○日」「平成○年○月○日」形式または「YYYY-MM-DD」形式に対応</li>
            <li>車台番号に全角文字が含まれる行はエラーとして記録されスキップされます</li>
        </ul>
    </div>

    <!-- アップロードフォーム -->
    <div class="vm-card">
        <div class="vm-card-title">
            <span class="dashicons dashicons-media-spreadsheet"></span> ファイル選択
        </div>
        <div class="vm-csv-upload-area" id="vm-drop-area">
            <span class="dashicons dashicons-upload vm-drop-icon"></span>
            <p>CSVファイルをドラッグ＆ドロップ、またはボタンから選択</p>
            <label class="vm-btn vm-btn-secondary vm-file-label">
                ファイルを選択
                <input type="file" id="vm-csv-file" accept=".csv" style="display:none;">
            </label>
            <p id="vm-file-name" class="vm-file-name-display"></p>
        </div>

        <div class="vm-csv-options" id="vm-csv-options" style="display:none;">
            <label class="vm-label">重複時の処理</label>
            <div class="vm-radio-group">
                <label>
                    <input type="radio" name="duplicate_mode" value="skip" checked>
                    スキップ（既存データを保持）
                </label>
                <label>
                    <input type="radio" name="duplicate_mode" value="overwrite">
                    上書き（既存データを更新）
                </label>
            </div>
            <div class="vm-csv-btn-row">
                <button id="vm-preview-btn" class="vm-btn vm-btn-secondary">
                    <span class="dashicons dashicons-visibility"></span> プレビュー確認
                </button>
                <button id="vm-import-btn" class="vm-btn vm-btn-primary" style="display:none;">
                    <span class="dashicons dashicons-database-import"></span> インポート実行
                </button>
            </div>
        </div>
    </div>

    <!-- プレビューエリア -->
    <div id="vm-preview-area" style="display:none;">
        <div class="vm-card">
            <div class="vm-card-title">
                <span class="dashicons dashicons-visibility"></span>
                プレビュー（先頭10件）
                <span id="vm-preview-total" class="vm-badge-info"></span>
            </div>
            <div class="vm-table-wrap">
                <table class="vm-data-table vm-preview-table">
                    <thead>
                        <tr>
                            <th>運輸支局</th><th>分類番号</th><th>用途区別</th>
                            <th>一連指定番号</th><th>車台番号</th>
                            <th>登録年月日</th><th>有効期限満了日</th>
                            <th>車名</th><th>型式</th>
                        </tr>
                    </thead>
                    <tbody id="vm-preview-tbody"></tbody>
                </table>
            </div>
            <div class="vm-preview-confirm">
                <button id="vm-import-confirm-btn" class="vm-btn vm-btn-primary">
                    <span class="dashicons dashicons-database-import"></span>
                    上記内容でインポートを実行する
                </button>
                <button id="vm-preview-cancel" class="vm-btn vm-btn-secondary">キャンセル</button>
            </div>
        </div>
    </div>

    <!-- 結果エリア -->
    <div id="vm-result-area" style="display:none;">
        <div class="vm-card">
            <div class="vm-card-title">
                <span class="dashicons dashicons-yes-alt"></span> インポート結果
            </div>
            <div class="vm-result-summary">
                <div class="vm-result-item vm-result-success">
                    <span class="vm-result-num" id="vm-result-inserted">0</span>
                    <span class="vm-result-label">件 新規登録</span>
                </div>
                <div class="vm-result-item vm-result-update">
                    <span class="vm-result-num" id="vm-result-updated">0</span>
                    <span class="vm-result-label">件 上書き更新</span>
                </div>
                <div class="vm-result-item vm-result-skip">
                    <span class="vm-result-num" id="vm-result-skipped">0</span>
                    <span class="vm-result-label">件 スキップ</span>
                </div>
                <div class="vm-result-item vm-result-error">
                    <span class="vm-result-num" id="vm-result-errors">0</span>
                    <span class="vm-result-label">件 エラー</span>
                </div>
            </div>
            <div id="vm-error-list" style="display:none;">
                <h4 class="vm-error-list-title">エラー詳細</h4>
                <ul id="vm-error-ul" class="vm-error-ul"></ul>
            </div>
            <div class="vm-result-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=vehicle-manager' ) ); ?>"
                   class="vm-btn vm-btn-primary">車両一覧を確認する</a>
                <button id="vm-reset-btn" class="vm-btn vm-btn-secondary">別ファイルを読み込む</button>
            </div>
        </div>
    </div>

    <!-- ローディング -->
    <div id="vm-loading" class="vm-loading-overlay" style="display:none;">
        <div class="vm-loading-box">
            <div class="vm-spinner"></div>
            <p id="vm-loading-msg">処理中...</p>
        </div>
    </div>

</div><!-- .vm-wrap -->
