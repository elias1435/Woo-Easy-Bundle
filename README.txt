=== Woo Easy Bundle ===
Contributors: Muhammad Elias
Author URI: https://buildwithelias.com/
Tags: woocommerce, product bundles, bundle products, grouped products, discounts
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create easy WooCommerce bundle products by selecting parent products, setting one discounted bundle price, and letting customers choose variation options on the product page.

== Description ==

Woo Easy Bundle is a lightweight WooCommerce bundle helper built for store owners who want a simple way to sell fixed bundle offers without complex setup.

Instead of selecting every variation one by one, you can select the main parent products included in a bundle. If a selected product has variations, customers choose the needed options directly on the bundle product page before adding the bundle to cart.

Example:

Create a product called "Built Different Bundle" and select:
* Hoodie
* Mug
* Cap

Then set the bundle price directly in the normal WooCommerce product price field. Customers can choose hoodie size/color, mug size, cap color, and purchase the bundle as one product.

== Features ==

* Create bundle offers using a normal WooCommerce Simple Product
* Select parent products as bundle items
* Support variable products with customer option dropdowns
* Set one discounted bundle price using WooCommerce regular/sale price fields
* Show included bundle products on the product page
* Save selected bundle options in cart and order details
* Display bundle value and savings when child product prices are available
* Reduce stock for selected child products/variations when stock management is enabled
* Lightweight and easy to manage

== Installation ==

1. Upload the plugin ZIP from WordPress Admin → Plugins → Add New → Upload Plugin.
2. Activate Woo Easy Bundle.
3. Make sure WooCommerce is installed and active.
4. Create or edit a WooCommerce Simple Product.
5. Set your discounted bundle price in Product data → General.
6. Open Product data → Easy Bundle.
7. Enable the bundle option.
8. Select the parent products included in the bundle.
9. Publish or update the product.

== How to Use ==

1. Create individual products first, for example:
   * Hoodie
   * Mug
   * Cap

2. Create a new Simple Product for the bundle, for example:
   * Built Different Bundle

3. Set the bundle price:
   * Regular price: 79

4. Open the Easy Bundle tab and select:
   * Hoodie
   * Mug
   * Cap

5. Customers will see the bundle on the product page and select the product options before adding to cart.

== Notes ==

* This plugin creates a fixed bundle offer sold as one WooCommerce product.
* The bundle price is controlled by the bundle product's normal WooCommerce price.
* For variable products, select the parent product only.
* If child products manage stock, stock is reduced for the selected product/variation when WooCommerce reduces order stock.
* This plugin does not create separate line items for each child product in the cart; selections are saved as metadata on the bundle item.

== Frequently Asked Questions ==

= Can I select a variable product instead of every variation? =

Yes. Select the parent variable product. Customers will choose the variation on the bundle product page.

= How do I set the bundle discount price? =

Set the price on the bundle product itself under Product data → General.

= Does it work with simple products? =

Yes. Simple products appear as included items without requiring customer selection.

= Does it manage stock? =

If the selected child product or variation is set to manage stock, stock can be reduced when WooCommerce reduces order stock.

== Changelog ==

= 1.2.0 =
* Renamed plugin to Woo Easy Bundle.
* Added parent product selection workflow.
* Rendered bundle options inside the WooCommerce cart form.
* Added support for variation selection on bundle product pages.
* Added cart/order metadata for selected bundle options.
