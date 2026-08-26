<?php

namespace htrxuan\hdpo;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Single source of truth for reading/writing a product's option groups. Both the admin save
 * handler and the cart-pricing code go through here, so the cart-pricing code always looks up
 * the authoritative price fresh from the database rather than trusting anything submitted by
 * the shopper's browser.
 */
class HDPO_Options
{

    const META_KEY = '_hdpo_options';
    const ALLOWED_TYPES = array('text', 'select', 'checkbox');

    public static function get_options($product_id)
    {
        $options = get_post_meta($product_id, self::META_KEY, true);
        return is_array($options) ? $options : array();
    }

    public static function get_group($product_id, $group_index)
    {
        $options = self::get_options($product_id);
        $group_index = (int) $group_index;
        return isset($options[$group_index]) ? $options[$group_index] : null;
    }

    public static function save_options($product_id, array $groups)
    {
        $sanitized = array();

        foreach ($groups as $group) {
            $label = isset($group['label']) ? sanitize_text_field($group['label']) : '';
            if ('' === $label) {
                // Skip blank rows (e.g. the JS repeater's unused template row, or a row the
                // merchant added then left empty).
                continue;
            }

            $type = isset($group['type']) && in_array($group['type'], self::ALLOWED_TYPES, true) ? $group['type'] : 'text';

            $sanitized_group = array(
                'label'    => $label,
                'type'     => $type,
                'required' => !empty($group['required']) ? '1' : '',
                'price'    => isset($group['price']) && '' !== $group['price'] ? wc_format_decimal($group['price']) : '',
                'choices'  => array(),
            );

            if ('select' === $type && !empty($group['choices']) && is_array($group['choices'])) {
                foreach ($group['choices'] as $choice) {
                    $choice_label = isset($choice['label']) ? sanitize_text_field($choice['label']) : '';
                    if ('' === $choice_label) {
                        continue;
                    }
                    $sanitized_group['choices'][] = array(
                        'label' => $choice_label,
                        'price' => isset($choice['price']) && '' !== $choice['price'] ? wc_format_decimal($choice['price']) : '0',
                    );
                }
            }

            $sanitized[] = $sanitized_group;
        }

        if (empty($sanitized)) {
            delete_post_meta($product_id, self::META_KEY);
        } else {
            update_post_meta($product_id, self::META_KEY, $sanitized);
        }
    }
}
