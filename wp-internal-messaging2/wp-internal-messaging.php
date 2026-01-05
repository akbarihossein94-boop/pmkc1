<?php
/**
 * Plugin Name: سامانه پیام داخلی وردپرس
 * Description: افزونه پیام‌رسان داخلی برای وردپرس (ایجاد پیام، صندوق‌ها، گروهی و ...).
 * Version: 1.2.0
 * Author: Your Name
 * Text Domain: wp-internal-messaging
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
 * ثابت‌ها
 */
define( 'WPIM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPIM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/*
 * فایل‌ها
 */
require_once WPIM_PLUGIN_DIR . 'includes/class-wpim-messages.php';
require_once WPIM_PLUGIN_DIR . 'includes/class-wpim-admin.php';
require_once WPIM_PLUGIN_DIR . 'includes/class-wpim-ajax.php';

/*
 * هوک‌ها
 */

// نوع نوشته پیام داخلی روی init
add_action( 'init', [ 'WPIM_Messages', 'register_post_type' ] );

// راه‌اندازی بخش مدیریت و آژاکس
function wpim_init() {
    WPIM_Admin::init();
    WPIM_Ajax::init();
}
add_action( 'init', 'wpim_init' );