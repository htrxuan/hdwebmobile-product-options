<?php

namespace htrxuan\hdpo;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adds an "Options & Add-ons" tab to the Product Data metabox, mirroring the pattern already
 * used by Frequently Bought Together (woocommerce_product_data_tabs / _panels /
 * process_product_meta). The panel is a small vanilla-JS repeater (no build step, matching
 * house convention) for adding text/dropdown/checkbox option groups, each with a flat price
 * adjustment -- never a formula, see class-hdpo-cart.php for why.
 */
class HDPO_Admin
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
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_data_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'render_product_data_panel'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_meta'));
        require_once HDPO_PLUGIN_DIR . 'includes/class-hdpo-hub.php';
        add_filter('hdwebmobile_hub_tabs', array($this, 'register_hub_tabs'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function add_product_data_tab($tabs)
    {
        $tabs['hdpo'] = array(
            'label'    => __('Options & Add-ons', 'hdwebmobile-product-options'),
            'target'   => 'hdpo_product_data',
            'class'    => array('show_if_simple'),
            'priority' => 22,
        );
        return $tabs;
    }

    public function enqueue_admin_assets($hook)
    {
        if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
            return;
        }

        global $post;
        if (!$post || 'product' !== $post->post_type) {
            return;
        }

        wp_enqueue_style('hdpo-admin', HDPO_PLUGIN_URL . 'assets/css/hdpo-admin.css', array(), self::asset_version('assets/css/hdpo-admin.css'));
        wp_enqueue_script('hdpo-admin', HDPO_PLUGIN_URL . 'assets/js/hdpo-admin.js', array(), self::asset_version('assets/js/hdpo-admin.js'), true);
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

    public function render_product_data_panel()
    {
        global $post;

        $product_id = $post->ID;
        $groups     = HDPO_Options::get_options($product_id);

        wp_nonce_field('hdpo_save_meta', 'hdpo_meta_nonce');
        ?>
        <div id="hdpo_product_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <p style="padding: 0 12px;">
                    <?php esc_html_e('Add paid options customers choose before adding this product to their cart (e.g. engraving text, a size upgrade, gift wrap). Prices are flat amounts only -- no formulas.', 'hdwebmobile-product-options'); ?>
                </p>

                <div id="hdpo-groups" class="hdpo-groups">
                    <?php foreach ($groups as $index => $group) : ?>
                        <?php echo self::render_group_row($index, $group); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_group_row() escapes all interpolated values internally. ?>
                    <?php endforeach; ?>
                </div>

                <p style="padding: 0 12px;">
                    <button type="button" class="button" id="hdpo-add-group"><?php esc_html_e('+ Add Option', 'hdwebmobile-product-options'); ?></button>
                </p>
            </div>
        </div>

        <template id="hdpo-group-template">
            <?php
            echo self::render_group_row('__INDEX__', array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_group_row() escapes all interpolated values internally.
                'label'    => '',
                'type'     => 'text',
                'required' => '',
                'price'    => '',
                'choices'  => array(),
            ));
            ?>
        </template>
        <template id="hdpo-choice-template">
            <?php echo self::render_choice_row('__GROUP__', '__CINDEX__', array('label' => '', 'price' => '')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_choice_row() escapes all interpolated values internally. ?>
        </template>
        <?php
    }

    private static function render_group_row($index, $group)
    {
        $label     = isset($group['label']) ? $group['label'] : '';
        $type      = isset($group['type']) ? $group['type'] : 'text';
        $required  = !empty($group['required']);
        $price     = isset($group['price']) ? $group['price'] : '';
        $choices   = isset($group['choices']) && is_array($group['choices']) ? $group['choices'] : array();
        $is_select = 'select' === $type;

        ob_start();
        ?>
        <div class="hdpo-group-row" data-index="<?php echo esc_attr($index); ?>">
            <div class="hdpo-group-row-header">
                <input
                    type="text"
                    class="hdpo-group-label"
                    placeholder="<?php esc_attr_e('Option label (e.g. Engraving Text)', 'hdwebmobile-product-options'); ?>"
                    name="hdpo_options[<?php echo esc_attr($index); ?>][label]"
                    value="<?php echo esc_attr($label); ?>"
                />
                <select class="hdpo-type-select" name="hdpo_options[<?php echo esc_attr($index); ?>][type]">
                    <option value="text" <?php selected($type, 'text'); ?>><?php esc_html_e('Text field', 'hdwebmobile-product-options'); ?></option>
                    <option value="select" <?php selected($type, 'select'); ?>><?php esc_html_e('Dropdown', 'hdwebmobile-product-options'); ?></option>
                    <option value="checkbox" <?php selected($type, 'checkbox'); ?>><?php esc_html_e('Checkbox', 'hdwebmobile-product-options'); ?></option>
                </select>
                <label class="hdpo-required-label">
                    <input type="checkbox" name="hdpo_options[<?php echo esc_attr($index); ?>][required]" value="1" <?php checked($required); ?> />
                    <?php esc_html_e('Required', 'hdwebmobile-product-options'); ?>
                </label>
                <button type="button" class="button-link hdpo-remove-group" aria-label="<?php esc_attr_e('Remove option', 'hdwebmobile-product-options'); ?>">&times;</button>
            </div>

            <div class="hdpo-price-field" <?php echo $is_select ? 'hidden' : ''; ?>>
                <label>
                    <?php esc_html_e('Price adjustment:', 'hdwebmobile-product-options'); ?>
                    <input type="number" step="0.01" min="0" class="hdpo-price-input" name="hdpo_options[<?php echo esc_attr($index); ?>][price]" value="<?php echo esc_attr($price); ?>" />
                </label>
            </div>

            <div class="hdpo-choices-field" <?php echo $is_select ? '' : 'hidden'; ?>>
                <div class="hdpo-choices" data-group-index="<?php echo esc_attr($index); ?>">
                    <?php foreach ($choices as $cindex => $choice) : ?>
                        <?php echo self::render_choice_row($index, $cindex, $choice); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_choice_row() escapes all interpolated values internally. ?>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button button-small hdpo-add-choice"><?php esc_html_e('+ Add Choice', 'hdwebmobile-product-options'); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_choice_row($group_index, $choice_index, $choice)
    {
        $label = isset($choice['label']) ? $choice['label'] : '';
        $price = isset($choice['price']) ? $choice['price'] : '';

        ob_start();
        ?>
        <div class="hdpo-choice-row">
            <input
                type="text"
                placeholder="<?php esc_attr_e('Choice label (e.g. Large)', 'hdwebmobile-product-options'); ?>"
                name="hdpo_options[<?php echo esc_attr($group_index); ?>][choices][<?php echo esc_attr($choice_index); ?>][label]"
                value="<?php echo esc_attr($label); ?>"
            />
            <input
                type="number"
                step="0.01"
                min="0"
                class="hdpo-price-input"
                placeholder="<?php esc_attr_e('Price', 'hdwebmobile-product-options'); ?>"
                name="hdpo_options[<?php echo esc_attr($group_index); ?>][choices][<?php echo esc_attr($choice_index); ?>][price]"
                value="<?php echo esc_attr($price); ?>"
            />
            <button type="button" class="button-link hdpo-remove-choice" aria-label="<?php esc_attr_e('Remove choice', 'hdwebmobile-product-options'); ?>">&times;</button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function save_product_meta($post_id)
    {
        if (!isset($_POST['hdpo_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hdpo_meta_nonce'])), 'hdpo_save_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified above; every field is sanitized per-type inside HDPO_Options::save_options().
        $groups = isset($_POST['hdpo_options']) && is_array($_POST['hdpo_options']) ? wp_unslash($_POST['hdpo_options']) : array();

        HDPO_Options::save_options($post_id, $groups);
    }

    /**
     * This plugin's per-product settings live entirely on the product-data tab, so this small
     * page exists solely to host the "more plugins by this author" panel -- kept minimal rather
     * than skipped, so every hdwebmobile plugin offers the same discovery path.
     */
    public function register_hub_tabs($tabs)
    {
        $tabs['product-options'] = array(
            'label'  => __('Product Options & Add-ons', 'hdwebmobile-product-options'),
            'order'  => 100,
            'render' => array($this, 'render_plugins_page'),
        );
        return $tabs;
    }

    public function render_plugins_page()
    {
        ?>
        <p><?php esc_html_e('Add paid text, dropdown, and checkbox options to products (engraving, gift wrap, size upgrades) -- flat pricing only, no formula evaluation. There\'s nothing to configure here -- go to any product\'s own "Options & Add-ons" tab under Product Data to add its options.', 'hdwebmobile-product-options'); ?></p>
        <?php
    }
}
