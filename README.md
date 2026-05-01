# Woo Easy Bundle

**Woo Easy Bundle** is a lightweight WooCommerce plugin that lets you create simple bundle offers by selecting existing products, setting one discounted bundle price, and allowing customers to choose variation options on the bundle product page.

Author: **Muhammad Elias**  
Author URI: https://buildwithelias.com/

## Preview

![Woo Easy Bundle Preview](assets/woo-easy-bundle-preview.png)

## What it does

Woo Easy Bundle lets you create a normal WooCommerce **Simple Product** and turn it into a bundle offer.

For example, create a bundle product called **Built Different Bundle**, set the price to **$79**, then select:

- Hoodie
- Mug
- Cap

If Hoodie, Mug, or Cap has variations, the customer can choose those options on the bundle product page before adding the bundle to cart.

## Features

- Create bundle offers from a normal WooCommerce Simple Product
- Select parent/simple products as bundle items
- Support variable products with customer option dropdowns
- Set one discounted bundle price using WooCommerce pricing fields
- Show included bundle items on the product page
- Save bundle selections in cart and order details
- Display bundle value and savings when child prices are available
- Reduce stock for selected products/variations if stock management is enabled

## Installation

1. Download the plugin ZIP.
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**.
3. Upload and activate **Woo Easy Bundle**.
4. Make sure WooCommerce is active.

## Usage

1. Create your individual products first.
2. Create a new WooCommerce **Simple Product** for the bundle.
3. Set the bundle price in **Product data → General**.
4. Open **Product data → Easy Bundle**.
5. Check **Enable bundle**.
6. Select the products included in the bundle.
7. Publish/update the bundle product.

## Notes

- The customer buys one bundle product.
- Selected bundle options are stored as cart/order metadata.
- Child products are not added as separate cart line items.
- Variable product parents can be selected; customers choose variations on the frontend.

## License

GPLv2 or later.
