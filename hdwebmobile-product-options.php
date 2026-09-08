<?php

/**
 * Plugin Name: HDWebmobile Product Options & Add-ons
 * Plugin URI: https://hdwebmobile.com/plugins/hdwebmobile-product-options/
 * Description: Let customers add paid extras to a product (engraving, gift wrap, size upgrades) with text, dropdown, and checkbox options. Flat-amount pricing only, no formula evaluation.
 * Version: 1.0.0
 * Author: htrxuan - Han Tran
 * Author URI: https://hdwebmobile.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hdwebmobile-product-options-add-ons
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.9
 */

namespace htrxuan\hdpo;

if (!defined('ABSPATH')) {
    exit;
}

define('HDPO_VERSION', '1.0.0');
define('HDPO_PLUGIN_FILE', __FILE__);
define('HDPO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDPO_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HDPO_PLUGIN_DIR . 'includes/class-hdpo-activator.php';

register_activation_hook(__FILE__, array(HDPO_Activator::class, 'activate'));

add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', HDPO_PLUGIN_FILE, true);
    }
});

add_action('plugins_loaded', function () {
    require_once HDPO_PLUGIN_DIR . 'includes/class-hdpo-core.php';
    HDPO_Core::get_instance();
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $donate_link = '<a href="https://paypal.me/htrxuan/20" target="_blank" rel="noopener noreferrer">' . esc_html__('Donate', 'hdwebmobile-product-options-add-ons') . '</a>';
    array_unshift($links, $donate_link);
    return $links;
});
