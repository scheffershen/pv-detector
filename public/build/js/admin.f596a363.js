"use strict";(self.webpackChunkveille_pv_xxx_com=self.webpackChunkveille_pv_xxx_com||[]).push([[205],{4040:(e,i,t)=>{t(8309),t(9826),t(1539),t(2564),t(9070),t(1920),t(4290),t(4728),t(858),t(3587);var n,o=t(8901),s=t.n(o);t(686),t(3130);function a(e,i,t){return i in e?Object.defineProperty(e,i,{value:t,enumerable:!0,configurable:!0,writable:!0}):e[i]=t,e}window.toastr=s(),window.toastr.options=(a(n={closeButton:!0,debug:!1,progressBar:!1,positionClass:"toast-top-right",onclick:null,showDuration:"300",hideDuration:"1000",timeOut:"15000",extendedTimeOut:"1000",showEasing:"swing",hideEasing:"linear",showMethod:"fadeIn"},"progressBar",!0),a(n,"hideMethod","fadeOut"),n),$((function(){function e(){s().error("You have been logged out due to inactivity.","Error")}$("select#imageFilter").on("change",(function(){$(".images").hide(),$("#image"+$(this).val()).show()})),$(".js-select2").select2(),$("#datatable").DataTable({keys:!0}),$("input[type=file]").on("change",(function(e){e.target.files[0].name;var i=new FileReader;i.onload=function(e){$("#preview").attr("src",e.target.result)},i.readAsDataURL(e.target.files[0])})),$(".js-datepicker").datepicker({format:"yyyy-mm-dd"}),$("select.chosen").chosen({width:"100%","white-space":"nowrap",disable_search_threshold:10,no_results_text:"Oops, nothing found!"}),$("input").on("input",(function(){$(this).parent().find(".invalid-feedback").remove(),$(this).removeClass("is-invalid"),$(".alert").remove()})),$("#timer").TimeCircles({animation_interval:"smooth",count_past_zero:!1,time:{Days:{show:!1},Hours:{show:!1},Minutes:{text:""},Seconds:{text:""}}}).addListener((function(i,t,n){0==n&&(s().error("You have been logged out due to inactivity.","Error"),setInterval(e,15e3))})),$("body").append("<div class='spinner'><div class='sk-folding-cube'><div class='sk-cube1 sk-cube'></div><div class='sk-cube2 sk-cube'></div><div class='sk-cube4 sk-cube'></div><div class='sk-cube3 sk-cube'></div></div></div>"),$("body").on("click",".showSpinner",(function(e){$(".spinner").show()})),$(".floatDown").on("click",(function(){window.scrollTo(0,document.body.scrollHeight)})),$(".floatUp").on("click",(function(){window.scrollTo(0,0)}))}))}},e=>{e.O(0,[225,755,587,706],(()=>{return i=4040,e(e.s=i);var i}));e.O()}]);