'use strict';

$(() => {

    $("form").validate({
        rules: {
            "reset_password[password][first]": {
                required: true,
                minlength: 8
            },
            "reset_password[password][second]": {
                required: true,
                minlength: 8,
                equalTo: "#reset_password_password_first"
            },            
         },
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

    $('body').on('blur', '#reset_password_password_first', function(){
        if ($('#reset_password_password_first').val()) {
            var patt = new RegExp("^.*(?=.{8,})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])[a-zA-Z0-9!*@#$%^&+=]*$");
            if (!patt.test($('#reset_password_password_first').val())) {
                $(':input[type="submit"]').prop('disabled', true);
                 toastr.error("Le mot de passe doit contenir au moins un chiffre, un caractère minuscule et un caractère majuscule, et minimum 8 caractères", "Warning"); 
                 $('#reset_password_password_first').addClass('is-invalid');
            } else {
                $(':input[type="submit"]').prop('disabled', false);
                $('#reset_password_password_first').removeClass('is-invalid');
            }
        }
    }); 

});