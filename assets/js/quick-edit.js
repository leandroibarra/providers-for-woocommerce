jQuery(document).ready(function() {
	// It is a copy of the inline edit function
	var wp_inline_edit_function = inlineEditPost.edit;

	// Overwrite its with our own custom field
	inlineEditPost.edit = function(post_id) {
		// Let's merge arguments from the original function
		wp_inline_edit_function.apply( this, arguments );

		// Get the post ID from the argument
		var id = 0;

		if (typeof(post_id) == 'object') {
			// If it is object, get the ID number
			id = parseInt(this.getId(post_id));
		}

		// If post id exists
		if (id > 0) {
			// Obtain value from hidden field (in product list column)
			var provider_id = jQuery('#hidden-provider-' + id).val();

			if (provider_id !== '') {
				// Product has value in custom field
				jQuery('select[name=change_provider] option[value=' + provider_id + ']').attr('selected', true);
			} else {
				// Product hasn't value in custom field
				jQuery('select[name=change_provider] option').removeAttr('selected');
			}
		}
	}
});

jQuery('[id^=cb-select-all-]').on('click', function() {
	var checked = jQuery(this).is(':checked');

	if (checked) {
		jQuery('[id^=cb-select-all-], [id^=cb-select-]').prop('checked', true);
	} else {
		jQuery('[id^=cb-select-all-], [id^=cb-select-]').prop('checked', false);
	}
});

jQuery('[id^=cb-select-]').on('click', function() {
	if (jQuery(this).is(':checked')) {
		if (jQuery('[id^=cb-select-]').length === jQuery('[id^=cb-select-]:checked').length) {
			jQuery('[id^=cb-select-all-]').prop('checked', true);
		}
	} else {
		jQuery('[id^=cb-select-all-]').prop('checked', false);
	}
});