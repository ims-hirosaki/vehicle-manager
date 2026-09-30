<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$notice = array();

// ── POST 処理 ─────────────────────────────────────────
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['vm_master_action'] ) ) {
    check_admin_referer( 'vm_master_nonce', '_vm_master_nonce' );
    $act = sanitize_key( $_POST['vm_master_action'] );

    if ( 'add' === $act ) {
        $res = VM_Master::add_bureau( wp_unslash( $_POST['bureau_name'] ?? '' ) );
        $notice = ( true === $res )
            ? array( 'success', '運輸支局を追加しました。' )
            : array( 'error', $res );
    } elseif ( 'delete' === $act ) {
        $res = VM_Master::delete_bureau( wp_unslash( $_POST['bureau_name'] ?? '' ) );
        $notice = ( true === $res )
            ? array( 'success', '運輸支局を削除しました。' )
            : array( 'error', $res );
    } elseif ( 'move' === $act ) {
        $list = VM_Master::get_bureaus();
        $name = wp_unslash( $_POST['bureau_name'] ?? '' );
        $idx  = array_search( $name, $list, true );
        $dir  = ( 'up' === ( $_POST['dir'] ?? '' ) ) ? -1 : 1;
        $to   = ( false === $idx ) ? false : $idx + $dir;
        if ( false !== $to && isset( $list[ $to ] ) ) {
            $tmp          = $list[ $idx ];
            $list[ $idx ] = $list[ $to ];
            $list[ $to ]  = $tmp;
            VM_Master::reorder_bureaus( $list );
        }
    }
}

$bureaus = VM_Master::get_bureaus();
$usage   = VM_Master::bureau_usage_counts();
$last    = count( $bureaus ) - 1;
?>

<div class="vm-wrap">
    <div class="vm-page-header">
        <h1 class="vm-page-title">
            <span class="dashicons dashicons-admin-generic"></span>
            マスタ管理
        </h1>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=vehicle-manager' ) ); ?>"
           class="vm-back-link">← 車両一覧に戻る</a>
    </div>

    <?php if ( $notice ) : ?>
        <div class="notice notice-<?php echo esc_attr( $notice[0] ); ?> is-dismissible">
            <p><?php echo esc_html( $notice[1] ); ?></p>
        </div>
    <?php endif; ?>

    <div class="vm-card">
        <div class="vm-card-title">
            <span class="dashicons dashicons-location"></span> 運輸支局マスタ
        </div>

        <form method="post" style="margin-bottom:16px;">
            <?php wp_nonce_field( 'vm_master_nonce', '_vm_master_nonce' ); ?>
            <input type="hidden" name="vm_master_action" value="add">
            <input type="text" name="bureau_name" class="vm-input" maxlength="50"
                   placeholder="例: 宮城" required>
            <button type="submit" class="vm-btn vm-btn-primary">追加</button>
        </form>

        <table class="widefat striped" style="max-width:560px;">
            <thead>
                <tr><th>運輸支局</th><th style="width:90px;">使用車両数</th><th style="width:200px;">操作</th></tr>
            </thead>
            <tbody>
            <?php foreach ( $bureaus as $i => $b ) :
                $cnt = $usage[ $b ] ?? 0; ?>
                <tr>
                    <td><?php echo esc_html( $b ); ?></td>
                    <td><?php echo esc_html( $cnt ); ?> 件</td>
                    <td>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field( 'vm_master_nonce', '_vm_master_nonce' ); ?>
                            <input type="hidden" name="vm_master_action" value="move">
                            <input type="hidden" name="bureau_name" value="<?php echo esc_attr( $b ); ?>">
                            <button type="submit" name="dir" value="up" class="button" <?php disabled( 0 === $i ); ?>>↑</button>
                            <button type="submit" name="dir" value="down" class="button" <?php disabled( $last === $i ); ?>>↓</button>
                        </form>
                        <form method="post" style="display:inline;"
                              onsubmit="return confirm('「<?php echo esc_js( $b ); ?>」を削除しますか？');">
                            <?php wp_nonce_field( 'vm_master_nonce', '_vm_master_nonce' ); ?>
                            <input type="hidden" name="vm_master_action" value="delete">
                            <input type="hidden" name="bureau_name" value="<?php echo esc_attr( $b ); ?>">
                            <button type="submit" class="button" <?php disabled( $cnt > 0 ); ?>
                                    title="<?php echo $cnt > 0 ? '使用中のため削除できません' : ''; ?>">削除</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ( ! $bureaus ) : ?>
                <tr><td colspan="3">運輸支局が登録されていません。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <p class="description">車両登録フォームの「運輸支局」の選択肢に反映されます。使用中の運輸支局は削除できません。</p>
    </div>
</div>
