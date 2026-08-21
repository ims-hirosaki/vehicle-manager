<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ── フィルター・ページング ────────────────────────────────────
$per_page         = 20;
$filter_transport = sanitize_text_field( $_GET['transport_bureau'] ?? '' );
$orderby          = sanitize_text_field( $_GET['orderby'] ?? 'serial_number' );
$order            = strtoupper( sanitize_text_field( $_GET['order'] ?? 'ASC' ) );
$current_page     = max( 1, intval( $_GET['paged'] ?? 1 ) );
$offset           = ( $current_page - 1 ) * $per_page;

$args  = array(
    'transport_bureau' => $filter_transport,
    'orderby'          => $orderby,
    'order'            => $order,
    'limit'            => $per_page,
    'offset'           => $offset,
);
$vehicles   = VM_Vehicle::get_list( $args );
$total      = VM_Vehicle::count( $args );
$total_pages = ceil( $total / $per_page );

// 運輸支局選択肢
global $wpdb;
$bureaus = $wpdb->get_col( "SELECT DISTINCT transport_bureau FROM {$wpdb->prefix}vehicle_manager ORDER BY transport_bureau" );

// ソートリンク生成ヘルパー
function vm_sort_link( $column, $label, $current_orderby, $current_order, $filter_transport ) {
    $new_order = ( $current_orderby === $column && $current_order === 'ASC' ) ? 'DESC' : 'ASC';
    $icon = '';
    if ( $current_orderby === $column ) {
        $icon = $current_order === 'ASC' ? ' ▲' : ' ▼';
    }
    $url = add_query_arg( array(
        'page'             => 'vehicle-manager',
        'orderby'          => $column,
        'order'            => $new_order,
        'transport_bureau' => $filter_transport,
    ), admin_url( 'admin.php' ) );
    return '<a href="' . esc_url( $url ) . '" class="vm-sort-link">' . esc_html( $label ) . $icon . '</a>';
}

// 今日の日付
$today    = date( 'Y-m-d' );
$warn_day = date( 'Y-m-d', strtotime( '+30 days' ) );
?>

<div class="vm-wrap">
    <!-- ページタイトル -->
    <div class="vm-page-header">
        <h1 class="vm-page-title">
            <span class="dashicons dashicons-car"></span>
            車両一覧
            <span class="vm-badge"><?php echo esc_html( $total ); ?>件</span>
        </h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=vm-vehicle-form&action=new' ) ); ?>"
           class="vm-btn vm-btn-primary">
            <span class="dashicons dashicons-plus-alt2"></span> 新規登録
        </a>
    </div>

    <!-- フィルターバー -->
    <div class="vm-card vm-filter-bar">
        <form method="get">
            <input type="hidden" name="page" value="vehicle-manager">
            <label class="vm-label">運輸支局</label>
            <select name="transport_bureau" class="vm-select" onchange="this.form.submit()">
                <option value="">すべて</option>
                <?php foreach ( $bureaus as $b ) : ?>
                    <option value="<?php echo esc_attr( $b ); ?>" <?php selected( $filter_transport, $b ); ?>>
                        <?php echo esc_html( $b ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="vm-btn vm-btn-secondary">絞り込む</button>
            <?php if ( $filter_transport ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=vehicle-manager' ) ); ?>"
                   class="vm-btn vm-btn-ghost">クリア</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- 一覧テーブル -->
    <div class="vm-card vm-card-table">
        <div class="vm-table-wrap">
            <table class="vm-data-table">
                <thead>
                    <tr>
                        <th><?php echo vm_sort_link( 'transport_bureau', '運輸支局', $orderby, $order, $filter_transport ); ?></th>
                        <th>分類番号</th>
                        <th>用途区別</th>
                        <th><?php echo vm_sort_link( 'serial_number', '一連指定番号', $orderby, $order, $filter_transport ); ?></th>
                        <th><?php echo vm_sort_link( 'registration_date', '登録年月日', $orderby, $order, $filter_transport ); ?></th>
                        <th>初度登録年月</th>
                        <th><?php echo vm_sort_link( 'expiry_date', '有効期限満了日', $orderby, $order, $filter_transport ); ?></th>
                        <th>ブレーキ</th>
                        <th>リーフスプリング</th>
                        <th class="vm-col-actions">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $vehicles ) ) : ?>
                        <tr>
                            <td colspan="10" class="vm-empty">登録データがありません。</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $vehicles as $v ) :
                            // 有効期限の状態
                            $row_class = '';
                            if ( $v->expiry_date ) {
                                if ( $v->expiry_date < $today ) {
                                    $row_class = 'vm-row-expired';
                                } elseif ( $v->expiry_date <= $warn_day ) {
                                    $row_class = 'vm-row-warn';
                                }
                            }
                        ?>
                        <tr class="<?php echo esc_attr( $row_class ); ?>">
                            <td><?php echo esc_html( $v->transport_bureau ); ?></td>
                            <td><?php echo esc_html( $v->classification_number ); ?></td>
                            <td><?php echo esc_html( $v->purpose_category ); ?></td>
                            <td class="vm-serial"><?php echo esc_html( $v->serial_number ); ?></td>
                            <td><?php echo esc_html( VM_Vehicle::date_to_wareki( $v->registration_date ) ); ?></td>
                            <td><?php echo esc_html( VM_Vehicle::ym_to_wareki( $v->initial_registration_ym ) ); ?></td>
                            <td class="vm-expiry-cell">
                                <?php echo esc_html( VM_Vehicle::date_to_wareki( $v->expiry_date ) ); ?>
                                <?php if ( $v->expiry_date && $v->expiry_date < $today ) : ?>
                                    <span class="vm-badge-danger">期限切れ</span>
                                <?php elseif ( $v->expiry_date && $v->expiry_date <= $warn_day ) : ?>
                                    <span class="vm-badge-warn">30日以内</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( $v->brake ); ?></td>
                            <td><?php echo esc_html( $v->leaf_spring ); ?></td>
                            <td class="vm-col-actions">
                                <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'vm-vehicle-form', 'action' => 'edit', 'id' => $v->id ), admin_url( 'admin.php' ) ) ); ?>"
                                   class="vm-btn-sm">編集</a>
                                <button class="vm-btn-sm vm-btn-sm-danger vm-delete-btn"
                                        data-id="<?php echo esc_attr( $v->id ); ?>"
                                        data-serial="<?php echo esc_attr( $v->serial_number ); ?>">
                                    削除
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ページネーション -->
        <?php if ( $total_pages > 1 ) :
            $base_url = add_query_arg( array(
                'page'             => 'vehicle-manager',
                'orderby'          => $orderby,
                'order'            => $order,
                'transport_bureau' => $filter_transport,
            ), admin_url( 'admin.php' ) );
        ?>
        <div class="vm-pagination">
            <?php if ( $current_page > 1 ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1, $base_url ) ); ?>"
                   class="vm-page-btn">‹ 前へ</a>
            <?php endif; ?>

            <?php for ( $i = max( 1, $current_page - 2 ); $i <= min( $total_pages, $current_page + 2 ); $i++ ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'paged', $i, $base_url ) ); ?>"
                   class="vm-page-btn <?php echo $i === $current_page ? 'vm-page-current' : ''; ?>">
                    <?php echo esc_html( $i ); ?>
                </a>
            <?php endfor; ?>

            <?php if ( $current_page < $total_pages ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1, $base_url ) ); ?>"
                   class="vm-page-btn">次へ ›</a>
            <?php endif; ?>

            <span class="vm-page-info">
                <?php echo esc_html( $total ); ?>件中
                <?php echo esc_html( $offset + 1 ); ?>〜<?php echo esc_html( min( $offset + $per_page, $total ) ); ?>件を表示
            </span>
        </div>
        <?php endif; ?>
    </div><!-- .vm-card -->
</div><!-- .vm-wrap -->

<!-- 削除確認モーダル -->
<div id="vm-delete-modal" class="vm-modal" style="display:none;">
    <div class="vm-modal-box">
        <p class="vm-modal-msg">一連指定番号 <strong id="vm-delete-serial"></strong> の車両を削除しますか？<br>この操作は取り消せません。</p>
        <div class="vm-modal-actions">
            <button id="vm-delete-cancel" class="vm-btn vm-btn-secondary">キャンセル</button>
            <button id="vm-delete-confirm" class="vm-btn vm-btn-danger" data-id="">削除する</button>
        </div>
    </div>
</div>
<div id="vm-modal-overlay" class="vm-modal-overlay" style="display:none;"></div>
