<?php

add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        'GG.deals Price Sync',
        'GG.deals Price Sync',
        'manage_woocommerce',
        'ggdeals-price-sync',
        'ggdeals_price_sync_main_page'
    );
});

function ggdeals_price_sync_main_page() {
    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'sync';

    $tabs = [
        'sync' => 'Sync',
        'logs' => 'Logs',
    ];

    echo '<div class="wrap"><h1>GG.deals Price Sync</h1>';

    // Tabs navigation
    echo '<h2 class="nav-tab-wrapper">';
    foreach ($tabs as $key => $label) {
        $class = ($tab === $key) ? 'nav-tab nav-tab-active' : 'nav-tab';
        echo '<a href="?page=ggdeals-price-sync&tab=' . esc_attr($key) . '" class="' . esc_attr($class) . '">' . esc_html($label) . '</a>';
    }
    echo '</h2>';

    // Load tab content
    if ($tab === 'logs') {
        ggdeals_sync_logs_page();
    } else {
        ggdeals_sync_admin_page();
    }

    echo '</div>';
}

function ggdeals_sync_admin_page() {
    echo '<form method="post">';
    if (isset($_POST['ggdeals_manual_sync'])) {
        ggdeals_update_woo_prices(true);  // Pass true for debug logging
        update_option('ggdeals_global_last_sync', current_time('mysql'));
        echo '<div class="notice notice-success"><p>Manual sync completed.</p></div>';
    }
    echo '<button class="button button-primary" name="ggdeals_manual_sync">ðŸ”„ Sync Now</button>';
    echo '</form><br>';

    $last_sync = get_option('ggdeals_global_last_sync');
    echo '<p><strong>Last Synced:</strong> ' . esc_html($last_sync ?: 'Never') . '</p>';

    echo '<table class="widefat fixed striped"><thead><tr>
        <th>Product</th>
        <th>Steam App ID</th>
        <th>Current Price</th>
        <th>Last Synced</th>
    </tr></thead><tbody>';

    // Only products with _steam_app_id meta key
    $products = get_posts([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_key' => '_steam_app_id',
        'meta_compare' => 'EXISTS',
    ]);

    usort($products, function($a, $b) {
        return strcmp(
            get_post_meta($a->ID, '_steam_app_id', true),
            get_post_meta($b->ID, '_steam_app_id', true)
        );
    });

    foreach ($products as $product) {
        $appid = get_post_meta($product->ID, '_steam_app_id', true);
        $price = get_post_meta($product->ID, '_price', true);
        $last_product_sync = get_post_meta($product->ID, '_ggdeals_last_sync', true);

        echo '<tr>';
        echo '<td><a href="' . esc_url(get_edit_post_link($product->ID)) . '">' . esc_html($product->post_title) . '</a></td>';
        echo '<td>' . esc_html($appid ?: '-') . '</td>';
        echo '<td>Â£' . esc_html(number_format((float)$price, 2)) . '</td>';
        echo '<td>' . esc_html($last_product_sync ?: '-') . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

function ggdeals_sync_logs_page() {
    echo '<h2>Sync Logs</h2>';

    $log_file = wp_upload_dir()['basedir'] . '/ggdeals-sync.log';

    if (isset($_POST['clear_logs'])) {
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
            echo '<div class="notice notice-success"><p>Logs cleared.</p></div>';
        }
    }

    echo '<form method="post" style="margin-bottom:1em;">
        <button class="button button-secondary" name="clear_logs" onclick="return confirm(\'Are you sure you want to clear logs?\')">Clear Logs</button>
    </form>';

    if (!file_exists($log_file)) {
        echo '<p>No logs found.</p>';
        return;
    }

    $contents = file_get_contents($log_file);
    echo '<pre style="background:#1e1e1e; color:#dcdcdc; padding:1em; max-height:500px; overflow:auto; white-space:pre-wrap;">' . esc_html($contents) . '</pre>';
}
