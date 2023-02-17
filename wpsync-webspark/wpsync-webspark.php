<?php
namespace Webspark;
/**
 * Plugin Name: wpsync-webspark
 * Description: Custom plugin to update product base via webspark API
 * Version: 1.0
 * Author: XyaHPaK
 **/
register_activation_hook(__FILE__,  function() {
    global $wpdb;
    $sqls = [
        "START TRANSACTION",
        "DROP TABLE IF EXISTS `temp_products`",
        "CREATE TABLE `temp_products` (
            `id` int(11) NOT NULL,
          `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
          `processed` tinyint(1) NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "ALTER TABLE `temp_products` ADD PRIMARY KEY (`id`)",
        "ALTER TABLE `temp_products` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT",
    ];
    foreach($sqls as $s) {
        $r = $wpdb->query(trim($s));
        if($r === false) {
            $wpdb->query('ROLLBACK');
        }
    }
    $wpdb->query('COMMIT');
    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        wp_schedule_single_event( time() + 120, 'cron_update_products' );
    }
});
add_action( 'cron_update_products', 'products_update' );
register_deactivation_hook( __FILE__, 'update_products_deactivate' );
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

spl_autoload_register(function ($class) {
    if ( strpos($class, __NAMESPACE__) !== false ) {
        require_once __DIR__ . '/classes/Main.php';
    }
});

require_once __DIR__ . '/functions.php';
