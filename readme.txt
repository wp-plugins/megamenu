=== Plugin Name ===
Contributors: megamenu
Tags: menu, mega menu, navigation, menu icons, menu style, responsive menu, megamenu, widget, dropdown menu, drag and drop, hover, click, responsive, retina, theme editor, widget, sidebar, icons, dashicons
Requires at least: 3.8
Tested up to: 4.1
Stable tag: 1.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easy to use drag & drop WordPress Mega Menu plugin. Integrates with the existing WordPress menu system. Responsive, retina & touch ready.

== Description ==

WordPress Mega Menu Plugin. Use the built in drag & drop widget editor to build your mega panels.

https://www.youtube.com/watch?v=44dJwP1AXT8

Documentation & Demo: http://www.maxmegamenu.com

Features:

* Zero theme edits
* Drag & drop widget editor
* Menu panels can be from 1-8 columns wide (widgets can span multiple columns)
* Flyout (traditional) or Mega Menu menu styles
* Menu Icons
* Hide Text option
* Disable Link option
* Menu item align option (float links to right hand side of menu)
* Sub menu align option
* Activate Menu on either hover (intent) or click
* Fade/Slide/None transitions
* Compatible with touch screen devices
* Built in theme editor
* Works with multiple menus on the same page
* Works with menus tagged to multiple Theme Locations
* < 1kb JavaScript when gzipped (also works when JS is disabled)
* Responsive
* Retina Ready
* Safe: You can uninstall and go back to your old menu
* Tested in IE9+, FireFox, Opera, Safari & Chrome (IE6, 7 & 8 are not supported but may work)

The technical stuff:

* This plugin will not pick up styling from your old menu, but the built in theme editor will allow you to tailor your Mega Menu styling to your theme.
* Your theme will need a registered Theme Location to work
* The menu CSS is dynamically parsed SCSS. Developers can create their own SCSS file if needed - just copy the megamenu.css file to your theme directory and make any required edits.
* The parsed SCSS is cached for performance. The cache is refreshed when a menu is saved or a theme has been created/updated.
* Max Mega Menu is compatible with Widget & Menu Output Cache plugin (https://wordpress.org/plugins/widget-output-cache/).
* Behind the scenes, all menu widgets are stored as standard WordPress widgets in a new widget area that the plugin creates.

Recommended Widgets:

* Image Widget
* Contact Form 7 Widget
* Very Simple Google Maps (this only gives a shortcode, so install the ShortCode Widget and use something like `[vsgmap address="your address, country" width='100%' height='200']`)

Translations:

* Italian (thanks to aeco)
* German (thanks to Thomas Meyer & dirk@d10n)

Tested with the 20 most popular themes, all compatible with the exceptions of:

* Tesla: compatible but requires edits: open header.php and remove the second call to wp_nav_menu (line 130 - 147)
* Vantage: compatible (but hover only)
* Stargazer: compatible (but hover only)

== FAQ ==

Getting started:

http://www.maxmegamenu.com/documentation/

Not working with your theme?

http://www.maxmegamenu.com/documentation/removing-residual-styling/

== Installation ==

1. Go to the Plugins Menu in WordPress
1. Search for "Max Menu"
1. Click "Install"

== Screenshots ==

See http://www.maxmegamenu.com for more screenshots

1. New menu changes
2. Drag and Drop widget editor for each menu item
3. Front end: Mega Menu
4. Front end: Flyout Menu
5. Back end: Use the theme editor to change the appearance of your menus

== Changelog ==

= 1.6 =

* Fix: responsive collapse menu
* Fix: checkbox appearing on Enfold theme

= 1.6-beta =

* Change: Menu ID removed from menu class and ID attributes on menu wrappers. E.g. "#mega-menu-wrap-primary-2" will now be "#mega-menu-wrap-primary", "#mega-menu-primary-2" will now be "#mega-menu-primary".
* Fix: Polylang & WPML compatibility (fixed due to the above)
* Fix: Multi Site support (mega menu settings will need to be reapplied in some cases for multi site installs)
* Fix: Remove jQuery 1.8 dependency
* Change: Theme editor slightly revised

= 1.5.3 =

* Fix: Widget ordering bug when mega menu contains sub menu items (reported by & thanks to: milenasmart)
* Misc: Add megamenu_save_menu_item_settings action

= 1.5.2 =

* Feature: Responsive menu text option in theme editor
* Fix: Bug causing menu item to lose checkbox settings when saving mega menu state
* Fix: Mobile menu items disappearing
* Change: Refactor public js
* Change: jQuery actions fired on panel open/close
* Change: Tabify icon options
* Change: Show 'up' icon when mobile sub menu is open
* Change: Make animations filterable
* Change: Add filter for SCSS and SCSS vars
* Change: Add filter for menu item tabs
* Update: Update german language files (thanks to dirk@d10n)

= 1.5.1 =

* Fix: Bug causing menu item to lose checkbox settings when saving icon

= 1.5 =

* New feature: Change number of columns to use in Mega Menus (per menu item)
* New feature: Define how many columns each second level menu items should take up
* New feature: Hide menu item text
* New feature: Hide menu item arrow indicator
* New feature: Disable menu item link
* New feature: Align menu item
* Fix: Allow css to be cached when menu is not found
* Fix: Apply inline-block styling to second level menu items displayed in Mega Menu
* Fix: AJAX Error when widgets lack description (reported by and thanks to: novlenova)
* Improvement: Refactor extraction and setting of menu item settings

= 1.4 =

* Update: Admin interface improvements
* New feature: CSS Output options

= 1.3.3 =

* Fix: theme warnings (thanks to armandsdz!)
* Update: compatibile version number updated to 4.1

= 1.3.2 =

* Theme Editor restyled
* Fix: Flyout menu item height when item wraps onto 2 lines
* Fix: Add indentation to third level items in mega panel

= 1.3.1 =

* Fix secondary menu bug
* Add option to print CSS to <head> instead of enqueuing as external file

= 1.3 =

* maxmenu shortcode added. Example: [maxmenu location=primary]
* 'megamenu_after_install' and 'megamenu_after_upgrade' hooks added
* 'megamenu_scss' hook added
* Fix: CSS automatically regenerated after upgrade
* Fix: Don't override the echo argument for wp_nav_menu
* Fix: Theme duplication when default theme has been edited
* Change: CSS cache set to never expire
* Added import SCSS import paths
* German Translations added (thanks to Thomas Meyer)

= 1.2.2 =

* Add support for "click-click-go" menu item class to follow a link on second click
* Remove widget overflow

= 1.2.1 =

* Fix IE11 gradients
* Fix hover bug introducted in 1.2

= 1.2 =

* Less agressive cache clearing
* Compatible with Nav Menu Roles
* UX improvements for the panel editor
* Hover effect on single items fixed
* JS cleaned up

= 1.1 =

* Added Fade and SlideDown transitions for panels
* Added panel border, flyout border & panel border radius settings
* JavaScript tidied up
* Ensure hoverIntent is enqueued before Mega Menu

= 1.0.4 =

* Italian translation added. Thanks to aeco!

= 1.0.3 =

* Add Panel Header Font Weight theme setting
* Allow semi transparent colors to be picked

= 1.0.2 =

* Update minimum required WP version from 3.9 to 3.8.

= 1.0.1 =

* Fix PHP Short Tag (thanks for the report by polderme)

= 1.0 =

* Initial version

== Upgrade Notice ==
