jQuery(document).ready(function() {
	jQuery('#table-provider-products').DataTable({
		'order': [],
		'ordering': false,
		'searching': false,
		'paging': false,
		'info': false,
		'columns': [
			null,
			{
				'width': '260px'
			},
			null,
			null,
			null,
			null,
			null
		],
		'language': {
			'emptyTable': "No hay productos para este proveedor"
		},
		initComplete: function() {
			jQuery('#table-provider-products, .save-all').css('visibility', 'visible');
			jQuery('#overlay').hide();
		},
	});

	jQuery('#table-provider-products').on('click', '.save-product', function() {
		var a = jQuery(this);
		var all = jQuery('.save-all');

		if (!jQuery(a).hasClass('disabled')) {
			jQuery(a).addClass('disabled');
			jQuery(all).addClass('disabled');

			var index = jQuery(this).closest('tr').data('index');
			var post_id = jQuery(this).closest('tr').data('post-id');
			var html = jQuery(this).html();

			jQuery.ajax({
				type: 'POST',
				url: 'admin-ajax.php',
				data: {
					action: 'save_product',
					post_id: post_id,
					purchase_price: jQuery('#purchase_price_' + index).val(),
					profit_margin: jQuery('#profit_margin_' + index).val(),
					_price: jQuery('#_price_' + index).val(),
					_stock: jQuery('#_stock_' + index).val()
				},
				beforeSend: function() {
					jQuery(a).html(
						jQuery('<div />', {
							'class': 'spinner-border'
						})
					);
				},
				complete: function() {
					jQuery(a).find('.spinner-border').remove();
					jQuery(a).removeClass('disabled').html(html);
					jQuery(all).removeClass('disabled');
				},
				success: function() {
					jQuery(a).find('.spinner-border').remove();
					jQuery(a).removeClass('disabled').html(html);
					jQuery(all).removeClass('disabled');
				}
			});
		}
	});

	jQuery('.save-all').click(function() {
		var saveAll = jQuery(this);

		if (!jQuery(saveAll).hasClass('disabled')) {
			jQuery(saveAll).addClass('disabled');

			var saveAllHtml = jQuery(this).html();

			jQuery(saveAll).html(
				jQuery('<div />', {
					'class': 'spinner-border'
				})
			);

			var allA = jQuery('#table-provider-products .save-product');
			var lastIndex = allA.length - 1;

			jQuery(allA).addClass('disabled');

			jQuery(allA).each(function(index, element) {
				var index = jQuery(element).closest('tr').data('index');
				var post_id = jQuery(element).closest('tr').data('post-id');
				var html = jQuery(element).html();

				jQuery.ajax({
					type: 'POST',
					url: 'admin-ajax.php',
					data: {
						action: 'save_product',
						post_id: post_id,
						purchase_price: jQuery('#purchase_price_' + index).val(),
						profit_margin: jQuery('#profit_margin_' + index).val(),
						_price: jQuery('#_price_' + index).val(),
						_stock: jQuery('#_stock_' + index).val()
					},
					beforeSend: function() {
						jQuery(element).html(
							jQuery('<div />', {
								'class': 'spinner-border'
							})
						);
					},
					complete: function() {
						jQuery(element).find('.spinner-border').remove();
						jQuery(element).html(html);

						if (lastIndex === index) {
							jQuery(allA).removeClass('disabled');
							jQuery(saveAll).removeClass('disabled').html(saveAllHtml);
						}
					},
					success: function() {
						jQuery(element).find('.spinner-border').remove();
						jQuery(element).html(html);

						if (lastIndex === index) {
							jQuery(allA).removeClass('disabled');
							jQuery(saveAll).removeClass('disabled').html(saveAllHtml);
						}
					}
				});
			});
		}
	});

	jQuery('#table-provider-products').on('change', '.purchase_price, .profit_margin, .price, .stock', function() {
		var max = parseInt(jQuery(this).attr('max'));
		var min = parseInt(jQuery(this).attr('min'));
		var val = jQuery(this).val();

		if (val > max) {
			jQuery(this).val(max);
		} else if (val < min) {
			jQuery(this).val(min);
		} else {
			if (jQuery(this).hasClass('purchase_price')) {
				var profit_margin = parseInt(jQuery(this).closest('tr').find('.profit_margin').val());

				if (profit_margin > 0) {
					val = parseFloat(val);

					var newValue = parseFloat(val + (val * profit_margin / 100));

					var element = jQuery(this).closest('tr').find('.price');
					var valueMin = parseInt(jQuery(element).attr('min'));
					var valueMax = parseInt(jQuery(element).attr('max'));

					if (newValue > valueMax) {
						jQuery(element).val(valueMax);
					} else if (newValue < valueMin) {
						jQuery(element).val(valueMin);
					} else {
						jQuery(element).val(newValue);
					}
				}
			} else if (jQuery(this).hasClass('profit_margin')) {
				var purchase_price = parseFloat(jQuery(this).closest('tr').find('.purchase_price').val());

				if (purchase_price > 0) {
					val = parseInt(val);

					var newValue = parseFloat(purchase_price + (purchase_price * val / 100));

					var element = jQuery(this).closest('tr').find('.price');
					var valueMin = parseInt(jQuery(element).attr('min'));
					var valueMax = parseInt(jQuery(element).attr('max'));

					if (newValue > valueMax) {
						jQuery(element).val(valueMax);
					} else if (newValue < valueMin) {
						jQuery(element).val(valueMin);
					} else {
						jQuery(element).val(newValue);
					}
				}
			} else if (jQuery(this).hasClass('price')) {
				var profit_margin = parseInt(jQuery(this).closest('tr').find('.profit_margin').val());
				var purchase_price = parseFloat(jQuery(this).closest('tr').find('.purchase_price').val());

				if (profit_margin > 0 && purchase_price > 0) {
					val = parseFloat(val);

					var newValue = parseInt((val - purchase_price) / purchase_price * 100);

					var element = jQuery(this).closest('tr').find('.profit_margin');
					var valueMin = parseInt(jQuery(element).attr('min'));
					var valueMax = parseInt(jQuery(element).attr('max'));

					if (newValue > valueMax) {
						jQuery(element).val(valueMax);
					} else if (newValue < valueMin) {
						jQuery(element).val(valueMin);
					} else {
						jQuery(element).val(newValue);
					}
				}
			}
		}
	}).on('keypress', '.stock', function(event) {
		return event.charCode >= 48 && event.charCode <= 57;
	});
});
