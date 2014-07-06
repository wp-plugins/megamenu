=== Plugin Name ===
Contributors: megamenu
Tags: menu, mega menu, menu icons, menu style, responsive menu, megamenu, widget, dropdown menu, drag and drop, hover, click, responsive, retina, theme editor
Requires at least: 3.8
Tested up to: 3.9
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easy to use drag & drop mega menu builder. Integrates with the existing WordPress 3 menu system. Responsive, retina & touch ready.

== Description ==

Mega Menu Plugin for WordPress. Use the built in drag & drop widget editor to build your mega panels.

Documentation & Demo: http://www.megamenu.co.uk

Features:

* Drag & drop widget editor 
* 6 column panels (wigets can span multiple columns)
* Flyout (traditional) or Mega Menu menu styles
* Menu Icons
* Activate Menu on either hover (intent) or click
* Compatible with touch screen devices
* 3 'down' arrow icon styles
* Built in theme editor
* Works with multiple menus on the same page
* Works with menus tagged to multiple Theme Locations
* < 1kb JavaScript when gzipped (also works when JS is disabled)
* Responsive
* Retina Ready
* Safe: You can uninstall and go back to your old menu
* Tested in IE9+, FireFox, Opera, Safari & Chrome (IE6, 7 & 8 are not supported but may work)

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
2. Drag and Drop widget editor for each menu item
3. Front end: Mega Menu
4. Front end: Flyout Menu
5. Back end: Use the theme editor to change the appearance of your menus

== Changelog ==

= 1.0.2 =

* Update minimum required WP version from 3.9 to 3.8.

= 1.0.1 =

* Fix PHP Short Tag (thanks for the report by polderme)

= 1.0 =

* Initial version

== Upgrade Notice ==
