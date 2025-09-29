;(function($, window, document, undefined) {
	var $win = $(window);
	var $doc = $(document);

	$doc.ready(function() {

		$(document).on("booked-on-new-app", function(event) {
			$field_container = $('.field.field-paid-service');
			booked_wc_products_field();
		});
		booked_wc_add_new_options();
		booked_wc_reorder_custom_field_values();

		$('body').on('click','button.mark-paid',function(){

			var thisAppt = $(this).data('appt-id'),
				apptBlock = $(this).parents('.appt-block');

			if (thisAppt){

				var confirm_mark_paid = confirm(booked_wc_variables.i18n_mark_paid);

				if (confirm_mark_paid){

					var data = {
						'action': 'booked_wc_mark_paid',
						'appt_id': parseInt(thisAppt)
					};

					$.post(
						booked_wc_variables.ajaxurl,
						data,
						function(response) {
							if (response != 'no_order'){
								apptBlock.find('.booked-wc_status-text').removeClass('awaiting').addClass('paid');
								apptBlock.find('.booked-wc_status-text').html('<a target="_blank" href="' + response + '"><i class="fa-solid fa-pencil"></i>&nbsp;&nbsp;' + booked_wc_variables.i18n_paid + '</a>');
							}
						}
					);

				} else {

					return false;

				}

			}

			return false;

		});

	});

	$win.on('load', function() {

		init_booked_cf_payment_sortables();
		$('.booked-cf-block').on('click','.cfButton',function(e){
			init_booked_cf_payment_sortables();
		});

	});

	function update_CF_Data(CF_SortablesForm){

		var sortableContent = JSON.stringify(CF_SortablesForm.serializeArray());
		$('#booked_custom_fields').val(sortableContent);

	}

	function init_booked_cf_payment_sortables(){

		if (typeof jQuery.ui.sortable == 'function') {
			var CF_SortablesForm = $('#booked-cf-sortables-form');

			$('#booked-cf-paid-service').sortable({
				handle: ".sub-handle",
				stop: function(){
					update_CF_Data(CF_SortablesForm);
				}
			});
		}

	}

	function booked_wc_products_field() {

		var $dropdown = $('select', $field_container);
		$dropdown.on('change', function() {
			var $this = $(this),
				product_id = $this.val(),
				field_name = $this.attr('name'),
				$variations_container = $this.parent().find('.paid-variations');

			booked_wc_load_variations(product_id, field_name, $variations_container);
		});

	}

	function booked_wc_load_variations( product_id, field_name, variations_container ) {

		if ( !product_id ) {
			variations_container.html('');
			return;
		};

		var data = {
			'action': 'booked_wc_load_variations',
			'product_id': parseInt(product_id),
			'field_name': field_name
		};

		$.post(
			booked_wc_variables.ajaxurl,
			data,
			function(response) {
				variations_container.html(response);
			}
		);

	}

	function booked_wc_add_new_options() {

		// Custom Fields
		var CF_SortablesTemplatesContainer	= $('#booked-cf-sortable-templates'),
			separator = '---';

		$doc.on("booked-on-cbutton-click", function(event, params) {
			var $this = params.button_object,
				$this_parent = $this.parents('li'),
				button_type = params.button_type,
				unique_number = params.random_number; // $this_parent.length ? $this_parent.find('input[type=text]:first').attr('name').split(separator)[1] : '';

			if ( button_type === 'single-paid-service' ) {

				var $options_list = $this.parent().find('#booked-cf-paid-service');

				$( '> li', $options_list).each(function() {
					var $this_li = $(this),
						$option_field = $this_li.find('select'),
						this_name = $option_field.attr('name');

					if ( !booked_wc_strpos(this_name, separator) ) {
						var field_name = this_name + separator + unique_number;

						$option_field.attr('name', field_name);
					};
				});

				booked_wc_update_data();
			};
		});

		$doc.on('change', '#bookedCFTemplate-single-paid-service select', function() {
			booked_wc_update_data();
		});

	}

	function booked_wc_strpos(haystack, needle, offset) {
		var i = (haystack+'').indexOf(needle, (offset || 0));
		return i === -1 ? false : i;
	}

	function booked_wc_update_data(CF_SortablesForm){
		var sortables_form = $('#booked-cf-sortables-form'),
			sortableContent = JSON.stringify(sortables_form.serializeArray());
		$('#booked_custom_fields').val(sortableContent);
	}

	function booked_wc_reorder_custom_field_values() {
		var $order_items = $('#order_line_items > .item');
		if ( !$order_items.length ) {
			return;
		};

		$order_items.each(function() {
			var $this = $(this),
				$metas = $('div.view > table > tbody > tr', $this);

			$metas.each(function() {
				var $this = $(this),
					$label = $this.find('th'),
					$value = $this.find('td');

				if ( $label.text()==='Form Field:' ) {
					$label.text( $value.text().replace(/:.+/, ':') );
					$value.text( $value.text().replace(/^[^:]+:/, '') );
				};
			});
		});
	}
})(jQuery, window, document);
