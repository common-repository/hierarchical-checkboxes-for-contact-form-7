jQuery(document).ready(function() {
	
	jQuery("span.wpcf7-list-item-parent").hide();

	jQuery("span.wpcf7-hierarchicalcheckboxes span.wpcf7-list-item > a").on('click', function(event) { 
		event.preventDefault();
		submenu = jQuery(this).parent().find(".wpcf7-list-item-parent"); 
		if (submenu.is(":visible")) { 
			submenu.fadeOut();
		} else {
			submenu.fadeIn();
		}
	});
});