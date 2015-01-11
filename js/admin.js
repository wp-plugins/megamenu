/*global console,ajaxurl,$,jQuery*/

/**
 * Mega Menu jQuery Plugin
 * @todo sort out widget.
 */
(function ($) {
    "use strict";

    $.fn.megaMenu = function (options) {

        var panel = $("<div />");

        panel.settings = $.extend({
            cols: 6
        }, options);

        panel.log = function (message) {
            if (window.console && console.log) {
                console.log(message);
            }

            if (message == -1) {
                alert(megamenu.nonce_check_failed);
            }
        };


        panel.init = function () {
            panel.log(megamenu.debug_launched + " " + panel.settings.menu_item_id);

            $.colorbox({
                html: "",
                initialWidth: '991',
                scrolling: false,
                top: 100
            });

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: { 
                    action: "mm_get_lightbox_html", 
                    _wpnonce: megamenu.nonce, 
                    menu_item_id: panel.settings.menu_item_id,
                    menu_item_depth: panel.settings.menu_item_depth
                },
                cache: false,
                beforeSend: function() {
                    $('#cboxLoadedContent').empty();
                    $('#cboxClose').empty();
                    $('#cboxLoadingGraphic').show();
                },
                complete: function() {
                    $('#cboxLoadingGraphic').hide();
                    $('#cboxLoadingOverlay').remove();
                },
                success: function(response) { 
                    var json = $.parseJSON(response);

                    var header_container = $("<div />").addClass("mm_header_container");

                    var title = $("<div />").addClass("mm_title").html(panel.settings.menu_item_title);

                    var saving = $("<div class='mm_saving '><div class='spinner'></div><div class='text'>" + megamenu.saving + "</div></div>");

                    header_container.append(title).append(saving);

                    var tabs_container = $("<div class='mm_tab_container' />");

                    var content_container = $("<div class='mm_content_container' />");

                    $.each(json, function(idx, obj) {

                        var content = $("<div />").addClass('mm_content').addClass(idx).html(this.content).hide();

                        if (idx == 'menu_icon') {
                        
                            var form = content.find('form');

                            // bind save button action
                            form.on("change", function (e) {

                                start_saving();

                                e.preventDefault();

                                var data = $(this).serialize();

                                var post = data + '&action=mm_save_menu_item_settings&_wpnonce=' + megamenu.nonce + '&menu_item_id=' + panel.settings.menu_item_id;

                                $.post(ajaxurl, post, function (submit_response) {

                                    end_saving();

                                    panel.log(submit_response);
                                });

                            });
                        }

                        if (idx == 'general_settings') {
                        
                            var form = content.find('form');

                            // bind save button action
                            form.on("submit", function (e) {

                                start_saving();

                                e.preventDefault();

                                var data = $(this).serialize();

                                var post = data + '&action=mm_save_menu_item_settings&_wpnonce=' + megamenu.nonce + '&menu_item_id=' + panel.settings.menu_item_id;

                                $.post(ajaxurl, post, function (submit_response) {

                                    end_saving();

                                    panel.log(submit_response);
                                });

                            });
                        }

                        if (idx == 'mega_menu') {

                            var widget_selector = content.find('select');

                            widget_selector.on('change', function() {

                                var selector = $(this);

                                if (selector.val() != 'disabled') {

                                    start_saving();

                                    var postdata = {
                                        action: "mm_add_widget",
                                        id_base: selector.val(),
                                        menu_item_id: panel.settings.menu_item_id,
                                        title: selector.find('option:selected').text(),
                                        _wpnonce: megamenu.nonce
                                    };

                                    $.post(ajaxurl, postdata, function (widget_html) {

                                        end_saving();

                                        $(".no_widgets").hide();

                                        var widget = $(widget_html);

                                        add_events_to_widget(widget);

                                        $("#widgets").append(widget);
                                    });

                                }


                            });

                            var enable_checkbox = content.find('input');

                            enable_checkbox.on('change', function() {

                                start_saving();

                                var postdata = {
                                    action: "mm_save_menu_item_settings",
                                    settings: { type: $(this).is(':checked') ? 'megamenu' : 'flyout'},
                                    menu_item_id: panel.settings.menu_item_id,
                                    _wpnonce: megamenu.nonce
                                };

                                $.post(ajaxurl, postdata, function (select_response) {

                                    end_saving();

                                    panel.log(select_response);
                                });

                            });

                            var widget_area = content.find('#widgets');

                            widget_area.sortable({
                                forcePlaceholderSize: true,
                                placeholder: "drop-area",
                                start: function (event, ui) {
                                    $(".widget").removeClass("open");
                                    ui.item.data('start_pos', ui.item.index());
                                },
                                stop: function (event, ui) {
                                    // clean up
                                    ui.item.removeAttr('style');

                                    var start_pos = ui.item.data('start_pos');

                                    if (start_pos !== ui.item.index()) {
                                        ui.item.trigger("on_drop");
                                    }
                                }
                            });


                            $('.widget', widget_area).each(function() {
                                add_events_to_widget($(this));
                            });

                        }

                        var tab = $("<div />").addClass('mm_tab').html(this.title).css('cursor', 'pointer').on('click', function() {
                            $(".mm_content").hide();
                            $(".mm_tab").removeClass('active');
                            $(this).addClass('active');
                            content.show();
                        });

                        if ( ( panel.settings.menu_item_depth == 0 && idx == 'mega_menu' ) || 
                             ( panel.settings.menu_item_depth > 0 && idx == 'menu_icon' ) ) {
                            content.show();
                            tab.addClass('active');
                        }

                        tabs_container.append(tab);
                        content_container.append(content);
                    });

                    $('#cboxLoadedContent').addClass('depth-' + panel.settings.menu_item_depth).append(header_container).append(tabs_container).append(content_container);
                    $('#cboxLoadedContent').css({'width': '100%', 'height': '100%', 'display':'block'});
                }
            });

        };

        var start_saving = function() {
            $('.mm_saving').show();
        }

        var end_saving = function() {

            $('.mm_saving').addClass("saved");

            $('.mm_saving').delay(500).fadeOut('fast', function() {
                $('.mm_saving').removeClass("saved");
            });

        }

        var add_events_to_widget = function (widget) {

            var widget_spinner = widget.find(".spinner");
            var expand = widget.find(".widget-expand");
            var contract = widget.find(".widget-contract");
            var edit = widget.find(".widget-edit");
            var widget_inner = widget.find(".widget-inner");
            var widget_id = widget.attr("data-widget-id");

            widget.bind("on_drop", function () {

                start_saving();

                var position = $(this).index();

                $.post(ajaxurl, {
                    action: "mm_move_widget",
                    widget_id: widget_id,
                    position: position,
                    menu_item_id: panel.settings.menu_item_id,
                    _wpnonce: megamenu.nonce
                }, function (move_response) {
                    end_saving();
                    panel.log(move_response);
                });
            });

            expand.on("click", function () {

                var cols = parseInt(widget.attr("data-columns"), 10);

                if (cols < panel.settings.cols) {
                    cols = cols + 1;

                    widget.attr("data-columns", cols);

                    start_saving();

                    $.post(ajaxurl, {
                        action: "mm_update_columns",
                        widget_id: widget_id,
                        columns: cols,
                        _wpnonce: megamenu.nonce
                    }, function (expand_response) {
                        end_saving();
                        panel.log(expand_response);
                    });
                }

            });

            contract.on("click", function () {

                var cols = parseInt(widget.attr("data-columns"), 10);

                if (cols > 0) {
                    cols = cols - 1;
                    widget.attr("data-columns", cols);
                }

                start_saving();

                $.post(ajaxurl, {
                    action: "mm_update_columns",
                    widget_id: widget_id,
                    columns: cols,
                    _wpnonce: megamenu.nonce
                }, function (contract_response) {
                    end_saving();
                    panel.log(contract_response);
                });

            });


            edit.on("click", function () {

                if (! widget.hasClass("open") && ! widget.data("loaded")) {

                    widget_spinner.show();

                    // retrieve the widget settings form
                    $.post(ajaxurl, {
                        action: "mm_edit_widget",
                        widget_id: widget_id,
                        _wpnonce: megamenu.nonce
                    }, function (form) {

                        var $form = $(form);

                        // bind delete button action
                        $(".delete", $form).on("click", function (e) {
                            e.preventDefault();

                            var data = {
                                action: "mm_delete_widget",
                                widget_id: widget_id,
                                _wpnonce: megamenu.nonce
                            };

                            $.post(ajaxurl, data, function (delete_response) {
                                widget.remove();
                                panel.log(delete_response);
                            });

                        });

                        // bind close button action
                        $(".close", $form).on("click", function (e) {
                            e.preventDefault();

                            widget.toggleClass("open");
                        });

                        // bind save button action
                        $form.on("submit", function (e) {
                            e.preventDefault();

                            var data = $(this).serialize();

                            start_saving();

                            $.post(ajaxurl, data, function (submit_response) {
                                end_saving();
                                panel.log(submit_response);
                            });

                        });

                        widget_inner.html($form);

                        widget.data("loaded", true).toggleClass("open");

                        widget_spinner.hide();
                    });

                } else {
                    widget.toggleClass("open");
                }

                // close all other widgets
                $(".widget").not(widget).removeClass("open");

            });

            return widget;
        };

        panel.init();

    };

}(jQuery));

/**
 *
 */
jQuery(function ($) {
    "use strict";

    $(".megamenu_launch").live("click", function (e) {
        e.preventDefault();

        $(this).megaMenu();
    });

    $('#megamenu_accordion').accordion({
        heightStyle: "content", 
        collapsible: true,
        active: false,
        animate: 200
    });

    $('#menu-to-edit li.menu-item').each(function() {

        var menu_item = $(this);
        var title = menu_item.find('.item-title').text();
        var id = parseInt(menu_item.attr('id').match(/[0-9]+/)[0], 10);

        var button = $("<span>").addClass("mm_launch")
                                .html(megamenu.launch_lightbox)
                                .on('click', function(e) {
                                    e.preventDefault();

                                    var depth = menu_item.attr('class').match(/\menu-item-depth-(\d+)\b/)[1];

                                    $(this).megaMenu({
                                        menu_item_id: id,
                                        menu_item_title: title,
                                        menu_item_depth: depth 
                                    });
                                });

        $('.item-title', menu_item).append(button);
    });

});