<?php
error_reporting(E_ALL);
function products_update( ) {
    try {
        $r = (new Webspark\Main('https://wp.webspark.dev/wp-api/products', 5))->update_products();
        return $r;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
function update_products_deactivate() {
    wp_clear_scheduled_hook( 'cron_update_products' );
}
