<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$notice = array();
$types  = VM_Master::types();

// ── POST 処理 ─────────────────────────────────────────
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['vm_master_action'] ) ) {
    check_admin_referer( 'vm_master_nonce', '_vm_master_nonce' );
    $act  = sanitize_key( $_POST['vm_master_action'] );
    $mkey = sanitize_key( $_POST['master_type'] ?? '' );
    $name = wp_unslash( $_POST['item_name'] ?? '' );

    if ( isset( $types[ $mkey ] ) ) {
        $label = $types[ $mkey ]['label'];
        if ( 'add' === $act ) {
            $res    = VM_Master::add_item( $mkey, $name );
            $notice = ( true === $res ) ? array( 'success', $label . 'を追加しました。' ) : array( 'error', $res );
        } elseif ( 'delete' === $act ) {
            $res    = VM_Master::delete_item( $mkey, $name );
            $notice = ( true === $res ) ? array( 'success', $label . 'を削除しました。' ) : array( 'error', $res );
        } elseif ( 'move' === $act ) {
            VM_Master::move_item( $mkey, $name, ( 'up' === ( $_POST['dir'] ?? '' ) ) ? 'up' : 'down' );
        }
    }
}
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

    <?php foreach ( $types as $mkey => $t ) :
        $items = VM_Master::get_items( $mkey );
        $usage = VM_Master::usage_counts( $mkey );
        $last  = count( $items ) - 1; ?>
    <div class="vm-card">
        <div class="vm-card-title">
            <span class="dashicons dashicons-list-view"></span> <?php echo esc_html( $t['label'] ); ?>マスタ
        </div>

        <form method="post" style="margin-bottom:16px;">
            <?php wp_nonce_field( 'vm_master_nonce', '_vm_master_nonce' ); ?>
            <input type="hidden" name="vm_master_action" value="add">
            <input type="hidden" name="master_type" value="<?php echo esc_attr( $mkey ); ?>">
            <input type="text" name="item_name" class="vm-input" maxlength="<?php echo esc_attr( $t['max'] ); ?>"
                   placeholder="<?php echo esc_attr( $t['placeholder'] ); ?>" required>
            <button type="submit" class="vm-btn vm-btn-primary">追加</button>
        </form>

        <table class="widefat striped" style="max-width:560px;">
            <thead>
                <tr><th><?php echo esc_html( $t['label'] ); ?></th><th style="width:90px;">使用車両数</th><th style="width:200px;">操作</th></tr>
            </thead>
            <tbody>
            <?php foreach ( $items as $i => $item ) :
                $cnt = $usage[ $item ] ?? 0; ?>
                <tr>
                    <td><?php echo esc_html( $item ); ?></td>
                    <td><?php echo esc_html( $cnt ); ?> 件</td>
                    <td>
                        <form method="post" style="display:inline;">
                            <?php wp_nonce_field( 'vm_master_nonce', '_vm_master_nonce' ); ?>
                            <input type="hidden" name="vm_master_action" value="move">
                            <input type="hidden" name="master_type" value="<?php echo esc_attr( $mkey ); ?>">
                            <input type="hidden" name="item_name" value="<?php echo esc_attr( $item ); ?>">
                            <button type="submit" name="dir" value="up" class="button" <?php disabled( 0 === $i ); ?>>↑</button>
                            <button type="submit" name="dir" value="down" class="button" <?php disabled( $last === $i ); ?>>↓</button>
                        </form>
                        <form method="post" style="display:inline;"
                              onsubmit="return confirm('「<?php echo esc_js( $item ); ?>」を削除しますか？');">
                            <?php wp_nonce_field( 'vm_master_nonce', '_vm_master_nonce' ); ?>
                            <input type="hidden" name="vm_master_action" value="delete">
                            <input type="hidden" name="master_type" value="<?php echo esc_attr( $mkey ); ?>">
                            <input type="hidden" name="item_name" value="<?php echo esc_attr( $item ); ?>">
                            <button type="submit" class="button" <?php disabled( $cnt > 0 ); ?>
                                    title="<?php echo $cnt > 0 ? '使用中のため削除できません' : ''; ?>">削除</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ( ! $items ) : ?>
                <tr><td colspan="3">登録されていません。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <p class="description">車両登録フォームの「<?php echo esc_html( $t['label'] ); ?>」の選択肢に反映されます。使用中の項目は削除できません。</p>
    </div>
    <?php endforeach; ?>
</div>
