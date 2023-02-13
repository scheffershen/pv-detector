import $ from 'jquery';
window.jQuery = $;
window.$ = $;
import 'popper.js';
import 'bootstrap';
import 'jquery-ui/ui/effects/effect-shake.js';
import 'jquery-validation'; 
import Cookies from 'js-cookie/src/js.cookie';

$( () => {
    
    $(".fa-spinner").hide();

    if ($(".alert").length > 0 ) {
        $('input').on('input', function() {
            $(".alert").fadeOut();
          });        
        $(".card").effect("shake");
    }

    // jQuery Validation
    $("form").validate( {
        submitHandler: (form) => {
            $('button').attr("disabled", true);
            $(".fa-spinner").show();
            form.submit();
           },
        error: () => {
            $('button').attr("disabled", false);
            $(".fa-spinner").hide();
       }
    });
    
    // if IE if Remeber me 
    //$(":submit").on("click", function(e) {
    //     if ($("#remember_me")[0].checked) { 
    //         if ($("#username").val()) {
    //             Cookies.set("username", $("#username").val(), { expires: 365 });
    //         } 
    //         if ($("#password").val()) {
    //             Cookies.set("password", $("#password").val(), { expires: 365 });
    //         } 
    //     } else {
    //         Cookies.remove("username");
    //         Cookies.remove("password");
    //     } 
    // });
            
    // if (typeof Cookies.get('username') != "undefined" && typeof Cookies.get('password') != "undefined") {
    //         $("#remember_me").attr('checked','checked').parent().addClass("checked");
    //         $("#username").val(Cookies.get('username'));
    //         $("input[name=password]").val(Cookies.get('password'));
    //         $("#password").focus();
    // }

});