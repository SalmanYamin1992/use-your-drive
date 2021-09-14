(function ($) {
    'use strict';

    /* binding to the load field settings event to initialize */
    $(document).on("gform_load_field_settings", function (event, field, form) {
        jQuery("#field_wpcp_useyourdrive").val(field.defaultValue);
        if (field["UseyourdriveShortcode"] !== undefined && field["UseyourdriveShortcode"] !== '') {
            jQuery("#field_wpcp_useyourdrive").val(field["UseyourdriveShortcode"]);
        }
    });

    /* Shortcode Generator Popup */
    $('.wpcp-shortcodegenerator.useyourdrive').on('click', function (e) {
        var shortcode = jQuery("#field_wpcp_useyourdrive").val();
        shortcode = shortcode.replace('[useyourdrive ', '').replace('"]', '');
        var query = encodeURIComponent(shortcode).split('%3D%22').join('=').split('%22%20').join('&');
        tb_show("Build Shortcode for Form", ajaxurl + '?action=useyourdrive-getpopup&' + query + '&type=shortcodebuilder&asuploadbox=1&callback=wpcp_uyd_gf_add_content&TB_iframe=true&height=600&width=800');
    });

    /* Callback function to add shortcode to GF field */
    if (typeof window.wpcp_uyd_gf_add_content === 'undefined') {
        window.wpcp_uyd_gf_add_content = function (data) {
            $('#field_wpcp_useyourdrive').val(data);
            SetFieldProperty('UseyourdriveShortcode', data);

            tb_remove();
        }
    }
})(jQuery);