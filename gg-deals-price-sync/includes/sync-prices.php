<?php
if (!defined('ABSPATH')) exit;

// Log helper
function ggdeals_log($message) {
    $log_file = wp_upload_dir()['basedir'] . '/ggdeals-sync.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message\n", FILE_APPEND);
}

// Main sync function using new GG.deals batch price API
function ggdeals_update_woo_prices($debug = true) {
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

            $prices_data = $game_data['prices'] ?? [];

            // Parse prices
            $retail_price_str = $prices_data['currentRetail'] ?? null;
            $keyshop_price_str = $prices_data['currentKeyshops'] ?? null;

            // Convert to float
            $retail_price = $retail_price_str ? floatval($retail_price_str) : 0;
            $keyshop_price = $keyshop_price_str ? floatval($keyshop_price_str) : 0;

            if ($keyshop_price <= 0 && $retail_price <= 0) {
                if ($debug) ggdeals_log("No valid prices for product ID $product_id, skipping.");
                continue;
            }

            // Calculate sale price with 15% markup on keyshop price (or fallback to retail if keyshop missing)
            $base_price = ($keyshop_price > 0) ? $keyshop_price : $retail_price;
            $sale_price = round($base_price * 1.15, 2);

            if ($retail_price <= 0) {
                // If no retail price, fallback retail price to sale price (to avoid empty regular price)
                $retail_price = $sale_price;
            }

            $current_sale_price = floatval(get_post_meta($product_id, '_sale_price', true));
            $current_regular_price = floatval(get_post_meta($product_id, '_regular_price', true));
            $current_price = floatval(get_post_meta($product_id, '_price', true));

            if ($debug) {
                ggdeals_log("Product ID $product_id - Retail: £$retail_price, Sale: £$sale_price, Current Regular: £$current_regular_price, Current Sale: £$current_sale_price");
            }

            // Update if prices differ
            if ($current_sale_price !== $sale_price || $current_regular_price !== $retail_price) {
                update_post_meta($product_id, '_regular_price', $retail_price);
                update_post_meta($product_id, '_sale_price', $sale_price);
                update_post_meta($product_id, '_price', $sale_price); // WooCommerce main price field
                update_post_meta($product_id, '_ggdeals_last_sync', current_time('mysql'));
                wc_delete_product_transients($product_id);

                if ($debug) {
                    ggdeals_log("Updated product ID $product_id prices: regular £$retail_price, sale £$sale_price");
                }

                do_action('ggdeals_price_updated', $product_id, $sale_price);
            } else {
                if ($debug) {
                    ggdeals_log("No price change for product ID $product_id.");
                }
                // Still update last sync timestamp even if no price change
                update_post_meta($product_id, '_ggdeals_last_sync', current_time('mysql'));
            }
        }
    }

    // Clear SpeedyCache cache after syncing
    exec('wp speedycache clear cache 2>&1', $output, $return_var);
    if ($return_var === 0) {
        ggdeals_log('SpeedyCache cache cleared successfully.');
    } else {
        ggdeals_log('Failed to clear SpeedyCache cache: ' . implode("\n", $output));
    }
}
