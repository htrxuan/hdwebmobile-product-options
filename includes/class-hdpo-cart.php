<?php

namespace htrxuan\hdpo;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * All server-side price truth lives here. The frontend only ever submits a *selected value*
 * (raw text, a checkbox flag, or a choice index) -- never a price. Every price used here is
 * looked up fresh from the product's own _hdpo_options meta via HDPO_Options, so a tampered
 * request (fabricated group index, out-of-range choice index) is simply ignored rather than
 * trusted. This is a deliberate design constraint, not an oversight: it's the direct fix for the
 * class of vulnerability where a competing plugin's unsafe custom-pricing-formula evaluation led
 * to a critical RCE (CVE-2026-4001) -- this plugin has no formula evaluation of any kind.
 */
class HDPO_Cart
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
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_add_to_cart'), 10, 3);
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 2);
        add_filter('woocommerce_get_item_data', array($this, 'get_item_data'), 10, 2);
        add_action('woocommerce_before_calculate_totals', array($this, 'adjust_price'));
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_order_line_item_meta'), 10, 4);
    }

    public function validate_add_to_cart($passed, $product_id, $quantity)
    {
        $groups = HDPO_Options::get_options($product_id);
        if (empty($groups)) {
            return $passed;
        }

        $submitted = self::get_submitted_options();

        foreach ($groups as $index => $group) {
            if (empty($group['required'])) {
                continue;
            }

            $value = isset($submitted[$index]) ? $submitted[$index] : '';

            if (!self::is_value_filled($group, $value)) {
                wc_add_notice(
                    sprintf(
                        /* translators: %s: option label */
                        __('"%s" is required.', 'hdwebmobile-product-options-add-ons'),
                        $group['label']
                    ),
                    'error'
                );
                $passed = false;
            }
        }

        return $passed;
    }

    public function add_cart_item_data($cart_item_data, $product_id)
    {
        $groups = HDPO_Options::get_options($product_id);
        if (empty($groups)) {
            return $cart_item_data;
        }

        $submitted = self::get_submitted_options();
        $selected  = array();

        foreach ($groups as $index => $group) {
            if (!array_key_exists($index, $submitted)) {
                continue;
            }

            $entry = self::resolve_selection($group, $submitted[$index]);
            if (null !== $entry) {
                $selected[$index] = $entry;
            }
        }

        if (!empty($selected)) {
            $cart_item_data['hdpo_options'] = $selected;
        }

        return $cart_item_data;
    }

    public function get_item_data($item_data, $cart_item)
    {
        if (empty($cart_item['hdpo_options'])) {
            return $item_data;
        }

        foreach ($cart_item['hdpo_options'] as $option) {
            $value = $option['value_label'];
            if ($option['price'] > 0) {
                $value .= ' (+' . wc_price($option['price']) . ')';
            }
            $item_data[] = array(
                'key'   => $option['label'],
                'value' => wp_strip_all_tags($value),
            );
        }

        return $item_data;
    }

    public function adjust_price($cart)
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {
            if (empty($cart_item['hdpo_options'])) {
                continue;
            }

            $sum = 0.0;
            foreach ($cart_item['hdpo_options'] as $option) {
                $sum += (float) $option['price'];
            }

            if ($sum <= 0) {
                continue;
            }

            // Always re-derive the base price from a fresh product lookup rather than the
            // cart's in-memory product clone, which may already carry a surcharge applied by an
            // earlier calculate_totals() pass in this same request -- avoids compounding.
            $original_product = wc_get_product($cart_item['product_id']);
            if (!$original_product) {
                continue;
            }

            $cart_item['data']->set_price((float) $original_product->get_price() + $sum);
        }
    }

    public function add_order_line_item_meta($item, $cart_item_key, $values, $order)
    {
        if (empty($values['hdpo_options'])) {
            return;
        }

        foreach ($values['hdpo_options'] as $option) {
            $item->add_meta_data($option['label'], $option['value_label'], true);
        }
    }

    private static function get_submitted_options()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce's own add-to-cart nonce/session flow is verified upstream (WC_Form_Handler / Store API CartController) before these hooks fire; individual field values are sanitized per-type in resolve_selection() below.
        if (!isset($_POST['hdpo_options']) || !is_array($_POST['hdpo_options'])) {
            return array();
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- see justification above; raw values are only ever used as array lookup keys or re-sanitized per-type in resolve_selection().
        return wp_unslash($_POST['hdpo_options']);
    }

    private static function is_value_filled($group, $value)
    {
        if ('checkbox' === $group['type']) {
            return !empty($value);
        }

        if ('select' === $group['type']) {
            $choice_index = is_numeric($value) ? (int) $value : -1;
            return isset($group['choices'][$choice_index]);
        }

        return is_string($value) && '' !== trim($value);
    }

    /**
     * Resolves one submitted option value against its group's authoritative, freshly-looked-up
     * definition. Returns null for anything that can't be trusted (bogus choice index, empty
     * text, unchecked checkbox) so callers simply skip it rather than fabricating a price.
     */
    private static function resolve_selection($group, $raw_value)
    {
        if ('checkbox' === $group['type']) {
            if (empty($raw_value)) {
                return null;
            }
            return array(
                'label'       => $group['label'],
                'value_label' => __('Yes', 'hdwebmobile-product-options-add-ons'),
                'price'       => '' !== $group['price'] ? (float) $group['price'] : 0.0,
            );
        }

        if ('select' === $group['type']) {
            $choice_index = is_numeric($raw_value) ? (int) $raw_value : -1;
            if (!isset($group['choices'][$choice_index])) {
                return null;
            }
            $choice = $group['choices'][$choice_index];
            return array(
                'label'       => $group['label'],
                'value_label' => $choice['label'],
                'price'       => (float) $choice['price'],
            );
        }

        // text
        $text_value = is_string($raw_value) ? sanitize_text_field($raw_value) : '';
        if ('' === $text_value) {
            return null;
        }
        return array(
            'label'       => $group['label'],
            'value_label' => $text_value,
            'price'       => '' !== $group['price'] ? (float) $group['price'] : 0.0,
        );
    }
}
