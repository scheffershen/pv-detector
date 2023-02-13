'use strict';

$( () => {
    $('fieldset').hide();

    $('.add-another-collection-widget').on('click', function (e) {
        var list = $($(this).attr('data-list-selector'));
        // Try to find the counter of the list or use the length of the list
        var counter = list.data('widget-counter') || list.children().length;

        // grab the prototype template
        var newWidget = list.attr('data-prototype');
        // replace the "__name__" used in the id and name of the prototype
        // with a number that's unique to your emails
        // end name attribute looks like name="contact[emails][2]"
        newWidget = newWidget.replace(/__name__/g, counter);
        // Increase the counter
        counter++;
        // And store it, the length cannot be used if deleting widgets is allowed
        list.data('widget-counter', counter);

        // create a new list element and add it to the list
        var newElem = $(list.attr('data-widget-tags')).html(newWidget);

        var removeButton = $('<button type="button" class="icon text-danger js-remove-lien float-right"><i class="fas fa-trash"></i></button>');

        newElem.append(removeButton);
        newElem.appendTo(list);
    });

    $('body').on('click', '.js-remove-lien', function(e) {
        e.preventDefault();
        $(this).closest('.card-body').fadeOut().remove();        
    });

    // jQuery Validation
    $("form").validate( {
        submitHandler: (form) => {
               $(".spinner").show();
               form.submit();
           },
        error: () => {
               $(".spinner").hide();
       },
       ignore: ":hidden:not(.summer-note),.note-editable.panel-body"  
    });

});