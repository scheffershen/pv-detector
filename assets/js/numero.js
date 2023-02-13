'use strict';

import 'bootstrap-datepicker/js/locales/bootstrap-datepicker.fr.js';

$( () => {
  
    // Page Preloader
    $('#status').fadeOut();
    $('#preloader').delay(350).fadeOut(function(){
      $('body').delay(350).css({'overflow':'visible'});
    });

    // pdf or image preview
    $('body').on('change', 'input[type=file]', function(e) {
        if ($(this).attr('id') == 'numero_file' ) {
          var fileName = e.target.files[0].name;
          //console.log(fileName);
          var ext = fileName.substring(fileName.lastIndexOf('.') + 1);
          console.log(ext);
          if (ext == "pdf" ) {
              var reader = new FileReader();
              reader.onload = function(e) {
                // get loaded data and render thumbnail.
                // $("#pdf-preview").html("<embed src='"+e.target.result+"' width='100%' height='800px' type='application/pdf'/>"+fileName);    
                $("#pdf-preview").html(fileName);
              };
              // read the image file as a data URL.
              reader.readAsDataURL(this.files[0]);  
          } else {
            $(this).val('');
            toastr.error("It is not pdf file");           
          } 
        } else if ($(this).attr('id') == "numero_imagesZip" ) {
          $("#attachement").html(e.target.files[0].name);
          $("#image-preview").html("");
        } else {
          //console.log($(this).attr('id'));
          //console.log($(this).data('id'));
          var el = $(this);
          var reader = new FileReader();
          reader.onload = function(e) {
            if ($(this).data('id') != "undefined" && $("#img-preview"+$(this).data('id')).length > 0) {
              $("#img-preview"+$(this).data('id')).parent().remove();
            }
            el.parent().find('div.form-group').remove();
            el.before("<div class='form-group'><img class='img-fluid' src='"+ e.target.result+"'></div>");
          };
          // read the image file as a data URL.
          reader.readAsDataURL(this.files[0]);  
        }  
    }); 

    $('.add-another-collection-widget').click(function (e) {
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

        var removeButton = $('<button type="button" class="icon text-danger js-remove-image float-right"><i class="fas fa-trash"></i></button>');

        newElem.append(removeButton);
        newElem.appendTo(list);
    });

    $('body').on('click', '.js-remove-image', function(e) {
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
    });
    
    $('fieldset').fadeOut();

    $('#numero_isImage').on('click', function () {
      if($(this).prop('checked')) {
        $(".pdf-file").fadeOut();
        $(".image-files").fadeIn();        
      } else {
        $(".image-files").fadeOut();
        $(".pdf-file").fadeIn();
      }
    });   

    if ($("#_locale").val() == 'fr' ) {
        $('.pop-date').datepicker({
            language: 'fr', format: "dd/mm/yyyy",
        });
    } else {
        $('.pop-date').datepicker({
            language: 'en', format: "yyyy-mm-dd",
        });
    }

});