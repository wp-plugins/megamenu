/*jslint browser: true, white: true */
/*global console,jQuery,megamenu,window,navigator*/

/**
 * Max Mega Menu jQuery Plugin
 */
(function($) {

    "use strict";

    $.maxmegamenu = function(menu, options) {

        var plugin = this;
        var $menu = $(menu);

        var defaults = {
            event: $menu.attr('data-event'),
            effect: $menu.attr('data-effect'),
            panel_width: $menu.attr('data-panel-width'),
            second_click: $menu.attr('data-second-click'),
            breakpoint: $menu.attr('data-breakpoint')
        };

        plugin.settings = {};

        var isTouchDevice = function() {
            return ('ontouchstart' in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0);
        };

        plugin.closePanels = function() {
            $('.mega-toggle-on > a', $menu).each(function() {
                plugin.hidePanel($(this), true);
            });
        };

        plugin.hidePanel = function(anchor, immediate) {
            if (immediate) {
                anchor.siblings('.mega-sub-menu').removeClass('mega-toggle-on');
                anchor.parent().removeClass('mega-toggle-on').triggerHandler("close_panel");
            } else {
                if ( megamenu.effect[plugin.settings.effect] ) {
                    var effect = megamenu.effect[plugin.settings.effect]['out'];

                    if (effect.css) {
                        anchor.siblings('.mega-sub-menu').css(effect.css);
                    }

                    if (effect.animate) {
                        anchor.siblings('.mega-sub-menu').animate(effect.animate, 'slow', function() {
                            anchor.parent().removeClass('mega-toggle-on').triggerHandler("close_panel");
                        });
                    } else {
                        anchor.parent().removeClass('mega-toggle-on').triggerHandler("close_panel");
                    }
                } else {
                    anchor.parent().removeClass('mega-toggle-on').triggerHandler("close_panel");
                }
            }
        };

        plugin.showPanel = function(anchor) {
            // automatically hide open panels, but only for desktop.
            if ( $(window).width() > plugin.settings.breakpoint ) {
                // all open children of open siblings
                anchor.parent().siblings().find('.mega-toggle-on').andSelf().children('a').each(function() { 
                    plugin.hidePanel($(this), true);
                });
            }

            // apply dynamic width and sub menu position
            if ( anchor.parent().hasClass('mega-menu-megamenu') && $(plugin.settings.panel_width).length ) {
                var submenu_offset = $menu.offset();
                var target_offset = $(plugin.settings.panel_width).offset();

                anchor.siblings('.mega-sub-menu').css({
                    width: $(plugin.settings.panel_width).outerWidth(),
                    left: (target_offset.left - submenu_offset.left) + "px"
                });
            }

            if ( megamenu.effect[plugin.settings.effect] ) {
                var effect = megamenu.effect[plugin.settings.effect]['in'];

                if (effect.css) {
                    anchor.siblings('.mega-sub-menu').css(effect.css);
                }

                if (effect.animate) {
                    anchor.siblings('.mega-sub-menu').animate(effect.animate, 'fast', 'swing', function() {
                        $(this).css('visiblity', 'visible');
                    });
                }
            }

            anchor.parent().addClass('mega-toggle-on').triggerHandler("open_panel");
        };

        var openOnClick = function() {
            // hide menu when clicked away from
            $(document).on('click', function(event) {
                if (!$(event.target).closest('.mega-menu li').length) {
                    plugin.closePanels();
                }
            });

            $('li.mega-menu-megamenu.mega-menu-item-has-children > a, li.mega-menu-flyout.mega-menu-item-has-children > a, li.mega-menu-flyout li.mega-menu-item-has-children > a', menu).on({
                click: function(e) {
                    // check for second click
                    if ( plugin.settings.second_click == 'go' || $(this).parent().hasClass("mega-click-click-go") ) {
                        if ( ! $(this).parent().hasClass("mega-toggle-on") ) {
                            e.preventDefault();
                            plugin.showPanel($(this));
                        }
                    } else {
                        e.preventDefault();

                        if ( $(this).parent().hasClass("mega-toggle-on") ) {
                            plugin.hidePanel($(this), false);                            
                        } else {
                            plugin.showPanel($(this));
                        }
                    }
                }
            });
        };

        var openOnHover = function() {
            $('li.mega-menu-megamenu.mega-menu-item-has-children, li.mega-menu-flyout.mega-menu-item-has-children, li.mega-menu-flyout li.mega-menu-item', menu).hoverIntent({
                over: function () {
                    plugin.showPanel($(this).children('a'));
                },
                out: function () {
                    if ($(this).hasClass("mega-toggle-on")) {
                        plugin.hidePanel($(this).children('a'), false);
                    }
                },
                timeout: megamenu.timeout
            });
        };

        plugin.init = function() {
            plugin.settings = $.extend({}, defaults, options);

            $menu.removeClass('mega-no-js');

            $menu.siblings('.mega-menu-toggle').on('click', function() {
                $(this).toggleClass('mega-menu-open');
            });

            if (isTouchDevice() || plugin.settings.event === 'click') {
                openOnClick();
            } else {
                openOnHover();
            }
        };

        plugin.init();

    };

    $.fn.maxmegamenu = function(options) {
        return this.each(function() {
            if (undefined === $(this).data('maxmegamenu')) {
                var plugin = new $.maxmegamenu(this, options);
                $(this).data('maxmegamenu', plugin);
            }
        });
    };

    $(function() {
        $(".mega-menu").maxmegamenu();
    });

})(jQuery);