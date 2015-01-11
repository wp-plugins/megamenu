/*global console,ajaxurl,$,jQuery*/

/**
 *
 */
jQuery(function ($) {
    "use strict";

    $("input[type=range]").on('input change', function() {
        console.log($(this).val());
        $(this).next('.pixel_value').html($(this).val() + 'px');
    });


    if ($('#codemirror').length) {
        var codeMirror = CodeMirror.fromTextArea(document.getElementById('codemirror'), {
            tabMode: 'indent',
            lineNumbers: true,
            lineWrapping: true,
            onChange: function(cm) {
                cm.save();
            }
        });
    }

    $(".mm_colorpicker").spectrum({
        preferredFormat: "rgb",
        showInput: true,
        showAlpha: true,
        clickoutFiresChange: true,
        change: function(color) { 
            if (color.getAlpha() === 0) {
                $(this).siblings('div.chosen-color').html('transparent');
            } else {
                $(this).siblings('div.chosen-color').html(color.toRgbString());
            }
        }
    });

    $(".confirm").on("click", function() {
        return confirm(megamenu_settings.confirm);
    });

    $('#theme_selector').bind('change', function () {
        var url = $(this).val();
        if (url) { 
            window.location = url; 
        }
        return false;
    });

    $('.icon_dropdown').on("change", function() {
        var icon = $("option:selected", $(this)).attr('data-class');
        // clear and add selected dashicon class
        $(this).next('.selected_icon').removeClass().addClass(icon).addClass('selected_icon');
    });

    $('select#mega_css').on("change", function() {
        var select = $(this);
        var selected = $(this).val();
        select.next().children().hide();
        select.next().children('.' + selected).show();
    });


});