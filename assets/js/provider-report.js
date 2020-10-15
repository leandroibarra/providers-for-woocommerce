jQuery(document).ready(function() {
	var date = moment(new Date());

	setDate(date, date);

	jQuery('#date-range').daterangepicker({
		'autoApply': true,
		ranges: {
			'Hoy': [moment(), moment()],
			'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
			'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
			'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
			'Este Mes': [moment().startOf('month'), moment()],
			'El Mes Pasado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
		},
		'showCustomRangeLabel': false,
		'alwaysShowCalendars': true,
		'maxDate': moment(new Date()),
		'maxSpan': {
			'months': 2
		},
		'startDate': date,
		'endDate': date,
		'locale': {
			'format': 'DD/MM/YYYY',
			'separator': ' - ',
			'applyLabel': 'Aplicar',
			'cancelLabel': 'Cancelar',
			'fromLabel': 'Desde',
			'toLabel': 'Hasta',
			'customRangeLabel': 'Personalizado',
			'weekLabel': 'S',
			'daysOfWeek': [
				'Do',
				'Lu',
				'Ma',
				'Mi',
				'Ju',
				'Vi',
				'Sa'
			],
			'monthNames': [
				'Enero',
				'Febrero',
				'Marzo',
				'Abril',
				'Mayo',
				'Junio',
				'Julio',
				'Agosto',
				'Septiembre',
				'Octubre',
				'Noviembre',
				'Diciembre'
			],
			'firstDay': 1
		},
	}, function(start, end, label) {
		setDate(start, end);

		validate();
	});

	jQuery('.category-select-all').on('click', function() {
		jQuery('.category-id').prop('checked', true);
		validate();
	});

	jQuery('.category-select-none').on('click', function() {
		jQuery('.category-id').prop('checked', false);
		validate();
	});

	jQuery('.category-wrapper').on('change', '.category-id', function() {
		validate();
	});
});

function validate() {
	if (jQuery('#date-range').val() !== '' && jQuery('.category-id:checked').length > 0) {
		jQuery('.export-button').removeAttr('disabled').removeClass('disabled');
	} else {
		jQuery('.export-button').attr('disabled', true).addClass('disabled');
	}
}

function setDate(start, end) {
	jQuery('#start_date').val(moment(start).startOf('day').format('YYYY-MM-DD HH:mm:ss'));
	jQuery('#end_date').val(moment(end).endOf('day').format('YYYY-MM-DD HH:mm:ss'));
}