<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VM_Vehicle {

    // ── 和暦 → DATE 変換 ─────────────────────────────────────
    public static function wareki_to_date( $str ) {
        if ( ! $str ) return null;
        $str = trim( $str );

        // YYYY-MM-DD はそのまま
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $str ) ) {
            return $str;
        }
        // 令和
        if ( preg_match( '/令和\s*(\d+)\s*年\s*(\d+)\s*月\s*(\d+)\s*日/', $str, $m ) ) {
            return sprintf( '%04d-%02d-%02d', 2018 + (int) $m[1], (int) $m[2], (int) $m[3] );
        }
        // 平成
        if ( preg_match( '/平成\s*(\d+)\s*年\s*(\d+)\s*月\s*(\d+)\s*日/', $str, $m ) ) {
            return sprintf( '%04d-%02d-%02d', 1988 + (int) $m[1], (int) $m[2], (int) $m[3] );
        }
        // 昭和
        if ( preg_match( '/昭和\s*(\d+)\s*年\s*(\d+)\s*月\s*(\d+)\s*日/', $str, $m ) ) {
            return sprintf( '%04d-%02d-%02d', 1925 + (int) $m[1], (int) $m[2], (int) $m[3] );
        }
        return null;
    }

    // ── 和暦 → YYYY-MM 変換（初度登録年月用） ─────────────────
    public static function wareki_to_ym( $str ) {
        if ( ! $str ) return null;
        $str = trim( $str );

        if ( preg_match( '/^\d{4}-\d{2}$/', $str ) ) return $str;

        if ( preg_match( '/令和\s*(\d+)\s*年\s*(\d+)\s*月/', $str, $m ) ) {
            return sprintf( '%04d-%02d', 2018 + (int) $m[1], (int) $m[2] );
        }
        if ( preg_match( '/平成\s*(\d+)\s*年\s*(\d+)\s*月/', $str, $m ) ) {
            return sprintf( '%04d-%02d', 1988 + (int) $m[1], (int) $m[2] );
        }
        if ( preg_match( '/昭和\s*(\d+)\s*年\s*(\d+)\s*月/', $str, $m ) ) {
            return sprintf( '%04d-%02d', 1925 + (int) $m[1], (int) $m[2] );
        }
        return null;
    }

    // ── DATE → 和暦 変換（表示用） ───────────────────────────
    public static function date_to_wareki( $date_str ) {
        if ( ! $date_str || $date_str === '0000-00-00' ) return '';
        $ts = strtotime( $date_str );
        if ( ! $ts ) return esc_html( $date_str );

        $year  = (int) date( 'Y', $ts );
        $month = (int) date( 'n', $ts );
        $day   = (int) date( 'j', $ts );

        if ( $ts >= strtotime( '2019-05-01' ) ) {
            return sprintf( '令和%d年%d月%d日', $year - 2018, $month, $day );
        } elseif ( $ts >= strtotime( '1989-01-08' ) ) {
            return sprintf( '平成%d年%d月%d日', $year - 1988, $month, $day );
        } else {
            return sprintf( '昭和%d年%d月%d日', $year - 1925, $month, $day );
        }
    }

    // ── YYYY-MM → 和暦 変換（初度登録年月表示用） ─────────────
    public static function ym_to_wareki( $ym_str ) {
        if ( ! $ym_str ) return '';
        if ( ! preg_match( '/^(\d{4})-(\d{2})$/', $ym_str, $m ) ) return esc_html( $ym_str );
        $year  = (int) $m[1];
        $month = (int) $m[2];

        if ( $year > 2019 || ( $year === 2019 && $month >= 5 ) ) {
            return sprintf( '令和%d年%d月', $year - 2018, $month );
        } elseif ( $year >= 1989 ) {
            return sprintf( '平成%d年%d月', $year - 1988, $month );
        } else {
            return sprintf( '昭和%d年%d月', $year - 1925, $month );
        }
    }

    // ── バリデーション ────────────────────────────────────────
    public static function validate( $data ) {
        $errors = array();

        if ( empty( $data['transport_bureau'] ) )      $errors[] = '運輸支局は必須です。';
        if ( empty( $data['classification_number'] ) ) $errors[] = '分類番号は必須です。';
        if ( empty( $data['purpose_category'] ) )      $errors[] = '用途区別は必須です。';
        if ( empty( $data['serial_number'] ) )         $errors[] = '一連指定番号は必須です。';
        if ( empty( $data['chassis_number'] ) ) {
            $errors[] = '車台番号は必須です。';
        } elseif ( ! preg_match( '/^[A-Za-z0-9\-_]+$/', $data['chassis_number'] ) ) {
            $errors[] = '車台番号は半角英数字・ハイフン・アンダースコアのみ使用できます。';
        }

        return $errors;
    }

    // ── POST データを DB 用配列に整形 ─────────────────────────
    public static function sanitize_post( $post ) {
        return array(
            'transport_bureau'        => sanitize_text_field( $post['transport_bureau']        ?? '' ),
            'classification_number'   => sanitize_text_field( $post['classification_number']   ?? '' ),
            'purpose_category'        => sanitize_text_field( $post['purpose_category']        ?? '' ),
            'serial_number'           => sanitize_text_field( $post['serial_number']           ?? '' ),
            'chassis_number'          => sanitize_text_field( $post['chassis_number']          ?? '' ),
            'registration_date'       => self::wareki_to_date( $post['registration_date']      ?? '' ),
            'initial_registration_ym' => self::wareki_to_ym( $post['initial_registration_ym'] ?? '' ),
            'expiry_date'             => self::wareki_to_date( $post['expiry_date']            ?? '' ),
            'vehicle_name'            => sanitize_text_field( $post['vehicle_name']            ?? '' ),
            'model'                   => sanitize_text_field( $post['model']                   ?? '' ),
            'engine_model'            => sanitize_text_field( $post['engine_model']            ?? '' ),
            'vehicle_type'            => sanitize_text_field( $post['vehicle_type']            ?? '普通' ),
            'usage_type'              => sanitize_text_field( $post['usage_type']              ?? '' ),
            'ownership_type'          => sanitize_text_field( $post['ownership_type']          ?? '事業用' ),
            'body_shape'              => sanitize_text_field( $post['body_shape']              ?? '' ),
            'passenger_capacity'      => intval( $post['passenger_capacity']                   ?? 2 ),
            'max_load_kg'             => self::nullint( $post['max_load_kg']                   ?? '' ),
            'vehicle_weight_kg'       => self::nullint( $post['vehicle_weight_kg']             ?? '' ),
            'gross_weight_kg'         => self::nullint( $post['gross_weight_kg']               ?? '' ),
            'length_cm'               => self::nullint( $post['length_cm']                     ?? '' ),
            'width_cm'                => self::nullint( $post['width_cm']                      ?? '' ),
            'height_cm'               => self::nullint( $post['height_cm']                     ?? '' ),
            'front_front_axle_kg'     => self::nullint( $post['front_front_axle_kg']           ?? '' ),
            'front_rear_axle_kg'      => self::nullint( $post['front_rear_axle_kg']            ?? '' ),
            'rear_front_axle_kg'      => self::nullint( $post['rear_front_axle_kg']            ?? '' ),
            'rear_rear_axle_kg'       => self::nullint( $post['rear_rear_axle_kg']             ?? '' ),
            'displacement'            => self::nullfloat( $post['displacement']                ?? '' ),
            'fuel_type'               => sanitize_text_field( $post['fuel_type']               ?? '軽油' ),
            'model_designation_number'=> sanitize_text_field( $post['model_designation_number']?? '' ),
            'category_class_number'   => sanitize_text_field( $post['category_class_number']   ?? '' ),
            'original_name'           => sanitize_text_field( $post['original_name']           ?? '' ),
            'inspection_category'     => sanitize_text_field( $post['inspection_category']     ?? '' ),
            'brake'                   => sanitize_text_field( $post['brake']                   ?? '' ),
            'leaf_spring'             => sanitize_text_field( $post['leaf_spring']             ?? '' ),
        );
    }

    private static function nullint( $val ) {
        $val = trim( (string) $val );
        return $val !== '' ? intval( $val ) : null;
    }
    private static function nullfloat( $val ) {
        $val = trim( (string) $val );
        return $val !== '' ? floatval( $val ) : null;
    }

    // ── 登録 ─────────────────────────────────────────────────
    public static function insert( $data ) {
        global $wpdb;
        $table  = $wpdb->prefix . VM_TABLE;
        $result = $wpdb->insert( $table, $data );
        if ( $result === false ) return new WP_Error( 'db_error', $wpdb->last_error );
        return $wpdb->insert_id;
    }

    // ── 更新 ─────────────────────────────────────────────────
    public static function update( $id, $data ) {
        global $wpdb;
        $table  = $wpdb->prefix . VM_TABLE;
        $result = $wpdb->update( $table, $data, array( 'id' => $id ) );
        if ( $result === false ) return new WP_Error( 'db_error', $wpdb->last_error );
        return true;
    }

    // ── 1件取得 ───────────────────────────────────────────────
    public static function get( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . VM_TABLE;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
    }

    // ── 削除 ─────────────────────────────────────────────────
    public static function delete( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . VM_TABLE;
        return $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
    }

    // ── 一覧取得 ──────────────────────────────────────────────
    public static function get_list( $args = array() ) {
        global $wpdb;
        $table = $wpdb->prefix . VM_TABLE;

        $defaults = array(
            'transport_bureau' => '',
            'orderby'          => 'serial_number',
            'order'            => 'ASC',
            'limit'            => 20,
            'offset'           => 0,
        );
        $args = wp_parse_args( $args, $defaults );

        $where  = '1=1';
        $params = array();

        if ( ! empty( $args['transport_bureau'] ) ) {
            $where   .= ' AND transport_bureau = %s';
            $params[] = $args['transport_bureau'];
        }

        $allowed_orderby = array( 'serial_number', 'registration_date', 'expiry_date', 'transport_bureau', 'id' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'serial_number';
        $order   = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $params[] = (int) $args['limit'];
        $params[] = (int) $args['offset'];

        if ( $params ) {
            return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
        }
        return $wpdb->get_results( $sql );
    }

    // ── 件数取得 ──────────────────────────────────────────────
    public static function count( $args = array() ) {
        global $wpdb;
        $table = $wpdb->prefix . VM_TABLE;

        $where  = '1=1';
        $params = array();

        if ( ! empty( $args['transport_bureau'] ) ) {
            $where   .= ' AND transport_bureau = %s';
            $params[] = $args['transport_bureau'];
        }

        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        if ( $params ) {
            return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
        }
        return (int) $wpdb->get_var( $sql );
    }

    // ── タグデータ更新（CSV取込後に呼び出す） ─────────────────
    public static function rebuild_tags() {
        global $wpdb;
        $table = $wpdb->prefix . VM_TABLE;

        $tag_columns = array(
            'vehicle_name',
            'model',
            'engine_model',
            'leaf_spring',
        );

        $tags = array();
        foreach ( $tag_columns as $col ) {
            $results = $wpdb->get_col(
                "SELECT {$col}, COUNT(*) as cnt
                 FROM {$table}
                 WHERE {$col} IS NOT NULL AND {$col} != ''
                 GROUP BY {$col}
                 HAVING cnt >= 3
                 ORDER BY cnt DESC"
            );
            $tags[ $col ] = $results;
        }

        // 車台番号プレフィックス（ハイフン前の部分で3回以上）
        $chassis_rows = $wpdb->get_col( "SELECT chassis_number FROM {$table} WHERE chassis_number != ''" );
        $prefix_cnt   = array();
        foreach ( $chassis_rows as $c ) {
            if ( preg_match( '/^([A-Z0-9]+)-/', $c, $m ) ) {
                $prefix_cnt[ $m[1] . '-' ] = ( $prefix_cnt[ $m[1] . '-' ] ?? 0 ) + 1;
            }
        }
        $chassis_tags = array();
        foreach ( $prefix_cnt as $prefix => $cnt ) {
            if ( $cnt >= 3 ) $chassis_tags[] = $prefix;
        }
        arsort( $prefix_cnt );
        $tags['chassis_number'] = $chassis_tags;

        update_option( 'vm_tag_data', wp_json_encode( $tags ) );
        return $tags;
    }

    // ── AJAX: 保存（新規・更新共通） ──────────────────────────
    public static function ajax_save() {
        check_ajax_referer( 'vm_form_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_custom_plugins' ) ) {
            wp_send_json_error( '権限がありません。' );
        }

        $data   = self::sanitize_post( $_POST );
        $errors = self::validate( $data );
        if ( $errors ) {
            wp_send_json_error( implode( '<br>', $errors ) );
        }

        $id = intval( $_POST['id'] ?? 0 );

        if ( $id > 0 ) {
            $result = self::update( $id, $data );
        } else {
            $result = self::insert( $data );
        }

        if ( is_wp_error( $result ) ) {
            // serial_number の重複チェック
            if ( strpos( $result->get_error_message(), 'Duplicate' ) !== false ) {
                wp_send_json_error( '一連指定番号「' . esc_html( $data['serial_number'] ) . '」はすでに登録されています。' );
            }
            wp_send_json_error( 'DB エラー: ' . $result->get_error_message() );
        }

        wp_send_json_success( array( 'id' => is_int( $result ) ? $result : $id ) );
    }

    // ── AJAX: 削除 ────────────────────────────────────────────
    public static function ajax_delete() {
        check_ajax_referer( 'vm_list_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_custom_plugins' ) ) {
            wp_send_json_error( '権限がありません。' );
        }

        $id = intval( $_POST['id'] ?? 0 );
        if ( $id <= 0 ) wp_send_json_error( '無効なID です。' );

        $result = self::delete( $id );
        if ( $result === false ) {
            wp_send_json_error( '削除に失敗しました。' );
        }
        wp_send_json_success();
    }

    // ── CSV 一括インポート AJAX ───────────────────────────────
    public static function ajax_csv_import() {
        check_ajax_referer( 'vm_csv_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_custom_plugins' ) ) {
            wp_send_json_error( '権限がありません。' );
        }

        if ( empty( $_FILES['csv_file'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( 'ファイルのアップロードに失敗しました。' );
        }

        $tmp  = $_FILES['csv_file']['tmp_name'];
        $raw  = file_get_contents( $tmp );

        // エンコーディング自動判定（CP932 / UTF-8 BOM / UTF-8）
        if ( substr( $raw, 0, 3 ) === "\xEF\xBB\xBF" ) {
            $content = substr( $raw, 3 ); // UTF-8 BOM 除去
        } elseif ( mb_detect_encoding( $raw, 'UTF-8', true ) === false ) {
            $content = mb_convert_encoding( $raw, 'UTF-8', 'SJIS-win' );
        } else {
            $content = $raw;
        }

        $lines = preg_split( '/\r\n|\r|\n/', trim( $content ) );
        if ( empty( $lines ) ) wp_send_json_error( 'ファイルが空です。' );

        // ヘッダー行を取得
        $header_line = array_shift( $lines );
        $headers     = str_getcsv( $header_line );
        $header_map  = array_flip( $headers );

        // CSV列名 → DBカラム名 マッピング
        $col_map = array(
            '運輸支局'                       => 'transport_bureau',
            '分類番号'                       => 'classification_number',
            '用途区別'                       => 'purpose_category',
            '一連指定番号'                   => 'serial_number',
            '車台番号'                       => 'chassis_number',
            '登録年月日'                     => 'registration_date',
            '初度登録年月'                   => 'initial_registration_ym',
            '有効期限満了日'                 => 'expiry_date',
            '車名'                           => 'vehicle_name',
            '型式'                           => 'model',
            '原動機の型式'                   => 'engine_model',
            '自動車の種別'                   => 'vehicle_type',
            '用途'                           => 'usage_type',
            '自家用・事業用の別'             => 'ownership_type',
            '車体の形状'                     => 'body_shape',
            '乗車定員'                       => 'passenger_capacity',
            '最大積載量（kg）'               => 'max_load_kg',
            '車両重量（kg）'                 => 'vehicle_weight_kg',
            '車両総重量（kg）'               => 'gross_weight_kg',
            '長さ（cm）'                     => 'length_cm',
            '幅（cm）'                       => 'width_cm',
            '高さ(cm）'                      => 'height_cm',
            '前前軸重（kg）'                 => 'front_front_axle_kg',
            '前後軸重（kg）'                 => 'front_rear_axle_kg',
            '後前軸重（kg）'                 => 'rear_front_axle_kg',
            '後後軸重（kg）'                 => 'rear_rear_axle_kg',
            '総排気量又は定格出力（kw L）'   => 'displacement',
            '燃料の種類'                     => 'fuel_type',
            '型式指定番号'                   => 'model_designation_number',
            '類別区分番号'                   => 'category_class_number',
            '原本名称'                       => 'original_name',
            '点検分類'                       => 'inspection_category',
            'ブレーキ'                       => 'brake',
            'リーフスプリング'               => 'leaf_spring',
        );

        $mode        = sanitize_text_field( $_POST['duplicate_mode'] ?? 'skip' ); // skip | overwrite
        $preview     = (bool) ( $_POST['preview'] ?? false );
        $preview_max = 10;

        $inserted   = 0;
        $skipped    = 0;
        $updated    = 0;
        $errors_log = array();
        $preview_rows = array();

        global $wpdb;
        $table = $wpdb->prefix . VM_TABLE;

        foreach ( $lines as $row_num_offset => $line ) {
            $line = trim( $line );
            if ( $line === '' ) continue; // 空行スキップ

            $row    = str_getcsv( $line );
            $row_no = $row_num_offset + 2; // 1行目はヘッダー

            // 車台番号が空ならスキップ
            $chassis_idx = $header_map['車台番号'] ?? -1;
            if ( $chassis_idx < 0 || trim( $row[ $chassis_idx ] ?? '' ) === '' ) {
                $skipped++;
                continue;
            }

            // CSV行をDB配列に変換
            $data = array();
            foreach ( $col_map as $csv_col => $db_col ) {
                $idx = $header_map[ $csv_col ] ?? -1;
                $val = $idx >= 0 ? trim( $row[ $idx ] ?? '' ) : '';

                if ( in_array( $db_col, array( 'registration_date', 'expiry_date' ), true ) ) {
                    $val = self::wareki_to_date( $val );
                } elseif ( $db_col === 'initial_registration_ym' ) {
                    $val = self::wareki_to_ym( $val );
                } elseif ( in_array( $db_col, array( 'max_load_kg', 'vehicle_weight_kg', 'gross_weight_kg',
                    'length_cm', 'width_cm', 'height_cm',
                    'front_front_axle_kg', 'front_rear_axle_kg',
                    'rear_front_axle_kg', 'rear_rear_axle_kg', 'passenger_capacity' ), true ) ) {
                    $val = $val !== '' ? intval( $val ) : null;
                } elseif ( $db_col === 'displacement' ) {
                    $val = $val !== '' ? floatval( $val ) : null;
                }

                $data[ $db_col ] = $val !== '' ? $val : null;
            }

            // 必須チェック
            if ( empty( $data['serial_number'] ) ) {
                $errors_log[] = array( 'row' => $row_no, 'msg' => '一連指定番号が空です。' );
                continue;
            }

            // 車台番号半角チェック
            if ( ! empty( $data['chassis_number'] ) && ! preg_match( '/^[A-Za-z0-9\-_]+$/', $data['chassis_number'] ) ) {
                $errors_log[] = array( 'row' => $row_no, 'msg' => '車台番号に全角文字が含まれています: ' . esc_html( $data['chassis_number'] ) );
                continue;
            }

            if ( $preview ) {
                if ( count( $preview_rows ) < $preview_max ) {
                    $preview_rows[] = $data;
                }
                $inserted++; // プレビュー時は件数だけカウント
                continue;
            }

            // 重複確認
            $existing_id = $wpdb->get_var(
                $wpdb->prepare( "SELECT id FROM {$table} WHERE serial_number = %s", $data['serial_number'] )
            );

            if ( $existing_id ) {
                if ( $mode === 'overwrite' ) {
                    $r = $wpdb->update( $table, $data, array( 'id' => (int) $existing_id ) );
                    if ( $r !== false ) {
                        $updated++;
                    } else {
                        $errors_log[] = array( 'row' => $row_no, 'msg' => 'UPDATE 失敗: ' . $wpdb->last_error );
                    }
                } else {
                    $skipped++;
                }
            } else {
                $r = $wpdb->insert( $table, $data );
                if ( $r ) {
                    $inserted++;
                } else {
                    $errors_log[] = array( 'row' => $row_no, 'msg' => 'INSERT 失敗: ' . $wpdb->last_error );
                }
            }
        }

        if ( ! $preview ) {
            // タグを再計算
            self::rebuild_tags();
        }

        wp_send_json_success( array(
            'inserted'     => $inserted,
            'updated'      => $updated,
            'skipped'      => $skipped,
            'errors'       => $errors_log,
            'preview_rows' => $preview_rows,
            'preview'      => $preview,
        ) );
    }
}
