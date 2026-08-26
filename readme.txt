=== HDWebmobile Product Options & Add-ons ===
Contributors: htrxuan
Donate link: https://paypal.me/htrxuan/20
Tags: woocommerce, product options, product add-ons, custom fields, price adjustment
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add paid text, dropdown, and checkbox options to products (engraving, gift wrap, size upgrades) -- flat pricing only, no formula evaluation.

== Description ==

HDWebmobile Product Options & Add-ons lets you add paid customization options to any simple product: a text field for engraving, a dropdown for a size or material upgrade, or a checkbox for gift wrap. Each option can add a flat amount to the price, and shoppers see a live running total as they make their selections — before they even click Add to Cart.

Every price is a flat amount you enter yourself and it's looked up fresh from the product whenever something is added to the cart — there is no formula language, expression evaluator, or anything resembling `eval()` anywhere in this plugin. That's a deliberate design choice: a well-known paid "product add-ons" plugin for WooCommerce disclosed a critical, unauthenticated Remote Code Execution vulnerability caused by unsafely evaluating merchant-defined pricing formulas. This plugin gets the same practical result (customers pay more for the options they pick) without that entire class of risk.

= Key Features =
* Text, dropdown, and checkbox product options, each with an optional flat price adjustment
* A live price preview updates on the product page as customers fill in options, before adding to cart
* Works correctly everywhere in WooCommerce: the product page, cart, Mini-Cart, Checkout (classic and block-based), the order confirmation page, order emails, and the admin Edit Order screen -- all using WooCommerce's own standard cart/order mechanisms, not a separate parallel system
* Required options are enforced on the server, not just with a browser-side `required` attribute -- a request that skips a required option is rejected before it reaches the cart
* Every submitted option value is re-validated against the product's own current configuration before any price is applied, so a tampered request can't fabricate a discount or a fake option
* No formula evaluation of any kind -- every price is a flat, merchant-entered amount

= Limitations (please read before installing) =
* Simple products only -- variable, grouped, and external products aren't supported in this version
* Flat-amount pricing only -- there's no percentage-of-price option and no pricing formulas; this is a deliberate security boundary as well as a simplicity one (see Description)
* No conditional logic between options (e.g. showing one option only when another has a specific value)
* Options are configured per product -- there's no shared, reusable option set applied across multiple products in this version

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/hdwebmobile-product-options` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress. WooCommerce must already be installed and active.
3. Edit a simple product, open the new "Options & Add-ons" tab under Product Data, and add your options.

== How to Use ==

= 1. Open a simple product for editing =
Go to **Products** and open any *simple* product (the tab only appears for simple products in this version).

= 2. Add your options =
Click the new **Options & Add-ons** tab under Product Data (Screenshot 1). Click **+ Add Option** for each option you want: give it a label, choose Text field, Dropdown, or Checkbox, optionally mark it required, and set a price adjustment. For a Dropdown, click **+ Add Choice** to add each choice with its own price. Click **Update** to save.

= 3. What shoppers see =
On the product page, your options appear above the Add to Cart button (Screenshot 2), each showing its price adjustment. As shoppers fill them in, a live "Total with options" preview updates automatically.

= 4. Cart, Checkout, and orders =
The chosen options and their price adjustments show on the cart, Mini-Cart, and Checkout (Screenshot 3), on the order confirmation page and order emails, and on the admin Edit Order screen (Screenshot 4) -- all automatically, using WooCommerce's own standard display mechanisms.

== Screenshots ==

1. The "Options & Add-ons" tab in Product Data, adding text, dropdown, and checkbox options.
2. The options on a real product page, with the live price preview.
3. The selected options shown on Cart/Checkout with their price adjustments.
4. The selected options shown on the admin Edit Order screen.

== Changelog ==

= 1.0.0 =
* Initial release: text/dropdown/checkbox product options with flat price adjustments, live price preview, server-side required-option enforcement, server-side re-validation of every submitted option against the product's current configuration.
