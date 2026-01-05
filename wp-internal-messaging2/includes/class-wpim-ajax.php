<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPIM_Ajax {

    public static function init() {
        add_action( 'wp_ajax_wpim_search_users', [ __CLASS__, 'search_users' ] );
    }

    /**
     * جستجوی کاربران برای autocomplete
     */
    public static function search_users() {
        check_ajax_referer( 'wpim_ajax_nonce', 'nonce' );

        if ( ! current_user_can( 'read' ) ) {
            wp_send_json_error();
        }

        $term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

        $users = get_users( [
            'search'         => '*' . $term . '*',
            'search_columns' => [ 'user_login', 'user_nicename', 'user_email', 'display_name' ],
            'number'         => 10,
        ] );

        $results = [];

        foreach ( $users as $user ) {
            $label = $user->display_name . ' (' . $user->user_email . ')';
            $results[] = [
                'id'    => $user->ID,
                'label' => $label,
                'value' => $label,
            ];
        }

        wp_send_json( $results );
    }
}