<?php
if (!defined('ABSPATH')) exit;

// Sends webhook on price update
add_action('ggdeals_price_updated', function ($product_id, $new_price) {
    $webhook_url = 'https://your-webhook-url.example.com/price-update'; // replace with your webhook URL

    $payload = [
        'product_id' => $product_id,
        'product_title' => get_the_title($product_id),
        'new_price' => $new_price,
        'timestamp' => current_time('mysql'),
        'site_url' => get_site_url(),
    ];

    wp_remote_post($webhook_url, [
        'method' => 'POST',
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode($payload),
        'data_format' => 'body',
    ]);
});
