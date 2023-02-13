"use strict";

$(() => {

    $("body").on("click", "input[name^='user[roles]']", function() {
        //let $form = $("form[name=user]");
        //let data = {};
        let checkbox = $("input[name='user[roles][]']");
        let role_client = false;

        $.each(checkbox, function(key, val) {
            if ($(val).is(":checked")) {
                 //data[$(val).attr("name")] = $(val).val();
                 if ($(val).val() == "ROLE_CLIENT") {
                    role_client = true;
                 }
            }
        });        

        if (role_client) {
            $(".clients").removeClass('d-none');
            // $(".spinner").show();
            // $.ajax({
            //     url: $("#admin_user_new_url").val(),
            //     type: $form.attr("method"),
            //     data: data,
            //     success: function(html) {
            //         $(".js-select2").select2("destroy");
            //         $("#user_clients").next().remove();
            //         $("#user_clients").replaceWith($(html).find("#user_clients"));
            //         $(".js-select2").select2();
            //         $(".spinner").hide();
            //     },
            //     error: function(jxh, textmsg, errorThrown) {
            //         $(".spinner").hide();
            //         toastr.error(jxh.status + " " + jxh.statusText, "Error");
            //     }
            // });
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
