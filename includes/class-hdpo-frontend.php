<?php

namespace htrxuan\hdpo;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders option fields via woocommerce_before_add_to_cart_button, which fires identically on
 * both the classic Single Product template and the block-based "Add to Cart Form" block --
 * confirmed directly in WooCommerce core (src/Blocks/BlockTypes/AddToCartForm.php calls
 * do_action('woocommerce_simple_add_to_cart') for simple products, the same classic action).
 * No separate Blocks-specific rendering path is needed.
 */
class HDPO_Frontend
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
        add_action('woocommerce_before_add_to_cart_button', array($this, 'render_fields'), 5);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function render_fields()
    {
        $product = $this->get_current_simple_product();
        if (!$product) {
            return;
        }

        $groups = HDPO_Options::get_options($product->get_id());
        if (empty($groups)) {
            return;
        }

        echo '<div class="hdpo-options">';
        foreach ($groups as $index => $group) {
            $this->render_group($index, $group);
        }
        echo '</div>';
    }

    private function render_group($index, $group)
    {
        $name       = 'hdpo_options[' . $index . ']';
        $required   = !empty($group['required']);
        $field_id   = 'hdpo-option-' . $index;
        $has_price  = 'checkbox' !== $group['type'] && '' !== $group['price'] && (float) $group['price'] > 0;
        ?>
        <div class="hdpo-option-group hdpo-option-group--<?php echo esc_attr($group['type']); ?>" data-hdpo-group="<?php echo esc_attr($index); ?>">
            <label class="hdpo-option-label" for="<?php echo esc_attr($field_id); ?>">
                <?php echo esc_html($group['label']); ?><?php if ($required) : ?> <span class="hdpo-required">*</span><?php endif; ?>
                <?php if ($has_price) : ?>
                    <span class="hdpo-price-hint">(+<?php echo wc_price($group['price']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_price() escapes its own output. ?>)</span>
                <?php endif; ?>
            </label>

            <?php if ('text' === $group['type']) : ?>
                <input
                    type="text"
                    id="<?php echo esc_attr($field_id); ?>"
                    name="<?php echo esc_attr($name); ?>"
                    <?php echo $required ? 'required' : ''; ?>
                    data-hdpo-price="<?php echo esc_attr('' !== $group['price'] ? $group['price'] : '0'); ?>"
                />
            <?php elseif ('checkbox' === $group['type']) : ?>
                <label class="hdpo-checkbox-label">
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr($field_id); ?>"
                        name="<?php echo esc_attr($name); ?>"
                        value="1"
                        data-hdpo-price="<?php echo esc_attr('' !== $group['price'] ? $group['price'] : '0'); ?>"
                    />
                    <?php if ('' !== $group['price'] && (float) $group['price'] > 0) : ?>
                        <span class="hdpo-price-hint">(+<?php echo wc_price($group['price']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_price() escapes its own output. ?>)</span>
                    <?php endif; ?>
                </label>
            <?php elseif ('select' === $group['type']) : ?>
                <select
                    id="<?php echo esc_attr($field_id); ?>"
                    name="<?php echo esc_attr($name); ?>"
                    <?php echo $required ? 'required' : ''; ?>
                >
                    <option value=""><?php esc_html_e('Choose an option&hellip;', 'hdwebmobile-product-options-add-ons'); ?></option>
                    <?php foreach ($group['choices'] as $choice_index => $choice) : ?>
                        <option value="<?php echo esc_attr($choice_index); ?>" data-hdpo-price="<?php echo esc_attr($choice['price']); ?>">
                            <?php
                            echo esc_html($choice['label']);
                            if ((float) $choice['price'] > 0) {
                                echo ' (+' . wp_strip_all_tags(wc_price($choice['price'])) . ')'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_strip_all_tags() strips markup, leaving plain text.
                            }
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>
        <?php
    }

    public function enqueue_assets()
    {
        $product = $this->get_current_simple_product();
        if (!$product) {
            return;
        }

        $groups = HDPO_Options::get_options($product->get_id());
        if (empty($groups)) {
            return;
        }

        wp_enqueue_style('hdpo-frontend', HDPO_PLUGIN_URL . 'assets/css/hdpo-frontend.css', array(), self::asset_version('assets/css/hdpo-frontend.css'));
        wp_enqueue_script('hdpo-frontend', HDPO_PLUGIN_URL . 'assets/js/hdpo-frontend.js', array(), self::asset_version('assets/js/hdpo-frontend.js'), true);

        wp_localize_script('hdpo-frontend', 'hdpoParams', array(
            'basePrice'     => (float) wc_get_price_to_display($product),
            'pricePrefix'   => wp_strip_all_tags(get_woocommerce_currency_symbol()),
            'decimals'      => wc_get_price_decimals(),
            'decimalSep'    => wc_get_price_decimal_separator(),
            'thousandSep'   => wc_get_price_thousand_separator(),
            'priceFormat'   => get_woocommerce_price_format(),
            'previewLabel'  => __('Total with options:', 'hdwebmobile-product-options-add-ons'),
        ));
    }

    /**
     * Returns the current simple \WC_Product on a real Single Product view, or null. Only
     * simple products are supported in this version (see readme Limitations).
     */
    private function get_current_simple_product()
    {
        if (!is_product()) {
            return null;
        }

        global $product;
        $current_product = $product instanceof \WC_Product ? $product : wc_get_product(get_the_ID());

        if (!$current_product instanceof \WC_Product || !$current_product->is_type('simple')) {
            return null;
        }

        return $current_product;
    }

    /**
     * Uses the file's own last-modified time as the cache-busting version instead of the
     * static plugin version, so every CSS/JS edit is picked up immediately without a manual
     * version bump.
     */
    private static function asset_version($relative_path)
    {
        $path = HDPO_PLUGIN_DIR . $relative_path;
        return file_exists($path) ? (string) filemtime($path) : HDPO_VERSION;
    }
}
