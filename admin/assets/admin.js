/* vehicle-manager / admin/assets/admin.js
   依存: jQuery (WordPress 同梱)
*/
(function ($) {
    'use strict';

    /* =========================================================
       共通ユーティリティ
    ========================================================= */
    function showNotice(msg, type) {
        // type: 'success' | 'error'
        var $n = $('#vm-form-notice');
        if (!$n.length) return;
        $n.attr('class', 'vm-notice vm-notice-' + type)
          .html('<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span> ' + msg)
          .show();
        $('html, body').animate({ scrollTop: $n.offset().top - 40 }, 300);
    }

    /* =========================================================
       タグバー初期化
    ========================================================= */
    function initTagBars() {
        var tags = (typeof vmData !== 'undefined') ? (vmData.tags || {}) : {};

        $('.vm-tag-bar').each(function () {
            var $bar    = $(this);
            var target  = $bar.data('target');
            var tagList = tags[target];
            if (!tagList || !tagList.length) return;

            tagList.forEach(function (tag) {
                $('<span class="vm-tag">')
                    .text(tag)
                    .on('click', function () {
                        var $input = $('#field-' + target);
                        if (!$input.length) return;
                        var cur = $input.val();
                        // すでに末尾にタグが含まれていない場合のみ付加
                        $input.val(cur + tag).trigger('input').focus();
                        // カーソルを末尾へ
                        var len = $input.val().length;
                        $input[0].setSelectionRange(len, len);
                    })
                    .appendTo($bar);
            });
        });
    }

    /* =========================================================
       車台番号 半角バリデーション（リアルタイム）
    ========================================================= */
    function initChassisValidation() {
        var $input = $('input[name="chassis_number"]');
        if (!$input.length) return;

        var $ok  = $('.vm-chassis-ok');
        var $ng  = $('.vm-chassis-ng');
        var $err = $('#chassis-error');

        function check() {
            var val = $input.val();
            if (val === '') {
                $ok.hide(); $ng.hide(); $err.hide();
                return;
            }
            if (/^[A-Za-z0-9\-_]+$/.test(val)) {
                $ok.show(); $ng.hide();
                $err.hide();
                $input.css('border-color', '');
            } else {
                $ok.hide(); $ng.show();
                $err.show();
                $input.css('border-color', 'var(--vm-danger)');
            }
        }

        $input.on('input', check);
        check(); // 編集時の初期チェック
    }

    /* =========================================================
       日付フィールド（カレンダー ↔ 和暦テキスト 連携）
    ========================================================= */
    function initDateFields() {

        // カレンダー → テキスト（西暦→和暦変換）
        $('.vm-date-picker').on('change', function () {
            var target   = $(this).data('wareki-target');
            var val      = $(this).val(); // YYYY-MM-DD or YYYY-MM
            var $text    = $('input[name="' + target + '"]');
            if (!val) { $text.val(''); return; }

            var wareki = toWareki(val);
            $text.val(wareki);
        });

        // テキスト → カレンダー（和暦→西暦変換）
        $('.vm-wareki-input').on('blur', function () {
            var name   = $(this).attr('name');
            var pickerName = name + '_picker';
            var $picker = $('input[name="' + pickerName + '"]');
            var val    = $(this).val().trim();
            if (!val) { $picker.val(''); return; }

            var iso = warekiToISO(val, $(this).hasClass('vm-wareki-ym'));
            if (iso) $picker.val(iso);
        });
    }

    // 西暦 → 和暦（表示用）
    function toWareki(iso) {
        var parts;
        var isYM = iso.length === 7; // YYYY-MM
        var date;

        if (isYM) {
            parts = iso.split('-');
            var y = parseInt(parts[0]), m = parseInt(parts[1]);
            if (y > 2019 || (y === 2019 && m >= 5)) {
                return '令和' + (y - 2018) + '年' + m + '月';
            } else if (y >= 1989) {
                return '平成' + (y - 1988) + '年' + m + '月';
            } else {
                return '昭和' + (y - 1925) + '年' + m + '月';
            }
        } else {
            parts = iso.split('-');
            var y = parseInt(parts[0]), m = parseInt(parts[1]), d = parseInt(parts[2]);
            date = new Date(y, m - 1, d);
            var ts = date.getTime();
            var reiwa = new Date(2019, 4, 1).getTime();  // 2019-05-01
            var heisei = new Date(1989, 0, 8).getTime(); // 1989-01-08
            if (ts >= reiwa) {
                return '令和' + (y - 2018) + '年' + m + '月' + d + '日';
            } else if (ts >= heisei) {
                return '平成' + (y - 1988) + '年' + m + '月' + d + '日';
            } else {
                return '昭和' + (y - 1925) + '年' + m + '月' + d + '日';
            }
        }
    }

    // 和暦テキスト → ISO（YYYY-MM-DD or YYYY-MM）
    function warekiToISO(str, isYM) {
        var m;
        if (!isYM) {
            m = str.match(/令和\s*(\d+)\s*年\s*(\d+)\s*月\s*(\d+)\s*日/);
            if (m) return pad4(2018 + parseInt(m[1])) + '-' + pad2(m[2]) + '-' + pad2(m[3]);
            m = str.match(/平成\s*(\d+)\s*年\s*(\d+)\s*月\s*(\d+)\s*日/);
            if (m) return pad4(1988 + parseInt(m[1])) + '-' + pad2(m[2]) + '-' + pad2(m[3]);
            m = str.match(/昭和\s*(\d+)\s*年\s*(\d+)\s*月\s*(\d+)\s*日/);
            if (m) return pad4(1925 + parseInt(m[1])) + '-' + pad2(m[2]) + '-' + pad2(m[3]);
            if (/^\d{4}-\d{2}-\d{2}$/.test(str)) return str;
        } else {
            m = str.match(/令和\s*(\d+)\s*年\s*(\d+)\s*月/);
            if (m) return pad4(2018 + parseInt(m[1])) + '-' + pad2(m[2]);
            m = str.match(/平成\s*(\d+)\s*年\s*(\d+)\s*月/);
            if (m) return pad4(1988 + parseInt(m[1])) + '-' + pad2(m[2]);
            m = str.match(/昭和\s*(\d+)\s*年\s*(\d+)\s*月/);
            if (m) return pad4(1925 + parseInt(m[1])) + '-' + pad2(m[2]);
            if (/^\d{4}-\d{2}$/.test(str)) return str;
        }
        return null;
    }

    function pad2(n) { return String(n).padStart(2, '0'); }
    function pad4(n) { return String(n).padStart(4, '0'); }

    /* =========================================================
       登録フォーム送信
    ========================================================= */
    function initVehicleForm() {
        var $form = $('#vm-vehicle-form');
        if (!$form.length) return;

        $form.on('submit', function (e) {
            e.preventDefault();

            // 車台番号バリデーション
            var chassis = $('input[name="chassis_number"]').val();
            if (chassis && !/^[A-Za-z0-9\-_]+$/.test(chassis)) {
                showNotice('車台番号に全角文字が含まれています。半角英数字・ハイフンのみ使用できます。', 'error');
                return;
            }

            var $btn = $('#vm-save-btn');
            $btn.prop('disabled', true).text('保存中...');

            $.post(vmData.ajaxUrl, $form.serialize(), function (res) {
                if (res.success) {
                    showNotice('保存しました。一覧に戻ります...', 'success');
                    setTimeout(function () {
                        window.location.href = vmData.listUrl;
                    }, 1200);
                } else {
                    showNotice(res.data || 'エラーが発生しました。', 'error');
                    $btn.prop('disabled', false).text('登録する');
                }
            }).fail(function (xhr) {
                showNotice('通信エラーが発生しました（HTTP ' + xhr.status + '）。', 'error');
                $btn.prop('disabled', false).text('登録する');
            });
        });
    }

    /* =========================================================
       車両一覧 — 削除モーダル
    ========================================================= */
    function initDeleteModal() {
        if (!$('#vm-delete-modal').length) return;

        $(document).on('click', '.vm-delete-btn', function () {
            var id     = $(this).data('id');
            var serial = $(this).data('serial');
            $('#vm-delete-serial').text(serial);
            $('#vm-delete-confirm').data('id', id);
            $('#vm-modal-overlay, #vm-delete-modal').show();
        });

        $('#vm-delete-cancel, #vm-modal-overlay').on('click', function () {
            $('#vm-modal-overlay, #vm-delete-modal').hide();
        });

        $('#vm-delete-confirm').on('click', function () {
            var id = $(this).data('id');
            $(this).prop('disabled', true).text('削除中...');

            $.post(vmData.ajaxUrl, {
                action: 'vm_delete',
                nonce:  vmData.listNonce,
                id:     id
            }, function (res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('削除失敗: ' + (res.data || '不明なエラー'));
                    $('#vm-modal-overlay, #vm-delete-modal').hide();
                    $('#vm-delete-confirm').prop('disabled', false).text('削除する');
                }
            }).fail(function () {
                alert('通信エラーが発生しました。');
                $('#vm-modal-overlay, #vm-delete-modal').hide();
            });
        });
    }

    /* =========================================================
       CSV インポート画面
    ========================================================= */
    function initCsvPage() {
        var $dropArea   = $('#vm-drop-area');
        var $fileInput  = $('#vm-csv-file');
        var $options    = $('#vm-csv-options');
        var $fileName   = $('#vm-file-name');
        var $previewBtn = $('#vm-preview-btn');
        var $importBtn  = $('#vm-import-btn');
        var $previewArea = $('#vm-preview-area');
        var $resultArea  = $('#vm-result-area');

        if (!$dropArea.length) return;

        var selectedFile = null;

        // ── ドラッグ&ドロップ ──────────────────────────
        $dropArea.on('dragover', function (e) {
            e.preventDefault();
            $(this).addClass('vm-drag-over');
        }).on('dragleave drop', function (e) {
            e.preventDefault();
            $(this).removeClass('vm-drag-over');
            if (e.type === 'drop') {
                var file = e.originalEvent.dataTransfer.files[0];
                if (file) setFile(file);
            }
        });

        $dropArea.on('click', function () { $fileInput.click(); });

        // ファイルラベルのクリックが dropArea に伝播しないように
        $('.vm-file-label').on('click', function (e) { e.stopPropagation(); });

        $fileInput.on('change', function () {
            if (this.files[0]) setFile(this.files[0]);
        });

        function setFile(file) {
            selectedFile = file;
            $fileName.text(file.name);
            $options.show();
            $previewArea.hide();
            $resultArea.hide();
        }

        // ── プレビュー ──────────────────────────────
        $previewBtn.on('click', function () {
            if (!selectedFile) { alert('ファイルを選択してください。'); return; }
            sendCsv(true);
        });

        // ── インポート確定 ──────────────────────────
        $('#vm-import-confirm-btn').on('click', function () {
            sendCsv(false);
        });

        // キャンセル
        $('#vm-preview-cancel').on('click', function () {
            $previewArea.hide();
        });

        // リセット
        $('#vm-reset-btn').on('click', function () {
            selectedFile = null;
            $fileInput.val('');
            $fileName.text('');
            $options.hide();
            $previewArea.hide();
            $resultArea.hide();
        });

        function sendCsv(preview) {
            if (!selectedFile) return;

            var mode = $('input[name="duplicate_mode"]:checked').val() || 'skip';
            var fd   = new FormData();
            fd.append('action', 'vm_csv_import');
            fd.append('nonce', vmData.csvNonce);
            fd.append('csv_file', selectedFile);
            fd.append('duplicate_mode', mode);
            fd.append('preview', preview ? '1' : '0');

            $('#vm-loading').show();
            $('#vm-loading-msg').text(preview ? 'プレビュー生成中...' : 'インポート中...');

            $.ajax({
                url:         vmData.ajaxUrl,
                type:        'POST',
                data:        fd,
                processData: false,
                contentType: false,
                success: function (res) {
                    $('#vm-loading').hide();
                    if (!res.success) {
                        alert('エラー: ' + (res.data || '不明なエラー'));
                        return;
                    }
                    var d = res.data;
                    if (preview) {
                        renderPreview(d);
                    } else {
                        renderResult(d);
                    }
                },
                error: function (xhr) {
                    $('#vm-loading').hide();
                    alert('通信エラー（HTTP ' + xhr.status + '）。');
                }
            });
        }

        function renderPreview(d) {
            var rows = d.preview_rows || [];
            var total = d.inserted || 0;

            $('#vm-preview-total').text('有効行合計: ' + total + ' 件');
            var $tbody = $('#vm-preview-tbody').empty();

            if (!rows.length) {
                $tbody.append('<tr><td colspan="9" class="vm-empty">プレビューできる行がありません</td></tr>');
            } else {
                rows.forEach(function (r) {
                    $tbody.append(
                        '<tr>' +
                        '<td>' + esc(r.transport_bureau) + '</td>' +
                        '<td>' + esc(r.classification_number) + '</td>' +
                        '<td>' + esc(r.purpose_category) + '</td>' +
                        '<td><strong>' + esc(r.serial_number) + '</strong></td>' +
                        '<td><code>' + esc(r.chassis_number) + '</code></td>' +
                        '<td>' + esc(r.registration_date) + '</td>' +
                        '<td>' + esc(r.expiry_date) + '</td>' +
                        '<td>' + esc(r.vehicle_name) + '</td>' +
                        '<td>' + esc(r.model) + '</td>' +
                        '</tr>'
                    );
                });
            }

            $previewArea.show();
            $('html, body').animate({ scrollTop: $previewArea.offset().top - 40 }, 400);
        }

        function renderResult(d) {
            $('#vm-result-inserted').text(d.inserted || 0);
            $('#vm-result-updated').text(d.updated || 0);
            $('#vm-result-skipped').text(d.skipped || 0);
            $('#vm-result-errors').text((d.errors || []).length);

            var $errList = $('#vm-error-list');
            var $errUl   = $('#vm-error-ul').empty();
            if (d.errors && d.errors.length) {
                d.errors.forEach(function (e) {
                    $errUl.append('<li>' + esc(e.row + '行目: ' + e.msg) + '</li>');
                });
                $errList.show();
            } else {
                $errList.hide();
            }

            $previewArea.hide();
            $resultArea.show();
            $('html, body').animate({ scrollTop: $resultArea.offset().top - 40 }, 400);
        }

        function esc(str) {
            if (str === null || str === undefined) return '';
            return $('<span>').text(String(str)).html();
        }
    }

    /* =========================================================
       DOMContentLoaded
    ========================================================= */
    $(function () {
        initTagBars();
        initChassisValidation();
        initDateFields();
        initVehicleForm();
        initDeleteModal();
        initCsvPage();
    });

}(jQuery));
