<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * マスタ管理（運輸支局・分類番号・用途区別）
 * wp_options に種類ごとの配列で保持する
 */
class VM_Master {

    /**
     * マスタ種類定義
     * key => label / option名 / 車両テーブルのカラム / 初期値 / 入力例 / 最大文字数
     */
    public static function types() {
        return array(
            'bureau' => array(
                'label'       => '運輸支局',
                'option'      => 'vm_transport_bureaus',
                'column'      => 'transport_bureau',
                'defaults'    => array( '青森', '熊本' ),
                'placeholder' => '例: 宮城',
                'max'         => 50,
            ),
            'class_number' => array(
                'label'       => '分類番号',
                'option'      => 'vm_class_numbers',
                'column'      => 'classification_number',
                'defaults'    => array( '130', '131', '830' ),
                'placeholder' => '例: 100',
                'max'         => 10,
            ),
            'purpose' => array(
                'label'       => '用途区別',
                'option'      => 'vm_purpose_categories',
                'column'      => 'purpose_category',
                'defaults'    => array( 'あ', 'い', 'う', 'え', 'か', 'き', 'く', 'け', 'こ', 'を' ),
                'placeholder' => '例: さ',
                'max'         => 10,
            ),
        );
    }

    public static function type( $key ) {
        $types = self::types();
        return $types[ $key ] ?? null;
    }

    /** 指定マスタの一覧 */
    public static function get_items( $key ) {
        $t = self::type( $key );
        if ( ! $t ) return array();
        $list = get_option( $t['option'], null );
        return is_array( $list ) ? array_values( $list ) : $t['defaults'];
    }

    public static function get_bureaus() { return self::get_items( 'bureau' ); }

    /** 初期化（未登録の場合のみ） */
    public static function init_defaults() {
        foreach ( self::types() as $t ) {
            if ( false === get_option( $t['option'], false ) ) {
                add_option( $t['option'], $t['defaults'] );
            }
        }
    }

    /** 項目ごとの登録車両数 */
    public static function usage_counts( $key ) {
        global $wpdb;
        $t = self::type( $key );
        if ( ! $t ) return array();
        $table = $wpdb->prefix . VM_TABLE;
        $col   = $t['column']; // types() の固定値のみ
        $rows  = $wpdb->get_results( "SELECT {$col} AS name, COUNT(*) AS cnt FROM {$table} GROUP BY {$col}" );
        $map   = array();
        foreach ( (array) $rows as $r ) {
            $map[ $r->name ] = (int) $r->cnt;
        }
        return $map;
    }

    /** @return true|string  成功時 true、失敗時エラーメッセージ */
    public static function add_item( $key, $name ) {
        $t = self::type( $key );
        if ( ! $t ) return '不正なマスタ種別です。';
        $name = trim( sanitize_text_field( (string) $name ) );
        if ( '' === $name ) {
            return $t['label'] . 'を入力してください。';
        }
        if ( mb_strlen( $name ) > $t['max'] ) {
            return $t['label'] . 'は' . $t['max'] . '文字以内で入力してください。';
        }
        $list = self::get_items( $key );
        if ( in_array( $name, $list, true ) ) {
            return '「' . $name . '」はすでに登録されています。';
        }
        $list[] = $name;
        update_option( $t['option'], $list );
        return true;
    }

    /** @return true|string */
    public static function delete_item( $key, $name ) {
        $t = self::type( $key );
        if ( ! $t ) return '不正なマスタ種別です。';
        $name  = (string) $name;
        $usage = self::usage_counts( $key );
        if ( ! empty( $usage[ $name ] ) ) {
            return '「' . $name . '」は ' . $usage[ $name ] . ' 件の車両で使用されているため削除できません。';
        }
        $list = array_values( array_diff( self::get_items( $key ), array( $name ) ) );
        update_option( $t['option'], $list );
        return true;
    }

    /** 1つ上/下へ移動 */
    public static function move_item( $key, $name, $dir ) {
        $t = self::type( $key );
        if ( ! $t ) return;
        $list = self::get_items( $key );
        $idx  = array_search( (string) $name, $list, true );
        if ( false === $idx ) return;
        $to = $idx + ( 'up' === $dir ? -1 : 1 );
        if ( ! isset( $list[ $to ] ) ) return;
        $tmp          = $list[ $idx ];
        $list[ $idx ] = $list[ $to ];
        $list[ $to ]  = $tmp;
        update_option( $t['option'], $list );
    }
}
