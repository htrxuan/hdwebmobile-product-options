# HDWebmobile Product Options & Add-ons

Add paid text, dropdown, and checkbox options to products (engraving, gift wrap, size upgrades) -- flat pricing only, no formula evaluation.

- **WordPress.org:** https://wordpress.org/plugins/hdwebmobile-product-options/
- **Requires:** WordPress 6.9+, WooCommerce, PHP 7.4+
- **License:** GPLv2 or later

## Description

HDWebmobile Product Options & Add-ons lets you add paid customization options to any simple product: a text field for engraving, a dropdown for a size or material upgrade, or a checkbox for gift wrap. Each option can add a flat amount to the price, and shoppers see a live running total as they make their selections — before they even click Add to Cart.

Every price is a flat amount you enter yourself and it's looked up fresh from the product whenever something is added to the cart — there is no formula language, expression evaluator, or anything resembling `eval()` anywhere in this plugin. That's a deliberate design choice: a well-known paid "product add-ons" plugin for WooCommerce disclosed a critical, unauthenticated Remote Code Execution vulnerability caused by unsafely evaluating merchant-defined pricing formulas. This plugin gets the same practical result (customers pay more for the options they pick) without that entire class of risk.

## Features

* Text, dropdown, and checkbox product options, each with an optional flat price adjustment
* A live price preview updates on the product page as customers fill in options, before adding to cart
* Works correctly everywhere in WooCommerce: the product page, cart, Mini-Cart, Checkout (classic and block-based), the order confirmation page, order emails, and the admin Edit Order screen -- all using WooCommerce's own standard cart/order mechanisms, not a separate parallel system
* Required options are enforced on the server, not just with a browser-side `required` attribute -- a request that skips a required option is rejected before it reaches the cart
* Every submitted option value is re-validated against the product's own current configuration before any price is applied, so a tampered request can't fabricate a discount or a fake option
* No formula evaluation of any kind -- every price is a flat, merchant-entered amount

## Development

Standard WordPress plugin structure:

```
hdwebmobile-product-options.php    Bootstrap
includes/class-hdpo-activator.php
includes/class-hdpo-admin.php
includes/class-hdpo-cart.php
includes/class-hdpo-core.php
includes/class-hdpo-frontend.php
includes/class-hdpo-hub.php
includes/class-hdpo-options.php
```

Part of the [HDWebmobile](https://hdwebmobile.com/plugins/) suite of focused, single-purpose WooCommerce plugins.

## License

GPLv2 or later. See [LICENSE](LICENSE).

