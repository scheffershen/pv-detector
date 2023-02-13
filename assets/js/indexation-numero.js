'use strict';

$( () => {
    $("#btn-indexations-clients").on("click", function() {
        $(this).removeClass('btn-light').addClass('btn-primary');
        $('.indexations').hide();
        $("#btn-all-indexations").removeClass('btn-primary').addClass('btn-light');
    });

    $("#btn-all-indexations").on("click", function() {
        $('.indexations').show();
        $(this).removeClass('btn-light').addClass('btn-primary');
        $("#btn-indexations-clients").removeClass('btn-primary').addClass('btn-light');
    });
});