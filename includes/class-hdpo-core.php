<?php

namespace htrxuan\hdpo;

if (!defined('ABSPATH')) {
    exit;
}

final class HDPO_Core
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
    }

    private function __clone()
    {
    }

    private function includes()
    {
        require_once HDPO_PLUGIN_DIR . 'includes/class-hdpo-options.php';
        require_once HDPO_PLUGIN_DIR . 'includes/class-hdpo-frontend.php';
        require_once HDPO_PLUGIN_DIR . 'includes/class-hdpo-cart.php';
        require_once HDPO_PLUGIN_DIR . 'includes/class-hdpo-admin.php';
    }

    private function init_hooks()
    {
        add_action('admin_notices', array($this, 'render_missing_woocommerce_notice'));

        if (!class_exists('WooCommerce')) {
            return;
        }

        HDPO_Frontend::get_instance();
        HDPO_Cart::get_instance();

        if (is_admin()) {
            HDPO_Admin::get_instance();
        }
    }

    public function render_missing_woocommerce_notice()
    {
        $screen = get_current_screen();
        if (!$screen || 'plugins' !== $screen->id) {
            return;
        }

        if (!get_transient('hdpo_wc_missing_notice')) {
            return;
        }
        delete_transient('hdpo_wc_missing_notice');
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e('HDWebmobile Product Options & Add-ons requires WooCommerce to be installed and active. The plugin has been deactivated.', 'hdwebmobile-product-options-add-ons'); ?>
            </p>
        </div>
        <?php
    }
}
