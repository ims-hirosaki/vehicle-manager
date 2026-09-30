<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * マスタ管理（運輸支局）
 * wp_options の vm_transport_bureaus に配列で保持する
 */
class VM_Master {

    const OPTION_BUREAUS = 'vm_transport_bureaus';

    public static function default_bureaus() {
        return array( '青森', '熊本' );
    }

    /** 運輸支局マスタ一覧 */
    public static function get_bureaus() {
        $list = get_option( self::OPTION_BUREAUS, null );
        if ( ! is_array( $list ) ) {
            return self::default_bureaus();
        }
        return array_values( $list );
    }

    /** 運輸支局マスタ初期化（未登録の場合のみ） */
    public static function init_defaults() {
        if ( false === get_option( self::OPTION_BUREAUS, false ) ) {
            add_option( self::OPTION_BUREAUS, self::default_bureaus() );
        }
    }

    /** 運輸支局ごとの登録車両数 */
    public static function bureau_usage_counts() {
        global $wpdb;
        $table = $wpdb->prefix . VM_TABLE;
        $rows  = $wpdb->get_results( "SELECT transport_bureau, COUNT(*) AS cnt FROM {$table} GROUP BY transport_bureau" );
        $map   = array();
        foreach ( (array) $rows as $r ) {
            $map[ $r->transport_bureau ] = (int) $r->cnt;
        }
        return $map;
    }

    /** @return true|string  成功時 true、失敗時エラーメッセージ */
    public static function add_bureau( $name ) {
        $name = trim( sanitize_text_field( (string) $name ) );
        if ( '' === $name ) {
            return '運輸支局名を入力してください。';
        }
        if ( mb_strlen( $name ) > 50 ) {
            return '運輸支局名は50文字以内で入力してください。';
        }
        $list = self::get_bureaus();
        if ( in_array( $name, $list, true ) ) {
            return '「' . $name . '」はすでに登録されています。';
        }
        $list[] = $name;
        update_option( self::OPTION_BUREAUS, $list );
        return true;
    }

    /** @return true|string */
    public static function delete_bureau( $name ) {
        $name  = (string) $name;
        $usage = self::bureau_usage_counts();
        if ( ! empty( $usage[ $name ] ) ) {
            return '「' . $name . '」は ' . $usage[ $name ] . ' 件の車両で使用されているため削除できません。';
        }
        $list = array_values( array_diff( self::get_bureaus(), array( $name ) ) );
        update_option( self::OPTION_BUREAUS, $list );
        return true;
    }

    /** 並び順の保存（表示順にマスタ名を渡す） */
    public static function reorder_bureaus( $names ) {
        $current = self::get_bureaus();
        $ordered = array();
        foreach ( (array) $names as $n ) {
            $n = (string) wp_unslash( $n );
            if ( in_array( $n, $current, true ) && ! in_array( $n, $ordered, true ) ) {
                $ordered[] = $n;
            }
        }
        // 渡されなかった既存項目は末尾に残す
        foreach ( $current as $n ) {
            if ( ! in_array( $n, $ordered, true ) ) {
                $ordered[] = $n;
            }
        }
        update_option( self::OPTION_BUREAUS, $ordered );
    }
}
