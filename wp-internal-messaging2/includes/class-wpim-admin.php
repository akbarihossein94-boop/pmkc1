<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPIM_Admin {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_form_submit' ] );
    }

    public static function register_menu() {
        $cap = 'read';

        // Main
        add_menu_page(
            'پیام‌های داخلی',
            'پیام‌های داخلی',
            $cap,
            'wpim_messages',
            [ __CLASS__, 'render_inbox_page' ],
            'dashicons-email',
            26
        );

        // Inbox
        add_submenu_page(
            'wpim_messages',
            'صندوق دریافت',
            'صندوق دریافت',
            $cap,
            'wpim_messages',
            [ __CLASS__, 'render_inbox_page' ]
        );

        // Create message
        add_submenu_page(
            'wpim_messages',
            'ایجاد پیام جدید',
            'ایجاد پیام جدید',
            $cap,
            'wpim_create',
            [ __CLASS__, 'render_create_message_page' ]
        );

        // View message (hidden from menu, only via link)
        add_submenu_page(
            null,
            'نمایش پیام',
            'نمایش پیام',
            $cap,
            'wpim_view',
            [ __CLASS__, 'render_view_message_page' ]
        );
    }

    public static function enqueue_assets( $hook ) {
        if ( empty( $_GET['page'] ) ) {
            return;
        }

        if ( ! in_array( $_GET['page'], [ 'wpim_messages', 'wpim_create', 'wpim_view' ], true ) ) {
            return;
        }

        wp_enqueue_style(
            'wpim-admin',
            WPIM_PLUGIN_URL . 'assets/css/admin.css',
            [],
            '1.6.0'
        );

        wp_enqueue_script(
            'wpim-admin',
            WPIM_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            '1.6.0',
            true
        );

        wp_localize_script( 'wpim-admin', 'WPIM', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wpim_ajax_nonce' ),
        ] );
    }

    protected static function get_persian_date_for_today() {
        $timestamp = current_time( 'timestamp' );
        if ( function_exists( 'parsidate' ) ) {
            $d = parsidate( 'Y-m-d', $timestamp, 'per' );
        } else {
            $d = date_i18n( 'Y-m-d', $timestamp );
        }
        return WPIM_Messages::to_persian_digits( $d );
    }

    public static function render_create_message_page() {
        $persian_date       = self::get_persian_date_for_today();
        $system_doc_preview = WPIM_Messages::get_next_system_doc_number_preview( $persian_date );
        $today              = $persian_date;

        $all_users = get_users( [
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'number'  => -1,
        ] );

        include WPIM_PLUGIN_DIR . 'templates/create-message.php';
    }

    /**
     * صندوق دریافت
     */
public static function render_inbox_page() {
    $current_user_id = get_current_user_id();

    $search = isset( $_GET['wpim_search'] ) ? sanitize_text_field( wp_unslash( $_GET['wpim_search'] ) ) : '';
    $filter = isset( $_GET['wpim_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['wpim_filter'] ) ) : 'all';
    $status = isset( $_GET['wpim_status_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['wpim_status_filter'] ) ) : 'all';

    // Base condition: this user is either recipient or CC
    // WordPress stores arrays as serialized strings, so we use LIKE with the ID as a plain string.
    $meta_query = [
        'relation' => 'OR',
        [
            'key'     => '_wpim_recipients',
            'value'   => (string) $current_user_id,
            'compare' => 'LIKE',
        ],
        [
            'key'     => '_wpim_cc',
            'value'   => (string) $current_user_id,
            'compare' => 'LIKE',
        ],
    ];

    $args = [
        'post_type'      => 'wpim_message',
        'post_status'    => [ 'publish' ],    // فقط پیام‌های ارسال‌شده در صندوق
        'posts_per_page' => 50,
        'meta_query'     => $meta_query,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    if ( $search ) {
        $args['s'] = $search;
    }

    // Extra filters
    $extra_meta = [];

    // Filter: copied (messages where I'm only in CC is more advanced; for now: in CC)
    if ( $filter === 'copied' ) {
        $extra_meta[] = [
            'key'     => '_wpim_cc',
            'value'   => (string) $current_user_id,
            'compare' => 'LIKE',
        ];
    }

    // Filter: forwarded
    if ( $filter === 'forwarded' ) {
        $extra_meta[] = [
            'key'   => '_wpim_forwarded',
            'value' => 1,
        ];
    }

    // Filter: unread (simple: no _wpim_read_by_{user} meta)
    if ( $filter === 'unread' ) {
        $extra_meta[] = [
            'key'     => '_wpim_read_by_' . $current_user_id,
            'compare' => 'NOT EXISTS',
        ];
    }

    // Status filter (viewed, actioned, followup, archived, ...)
    if ( $status !== 'all' ) {
        $extra_meta[] = [
            'key'   => '_wpim_message_status',
            'value' => $status,
        ];
    }

    // Merge extra filters with base OR condition
    if ( ! empty( $extra_meta ) ) {
        $args['meta_query'] = array_merge(
            [ 'relation' => 'AND' ],
            [
                $meta_query,          // (recipient OR CC)
            ],
            $extra_meta            // AND extra filters
        );
    }

    $messages = get_posts( $args );

    include WPIM_PLUGIN_DIR . 'templates/inbox.php';
}

    /**
     * نمایش یک پیام
     */
    public static function render_view_message_page() {
        $message_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $message    = $message_id ? get_post( $message_id ) : null;

        if ( ! $message || $message->post_type !== 'wpim_message' ) {
            echo '<div class="wrap wpim-wrap"><h1>پیام یافت نشد</h1></div>';
            return;
        }

        // Mark as "viewed" for this user (simple example)
        $current_user_id = get_current_user_id();
        update_post_meta( $message_id, '_wpim_read_by_' . $current_user_id, 1 );

        // Meta
        $sender_id    = (int) get_post_meta( $message_id, '_wpim_sender_id', true );
        $sender       = $sender_id ? get_user_by( 'id', $sender_id ) : null;
        $recipients   = (array) get_post_meta( $message_id, '_wpim_recipients', true );
        $cc           = (array) get_post_meta( $message_id, '_wpim_cc', true );
        $sys_doc      = get_post_meta( $message_id, '_wpim_system_doc_number', true );
        $int_doc      = get_post_meta( $message_id, '_wpim_internal_doc_number', true );
        $date         = get_post_meta( $message_id, '_wpim_date', true );
        $type         = get_post_meta( $message_id, '_wpim_message_type', true );
        $priority     = get_post_meta( $message_id, '_wpim_priority', true );
        $status       = get_post_meta( $message_id, '_wpim_message_status', true ) ?: 'none';
        $attachments  = (array) get_post_meta( $message_id, '_wpim_attachments', true );
        $signature    = get_post_meta( $message_id, '_wpim_signature', true );
        $internal_note= get_post_meta( $message_id, '_wpim_internal_note', true );

        include WPIM_PLUGIN_DIR . 'templates/view-message.php';
    }

    public static function handle_form_submit() {
        if (
            empty( $_POST['wpim_nonce'] ) ||
            ! wp_verify_nonce( $_POST['wpim_nonce'], 'wpim_save_message' )
        ) {
            return;
        }

        if ( ! current_user_can( 'read' ) ) {
            return;
        }

        $post_id = WPIM_Messages::save_message_from_request( $_POST, $_FILES );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            wp_redirect(
                add_query_arg(
                    'wpim_status',
                    'saved',
                    menu_page_url( 'wpim_create', false )
                )
            );
            exit;
        }
    }
}