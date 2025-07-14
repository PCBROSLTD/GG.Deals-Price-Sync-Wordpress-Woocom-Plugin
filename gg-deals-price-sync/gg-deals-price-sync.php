<?php
/*
Plugin Name: GG.deals Price Sync
Description: Sync WooCommerce product prices from GG.deals using Steam App IDs with logging and admin interface.
Version: 1.0
Author: PC Bros Ltd
*/

if (!defined('ABSPATH')) exit;

define('GGDEALS_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Includes
require_once GGDEALS_PLUGIN_PATH . 'includes/product-meta.php';
require_once GGDEALS_PLUGIN_PATH . 'includes/sync-prices.php';
require_once GGDEALS_PLUGIN_PATH . 'includes/webhook.php';
require_once GGDEALS_PLUGIN_PATH . 'admin/dashboard.php';

// Schedule cron on activation
register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('ggdeals_sync_prices_hook')) {
        wp_schedule_event(time(), 'twicedaily', 'ggdeals_sync_prices_hook');
    }
});

// Clear cron on deactivation
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('ggdeals_sync_prices_hook');
});

// Hook sync function to cron event
add_action('ggdeals_sync_prices_hook', function () {
    ggdeals_update_woo_prices(true); // true for debug logging on cron also
    update_option('ggdeals_global_last_sync', current_time('mysql'));
});
