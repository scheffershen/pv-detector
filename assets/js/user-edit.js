"use strict";

$(() => {

    $("body").on("click", "input[name^='user_edit[roles]']", function() {
        let checkbox = $("input[name='user_edit[roles][]']");
        let role_client = false;

        $.each(checkbox, function(key, val) {
            if ($(val).is(":checked")) {
                 if ($(val).val() == "ROLE_CLIENT") {
                    role_client = true;
                 }
            }
        });        

        if (role_client) {
            $(".clients").removeClass('d-none');
        } else {
            $(".clients").addClass('d-none');
        }
    });

    // jQuery Validation
    $("form").validate({
        submitHandler: form => {
            $(".spinner").show();
            form.submit();
        },
        error: () => {
            $(".spinner").hide();
        },
        ignore: ":hidden:not(.summer-note),.note-editable.panel-body"
    });
});
