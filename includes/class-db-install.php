<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_DB_Install {

    const DB_VERSION = '1.0.0';

    public static function install() {
        global $wpdb;
        $table   = $wpdb->prefix . VM_TABLE;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id                       INT          NOT NULL AUTO_INCREMENT,
            transport_bureau         VARCHAR(50)  NOT NULL                        COMMENT '運輸支局',
            classification_number    VARCHAR(10)  NOT NULL                        COMMENT '分類番号',
            purpose_category         VARCHAR(10)  NOT NULL                        COMMENT '用途区別',
            serial_number            VARCHAR(20)  NOT NULL                        COMMENT '一連指定番号（外部キー用）',
            chassis_number           VARCHAR(100) NOT NULL                        COMMENT '車台番号（半角のみ）',
            registration_date        DATE         DEFAULT NULL                    COMMENT '登録年月日',
            initial_registration_ym  VARCHAR(20)  DEFAULT NULL                    COMMENT '初度登録年月（YYYY-MM）',
            expiry_date              DATE         DEFAULT NULL                    COMMENT '有効期限満了日',
            vehicle_name             VARCHAR(100) DEFAULT NULL                    COMMENT '車名',
            model                    VARCHAR(100) DEFAULT NULL                    COMMENT '型式',
            engine_model             VARCHAR(50)  DEFAULT NULL                    COMMENT '原動機の型式',
            vehicle_type             VARCHAR(20)  DEFAULT '普通'                  COMMENT '自動車の種別',
            usage_type               VARCHAR(20)  DEFAULT NULL                    COMMENT '用途',
            ownership_type           VARCHAR(20)  DEFAULT '事業用'                COMMENT '自家用・事業用の別',
            body_shape               VARCHAR(50)  DEFAULT NULL                    COMMENT '車体の形状',
            passenger_capacity       TINYINT      DEFAULT 2                       COMMENT '乗車定員',
            max_load_kg              INT          DEFAULT NULL                    COMMENT '最大積載量（kg）',
            vehicle_weight_kg        INT          DEFAULT NULL                    COMMENT '車両重量（kg）',
            gross_weight_kg          INT          DEFAULT NULL                    COMMENT '車両総重量（kg）',
            length_cm                INT          DEFAULT NULL                    COMMENT '長さ（cm）',
            width_cm                 INT          DEFAULT NULL                    COMMENT '幅（cm）',
            height_cm                INT          DEFAULT NULL                    COMMENT '高さ（cm）',
            front_front_axle_kg      INT          DEFAULT NULL                    COMMENT '前前軸重（kg）',
            front_rear_axle_kg       INT          DEFAULT NULL                    COMMENT '前後軸重（kg）',
            rear_front_axle_kg       INT          DEFAULT NULL                    COMMENT '後前軸重（kg）',
            rear_rear_axle_kg        INT          DEFAULT NULL                    COMMENT '後後軸重（kg）',
            displacement             DECIMAL(6,2) DEFAULT NULL                    COMMENT '総排気量又は定格出力（kw L）',
            fuel_type                VARCHAR(20)  DEFAULT '軽油'                  COMMENT '燃料の種類',
            model_designation_number VARCHAR(50)  DEFAULT NULL                    COMMENT '型式指定番号',
            category_class_number    VARCHAR(50)  DEFAULT NULL                    COMMENT '類別区分番号',
            original_name            VARCHAR(100) DEFAULT NULL                    COMMENT '原本名称',
            inspection_category      VARCHAR(50)  DEFAULT NULL                    COMMENT '点検分類',
            brake                    VARCHAR(20)  DEFAULT NULL                    COMMENT 'ブレーキ',
            leaf_spring              VARCHAR(50)  DEFAULT NULL                    COMMENT 'リーフスプリング',
            created_at               DATETIME     DEFAULT CURRENT_TIMESTAMP,
            updated_at               DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY   idx_serial_number (serial_number),
            KEY          idx_transport (transport_bureau),
            KEY          idx_expiry (expiry_date),
            KEY          idx_chassis (chassis_number)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( 'vm_db_version', self::DB_VERSION );

        // タグ初期データ投入（まだ存在しない場合のみ）
        if ( ! get_option( 'vm_tag_data' ) ) {
            self::init_default_tags();
        }
    }

    private static function init_default_tags() {
        $default_tags = array(
            'vehicle_name'  => array( '日野', 'UDトラックス', '三菱', 'いすゞ' ),
            'model'         => array( '2PG-CD5CE', '2DG-FR1AHJ', 'QKG-CD5ZE', '2PG-CYY77D', '2PG-FU75HZ', '2DG-FR1AHB', 'QKG-FW1EXEG', '2RG-CD5FE' ),
            'engine_model'  => array( 'GH11', 'A09C', '6R20', '6UZ1', 'E13C' ),
            'chassis_number'=> array( 'FR1AH-', 'CYY77D-', 'FU75HZ-', 'FW1EXE-' ),
            'leaf_spring'   => array( 'フロント板羽' ),
        );
        update_option( 'vm_tag_data', wp_json_encode( $default_tags ) );
    }
}
