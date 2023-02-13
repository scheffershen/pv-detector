'use strict';

$( () => {
    $("form").validate({
         submitHandler: function(form) {
                $(".spinner").show();
                form.submit();
        },
         error: function() {
                $(".spinner").hide();
        }
    });

    $('input').on('input', function() {
        $(this).parent().find('.invalid-feedback').remove();
        $(this).removeClass('is-invalid');
        $(".alert").remove();
    });

});