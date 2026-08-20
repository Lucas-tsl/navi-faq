=== Navi FAQ ===
Contributors: lucastsl
Tags: faq, woocommerce, schema, json-ld, accordion
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

FAQ (questions/answers) on posts, pages, products and WooCommerce product categories, with FAQPage schema (JSON-LD).

== Description ==

Navi FAQ adds questions/answers to posts, pages, **products, and
WooCommerce product categories** — most existing FAQ plugins cover the
first three content types but not categories, since there is no native
post-meta equivalent for a taxonomy term.

**Main features**

* **Coverage**: posts, pages, products, product categories — extendable to
  other content types or taxonomies via the `navi_faq_post_types` and
  `navi_faq_taxonomies` filters.
* **Admin**: a FAQ box on the post/page/product edit screen, and the same
  field on the category edit screen — shared interface, add/remove
  questions in JavaScript, no page reload.
* **Optional theme grouping**: giving the same theme to several questions
  (e.g. "Shipping", "The product") automatically displays them under a
  shared tab on the front end. A single theme in use (or none): plain
  accordion, no unnecessary tabs.
* **Display**:
  * `[navi_faq]` — FAQ for the current context (the post/page/product being
    viewed, or a covered category archive page).
  * `[navi_faq_all]` — every FAQ on the site, grouped by title, for a
    centralized "Frequently Asked Questions" page.
  * Automatic display at the top of category archive pages (there is no
    content area to manually place a shortcode on this page type).
* **FAQPage schema (JSON-LD)** generated server-side, only where the
  content is actually visible: always on a covered category, only if
  `[navi_faq]` is placed in the content on a post/page/product — in line
  with Google's structured data guidelines.
* **Accessibility**: accordion built on native `<details>`/`<summary>`
  (works even without JavaScript), left/right/Home/End arrow-key
  navigation between theme tabs (the
  [ARIA Tabs pattern](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/)).

== Installation ==

1. Upload the `navi-faq` folder to `/wp-content/plugins/`, or install
   directly from **Plugins > Add New**.
2. Activate the plugin from the **Plugins** menu.
3. Edit a post, page, or product: a "FAQ (Navi)" box appears below the
   content editor.
4. For product categories (requires WooCommerce active): **Products >
   Categories**, edit a category — the same form appears there.
5. Place the `[navi_faq]` shortcode in a post/page/product's content to
   display it (categories display automatically).

== Frequently Asked Questions ==

= Is WooCommerce required? =

No. Without WooCommerce, the "product" post type and "product category"
taxonomy simply don't exist: the plugin keeps working normally on posts
and pages.

= How do I group questions by theme? =

In the FAQ box (post/page/product) or FAQ field (category), give the same
text in the "Theme" field on several questions — they then automatically
display under a shared tab on the front end.

= Does the FAQPage schema output on every page? =

No, only where the FAQ is actually displayed: automatically on a covered
category archive page, and on a post/page/product only if the `[navi_faq]`
shortcode is placed in its content.

== Screenshots ==

1. FAQ box on a product edit screen, with the optional "Theme" field.
2. Theme-grouped FAQ displayed as tabs on the front end.

== Changelog ==

= 0.1.0 =
* Initial release: FAQ on posts/pages/products/product categories, theme
  grouping, FAQPage schema (JSON-LD), native `<details>`/`<summary>`
  accordion, keyboard navigation between tabs.

== Upgrade Notice ==

= 0.1.0 =
Initial release.
