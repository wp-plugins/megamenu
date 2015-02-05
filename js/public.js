/*jslint browser: true, white: true */
/*global console,jQuery,megamenu,window,navigator*/

/**
 * Mega Menu jQuery Plugin
 */
(function ($) {
    "use strict";

    $.fn.megaMenu = function (options) {

        var menu = $(this);

        menu.settings = $.extend({
            event: menu.attr('data-event'),
            effect: menu.attr('data-effect')
        }, options);

        function isTouchDevice() {
            return ('ontouchstart' in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0);
        }

        function closePanels() {
            $('.mega-toggle-on > a', menu).each(function() {
                hidePanel($(this), true);
            });
        }

        function hidePanel(anchor, immediate) {
            anchor.parent().removeClass('mega-toggle-on').triggerHandler("close_panel");

            if (immediate) {
                anchor.siblings('.mega-sub-menu').hide();
            } else {
                var effect = megamenu.effect[menu.settings.effect]['out'];

                if (effect.css) {
                    anchor.siblings('.mega-sub-menu').css(effect.css);
                }

                if (effect.animate) {
                    anchor.siblings('.mega-sub-menu').animate(effect.animate, 'fast');
                }
            }
        }

        function showPanel(anchor) {

            // all open children of open siblings
            anchor.parent().siblings().find('.mega-toggle-on').andSelf().children('a').each(function() { 
                hidePanel($(this), true);
            });

            anchor.parent().addClass('mega-toggle-on').triggerHandler("open_panel");

            var effect = megamenu.effect[menu.settings.effect]['in'];

            if (effect.css) {
                anchor.siblings('.mega-sub-menu').css(effect.css);
            }

            if (effect.animate) {
                anchor.siblings('.mega-sub-menu').animate(effect.animate, 'fast', 'swing', function() {
                    $(this).css('display', 'block');
                });
            }
        }

        function openOnClick() {
            // hide menu when clicked away from
            $(document).on('click', function(event) {
                if (!$(event.target).closest('.mega-menu li').length) {
                    closePanels();
                }
            });

            $('li.mega-menu-megamenu.mega-menu-item-has-children > a, li.mega-menu-flyout.mega-menu-item-has-children > a, li.mega-menu-flyout li.mega-menu-item-has-children > a', menu).on({
                click: function (e) {

                    // check for second click
                    if ( $(this).parent().hasClass("mega-click-click-go") ) {
                        
                        if ( ! $(this).parent().hasClass("mega-toggle-on") ) {
                            e.preventDefault();
                            showPanel($(this));
                        }

                    } else {
                        e.preventDefault();

                        if ( $(this).parent().hasClass("mega-toggle-on") ) {
                            hidePanel($(this), false);                            
                        } else {
                            showPanel($(this));
                        }

                    }
                }
            });
        }

        function openOnHover() {
            $('li.mega-menu-megamenu.mega-menu-item-has-children, li.mega-menu-flyout.mega-menu-item-has-children, li.mega-menu-flyout li.mega-menu-item', menu).hoverIntent({
                over: function () {
                    showPanel($(this).children('a'));
                },
                out: function () {
                    if ($(this).hasClass("mega-toggle-on")) {
                        hidePanel($(this).children('a'), false);
                    }
                },
                timeout: megamenu.timeout
            });
        }

        function init() {
            menu.removeClass('mega-no-js');

            if (isTouchDevice() || menu.settings.event === 'click') {
                openOnClick();
            } else {
                openOnHover();
            }

        }

        init();
    };

}(jQuery));

jQuery(document).ready(function(){
    "use strict";
    jQuery('.mega-menu').each(function() {
        jQuery(this).megaMenu();
    });
});