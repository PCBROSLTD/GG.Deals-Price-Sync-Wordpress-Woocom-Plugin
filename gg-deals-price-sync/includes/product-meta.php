<?php
if (!defined('ABSPATH')) exit;

// Add Steam App ID field next to SKU
add_action('woocommerce_product_options_sku', function () {
    woocommerce_wp_text_input([
        'id' => '_steam_app_id',
        'label' => __('Steam App ID', 'ggdeals'),
        'description' => __('Used for syncing prices from GG.deals.', 'ggdeals'),
        'desc_tip' => true,
        'type' => 'text',
    ]);
});

// Save Steam App ID when product saved
add_action('woocommerce_admin_process_product_object', function ($product) {
    if (isset($_POST['_steam_app_id'])) {
        $product->update_meta_data('_steam_app_id', sanitize_text_field($_POST['_steam_app_id']));
    }
});
