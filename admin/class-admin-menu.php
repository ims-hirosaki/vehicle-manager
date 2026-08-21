<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Admin_Menu {

    public static function init() {
        add_action( 'admin_menu',            array( __CLASS__, 'register_menus' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

        // AJAX フック
        add_action( 'wp_ajax_vm_save',       array( 'VM_Vehicle', 'ajax_save' ) );
        add_action( 'wp_ajax_vm_delete',     array( 'VM_Vehicle', 'ajax_delete' ) );
        add_action( 'wp_ajax_vm_csv_import', array( 'VM_Vehicle', 'ajax_csv_import' ) );
    }

    public static function register_menus() {
        add_menu_page(
            '車両管理',
            '車両管理',
            'access_custom_plugins',
            'vehicle-manager',
            array( __CLASS__, 'render_list' ),
            'dashicons-car',
            28
        );
        add_submenu_page(
            'vehicle-manager',
            '車両一覧',
            '車両一覧',
            'access_custom_plugins',
            'vehicle-manager',
            array( __CLASS__, 'render_list' )
        );
        add_submenu_page(
            'vehicle-manager',
            '車両登録',
            '車両登録',
            'access_custom_plugins',
            'vm-vehicle-form',
            array( __CLASS__, 'render_form' )
        );
        add_submenu_page(
            'vehicle-manager',
            'CSV一括登録',
            'CSV一括登録',
            'access_custom_plugins',
            'vm-vehicle-csv',
            array( __CLASS__, 'render_csv' )
        );
    }

    public static function enqueue_assets( $hook ) {
        // $hook はサブメニューで親メニュー名（日本語）が入り不安定なため
        // 既存プラグインと同様に $_GET['page'] で判定する
        $page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
        $vm_pages = array(
            'vehicle-manager',
            'vm-vehicle-form',
            'vm-vehicle-csv',
        );
        if ( ! in_array( $page, $vm_pages, true ) ) return;

        // 既存プラグインと共通の employee-manager CSS を読み込む
        $emp_css_path = WP_PLUGIN_DIR . '/employee-manager/admin/assets/admin.css';
        if ( file_exists( $emp_css_path ) ) {
            wp_enqueue_style(
                'employee-manager-admin',
                plugins_url( 'employee-manager/admin/assets/admin.css' ),
                array(),
                VM_VERSION
            );
        }

        // 車両管理固有 CSS
        wp_enqueue_style(
            'vm-admin',
            VM_PLUGIN_URL . 'admin/assets/admin.css',
            array(),
            VM_VERSION
        );

        // JS
        wp_enqueue_script(
            'vm-admin',
            VM_PLUGIN_URL . 'admin/assets/admin.js',
            array( 'jquery' ),
            VM_VERSION,
            true
        );

        // タグデータを JS に渡す
        $tag_json = get_option( 'vm_tag_data', '{}' );
        $tags     = json_decode( $tag_json, true ) ?: array();

        wp_localize_script( 'vm-admin', 'vmData', array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'formNonce'     => wp_create_nonce( 'vm_form_nonce' ),
            'listNonce'     => wp_create_nonce( 'vm_list_nonce' ),
            'csvNonce'      => wp_create_nonce( 'vm_csv_nonce' ),
            'tags'          => $tags,
            'listUrl'       => admin_url( 'admin.php?page=vehicle-manager' ),
            'formUrl'       => admin_url( 'admin.php?page=vm-vehicle-form' ),
        ) );
    }

    public static function render_list() {
        if ( ! current_user_can( 'access_custom_plugins' ) ) wp_die( '権限がありません。', '', array( 'response' => 403 ) );
        require VM_PLUGIN_DIR . 'admin/views/vehicle-list.php';
    }
    public static function render_form() {
        if ( ! current_user_can( 'access_custom_plugins' ) ) wp_die( '権限がありません。', '', array( 'response' => 403 ) );
        require VM_PLUGIN_DIR . 'admin/views/vehicle-form.php';
    }
    public static function render_csv() {
        if ( ! current_user_can( 'access_custom_plugins' ) ) wp_die( '権限がありません。', '', array( 'response' => 403 ) );
        require VM_PLUGIN_DIR . 'admin/views/vehicle-csv.php';
    }
}

VM_Admin_Menu::init();
