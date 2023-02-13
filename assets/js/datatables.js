'use strict';

$(() => {

    $( "#filtreForm th" ).each(function() {
        const title = $(this).text();
        $(this).html( '<input type="text" class="form-control" placeholder="Search '+title+'" />' );
    });

    var table = $('#tablefiltre').DataTable({
        select: true,
        colReorder: true,
        responsive: false, 
        ordering:  true, 
        searching: true,
        paging: true,
        language: {
            decimal:        "",
            emptyTable:     "-",
            info: "[_START_/_END_]  <b>Total : </b>_TOTAL_",
            infoEmpty:      "0/0   [0]",
            infoFiltered:   "(Filtered from _MAX_ Entries)",
            infoPostFix:    "",
            thousands:      " ",
            lengthMenu:     "<b>Entries</b> _MENU_",
            loadingRecords: "Loading...",
            processing:     "In progress...",
            search:         "<b>Search</b>:",
            zeroRecords:    "No results",
            paginate: {next: ">>", previous: "<<"},
        },
    });

    table.columns().eq( 0 ).each( function ( colIdx ) {
        $( 'input', $('#filtreForm th')[colIdx] ).on( 'keyup change', function () {
            table
                .column( colIdx )
                .search( this.value )
                .draw();
        } );
    });
});