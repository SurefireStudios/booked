;(function($, window, document, undefined) {
	var $win = $(window);
	var $doc = $(document);
	var $field_container;

	$doc.ready(function() {

		$(document).on("booked-on-new-app", function(event) {
			$field_container = $('.field.field-paid-service');
			booked_wc_products_field($field_container);
		});

		booked_wc_btn_edit_appointment_shortcode();
		booked_wc_btn_edit_appointment_popup_app();
		booked_wc_btn_pay_appointment_shortcode();

		$(document).on("booked-before-loading-calendar-booking-options", function(event) {
			booked_wc_change_calendar_loading_paramenters();
		});

		$(document).on("booked-before-loading-booking-form", function(event) {
			booked_wc_change_booking_form_paramenters();
		});

		$(document).on("booked-on-requested-appointment", function(event,redirectObj) {
			redirectObj.redirect = booked_wc_redirect_to_checkout_if_product_option();
		});

	});

	function booked_wc_products_field(field_container) {

		var $dropdown = $('select', field_container);

		if ( $dropdown.find( 'option' ).length < 3 ){
			$dropdown.find( 'option:first-child' ).remove();
			var calendar_id = parseInt( $dropdown.data('calendar-id') ),
				product_id = $dropdown.val(),
				field_name = $dropdown.attr('name'),
				$variations_container = $dropdown.parent().find('.paid-variations');
				booked_wc_load_variations(product_id, field_name, calendar_id, $variations_container);
		}

		$dropdown.on('change', function() {
			var $this = $(this),
				calendar_id = parseInt( $this.data('calendar-id') ),
				product_id = $this.val(),
				field_name = $this.attr('name'),
				$variations_container = $this.parent().find('.paid-variations');

			booked_wc_load_variations(product_id, field_name, calendar_id, $variations_container);
		});
	}

	function booked_wc_load_variations( product_id, field_name, calendar_id, variations_container ) {

		if ( !product_id ) {
			variations_container.html('');
			return;
		};

		var data = {
			'action': booked_wc_variables.prefix + 'load_variations',
			'product_id': parseInt(product_id),
			'calendar_id': calendar_id,
			'field_name': field_name
		};

		$.post(
			booked_wc_variables.ajaxurl,
			data,
			function(response) {
				variations_container.html(response);
				resize_booked_modal();
			}
		);
	}

	function booked_wc_btn_edit_appointment_shortcode() {
		$('.booked-profile-appt-list .appt-block .edit').on('click', function(event) {

			event.preventDefault();

			var $button = $(this),
				appt_id = $button.attr('data-appt-id'),
				calendar_link = $button.attr('data-app-calendar');

			if (booked_wc_variables.i18n_confirm_appt_edit){
				confirm_edit = confirm(booked_wc_variables.i18n_confirm_appt_edit);
			} else {
				confirm_edit = true;
			}

			if ( confirm_edit === true ) {
				window.location.href = calendar_link;
			}

			return false;
		});
	}

	function booked_wc_btn_pay_appointment_shortcode() {
		$('.booked-profile-appt-list .appt-block .pay').on('click', function(event) {

			event.preventDefault();

			var $button = $(this),
				appt_id = $button.attr('data-appt-id');

			confirm_edit = confirm(booked_wc_variables.i18n_pay);
			if ( confirm_edit===true ){

				var data = {
					'action': booked_wc_variables.prefix + 'add_to_cart',
					'app_id': appt_id
				};

				jQuery.post(booked_wc_variables.ajaxurl, data, function(response) {
					if ( response.status === 'success' ) {
						window.location.href = booked_wc_variables.checkout_page;
					} else {
						alert( response.messages[0] );
					};
				}, 'json');
			}

			return false;
		});
	}

	function booked_wc_change_calendar_loading_paramenters() {

		if ( !booked_load_calendar_date_booking_options ) {
			return;
		};

		var current_url = window.location.href,
			url_parameters = current_url.replace(/^[^?]+\??/gi, ''),
			url_parameters_parts = url_parameters ? url_parameters.split('&') : false;

		if (url_parameters_parts && current_url.match('booked_wc_extension')) {

			// populate additional loading parameters
			for (var i = 0; i < url_parameters_parts.length; i++) {
				var data = url_parameters_parts[i].split('='),
					name = data[0].replace(/_\d+$/, ''),
					value = data[1];

				booked_load_calendar_date_booking_options[name] = value;
			}
		};

	}

	function booked_wc_change_booking_form_paramenters() {
		if ( !booked_appt_form_options ) {
			return;
		};

		var current_url = window.location.href,
			url_parameters = current_url.replace(/^[^?]+\??/gi, ''),
			url_parameters_parts = url_parameters ? url_parameters.split('&') : false;

		if (url_parameters_parts && current_url.match('booked_wc_extension')) {

			// populate additional loading parameters
			for (var i = 0; i < url_parameters_parts.length; i++) {
				var data = url_parameters_parts[i].split('='),
					name = data[0].replace(/_\d+$/, ''),
					value = data[1];

				booked_appt_form_options[name] = value;
			}
		};

	}

	function booked_wc_redirect_to_checkout_if_product_option() {

		var redirect = false,
			$form = $('form#newAppointmentForm');

		$('.field-paid-service', $form).each(function() {
			var $this = $(this);

			$('select', $this).each(function() {
				var $this_select = $(this);

				if ( $this_select.val()!=='' ) {
					redirect = true;
				};
			});
		});

		if ( redirect ) {
			window.location = booked_wc_variables.checkout_page;
			return true;
		}

		return false;

	}

	function booked_wc_btn_edit_appointment_popup_app() {

		$doc.on('click', '.booked-form input#submit-edit-request-appointment', function(e){

			var $thisButton = $(this);

			$('form#newAppointmentForm p.status').show().html('<i class="fa-solid fa-circle-notch fa-spin"></i>&nbsp;&nbsp;&nbsp;' + booked_js_vars.i18n_please_wait);
	        resize_booked_modal();

			e.preventDefault();

			$.ajax({
				type	: 'post',
				url 	: booked_js_vars.ajax_url,
				data	: $('#newAppointmentForm').serialize(),
				success	: function(data) {

					data = data.split('###');
					var data_result = data[0].substr(data[0].length - 5);

					if (data_result == 'error'){

						$thisButton.attr('disabled',false);
						$thisButton.parents('form').find('button.cancel').show();

						$('.booked-form input').each(function(){
							thisDefault = $(this).attr('title');
							thisVal = $(this).val();
							if (!thisVal){ $(this).val(thisDefault); }
						});

						$('form#newAppointmentForm p.status').show().html('<i class="fa-solid fa-triangle-exclamation" style="color:#E35656"></i>&nbsp;&nbsp;&nbsp;' + data[1]);
						resize_booked_modal();

					} else {

						window.location = booked_js_vars.profilePage

					}

				}
			});
		});

	}

})(jQuery, window, document);
