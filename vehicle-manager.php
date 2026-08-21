<?php
/**
 * Plugin Name: 車両管理
 * Plugin URI:  https://tanpopo-transport.local
 * Description: 有限会社たんぽぽ運送 — 車両情報の登録・管理・CSV一括インポート
 * Version:     1.0.0
 * Author:      たんぽぽ運送システム
 * Text Domain: vehicle-manager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── 定数 ─────────────────────────────────────────────
define( 'VM_VERSION',    '1.0.0' );
define( 'VM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VM_TABLE',      'vehicle_manager' ); // プレフィックスは $wpdb->prefix で付与

// ── 依存ファイル読み込み ──────────────────────────────
require_once VM_PLUGIN_DIR . 'includes/class-db-install.php';
require_once VM_PLUGIN_DIR . 'includes/class-vehicle.php';
require_once VM_PLUGIN_DIR . 'admin/class-admin-menu.php';

// ── 有効化 / 無効化フック ─────────────────────────────
register_activation_hook( __FILE__, array( 'VM_DB_Install', 'install' ) );

// =========================================================
//  他プラグインから呼び出せる公開API関数
// =========================================================

/**
 * 登録済み車両の一連指定番号（車番として使用）一覧を取得する
 *
 * @return array  一連指定番号の配列（重複無し・昇順）
 *
 * @example
 *   $numbers = vm_get_vehicle_numbers();
 */
function vm_get_vehicle_numbers() {
    global $wpdb;
    $table = $wpdb->prefix . VM_TABLE;
    $rows  = $wpdb->get_col( "SELECT DISTINCT serial_number FROM {$table} WHERE serial_number != '' ORDER BY serial_number ASC" );
    return is_array( $rows ) ? $rows : array();
}

/**
 * 指定した一連指定番号（車番）が登録されているか
 *
 * @param string $serial_number
 * @return bool
 *
 * @example
 *   if ( vm_vehicle_exists( '1234' ) ) { ... }
 */
function vm_vehicle_exists( $serial_number ) {
    global $wpdb;
    $serial_number = sanitize_text_field( (string) $serial_number );
    if ( '' === $serial_number ) {
        return false;
    }
    $table = $wpdb->prefix . VM_TABLE;
    $id    = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE serial_number = %s", $serial_number ) );
    return null !== $id;
}
