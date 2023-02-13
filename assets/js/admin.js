'use strict';

//import Cookies from 'js-cookie/src/js.cookie';
import 'datatables.net';
import 'datatables.net-dt';
import 'bootstrap-datepicker';
import 'chosen-js';
//import 'summernote';
import 'jquery-validation'; 
import toastr from 'toastr';
import 'select2'; 
import 'timecircles';

window.toastr = toastr;
window.toastr.options={closeButton:!0,debug:!1,progressBar:!1,positionClass:"toast-top-right",onclick:null,showDuration:"300",hideDuration:"1000",timeOut:"15000",extendedTimeOut:"1000",showEasing:"swing",hideEasing:"linear",showMethod:"fadeIn","progressBar": true,hideMethod:"fadeOut"};

$(() => {
    $('select#imageFilter').on("change", function() {        
        $('.images').hide();
        $('#image'+$(this).val()).show();
    });

    $('.js-select2').select2();
    
    // datatables.net
    $('#datatable').DataTable({
        keys: true
    }); 

    $('input[type=file]').on('change', (e) => {
        let fileName = e.target.files[0].name;
        let reader = new FileReader();
        reader.onload = (e) => {
          $("#preview").attr("src", e.target.result);
        };
        reader.readAsDataURL(e.target.files[0]);    
    });

    $('.js-datepicker').datepicker({
        format: 'yyyy-mm-dd',
    });

    // $('.summer-note').summernote({
    //     toolbar: [
    //         ['view', ['codeview']],
    //     ],
    //     height: 450,                 // set editor height
    //     minHeight: null,             // set minimum height of editor
    //     maxHeight: null,             // set maximum height of editor
    //     focus: false                 // set focus to editable area after initializing summernote
    // });

    $("select.chosen").chosen({
        'width':'100%',
        'white-space':'nowrap', 
        disable_search_threshold: 10,
        no_results_text: "Oops, nothing found!",
    });

    $('input').on('input', function() {
        $(this).parent().find('.invalid-feedback').remove();
        $(this).removeClass('is-invalid');
        $(".alert").remove();
    });

    // function showTime(){
    //     var date=new Date,h=date.getHours(),m=date.getMinutes(),s=date.getSeconds(),session="AM";0==h&&(h=12),12<h&&(h-=12,session="PM");var time=(h=h<10?"0"+h:h)+":"+(m=m<10?"0"+m:m)+":"+(s=s<10?"0"+s:s)+" "+session;document.getElementById("clock").innerText=time,document.getElementById("clock").textContent=time,setTimeout(showTime,1e3);
    // }
    // showTime();
     $("#timer").TimeCircles({animation_interval: "smooth", count_past_zero: false, time: { Days: { show: false }, Hours: { show: false }, Minutes: { text: '' }, Seconds: { text: ''} }}).addListener(function (unit, amount, total) {
        if (total == 0) {  
            toastr.error("You have been logged out due to inactivity.", "Error"); 
            setInterval(logoutAlert, 15000);                                                      
        }
    });

    function logoutAlert() {
        toastr.error("You have been logged out due to inactivity.", "Error"); 
    }

    $("body").append("<div class='spinner'><div class='sk-folding-cube'><div class='sk-cube1 sk-cube'></div><div class='sk-cube2 sk-cube'></div><div class='sk-cube4 sk-cube'></div><div class='sk-cube3 sk-cube'></div></div></div>");
    
    $('body').on('click', '.showSpinner', function(e) {
        $(".spinner").show();
    });

    $(".floatDown").on('click', function() {
        window.scrollTo(0,document.body.scrollHeight);
    })

    $(".floatUp").on('click', function() {
        window.scrollTo(0,0);
    })

    // var sessionTimer = setInterval(function(){
    //     $.ajax({
    //         method: 'post',
    //         url: $("#is_login").val(), 
    //         contentType: 'application/json; charset=utf-8', 
    //         cache: false,
    //         dataType: "json",
    //         success: function(response){
    //             console.log(response.authenticated); 
    //             if (!response.authenticated) {
    //                 toastr.error($("#message_connexion_perdue").val(), "Error");
    //             }     
    //         }, 
    //         error: function (jxh, textmsg, errorThrown) {
    //             toastr.error(jxh.status + " " + jxh.statusText, "Error");                  
    //         }            
    //     });
    // }, 15000);
   
});