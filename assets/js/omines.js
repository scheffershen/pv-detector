'use strict';

import "./libs/omines";

$( () => {
    $(document).ajaxStart(function() { $(".spinner").show(); });
    $(document).ajaxStop(function() { $(".spinner").hide(); });  
      
    $('#omines').initDataTables($("#datatable_settings").val(), {
        searching: true,
        dom:'<"html5buttons"B>lTfgitp',
    }).then(function(dt) {
        // dt contains the initialized instance of DataTables
        dt.on('draw', function() {
            //alert('Redrawing table');
        })
    });
})