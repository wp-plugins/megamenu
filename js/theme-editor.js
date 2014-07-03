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

    $(".mm_colorpicker").spectrum({
        preferredFormat: "hex",
        showInput: true,
        showAlpha: true,
        clickoutFiresChange: true
    });

    $(".confirm").on("click", function() {
        return confirm(megamenu_theme_editor.confirm);
    });

    $('.icon_dropdown').on("change", function() {
        var icon = $("option:selected", $(this)).attr('data-class');
        // clear and add selected dashicon class
        $(this).prev('.selected_icon').removeClass().addClass(icon).addClass('selected_icon');
    });

    $('.nav-tab-wrapper a').on('click', function() {
        $(this).siblings().removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        var tab = $(this).attr('data-tab');
        $('.row').hide();
        $('.row[data-tab=' + tab + ']').show();
    });

});