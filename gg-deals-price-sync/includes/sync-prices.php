<?php
if (!defined('ABSPATH')) exit;

// Log helper
function ggdeals_log($message) {
    $log_file = wp_upload_dir()['basedir'] . '/ggdeals-sync.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message\n", FILE_APPEND);
}

// Main sync function using new GG.deals batch price API
function ggdeals_update_woo_prices($debug = false) {
    $api_key = 'YOUR_API_KEY_HERE';  // <-- Replace with your actual API key
    $region = 'gb';  // Change region as needed

    // Get all products with Steam App IDs
    $products = get_posts([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_key' => '_steam_app_id',
        'meta_compare' => 'EXISTS'
    ]);

    if (empty($products)) {
        if ($debug) ggdeals_log("No products with Steam App ID found.");
        return;
    }

    // Map product IDs to Steam App IDs
    $steam_ids_map = [];
    foreach ($products as $product) {
        $appid = get_post_meta($product->ID, '_steam_app_id', true);
        if ($appid) {
            $steam_ids_map[$product->ID] = $appid;
        }
    }

    // API limit: max 100 IDs per request, so chunk Steam IDs
    $steam_id_chunks = array_chunk(array_values($steam_ids_map), 100);

    foreach ($steam_id_chunks as $chunk) {
        $ids_param = implode(',', $chunk);
        $url = "https://api.gg.deals/v1/prices/by-steam-app-id/?ids=$ids_param&key=$api_key&region=$region";

        $response = wp_remote_get($url, ['timeout' => 20]);

        if (is_wp_error($response)) {
            if ($debug) ggdeals_log("API request error: " . $response->get_error_message());
            continue;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($debug) ggdeals_log("Fetched batch data for Steam App IDs: $ids_param");

        if (empty($data['success']) || !$data['success'] || empty($data['data'])) {
            if ($debug) ggdeals_log("API returned unsuccessful response.");
            continue;
        }

        foreach ($steam_ids_map as $product_id => $appid) {
            if (!in_array($appid, $chunk)) continue;

            $game_data = $data['data'][$appid] ?? null;

            if ($game_data === null) {
                if ($debug) ggdeals_log("No price data for product ID $product_id (Steam App ID $appid), skipping.");
                continue;
            }

            $prices = $game_data['prices'] ?? [];

            // Prefer currentKeyshops, fallback to currentRetail
            $price_str = $prices['currentKeyshops'] ?: $prices['currentRetail'] ?: null;

            if ($price_str === null) {
                if ($debug) ggdeals_log("No current price found for product ID $product_id (Steam App ID $appid), skipping.");
                continue;
            }

            $new_price = round(floatval($price_str) * 1.15, 2); // Apply 15% markup

            $current_price = floatval(get_post_meta($product_id, '_price', true));
            $current_regular_price = floatval(get_post_meta($product_id, '_regular_price', true));

            if ($current_price !== $new_price || $current_regular_price !== $new_price) {
                update_post_meta($product_id, '_regular_price', $new_price);
                update_post_meta($product_id, '_price', $new_price);
                update_post_meta($product_id, '_ggdeals_last_sync', current_time('mysql'));
                wc_delete_product_transients($product_id);

                if ($debug) ggdeals_log("Updated product ID $product_id price to £$new_price");

                // Fire webhook action (if hooked elsewhere)
                do_action('ggdeals_price_updated', $product_id, $new_price);
            } else {
                if ($debug) ggdeals_log("No price change for product ID $product_id.");
            }
        }
    }
}
