jQuery(document).ready(function() {
	jQuery('#table-provider-orders').DataTable({
		'order': [],
		'ordering': false,
		'searching': false,
		'paging': false,
		'info': false,
		'columns': [
			null,
			null,
			{
				'width': '260px'
			},
			null,
			null,
			null,
			null
		],
		'language': {
			'emptyTable': "No hay productos para este proveedor"
		},
		initComplete: function() {
			jQuery('#table-provider-orders, .export-wrapper').css('visibility', 'visible');
			jQuery('#overlay').hide();
		},
	});

	jQuery('.export-wrapper').on('change', '.export-input', function() {
		var max = parseInt(jQuery(this).attr('max'));
		var min = parseInt(jQuery(this).attr('min'));
		var val = jQuery(this).val();
	
		if (val > max) {
			jQuery(this).val(max);
		} else if (val < min) {
			jQuery(this).val(min);
		}
	});

	jQuery('#table-provider-orders').on('click', '.select-all', function() {
		var checked = jQuery(this).is(':checked');

		if (checked) {
			jQuery('.select-product').prop('checked', true);
			jQuery('.select-all').prop('checked', true);

			jQuery('.export-input').removeAttr('disabled').removeClass('disabled');
		} else {
			jQuery('.select-product').prop('checked', false);
			jQuery('.select-all').prop('checked', false);

			jQuery('.export-input').val('').attr('disabled', true).addClass('disabled');
			jQuery('.export-button').attr('disabled', true).addClass('disabled');
		}
	});

	jQuery('#table-provider-orders').on('click', '.select-product', function() {
		if (!jQuery(this).is(':checked')) {
			jQuery('.select-all').prop('checked', false);
		} else {
			if (jQuery('.select-product').length === jQuery('.select-product:checked').length) {
				jQuery('.select-all').prop('checked', true);
			}
		}

		if (jQuery('.select-product:checked').length > 0) {
			jQuery('.export-input').removeAttr('disabled').removeClass('disabled');
		} else {
			jQuery('.export-input').val('').attr('disabled', true).addClass('disabled');
			jQuery('.export-button').attr('disabled', true).addClass('disabled');
		}
	});

	jQuery('.export-input').on('change', function() {
		if (jQuery(this).val() > 0) {
			jQuery('.export-button').removeAttr('disabled').removeClass('disabled');
		} else {
			jQuery('.export-button').attr('disabled', true).addClass('disabled');
		}
	});

	jQuery('body').on('click', '.export-button', function() {
		if (!jQuery(this).hasClass('disabled')) {
			jQuery('.select-product:checked').each(function(index, product) {
				jQuery('<input />', {
					'type': 'hidden',
					'name': 'product_id[' + index + ']',
					'value': jQuery(product).val()
				}).appendTo(jQuery('.export-form'));
			});
		}
	});
});
