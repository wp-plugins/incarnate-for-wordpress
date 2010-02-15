window._emailRegex = /^[a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z]$/i;
window._userHasSelected = false;

var incarnateFormHasRendered = false; // this is a global flag we use later in the PHP/jQuery DOM injection

var currentSearch = false;
function clearSearchFlag() {
	currentSearch = false;
}

jQuery(document).ready(function() {
	// set the incarnate endpoint URL
	var IncarnateServiceURL = "http://incarnate.visitmix.com/incarnate";
	if(typeof getIncarnateWebservice == 'function') {
		IncarnateServiceURL = getIncarnateWebservice();
	}
	
	if (IncarnateServiceURL.charAt(IncarnateServiceURL.length - 1) != "/") {
		IncarnateServiceURL = IncarnateServiceURL + "/";
	}

    // @see incarnate.svc/providers (json) or incarnate.svc/providers.xml
    var providers = ["Twitter", "MySpace", "Facebook", "YouTube", "XBoxLive"];

    var avatars = [];
    var index = 0; // paging
    var searchTerm = "";
    var fadeDuration = 300;
	
	jQuery("#IncarnateUserName").val(searchTerm);
	if(inc_readCookie("inc_avatar")) {
		jQuery("#IncarnateImg").attr("src", inc_readCookie("inc_avatar"));
		jQuery("#IncarnateImgSrc").attr("value", inc_readCookie("inc_avatar"));
    }
	
	jQuery("#IncarnateActivate").click(function () {
		if(currentSearch == true) return;
		
		setTimeout("clearSearchFlag();", 1000);
		currentSearch = true;
		
		jQuery("#IncarnateLoader").show();
        Incarnate();
    });

    jQuery("#IncarnateActivate").focus(function() {
        if(currentSearch == true) return;
		
		setTimeout("clearSearchFlag();", 1000);
		currentSearch = true;
		
		jQuery("#IncarnateLoader").show();
        Incarnate();
    });
    //if enter was hit, do the call
    jQuery("#IncarnateUserName").keyup(function(event) {
        
        if (event.keyCode == 13) {
            if (currentSearch == true) return;
            setTimeout("clearSearchFlag();", 1000);
            currentSearch = true;
            $("#IncarnateLoader").show();
            Incarnate();
        }
    });

	jQuery("form[action*=wp-comments-post]").submit(function() {
		var currentImage = jQuery("#IncarnateImgSrc").attr("value");
		inc_createCookie("inc_avatar", currentImage, 30);
	});
	
  
	
	
	function Incarnate() {
		var imgroot = "/images/"; // default value
		if(typeof getIncarnateImageRoot == 'function') {
			imgroot = getIncarnateImageRoot();
		}
		
		//clear out all state
		index = 0;
		avatars = [];
		window._userHasSelected = false;
		
		//don't search for empty string
		if (jQuery("#IncarnateUserName").val() == "") {
			jQuery("#IncarnateResultsContainer").fadeOut(fadeDuration);
				
			var wpEmailField = jQuery("form[action*=wp-comments-post] input[name=email]").val();
			if(window._emailRegex.test(wpEmailField)) {
				// there's an email in the wordpress field so we'll fall back on their gravatar
				jQuery.getJSON(IncarnateServiceURL + "GetHash?email=" + wpEmailField + "&callback=?",
				function(data) {
					var imgurl = "http://gravatar.com/avatar.php?gravatar_id=" + data + "&d=" + getIncarnateDefaultImage();
					jQuery("#IncarnateImg").attr("src", imgurl);
					jQuery("#IncarnateImgSrc").attr("value", imgurl);
					jQuery("#IncarnateLoader").hide();
				});
			} else {
				// there's no email in the wordpress field so we'll use our default image
				jQuery("#IncarnateImg").attr("src", getIncarnateDefaultImage());
				jQuery("#IncarnateImgSrc").attr("value", getIncarnateDefaultImage());
				jQuery("#IncarnateLoader").hide();
			}
			return;
		}
		searchTerm = jQuery("#IncarnateUserName").val();
		
		if (window._emailRegex.test(searchTerm)) {
			//do gravatar if we match an email address
			jQuery.getJSON(IncarnateServiceURL + "GetHash?email=" + searchTerm + "&callback=?",
			function(data) {
				var imgurl = "http://gravatar.com/avatar.php?gravatar_id=" + data + "&d=" + getIncarnateDefaultImage();
				//populate img tag                
				jQuery("#IncarnateImg").attr("src", imgurl);
				//populate hidden field for form submit
				jQuery("#IncarnateImgSrc").attr("value", imgurl);
				//save to the cookie
				jQuery("#IncarnateLoader").hide();
			});
			return;
		}
		if (searchTerm.indexOf("@") > 0) {
			jQuery("#IncarnateResultsContainer").hide();
			return;
		}

		jQuery("#IncarnateResultsContainer").empty();
		var cancelDiv = jQuery('<div style="height:16px;margin:2px 2px 0 0;"><img style="margin-left:8px;" src="http://incarnate.blob.core.windows.net/images/lockup_small.png"/><span style="float:right;cursor:pointer; "><img src="http://incarnate.visitmix.com/images/close.png"/></span></div>');
		cancelDiv.click(function() {
		jQuery("#IncarnateResultsContainer").hide();

		});
		jQuery("#IncarnateResultsContainer").append(cancelDiv);

		//use incarnate service
		jQuery(providers).each(function(name, value) {

			jQuery.getJSON(IncarnateServiceURL + value + "/" + searchTerm + "?callback=?",
			function(data) {
				if (data != null) {
					if (searchTerm == jQuery("#IncarnateUserName").val()) {
						if(window._userHasSelected == false)
							jQuery("#IncarnateResultsContainer").fadeIn(fadeDuration);
							
						jQuery("#IncarnateLoader").hide();

						var vCardDiv = jQuery('<div class="IncarnateResult" style="margin:5px;"></div>');
						vCardDiv.append('<img style="margin:3px;" width="48" src="' + data + '" />');
						var imgsrc = imgroot + value + '.png'
						vCardDiv.append('<img style="margin:0px 0px 20px 10px;" src="' + imgsrc + '"/>');
						vCardDiv.click(function() {
							//clear all values
							jQuery("#IncarnateImgSrc").attr("value", data);
							jQuery("#IncarnateImg").attr("src", data);
							
							jQuery("#IncarnateResultsContainer").fadeOut(fadeDuration);
							
							// manual selection should store the cookie
							inc_createCookie("inc_avatar", data, 30);
							
							window._userHasSelected = true;
						});
						vCardDiv.hover(function() {
						vCardDiv.css({ 'clear': 'both', 'background-color': '#99ccff', 'overflow': 'hidden' });
                    		},
							function() {
                    		vCardDiv.css({ 'clear': 'both', 'background-color': 'transparent' });
                  }
						);

						jQuery("#IncarnateResultsContainer").append(vCardDiv);

					}
				}

			});
		});
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// if the user is logged in we don't need to run the rest of these scripts
	if(getIncarnateLoggedIn()) {
		inc_eraseCookie("inc_avatar");
		return; 
	}
	
	// if the email is filled in but there isn't an avatar selected, pull in their gravatar
	var wpEmailField = jQuery("form[action*=wp-comments-post] input[name=email]").val();
	if(window._emailRegex.test(wpEmailField) && (!inc_readCookie("inc_avatar") || inc_readCookie("inc_avatar") == getIncarnateDefaultImage())) {
		jQuery("#IncarnateUserName").val(wpEmailField);
		Incarnate();
	}
	
	// if they enter an email but haven't selected an avatar, show their gravatar
	jQuery("form[action*=wp-comments-post] input[name=email]").blur(function() {
		if(!inc_readCookie("inc_avatar") || (inc_readCookie("inc_avatar") == getIncarnateDefaultImage()) || (jQuery("#IncarnateImg").attr("src") == getIncarnateDefaultImage())) {
			jQuery("#IncarnateUserName").val(jQuery(this).val());
			Incarnate();
		}
    });
});

function inc_createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function inc_readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function inc_eraseCookie(name) {
	inc_createCookie(name,"",-1);
}
