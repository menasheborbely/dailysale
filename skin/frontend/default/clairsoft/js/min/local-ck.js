var $j=jQuery.noConflict();$j(document).ready(function(){function o(){r+=1,r>15&&"closed"!==$j.cookie("subscribe")&&($j("#subscribe-pop").dialog("open"),r=0)}function i(){$j(".credit-payment button").html($j(".credit-payment button").text())}var e=$j(".wrapper").offset().top+380;$j("#backtotop").hide(),$j(window).scroll(function(){var o=$j(window).scrollTop();o>e?$j("#backtotop").fadeIn():$j("#backtotop").fadeOut()});var t=function(){$j(this).stop(!0,!1).animate({paddingRight:"135px"},"fast")},n=function(){$j(this).stop(!0,!1).animate({paddingRight:"0px"},"fast")};$j("#backtotop").hover(t,n).click(function(){return $j("html, body").animate({scrollTop:0},"fast"),!1}),$j("#subscribe-pop").hide(),$j("#subscribe-pop").dialog({height:220,width:425,autoOpen:!1,dialogClass:"dialogSubscribe",modal:!0,show:{effect:"drop",direction:"right"},hide:{effect:"drop",direction:"down"},draggable:!1,resizable:!1,close:function(){$j.cookie("subscribe","closed",{expires:15,path:"/"})}}),$j("#subscribe-pop button").click(function(){$j.cookie("subscribe","closed",{expires:730,path:"/"})}),$j("body").on("click",".ui-widget-overlay",function(){$j("#subscribe-pop").dialog("close")});var r=0;setInterval(o,1e3),$j(window).resize(function(){$j("#subscribe-pop").dialog("option","position",{my:"center",at:"center",of:window})});var a=function(){var o="#1b75bb";$j(this).stop(!0,!1).animate({borderTopColor:o,borderRightColor:o,borderBottomColor:o,borderLeftColor:o})},c=function(){var o="#ccc";$j(this).stop(!0,!1).animate({borderTopColor:o,borderRightColor:o,borderBottomColor:o,borderLeftColor:o},"slow")};$j(".products-grid li.item").hover(a,c),i(),$j("a[title='Sign Up']").click(function(o){o.preventDefault(),$j("html, body").animate({scrollTop:0},"slow"),$j("#signin-modal").addClass("md-show"),IWD.Signin.insertLoader(),IWD.Signin.prepareRegisterForm()}),$j(document).on("click",".account-create-signin .back-link, .account-forgotpassword .back-link",function(o){o.preventDefault(),$j("html, body").animate({scrollTop:0},"slow"),IWD.Signin.insertLoader(),IWD.Signin.prepareLoginForm()}),$j("a[title='Log In']").click(function(o){o.preventDefault(),$j("html, body").animate({scrollTop:0},"slow"),$j("#signin-modal").addClass("md-show"),IWD.Signin.prepareLoginForm(),$j(".login-form").hide(),IWD.Signin.insertLoader(),IWD.Signin.prepareLoginForm()}),$j(document).on("click",".account-create-signin .back-link, .account-forgotpassword .back-link",function(o){o.preventDefault(),$j("html, body").animate({scrollTop:0},"slow"),IWD.Signin.insertLoader(),IWD.Signin.prepareLoginForm()});var s={};$j(".cms-index-index li.item").each(function(){var o=$j(this).attr("class");s[o]?$j(this).remove():s[o]=!0}),$j("#qty").change(function(o){var i=$j(this).val();o.preventDefault(),"other"===i?($j("#otherqty").val("10").removeClass("hidden").focus().select(),$j("#qty").val("").remove()):$j("#otherqty").val(i).addClass("hidden")})});