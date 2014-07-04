=== Plugin Name ===
Contributors: megamenu
Tags: menu, mega menu, menu icons, menu style, responsive menu, megamenu, widget, dropdown menu, drag and drop, hover, click, responsive, retina, theme editor
Requires at least: 3.9
Tested up to: 3.9
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Mega Menu Plugin for WordPress.

== Description ==

Mega Menu Plugin for WordPress. Use the built in drag and drop widget editor to build your mega panels.

Documentation: http://www.megamenu.co.uk

Features:

* Build your menu panels using a drag and drop Widget area
* Choose Flyout (traditional) or Mega Menu menu dropdowns
* Choose menu activation on either hover or click
* Compatible with touch screen devices
* Choose an icon (dashicons) to show next to each menu item
* Choose from 3 'down' arrow icon styles
* Create your own themes using a built in theme editor
* Works with multiple menu's on the same page
* Works with menu's tagged to multiple Theme Locations
* Less than 1kb JavaScript when gzipped (also works when JS is disabled)
* Tested in IE9+, FireFox, Opera, Safari & Chrome (IE6, 7 & 8 are not supported but may work)
* Responsive
* Retina Ready
* Safe: You can uninstall and go back to your old menu

The technical stuff:

* Mega Menu will not pick up styling from your old menu, but the built in theme editor will allow you to tailor your Mega Menu styling to your theme.
* Your theme will need a registered Theme Location to work
* The menu CSS is dynamically parsed SCSS. Developers can create their own SCSS file if needed - just copy the megamenu.css file to your theme directory and make any required edits.
* The parsed SCSS is cached for performance. The cache is refreshed when a menu is saved or a theme has been created/updated.
* Mega Menu is compatible with Widget & Menu Output Cache plugin (https://wordpress.org/plugins/widget-output-cache/) as well as WP Super Cache.
* Behind the scenes, all menu widgets are stored as standard WordPress widgets in a new widget area that the plugin creates.

Recommended Widgets:

* Image Widget
* Contact Form 7 Widget
* Very Simple Google Maps (this only gives a shortcode, so install the ShortCode Widget and use something like `[vsgmap address="your address, country" width='100%' height='200']`)

Tested with the 20 most popular themes, all compatible with the exceptions of:

* Tesla: compatible but requires edits: open header.php and remove the second call to wp_nav_menu (line 130 - 147)
* Vantage: compatible (but hover only)
* Stargazer: compatible (but hover only)

== Installation ==

1. Go to the Plugins Menu in WordPress
1. Search for "Mega Menu"
1. Click "Install"

== Frequently Asked Questions ==

http://www.megamenu.co.uk

== Screenshots ==

See http://www.megamenu.co.uk for more screenshots

1. New menu changes
2. Drag and Drop widget editor
3. Front end: Mega Menu
4. Front end: Flyout Menu

== Changelog ==

= 1.0 =
* Initial version

== Upgrade Notice ==
