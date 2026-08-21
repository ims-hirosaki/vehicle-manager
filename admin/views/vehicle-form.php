<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$action = sanitize_text_field( $_GET['action'] ?? 'new' );
$id     = intval( $_GET['id'] ?? 0 );
$v      = null;

if ( $action === 'edit' && $id > 0 ) {
    $v = VM_Vehicle::get( $id );
    if ( ! $v ) {
        wp_die( '車両データが見つかりません。' );
    }
}

$is_edit = ( $v !== null );
$title   = $is_edit ? '車両情報の編集' : '新規車両登録';

// フォーム値取得（編集時は DB 値、新規はデフォルト）
function vm_val( $v, $key, $default = '' ) {
    if ( ! $v ) return $default;
    return esc_attr( $v->$key ?? $default );
}

// セレクタ選択肢
$transport_bureaus  = array( '青森', '熊本' );
$class_numbers      = array( '130', '131', '830' );
$purpose_categories = array( 'あ', 'い', 'う', 'え', 'か', 'き', 'く', 'け', 'こ', 'を' );
$usage_types        = array( '特種', '貨物' );
$body_shapes        = array( '冷蔵冷凍車', 'バン' );
$brake_types        = array( 'ドラム', 'ディスク' );
$fuel_types         = array( '軽油', 'ガソリン', 'LPG', '電気', 'ハイブリッド' );

// 表示用日付（編集時：和暦変換）
$reg_date_disp    = $v ? VM_Vehicle::date_to_wareki( $v->registration_date )   : '';
$expiry_date_disp = $v ? VM_Vehicle::date_to_wareki( $v->expiry_date )          : '';
$init_reg_disp    = $v ? VM_Vehicle::ym_to_wareki( $v->initial_registration_ym ) : '';
?>

<div class="vm-wrap">
    <!-- ヘッダー -->
    <div class="vm-page-header">
        <h1 class="vm-page-title">
            <span class="dashicons dashicons-<?php echo $is_edit ? 'edit' : 'plus-alt'; ?>"></span>
            <?php echo esc_html( $title ); ?>
        </h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=vehicle-manager' ) ); ?>"
           class="vm-back-link">← 車両一覧に戻る</a>
    </div>

    <!-- 通知エリア -->
    <div id="vm-form-notice" style="display:none;"></div>

    <form id="vm-vehicle-form" autocomplete="off">
        <?php wp_nonce_field( 'vm_form_nonce', '_wpnonce' ); ?>
        <input type="hidden" name="action" value="vm_save">
        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'vm_form_nonce' ) ); ?>">
        <input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">

        <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
             セクション1: 基本情報
        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
        <div class="vm-card">
            <div class="vm-card-title">
                <span class="dashicons dashicons-id-alt"></span> 基本情報
            </div>
            <div class="vm-form-grid">

                <!-- 運輸支局 -->
                <div class="vm-field">
                    <label class="vm-label vm-required">運輸支局</label>
                    <select name="transport_bureau" class="vm-select vm-select-sm">
                        <option value="">選択してください</option>
                        <?php foreach ( $transport_bureaus as $tb ) : ?>
                            <option value="<?php echo esc_attr( $tb ); ?>"
                                <?php selected( vm_val( $v, 'transport_bureau' ), $tb ); ?>>
                                <?php echo esc_html( $tb ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 分類番号 -->
                <div class="vm-field">
                    <label class="vm-label vm-required">分類番号</label>
                    <select name="classification_number" class="vm-select vm-select-sm">
                        <option value="">選択してください</option>
                        <?php foreach ( $class_numbers as $cn ) : ?>
                            <option value="<?php echo esc_attr( $cn ); ?>"
                                <?php selected( vm_val( $v, 'classification_number' ), $cn ); ?>>
                                <?php echo esc_html( $cn ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 用途区別 -->
                <div class="vm-field">
                    <label class="vm-label vm-required">用途区別</label>
                    <select name="purpose_category" class="vm-select vm-select-sm">
                        <option value="">選択してください</option>
                        <?php foreach ( $purpose_categories as $pc ) : ?>
                            <option value="<?php echo esc_attr( $pc ); ?>"
                                <?php selected( vm_val( $v, 'purpose_category' ), $pc ); ?>>
                                <?php echo esc_html( $pc ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 一連指定番号 -->
                <div class="vm-field">
                    <label class="vm-label vm-required">一連指定番号</label>
                    <input type="text" name="serial_number"
                           value="<?php echo vm_val( $v, 'serial_number' ); ?>"
                           class="vm-input vm-input-sm"
                           placeholder="例: 35">
                    <span class="vm-hint">外部参照キー。一意（重複不可）</span>
                </div>

                <!-- 車台番号 -->
                <div class="vm-field vm-field-wide">
                    <label class="vm-label vm-required">車台番号</label>
                    <div class="vm-tag-bar" data-target="chassis_number"></div>
                    <div class="vm-input-wrap">
                        <input type="text" name="chassis_number" id="field-chassis_number"
                               value="<?php echo vm_val( $v, 'chassis_number' ); ?>"
                               class="vm-input vm-input-half-an"
                               placeholder="例: FR1AH-105156"
                               pattern="[A-Za-z0-9\-_]+"
                               title="半角英数字・ハイフン・アンダースコアのみ使用できます">
                        <span class="vm-input-icon vm-chassis-ok" title="半角OK" style="display:none;">✔</span>
                        <span class="vm-input-icon vm-chassis-ng" title="全角NG" style="display:none;">✖</span>
                    </div>
                    <span class="vm-hint">半角英数字・ハイフンのみ。タグをクリックでプレフィックス入力後に続きを追記できます。</span>
                    <div class="vm-field-error" id="chassis-error" style="display:none;">
                        全角文字が含まれています。半角英数字・ハイフンのみ使用できます。
                    </div>
                </div>

            </div><!-- .vm-form-grid -->
        </div><!-- .vm-card -->

        <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
             セクション2: 日付情報
        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
        <div class="vm-card">
            <div class="vm-card-title">
                <span class="dashicons dashicons-calendar-alt"></span> 日付情報
            </div>
            <div class="vm-date-hint-box">
                <span class="dashicons dashicons-info-outline"></span>
                カレンダーから選択、または「令和○年○月○日」形式で直接入力できます。
            </div>
            <div class="vm-form-grid">

                <!-- 登録年月日 -->
                <div class="vm-field">
                    <label class="vm-label">登録年月日</label>
                    <div class="vm-date-field-wrap">
                        <input type="date" name="registration_date_picker"
                               class="vm-input vm-date-picker"
                               data-wareki-target="registration_date"
                               value="<?php echo esc_attr( $v->registration_date ?? '' ); ?>">
                        <input type="text" name="registration_date"
                               class="vm-input vm-wareki-input"
                               value="<?php echo esc_html( $reg_date_disp ); ?>"
                               placeholder="令和○年○月○日 または YYYY-MM-DD">
                    </div>
                </div>

                <!-- 初度登録年月 -->
                <div class="vm-field">
                    <label class="vm-label">初度登録年月</label>
                    <div class="vm-date-field-wrap">
                        <input type="month" name="initial_registration_ym_picker"
                               class="vm-input vm-date-picker"
                               data-wareki-target="initial_registration_ym"
                               value="<?php echo esc_attr( $v->initial_registration_ym ?? '' ); ?>">
                        <input type="text" name="initial_registration_ym"
                               class="vm-input vm-wareki-input vm-wareki-ym"
                               value="<?php echo esc_html( $init_reg_disp ); ?>"
                               placeholder="令和○年○月 または YYYY-MM">
                    </div>
                </div>

                <!-- 有効期限満了日 -->
                <div class="vm-field">
                    <label class="vm-label">有効期限満了日</label>
                    <div class="vm-date-field-wrap">
                        <input type="date" name="expiry_date_picker"
                               class="vm-input vm-date-picker"
                               data-wareki-target="expiry_date"
                               value="<?php echo esc_attr( $v->expiry_date ?? '' ); ?>">
                        <input type="text" name="expiry_date"
                               class="vm-input vm-wareki-input"
                               value="<?php echo esc_html( $expiry_date_disp ); ?>"
                               placeholder="令和○年○月○日 または YYYY-MM-DD">
                    </div>
                    <?php
                    if ( $v && $v->expiry_date ) {
                        $today = date( 'Y-m-d' );
                        if ( $v->expiry_date < $today ) {
                            echo '<div class="vm-field-warn vm-field-warn-danger"><span class="dashicons dashicons-warning"></span> 有効期限が切れています。</div>';
                        } elseif ( $v->expiry_date <= date( 'Y-m-d', strtotime( '+30 days' ) ) ) {
                            echo '<div class="vm-field-warn"><span class="dashicons dashicons-warning"></span> 有効期限まで30日以内です。</div>';
                        }
                    }
                    ?>
                </div>

            </div>
        </div>

        <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
             セクション3: 車両情報
        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
        <div class="vm-card">
            <div class="vm-card-title">
                <span class="dashicons dashicons-car"></span> 車両情報
            </div>
            <div class="vm-form-grid">

                <!-- 車名（タグ） -->
                <div class="vm-field vm-field-wide">
                    <label class="vm-label">車名</label>
                    <div class="vm-tag-bar" data-target="vehicle_name"></div>
                    <input type="text" name="vehicle_name" id="field-vehicle_name"
                           value="<?php echo vm_val( $v, 'vehicle_name' ); ?>"
                           class="vm-input"
                           placeholder="例: 日野">
                </div>

                <!-- 型式（タグ） -->
                <div class="vm-field vm-field-wide">
                    <label class="vm-label">型式</label>
                    <div class="vm-tag-bar" data-target="model"></div>
                    <input type="text" name="model" id="field-model"
                           value="<?php echo vm_val( $v, 'model' ); ?>"
                           class="vm-input"
                           placeholder="例: 2PG-CD5CE">
                </div>

                <!-- 原動機の型式（タグ） -->
                <div class="vm-field">
                    <label class="vm-label">原動機の型式</label>
                    <div class="vm-tag-bar" data-target="engine_model"></div>
                    <input type="text" name="engine_model" id="field-engine_model"
                           value="<?php echo vm_val( $v, 'engine_model' ); ?>"
                           class="vm-input"
                           placeholder="例: GH11">
                </div>

                <!-- 自動車の種別（固定値） -->
                <div class="vm-field">
                    <label class="vm-label">自動車の種別</label>
                    <div class="vm-fixed-field-wrap">
                        <input type="text" name="vehicle_type"
                               value="<?php echo vm_val( $v, 'vehicle_type', '普通' ); ?>"
                               class="vm-input vm-input-sm">
                        <span class="vm-fixed-badge">デフォルト: 普通</span>
                    </div>
                </div>

                <!-- 用途 -->
                <div class="vm-field">
                    <label class="vm-label">用途</label>
                    <select name="usage_type" class="vm-select vm-select-sm">
                        <option value="">選択してください</option>
                        <?php foreach ( $usage_types as $ut ) : ?>
                            <option value="<?php echo esc_attr( $ut ); ?>"
                                <?php selected( vm_val( $v, 'usage_type' ), $ut ); ?>>
                                <?php echo esc_html( $ut ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 自家用・事業用の別（固定値） -->
                <div class="vm-field">
                    <label class="vm-label">自家用・事業用の別</label>
                    <div class="vm-fixed-field-wrap">
                        <input type="text" name="ownership_type"
                               value="<?php echo vm_val( $v, 'ownership_type', '事業用' ); ?>"
                               class="vm-input vm-input-sm">
                        <span class="vm-fixed-badge">デフォルト: 事業用</span>
                    </div>
                </div>

                <!-- 車体の形状 -->
                <div class="vm-field">
                    <label class="vm-label">車体の形状</label>
                    <select name="body_shape" class="vm-select vm-select-sm">
                        <option value="">選択してください</option>
                        <?php foreach ( $body_shapes as $bs ) : ?>
                            <option value="<?php echo esc_attr( $bs ); ?>"
                                <?php selected( vm_val( $v, 'body_shape' ), $bs ); ?>>
                                <?php echo esc_html( $bs ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 乗車定員（固定値） -->
                <div class="vm-field">
                    <label class="vm-label">乗車定員</label>
                    <div class="vm-fixed-field-wrap">
                        <input type="number" name="passenger_capacity" min="1" max="99"
                               value="<?php echo vm_val( $v, 'passenger_capacity', '2' ); ?>"
                               class="vm-input vm-input-xs">
                        <span class="vm-unit">名</span>
                        <span class="vm-fixed-badge">デフォルト: 2</span>
                    </div>
                </div>

                <!-- 燃料の種類（固定値） -->
                <div class="vm-field">
                    <label class="vm-label">燃料の種類</label>
                    <div class="vm-fixed-field-wrap">
                        <select name="fuel_type" class="vm-select vm-select-sm">
                            <?php foreach ( $fuel_types as $ft ) : ?>
                                <option value="<?php echo esc_attr( $ft ); ?>"
                                    <?php selected( vm_val( $v, 'fuel_type', '軽油' ), $ft ); ?>>
                                    <?php echo esc_html( $ft ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="vm-fixed-badge">デフォルト: 軽油</span>
                    </div>
                </div>

                <!-- ブレーキ -->
                <div class="vm-field">
                    <label class="vm-label">ブレーキ</label>
                    <select name="brake" class="vm-select vm-select-sm">
                        <option value="">選択してください</option>
                        <?php foreach ( $brake_types as $bt ) : ?>
                            <option value="<?php echo esc_attr( $bt ); ?>"
                                <?php selected( vm_val( $v, 'brake' ), $bt ); ?>>
                                <?php echo esc_html( $bt ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- リーフスプリング（タグ） -->
                <div class="vm-field">
                    <label class="vm-label">リーフスプリング</label>
                    <div class="vm-tag-bar" data-target="leaf_spring"></div>
                    <input type="text" name="leaf_spring" id="field-leaf_spring"
                           value="<?php echo vm_val( $v, 'leaf_spring' ); ?>"
                           class="vm-input vm-input-sm"
                           placeholder="例: フロント板羽">
                </div>

            </div>
        </div>

        <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
             セクション4: 重量・寸法
        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
        <div class="vm-card">
            <div class="vm-card-title">
                <span class="dashicons dashicons-editor-expand"></span> 重量・寸法
            </div>
            <div class="vm-form-grid vm-form-grid-nums">

                <?php
                $num_fields = array(
                    'max_load_kg'         => array( '最大積載量', 'kg' ),
                    'vehicle_weight_kg'   => array( '車両重量', 'kg' ),
                    'gross_weight_kg'     => array( '車両総重量', 'kg' ),
                    'length_cm'           => array( '長さ', 'cm' ),
                    'width_cm'            => array( '幅', 'cm' ),
                    'height_cm'           => array( '高さ', 'cm' ),
                );
                foreach ( $num_fields as $fname => $info ) : ?>
                <div class="vm-field">
                    <label class="vm-label"><?php echo esc_html( $info[0] ); ?></label>
                    <div class="vm-input-unit">
                        <input type="number" name="<?php echo esc_attr( $fname ); ?>"
                               value="<?php echo vm_val( $v, $fname ); ?>"
                               class="vm-input vm-input-num" min="0" step="1">
                        <span class="vm-unit"><?php echo esc_html( $info[1] ); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
        </div>

        <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
             セクション5: 軸重
        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
        <div class="vm-card">
            <div class="vm-card-title">
                <span class="dashicons dashicons-dashboard"></span> 軸重
            </div>
            <div class="vm-form-grid vm-form-grid-nums">
                <?php
                $axle_fields = array(
                    'front_front_axle_kg' => '前前軸重',
                    'front_rear_axle_kg'  => '前後軸重',
                    'rear_front_axle_kg'  => '後前軸重',
                    'rear_rear_axle_kg'   => '後後軸重',
                );
                foreach ( $axle_fields as $fname => $label ) : ?>
                <div class="vm-field">
                    <label class="vm-label"><?php echo esc_html( $label ); ?></label>
                    <div class="vm-input-unit">
                        <input type="number" name="<?php echo esc_attr( $fname ); ?>"
                               value="<?php echo vm_val( $v, $fname ); ?>"
                               class="vm-input vm-input-num" min="0" step="1">
                        <span class="vm-unit">kg</span>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- 総排気量 -->
                <div class="vm-field">
                    <label class="vm-label">総排気量・定格出力</label>
                    <div class="vm-input-unit">
                        <input type="number" name="displacement"
                               value="<?php echo vm_val( $v, 'displacement' ); ?>"
                               class="vm-input vm-input-num" min="0" step="0.01">
                        <span class="vm-unit">kw / L</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
             セクション6: その他
        ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ -->
        <div class="vm-card">
            <div class="vm-card-title">
                <span class="dashicons dashicons-list-view"></span> その他
            </div>
            <div class="vm-form-grid">
                <?php
                $other_fields = array(
                    'model_designation_number' => '型式指定番号',
                    'category_class_number'    => '類別区分番号',
                    'original_name'            => '原本名称',
                    'inspection_category'      => '点検分類',
                );
                foreach ( $other_fields as $fname => $label ) : ?>
                <div class="vm-field">
                    <label class="vm-label"><?php echo esc_html( $label ); ?></label>
                    <input type="text" name="<?php echo esc_attr( $fname ); ?>"
                           value="<?php echo vm_val( $v, $fname ); ?>"
                           class="vm-input vm-input-sm"
                           placeholder="任意">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 保存ボタン -->
        <div class="vm-form-footer">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=vehicle-manager' ) ); ?>"
               class="vm-btn vm-btn-secondary">キャンセル</a>
            <button type="submit" id="vm-save-btn" class="vm-btn vm-btn-primary">
                <span class="dashicons dashicons-saved"></span>
                <?php echo $is_edit ? '更新する' : '登録する'; ?>
            </button>
        </div>

    </form>
</div><!-- .vm-wrap -->
