(function ($) {
    "use strict";
    PolyHandleRequest.ajaxComplete(toggleGravatarField);//Keep state
    toggleGravatarField();

    function toggleGravatarField() {
        if ($('#widget-polygon_widget-2-show_avatar').is(':checked')) {
            $('#widget-polygon_widget-2-show_gravatar').removeAttr('disabled');
        } else {
            $('#widget-polygon_widget-2-show_gravatar').attr('disabled', 'disabled');
        }
        
        $('#widget-polygon_widget-2-show_avatar').off('change').change(function () {
            toggleGravatarField();
        });

        var $poly_avatar_layout_item = $('.poly-avatar-layout-thumb-container .poly-avatar-layout-item');

        $poly_avatar_layout_item.off('click').on('click', function () {
            var $avatar_layout_parent = $(this).closest('.widget');
            var $avatar_layout = $avatar_layout_parent.find('.poly-avatar_layout');
            var $btn_widget_control_save = $avatar_layout_parent.find('.widget-control-save');
            $poly_avatar_layout_item.removeClass('active');
            $(this).addClass('active');
            $avatar_layout.val($(this).data('value'));
            $btn_widget_control_save.removeAttr('disabled');
        });
    }
})(jQuery);