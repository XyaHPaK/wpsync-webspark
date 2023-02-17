<?php
namespace Webspark;

class Main {

    function __construct($url, $attempts_count) {
        $this->url = $url;
        $this->attempts_count = $attempts_count;
    }

    function get_products() {
        $i = 0;
        $args['timeout'] = 5;
        while ( $i < $this->attempts_count ) {
            $i++;
            $r = wp_remote_get( $this->url, $args );
            if ( is_wp_error($r) ) {
                foreach ( $r->get_error_messages() as $http_error ) {
                    if ( strpos($http_error, 'timed out') !== false ) {
                        $args['timeout'] *= 2;
                    }
                }
                continue;
            }
            $r_body = json_decode($r['body'] ?? '');
            if ( !$r_body ) {
                continue;
            }
            if ( $r_body->error ) {
                continue;
            }
            if ( empty($r_body->data) ) {
                continue;
            }
            return $r_body->data;
        }
        throw new \Exception('no results after ' . $this->attempts_count . ' attempts');
    }
    function prepare_products() {
        global $wpdb;
        $wpdb->query('truncate table temp_products');
        $products = $this->get_products();
        foreach ($products as $product) {
            $wpdb->insert('temp_products', ['data'=> serialize($product)]);
        }
    }
    function update_products() {
        global $wpdb;
        $sql = 'select id, data from temp_products where processed = 0';
        $products = $wpdb->get_results($sql);
        if ( empty($products) ) {
            $this->prepare_products();
            $products = $wpdb->get_results($sql);
        }
        $existing = $wpdb->get_results("
            SELECT pm.post_id, pm.meta_value from wp_posts p 
            join wp_postmeta pm on p.id=pm.post_id 
            WHERE p.post_status='publish' and pm.meta_key='_sku'
        ");
        $ex = [];
        foreach ( $existing as $e ) {
            $ex[$e->meta_value] = $e->post_id;
        }
        if (! wp_next_scheduled ( 'cron_update_products' )) {
            wp_schedule_event( time(), 'hourly', 'cron_update_products' );
        }
        foreach ($products as $p) {
            if ( (time() - $_SERVER['REQUEST_TIME']) + 5 >= ini_get('max_execution_time') ) {
                wp_schedule_single_event( time() + 60, 'cron_update_products' );
                break;
            }
            $product = unserialize($p->data);

            if ( isset($ex[$product->sku]) ) {
                $post_id = wp_update_post( [
                    'ID' => $ex[$product->sku],
                    'post_author'  => get_current_user_id(),
                    'post_title' => $product->name,
                    'post_content' => $product->description,
                    'post_status' => 'publish',
                    'post_type' => "product",
                    'meta_input'   => [
                        '_sku' => $product->sku,
                        '_price' => $product->price,
                        '_stock' => $product->in_stock,
                    ],
                ] );
                unset($ex[$product->sku]);
            } else {
                $post_id = wp_insert_post( [
                    'post_author'  => get_current_user_id(),
                    'post_title' => $product->name,
                    'post_content' => $product->description,
                    'post_status' => 'publish',
                    'post_type' => "product",
                    'meta_input'   => [
                        '_sku' => $product->sku,
                        '_price' => $product->price,
                        '_stock' => $product->in_stock,
                    ],
                ] );
                $thumb_id = media_sideload_image($product->picture . '?ext=.jpg', $post_id, null, 'id');
                set_post_thumbnail($post_id, $thumb_id);
            }
            $wpdb->update('temp_products', ['processed' => true], ['id' => $p->id]);
        }
        foreach (array_values($ex) as $p_id) {
            wp_delete_post($p_id, true);
        }
    }
}
