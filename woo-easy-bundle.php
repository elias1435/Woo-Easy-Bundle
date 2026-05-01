<?php
/**
 * Plugin Name: Woo Easy Bundle
 * Description: Create easy WooCommerce bundle products by selecting parent products, setting one discounted bundle price, and letting customers choose variation options on the product page.
 * Version: 1.2.0
 * Author: Muhammad Elias
 * Requires Plugins: woocommerce
 * Author URI: https://buildwithelias.com/
 */

if (!defined('ABSPATH')) {
    exit;
}

class Woo_Easy_Bundle {

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        add_filter('woocommerce_product_data_tabs', array($this, 'add_bundle_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_bundle_panel'));
        add_action('woocommerce_admin_process_product_object', array($this, 'save_bundle_fields'));

        add_action('wp_enqueue_scripts', array($this, 'frontend_assets'));
        /*
         * IMPORTANT:
         * Bundle option selects must be inside WooCommerce's <form class="cart">.
         * If they render before the form, selected options do not submit to cart.
         */
        add_action('woocommerce_before_add_to_cart_button', array($this, 'show_bundle_box'), 5);

        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_bundle_add_to_cart'), 10, 3);
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_bundle_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'show_cart_bundle_items'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_bundle_order_meta'), 10, 4);

        add_filter('woocommerce_get_price_html', array($this, 'bundle_price_html'), 20, 2);

        add_action('woocommerce_reduce_order_stock', array($this, 'reduce_bundled_product_stock'), 10, 1);
        add_action('woocommerce_restore_order_stock', array($this, 'restore_bundled_product_stock'), 10, 1);
    }

    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p><strong>Woo Easy Bundle</strong> requires WooCommerce to be installed and active.</p></div>';
    }

    public function add_bundle_tab($tabs) {
        $tabs['mosee_bundle'] = array(
            'label'    => __('Easy Bundle', 'woo-easy-bundle'),
            'target'   => 'mosee_bundle_product_data',
            'class'    => array('show_if_simple'),
            'priority' => 70,
        );

        return $tabs;
    }

    public function add_bundle_panel() {
        global $post;

        $product = wc_get_product($post->ID);
        $is_bundle = $product ? $product->get_meta('_mosee_is_bundle') : '';
        $bundle_items = $product ? (array) $product->get_meta('_mosee_bundle_items') : array();
        $badge_text = $product ? $product->get_meta('_mosee_bundle_badge') : '';
        $subtitle = $product ? $product->get_meta('_mosee_bundle_subtitle') : '';

        ?>
        <div id="mosee_bundle_product_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <?php
                woocommerce_wp_checkbox(array(
                    'id'          => '_mosee_is_bundle',
                    'label'       => __('Enable bundle', 'woo-easy-bundle'),
                    'description' => __('Turn this simple product into a bundle offer. Select parent/simple products below. Customers can choose variation options on the bundle page.', 'woo-easy-bundle'),
                    'value'       => $is_bundle,
                ));

                woocommerce_wp_text_input(array(
                    'id'          => '_mosee_bundle_badge',
                    'label'       => __('Bundle badge', 'woo-easy-bundle'),
                    'placeholder' => 'Example: Premium 3 Piece Bundle',
                    'value'       => $badge_text,
                    'desc_tip'    => true,
                    'description' => __('Small label shown above the bundle items.', 'woo-easy-bundle'),
                ));

                woocommerce_wp_textarea_input(array(
                    'id'          => '_mosee_bundle_subtitle',
                    'label'       => __('Bundle short note', 'woo-easy-bundle'),
                    'placeholder' => 'Example: Hoodie + Mug + Cap included in this bundle.',
                    'value'       => $subtitle,
                    'desc_tip'    => true,
                    'description' => __('Short note shown on the product page under the badge.', 'woo-easy-bundle'),
                ));
                ?>

                <p class="form-field _mosee_bundle_items_field">
                    <label for="_mosee_bundle_items"><?php esc_html_e('Bundled products', 'woo-easy-bundle'); ?></label>

                    <select
                        class="wc-product-search"
                        multiple="multiple"
                        style="width: 50%;"
                        id="_mosee_bundle_items"
                        name="_mosee_bundle_items[]"
                        data-placeholder="<?php esc_attr_e('Search and select products...', 'woo-easy-bundle'); ?>"
                        data-action="woocommerce_json_search_products"
                    >
                        <?php
                        foreach ($bundle_items as $product_id) {
                            $child_product = wc_get_product($product_id);

                            if ($child_product) {
                                echo '<option value="' . esc_attr($product_id) . '" selected="selected">' . esc_html($child_product->get_formatted_name()) . '</option>';
                            }
                        }
                        ?>
                    </select>

                    <span class="description">
                        <?php esc_html_e('Select the main/simple products included in this bundle. For variable products, select the parent product only. Customers will choose the variation on the bundle product page.', 'woo-easy-bundle'); ?>
                    </span>
                </p>

                <p class="form-field">
                    <strong><?php esc_html_e('Pricing note:', 'woo-easy-bundle'); ?></strong>
                    <?php esc_html_e('Set the discounted bundle price in Product data → General → Regular price / Sale price.', 'woo-easy-bundle'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    public function save_bundle_fields($product) {
        $is_bundle = isset($_POST['_mosee_is_bundle']) ? 'yes' : 'no';
        $product->update_meta_data('_mosee_is_bundle', $is_bundle);

        $badge = isset($_POST['_mosee_bundle_badge']) ? sanitize_text_field(wp_unslash($_POST['_mosee_bundle_badge'])) : '';
        $subtitle = isset($_POST['_mosee_bundle_subtitle']) ? sanitize_textarea_field(wp_unslash($_POST['_mosee_bundle_subtitle'])) : '';

        $product->update_meta_data('_mosee_bundle_badge', $badge);
        $product->update_meta_data('_mosee_bundle_subtitle', $subtitle);

        $items = array();

        if (isset($_POST['_mosee_bundle_items']) && is_array($_POST['_mosee_bundle_items'])) {
            $items = array_filter(array_map('absint', wp_unslash($_POST['_mosee_bundle_items'])));
            $items = array_values(array_unique($items));
        }

        $product->update_meta_data('_mosee_bundle_items', $items);
    }

    private function is_bundle_product($product) {
        return $product && is_a($product, 'WC_Product') && $product->get_meta('_mosee_is_bundle') === 'yes';
    }

    private function get_bundle_items($product) {
        $items = (array) $product->get_meta('_mosee_bundle_items');
        return array_values(array_filter(array_map('absint', $items)));
    }

    private function get_selected_bundle_items_from_request($bundle_product) {
        $selected = array();
        $bundle_item_ids = $this->get_bundle_items($bundle_product);
        $posted = isset($_POST['mosee_bundle_selection']) && is_array($_POST['mosee_bundle_selection'])
            ? wc_clean(wp_unslash($_POST['mosee_bundle_selection']))
            : array();

        foreach ($bundle_item_ids as $parent_id) {
            $child_product = wc_get_product($parent_id);

            if (!$child_product) {
                continue;
            }

            if ($child_product->is_type('variable')) {
                $variation_id = isset($posted[$parent_id]) ? absint($posted[$parent_id]) : 0;
                $variation = $variation_id ? wc_get_product($variation_id) : false;

                if (!$variation || !$variation->is_type('variation') || (int) $variation->get_parent_id() !== (int) $parent_id) {
                    $selected[] = array(
                        'parent_id'    => $parent_id,
                        'product_id'   => 0,
                        'variation_id' => 0,
                        'name'         => $child_product->get_name(),
                        'valid'        => false,
                    );
                    continue;
                }

                $selected[] = array(
                    'parent_id'    => $parent_id,
                    'product_id'   => $variation_id,
                    'variation_id' => $variation_id,
                    'name'         => $child_product->get_name() . ' - ' . wc_get_formatted_variation($variation, true, false, true),
                    'valid'        => true,
                );

            } else {
                $selected[] = array(
                    'parent_id'    => $parent_id,
                    'product_id'   => $parent_id,
                    'variation_id' => 0,
                    'name'         => $child_product->get_name(),
                    'valid'        => true,
                );
            }
        }

        return $selected;
    }

    public function frontend_assets() {
        if (!is_product()) {
            return;
        }

        wp_register_style('woo-easy-bundle', false);
        wp_enqueue_style('woo-easy-bundle');

        $css = '
            .mosee-bundle-box{
                margin:24px 0;
                padding:22px;
                border:1px solid rgba(212,175,55,.35);
                border-radius:16px;
                background:linear-gradient(180deg,rgba(212,175,55,.08),rgba(0,0,0,.03));
            }
            .mosee-bundle-badge{
                display:inline-block;
                padding:6px 11px;
                border:1px solid rgba(212,175,55,.45);
                border-radius:999px;
                color:#d4af37;
                font-size:12px;
                font-weight:700;
                letter-spacing:.06em;
                text-transform:uppercase;
                margin-bottom:12px;
            }
            .mosee-bundle-box h3{
                margin:0 0 10px;
            }
            .mosee-bundle-note{
                margin:0 0 14px;
                opacity:.78;
            }
            .mosee-bundle-items{
                list-style:none;
                padding:0;
                margin:0;
                display:grid;
                gap:12px;
            }
            .mosee-bundle-items li{
                display:grid;
                grid-template-columns:54px 1fr;
                gap:12px;
                align-items:center;
                padding:12px;
                border-radius:12px;
                background:rgba(255,255,255,.04);
            }
            .mosee-bundle-items img{
                width:54px;
                height:54px;
                object-fit:cover;
                border-radius:10px;
            }
            .mosee-bundle-item-title{
                display:block;
                font-weight:700;
                margin-bottom:6px;
            }
            .mosee-bundle-option select{
                width:100%;
                min-height:42px;
            }
            .mosee-bundle-fixed{
                opacity:.78;
                font-size:13px;
            }
            .mosee-bundle-savings{
                margin-top:14px;
                font-weight:700;
                color:#d4af37;
            }
        ';

        wp_add_inline_style('woo-easy-bundle', $css);
    }

    public function show_bundle_box() {
        global $product;

        if (!$this->is_bundle_product($product)) {
            return;
        }

        $item_ids = $this->get_bundle_items($product);

        if (empty($item_ids)) {
            return;
        }

        $badge = $product->get_meta('_mosee_bundle_badge');
        $subtitle = $product->get_meta('_mosee_bundle_subtitle');
        $regular_total = 0;

        echo '<div class="mosee-bundle-box">';

        if (!empty($badge)) {
            echo '<div class="mosee-bundle-badge">' . esc_html($badge) . '</div>';
        }

        echo '<h3>' . esc_html__('Choose your bundle options', 'woo-easy-bundle') . '</h3>';

        if (!empty($subtitle)) {
            echo '<p class="mosee-bundle-note">' . esc_html($subtitle) . '</p>';
        }

        echo '<ul class="mosee-bundle-items">';

        foreach ($item_ids as $item_id) {
            $item_product = wc_get_product($item_id);

            if (!$item_product) {
                continue;
            }

            $regular_total += (float) $item_product->get_regular_price();

            echo '<li>';
            echo $item_product->get_image('woocommerce_thumbnail');
            echo '<div>';
            echo '<span class="mosee-bundle-item-title">' . esc_html($item_product->get_name()) . '</span>';

            if ($item_product->is_type('variable')) {
                $variations = $item_product->get_available_variations();

                echo '<div class="mosee-bundle-option">';
                echo '<select name="mosee_bundle_selection[' . esc_attr($item_id) . ']" required>';
                echo '<option value="">' . esc_html__('Choose option', 'woo-easy-bundle') . '</option>';

                foreach ($variations as $variation_data) {
                    $variation_id = isset($variation_data['variation_id']) ? absint($variation_data['variation_id']) : 0;
                    $variation = $variation_id ? wc_get_product($variation_id) : false;

                    if (!$variation || !$variation->is_purchasable() || !$variation->is_in_stock()) {
                        continue;
                    }

                    $label = wc_get_formatted_variation($variation, true, false, true);

                    if (empty($label)) {
                        $label = $variation->get_name();
                    }

                    echo '<option value="' . esc_attr($variation_id) . '">' . esc_html($label) . '</option>';
                }

                echo '</select>';
                echo '</div>';

            } else {
                echo '<span class="mosee-bundle-fixed">' . esc_html__('Included', 'woo-easy-bundle') . '</span>';
            }

            echo '</div>';
            echo '</li>';
        }

        echo '</ul>';

        $bundle_price = (float) $product->get_price();

        if ($regular_total > 0 && $bundle_price > 0 && $regular_total > $bundle_price) {
            $savings = $regular_total - $bundle_price;

            echo '<div class="mosee-bundle-savings">';
            echo esc_html__('Bundle value:', 'woo-easy-bundle') . ' ' . wp_kses_post(wc_price($regular_total));
            echo ' — ';
            echo esc_html__('You save:', 'woo-easy-bundle') . ' ' . wp_kses_post(wc_price($savings));
            echo '</div>';
        }

        echo '</div>';
    }

    public function validate_bundle_add_to_cart($passed, $product_id, $quantity) {
        $product = wc_get_product($product_id);

        if (!$this->is_bundle_product($product)) {
            return $passed;
        }

        $selected = $this->get_selected_bundle_items_from_request($product);

        foreach ($selected as $item) {
            if (empty($item['valid']) || empty($item['product_id'])) {
                wc_add_notice(sprintf(__('Please choose an option for %s.', 'woo-easy-bundle'), esc_html($item['name'])), 'error');
                return false;
            }

            $selected_product = wc_get_product($item['product_id']);

            if (!$selected_product || !$selected_product->is_purchasable() || !$selected_product->is_in_stock()) {
                wc_add_notice(sprintf(__('%s is not currently available.', 'woo-easy-bundle'), esc_html($item['name'])), 'error');
                return false;
            }
        }

        return $passed;
    }

    public function add_bundle_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $product = wc_get_product($product_id);

        if (!$this->is_bundle_product($product)) {
            return $cart_item_data;
        }

        $selected = $this->get_selected_bundle_items_from_request($product);

        $cart_item_data['mosee_bundle_selected_items'] = $selected;
        $cart_item_data['mosee_bundle_key'] = md5(wp_json_encode($selected) . microtime());

        return $cart_item_data;
    }

    public function show_cart_bundle_items($item_data, $cart_item) {
        if (empty($cart_item['mosee_bundle_selected_items'])) {
            return $item_data;
        }

        $names = array();

        foreach ((array) $cart_item['mosee_bundle_selected_items'] as $item) {
            if (!empty($item['name'])) {
                $names[] = $item['name'];
            }
        }

        if (!empty($names)) {
            $item_data[] = array(
                'key'   => __('Bundle selections', 'woo-easy-bundle'),
                'value' => esc_html(implode(', ', $names)),
            );
        }

        return $item_data;
    }

    public function add_bundle_order_meta($item, $cart_item_key, $values, $order) {
        if (empty($values['mosee_bundle_selected_items'])) {
            return;
        }

        $names = array();

        foreach ((array) $values['mosee_bundle_selected_items'] as $bundle_item) {
            if (!empty($bundle_item['name'])) {
                $names[] = $bundle_item['name'];
            }
        }

        if (!empty($names)) {
            $item->add_meta_data(__('Bundle selections', 'woo-easy-bundle'), implode(', ', $names), true);
            $item->add_meta_data('_mosee_bundle_selected_items', $values['mosee_bundle_selected_items'], true);
        }
    }

    public function bundle_price_html($price_html, $product) {
        if (!$this->is_bundle_product($product)) {
            return $price_html;
        }

        $item_ids = $this->get_bundle_items($product);
        $regular_total = 0;

        foreach ($item_ids as $item_id) {
            $item_product = wc_get_product($item_id);

            if ($item_product) {
                $regular_total += (float) $item_product->get_regular_price();
            }
        }

        if ($regular_total > 0 && (float) $product->get_price() > 0 && $regular_total > (float) $product->get_price()) {
            return '<span class="mosee-bundle-price"><del>' . wc_price($regular_total) . '</del> <ins>' . wc_price($product->get_price()) . '</ins></span>';
        }

        return $price_html;
    }

    public function reduce_bundled_product_stock($order) {
        if (!$order || !is_a($order, 'WC_Order')) {
            return;
        }

        if ($order->get_meta('_mosee_bundle_stock_reduced') === 'yes') {
            return;
        }

        foreach ($order->get_items() as $order_item) {
            $selected_items = $order_item->get_meta('Bundle selections');

            $product = $order_item->get_product();

            if (!$this->is_bundle_product($product)) {
                continue;
            }

            $bundle_quantity = max(1, (int) $order_item->get_quantity());
            $bundle_items = $order_item->get_meta('_mosee_bundle_selected_items');

            if (empty($bundle_items)) {
                $bundle_items = array();
            }

            foreach ((array) $bundle_items as $bundle_item) {
                if (empty($bundle_item['product_id'])) {
                    continue;
                }

                $child_product = wc_get_product(absint($bundle_item['product_id']));

                if ($child_product && $child_product->managing_stock()) {
                    wc_update_product_stock($child_product, $bundle_quantity, 'decrease');
                }
            }
        }

        $order->update_meta_data('_mosee_bundle_stock_reduced', 'yes');
        $order->save();
    }

    public function restore_bundled_product_stock($order) {
        if (!$order || !is_a($order, 'WC_Order')) {
            return;
        }

        if ($order->get_meta('_mosee_bundle_stock_reduced') !== 'yes') {
            return;
        }

        foreach ($order->get_items() as $order_item) {
            $product = $order_item->get_product();

            if (!$this->is_bundle_product($product)) {
                continue;
            }

            $bundle_quantity = max(1, (int) $order_item->get_quantity());
            $bundle_items = $order_item->get_meta('_mosee_bundle_selected_items');

            foreach ((array) $bundle_items as $bundle_item) {
                if (empty($bundle_item['product_id'])) {
                    continue;
                }

                $child_product = wc_get_product(absint($bundle_item['product_id']));

                if ($child_product && $child_product->managing_stock()) {
                    wc_update_product_stock($child_product, $bundle_quantity, 'increase');
                }
            }
        }

        $order->delete_meta_data('_mosee_bundle_stock_reduced');
        $order->save();
    }
}

new Woo_Easy_Bundle();
