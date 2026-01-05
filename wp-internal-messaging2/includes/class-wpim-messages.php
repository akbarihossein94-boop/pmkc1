<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPIM_Messages {

    public static function register_post_type() {
        $labels = [
            'name'          => 'پیام‌ها',
            'singular_name' => 'پیام',
        ];

        $args = [
            'labels'      => $labels,
            'public'      => false,
            'show_ui'     => false,
            'supports'    => [ 'title', 'editor', 'author' ],
            'has_archive' => false,
        ];

        register_post_type( 'wpim_message', $args );

        $tag_labels = [
            'name'          => 'برچسب‌های پیام',
            'singular_name' => 'برچسب پیام',
        ];

        register_taxonomy(
            'wpim_label',
            'wpim_message',
            [
                'labels'       => $tag_labels,
                'public'       => false,
                'show_ui'      => false,
                'hierarchical' => false,
            ]
        );
    }

    /**
     * تبدیل اعداد انگلیسی به فارسی (عمومی)
     */
    public static function to_persian_digits( $value ) {
        $en = [ '0','1','2','3','4','5','6','7','8','9' ];
        $fa = [ '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' ];
        return str_replace( $en, $fa, (string) $value );
    }

    /**
     * پیش‌نمایش شماره سند سیستمی بعدی (همه اعداد فارسی)
     */
    public static function get_next_system_doc_number_preview( $persian_date_display = null ) {
        $timestamp = current_time( 'timestamp' );

        $today_key  = date_i18n( 'Ymd', $timestamp );
        $option_key = 'wpim_count_' . $today_key;
        $current    = (int) get_option( $option_key, 0 );
        $next       = $current + 1;

        if ( empty( $persian_date_display ) ) {
            if ( function_exists( 'parsidate' ) ) {
                $persian_date_display = parsidate( 'Y-m-d', $timestamp, 'per' );
            } else {
                $persian_date_display = date_i18n( 'Y-m-d', $timestamp );
            }
        }

        $base = 'PMKC-' . $persian_date_display . '-' . $next;

        return self::to_persian_digits( $base );
    }

    /**
     * تخصیص شماره سند سیستمی هنگام ارسال (همه اعداد فارسی)
     */
    public static function assign_system_doc_number_on_send( $persian_date_display = null ) {
        $timestamp = current_time( 'timestamp' );

        $today_key  = date_i18n( 'Ymd', $timestamp );
        $option_key = 'wpim_count_' . $today_key;
        $count      = (int) get_option( $option_key, 0 ) + 1;
        update_option( $option_key, $count );

        if ( empty( $persian_date_display ) ) {
            if ( function_exists( 'parsidate' ) ) {
                $persian_date_display = parsidate( 'Y-m-d', $timestamp, 'per' );
            } else {
                $persian_date_display = date_i18n( 'Y-m-d', $timestamp );
            }
        }

        $base = 'PMKC-' . $persian_date_display . '-' . $count;

        return self::to_persian_digits( $base );
    }

    /**
     * ذخیره پیام (هسته – بقیه کدهای ذخیره متا، برچسب، پیوست مثل قبل)
     */
    public static function save_message_from_request( $post_data, $file_data ) {

        $action = isset( $post_data['wpim_action'] )
            ? sanitize_text_field( $post_data['wpim_action'] )
            : 'draft';

        $subject = sanitize_text_field( $post_data['wpim_subject'] ?? '' );
        $body    = wp_kses_post( $post_data['wpim_message_body'] ?? '' );

        $status  = ( $action === 'send' ) ? 'publish' : 'draft';

        $timestamp = current_time( 'timestamp' );
        if ( function_exists( 'parsidate' ) ) {
            $persian_date = parsidate( 'Y-m-d', $timestamp, 'per' );
        } else {
            $persian_date = date_i18n( 'Y-m-d', $timestamp );
        }
        $persian_date = self::to_persian_digits( $persian_date );

        if ( $status === 'publish' ) {
            $system_doc_number = self::assign_system_doc_number_on_send( $persian_date );
        } else {
            $system_doc_number = '';
        }

        $post_id = wp_insert_post( [
            'post_type'    => 'wpim_message',
            'post_title'   => $subject,
            'post_content' => $body,
            'post_status'  => $status,
            'post_author'  => get_current_user_id(),
        ] );

        if ( ! $post_id || is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // ... اینجا همان منطق قبلی شما برای گیرندگان، CC، اعلان‌ها، امضاء، یادداشت داخلی، برچسب‌ها و پیوست‌ها ...

        update_post_meta(
            $post_id,
            '_wpim_system_doc_number',
            $system_doc_number
        );
        update_post_meta(
            $post_id,
            '_wpim_date',
            $persian_date
        );

        // بقیه update_post_meta و پیوست‌ها را طبق نسخه قبلی این کلاس نگه دارید

        return $post_id;
    }
}