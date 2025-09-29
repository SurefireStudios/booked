// make it a global variable so other scripts can access it
var booked_load_calendar_date_booking_options;

;(function($, window, document, undefined) {

	var $win = $(window);

	$.fn.spin.presets.booked = {
	 	lines: 10, // The number of lines to draw
		length: 7, // The length of each line
		width: 5, // The line thickness
		radius: 11, // The radius of the inner circle
		corners: 1, // Corner roundness (0..1)
		rotate: 0, // The rotation offset
		direction: 1, // 1: clockwise, -1: counterclockwise
		color: '#555', // #rgb or #rrggbb or array of colors
		speed: 1, // Rounds per second
		trail: 60, // Afterglow percentage
		shadow: false, // Whether to render a shadow
		hwaccel: false, // Whether to use hardware acceleration
		className: 'booked-spinner', // The CSS class to assign to the spinner
		zIndex: 2e9, // The z-index (defaults to 2000000000)
		top: '50%', // Top position relative to parent
		left: '50%' // Left position relative to parent
	}

	$.fn.spin.presets.booked_white = {
	 	lines: 13, // The number of lines to draw
		length: 11, // The length of each line
		width: 5, // The line thickness
		radius: 18, // The radius of the inner circle
		scale: 1,
		corners: 1, // Corner roundness (0..1)
		rotate: 0, // The rotation offset
		direction: 1, // 1: clockwise, -1: counterclockwise
		color: '#fff', // #rgb or #rrggbb or array of colors
		speed: 1, // Rounds per second
		trail: 60, // Afterglow percentage
		shadow: false, // Whether to render a shadow
		hwaccel: false, // Whether to use hardware acceleration
		className: 'booked-spinner', // The CSS class to assign to the spinner
		zIndex: 2e9, // The z-index (defaults to 2000000000)
		top: '50%', // Top position relative to parent
		left: '50%' // Left position relative to parent
	}

	$win.on('load', function() {

		var currentlySaving = false;

		if ($('#booked-welcome-screen').length){
			$('#booked-welcome-screen').fitVids();
		}

		// Custom Time Slots
		var timeslotsContainter = $('#customTimeslotsContainer');

		timeslotsContainter.find('.booked-customTimeslot').each(function(){

			var thisTimeslot = $(this);
			var rand = Math.floor((Math.random() * 100000000) + 1);
			init_custom_timeslot_block(thisTimeslot,rand)

		});

		$('body').on('click','.addCustomTimeslot',function(e){

			e.preventDefault();
			var thisTimeslot = $('.booked-customTimeslotTemplate').clone().appendTo(timeslotsContainter).removeClass().addClass('booked-customTimeslot').show();
			var rand = Math.floor((Math.random() * 100000000) + 1);
			init_custom_timeslot_block(thisTimeslot,rand);

		});

		var preventApptsBefore = $('body').find('.booked_prevent_appointments_before');
		var preventApptsAfter = $('body').find('.booked_prevent_appointments_after');

		preventApptsBefore.datepicker({
			dateFormat: "yy-mm-dd",
			beforeShow: function(input, inst) {
				$('#ui-datepicker-div').removeClass();
				$('#ui-datepicker-div').addClass('booked_custom_date_picker');
		    },
		    onSelect: function(selected) {
				$('.booked_prevent_appointments_after').datepicker("option","minDate", selected);
				booked_datepicker_show_formatted_date( 'booked_prevent_appointments_before', selected );
	        }
		});
		preventApptsAfter.datepicker({
			dateFormat: "yy-mm-dd",
			beforeShow: function(input, inst) {
				$('#ui-datepicker-div').removeClass();
				$('#ui-datepicker-div').addClass('booked_custom_date_picker');
		    },
		    onSelect: function(selected) {
				$('.booked_prevent_appointments_before').datepicker("option","maxDate", selected);
				booked_datepicker_show_formatted_date( 'booked_prevent_appointments_after', selected );
	        }
		});

		$('body').on('change','select[name="booked_booking_type"]',function(){
			var thisSelectVal = $(this).find('option:selected').val();
			if (thisSelectVal == "guest"){
				var selectedRadio = $('input[name="booked_appointment_redirect_type"]:checked').val();
				if (selectedRadio == 'booked-profile'){
					$('input[name="booked_appointment_redirect_type"][value=""]').prop("checked",true);
				}
			}
		});

		$('body').on('change','#booked_hide_unavailable_timeslots,#booked_public_appointments',function(){
			var thisCheckbox = $(this);
			var thisID = thisCheckbox.attr('id');
			if (thisCheckbox.is(':checked') && thisID == 'booked_hide_unavailable_timeslots'){
				$('#booked_public_appointments').prop('checked', false);
			} else if (thisCheckbox.is(':checked') && thisID == 'booked_public_appointments') {
				$('#booked_hide_unavailable_timeslots').prop('checked', false);
			}
		});

		$('body').on('click','form#newAppointmentForm input[type=submit]',function(e){
			e.preventDefault();

			var thisForm			= $('#newAppointmentForm'),
				customerType 		= thisForm.find('input[name=customer_type]:checked').val(),
				customerID			= thisForm.find('select[name=user_id]').val(),
				name				= thisForm.find('input[name=name]').val(),
				surname				= thisForm.find('input[name=surname]').val(),
				guest_name			= thisForm.find('input[name=guest_name]').val(),
				guest_email			= thisForm.find('input[name=guest_email]').val(),
				guest_surname		= thisForm.find('input[name=guest_surname]').val(),
				email				= thisForm.find('input[name=email]').val(),
				password			= thisForm.find('input[name=password]').val(),
				date				= thisForm.find('input[name=date]').val(),
				calendar_id			= thisForm.data('calendar-id'),
				$activeTD 			= $('table.booked-calendar').find('td.active'),
				surnameActive		= thisForm.find('input[name=surname]').length,
				guest_surnameActive	= thisForm.find('input[name=guest_surname]').length,
				guest_emailActive	= thisForm.find('input[name=guest_email]').length,
				showRequiredError	= false;

			$(this).parents('form.booked-form').find('input,textarea,select').each(function(i,field){

				var required = $(this).attr('required');

				if (required && $(field).attr('type') == 'hidden'){
					var fieldParts = $(field).attr('name');
					fieldParts = fieldParts.split('---');
					fieldName = fieldParts[0];
					fieldNumber = fieldParts[1].split('___');
					fieldNumber = fieldNumber[0];

					if (fieldName == 'radio-buttons-label'){
						var radioValue = false;
						$('input:radio[name="single-radio-button---'+fieldNumber+'[]"]:checked').each(function(){
							if ($(this).val()){
								radioValue = $(this).val();
							}
						});
						if (!radioValue){
							showRequiredError = true;
						}
					} else if (fieldName == 'checkboxes-label'){
						var checkboxValue = false;
						$('input:checkbox[name="single-checkbox---'+fieldNumber+'[]"]:checked').each(function(){
							if ($(this).val()){
								checkboxValue = $(this).val();
							}
						});
						if (!checkboxValue){
							showRequiredError = true;
						}
					}

				} else if (required && $(field).attr('type') != 'hidden' && $(field).val() == ''){
		            showRequiredError = true;
		        }

		    });

		    if (showRequiredError){
			    alert(booked_js_vars.i18n_fill_out_required_fields);
			    return false;
		    }

		    if (customerType == 'guest' && guest_name && !guest_surnameActive && !guest_emailActive ||
		    	customerType == 'guest' && guest_name && guest_surnameActive && guest_surname && !guest_emailActive ||
		    	customerType == 'guest' && guest_name && guest_emailActive && guest_email && !guest_surnameActive ||
		    	customerType == 'guest' && guest_name && guest_emailActive && guest_email && guest_surnameActive && guest_surname ){

				$thisButton = $(this);

				$('form.booked-form input').each(function(){
					thisDefault = $(this).attr('title');
					thisVal = $(this).val();
					if (thisDefault == thisVal){ $(this).val(''); }
				});

				$thisButton.val(booked_js_vars.i18n_please_wait).attr('disabled',true);
				$thisButton.parents('form').find('button.cancel').attr('disabled',true);

				booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
					type	: 'post',
					url 	: booked_js_vars.ajax_url,
					data	: $('form.booked-form').serializeArray(),
					success: function(date) {

						data = date.split('###');

						if (data[0] == 'error'){

							$thisButton.val( booked_js_vars.i18n_create_appointment ).attr('disabled',false);
							$thisButton.parents('form').find('button.cancel').attr('disabled',false);

							$('form.booked-form input').each(function(){
								thisDefault = $(this).attr('title');
								thisVal = $(this).val();
								if (!thisVal){ $(this).val(thisDefault); }
							});

							alert(data[1]);

						} else {

							$.ajax({
								url		: booked_js_vars.ajax_url,
								type	: 'post',
								data	: {'action':'booked_admin_calendar_date','nonce': booked_js_vars.nonce,'date':data[1],'calendar_id':calendar_id},
								success	: function(html){
									$('tr.entryBlock').find('td').html( html );
									$('tr.entryBlock').find('.booked-appt-list').show();
									$('tr.entryBlock').find('.booked-appt-list').addClass('shown');
									$('.bookedAppointmentTab.active').fadeIn(300);
								}
							});

							$.ajax({
								url		: booked_js_vars.ajax_url,
								type	: 'post',
								data	: {'action':'booked_admin_refresh_date_square','nonce': booked_js_vars.nonce,'date':data[1],'calendar_id':calendar_id},
								success	: function(html){
									$activeTD.replaceWith(html);
									adjust_calendar_boxes();
								}
							});

							close_booked_modal();

						}

					}
				});

				return false;

			} else if ( customerType == 'guest' && guest_emailActive && !guest_email && guest_surnameActive && !guest_surname ||
			 			customerType == 'guest' && guest_emailActive && !guest_email && guest_surnameActive && guest_surname ){

				alert(booked_js_vars.i18n_appt_required_guest_fields_all);

			} else if ( customerType == 'guest' && guest_emailActive && !guest_email && !guest_surnameActive ){

				alert(booked_js_vars.i18n_appt_required_guest_fields_name_email);

			} else if ( customerType == 'guest' && guest_surnameActive && !guest_surname ){

				alert(booked_js_vars.i18n_appt_required_guest_fields_surname);

			} else if ( customerType == 'guest' && !guest_name ){

				alert(booked_js_vars.i18n_appt_required_guest_fields);

			}

			if (customerType == 'current' && customerID){

				$('form.booked-form input').each(function(){
					thisDefault = $(this).attr('title');
					thisVal = $(this).val();
					if (thisDefault == thisVal){ $(this).val(''); }
				});

				$(this).val(booked_js_vars.i18n_please_wait).attr('disabled',true);
				$(this).parents('form').find('button.cancel').attr('disabled',true);

				booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
					type	: 'post',
					url 	: booked_js_vars.ajax_url,
					data	: $('form.booked-form').serializeArray(),
					success: function(date) {

						data = date.split('###');

						if (data[0] == 'error'){

							$thisButton.val( booked_js_vars.i18n_create_appointment ).attr('disabled',false);
							$thisButton.parents('form').find('button.cancel').attr('disabled',false);

							$('form.booked-form input').each(function(){
								thisDefault = $(this).attr('title');
								thisVal = $(this).val();
								if (!thisVal){ $(this).val(thisDefault); }
							});

							alert(data[1]);

						} else {

							$.ajax({
								url		: booked_js_vars.ajax_url,
								type	: 'post',
								data	: {'action':'booked_admin_calendar_date','nonce': booked_js_vars.nonce,'date':data[1],'calendar_id':calendar_id},
								success	: function(html){
									$('tr.entryBlock').find('td').html( html );
									$('tr.entryBlock').find('.booked-appt-list').show();
									$('tr.entryBlock').find('.booked-appt-list').addClass('shown');
									$('.bookedAppointmentTab.active').fadeIn(300);
								}
							});

							$.ajax({
								url		: booked_js_vars.ajax_url,
								type	: 'post',
								data	: {'action':'booked_admin_refresh_date_square','nonce': booked_js_vars.nonce,'date':data[1],'calendar_id':calendar_id},
								success	: function(html){
									$activeTD.replaceWith(html);
									adjust_calendar_boxes();
								}
							});

						}

						close_booked_modal();

					}
				});

				return false;

			} else if (customerType == 'current' && !customerID){

				alert(booked_js_vars.i18n_choose_customer);

			}

			if ( customerType == 'new' && name && email && !surnameActive || customerType == 'new' && name && email && surnameActive && surname ){

				$('form.booked-form input').each(function(){
					thisDefault = $(this).attr('title');
					thisVal = $(this).val();
					if (thisDefault == thisVal){ $(this).val(''); }
				});

				$thisButton = $(this);

				$thisButton.val(booked_js_vars.i18n_please_wait).attr('disabled',true);
				$thisButton.parents('form').find('button.cancel').attr('disabled',true);

				booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
					type	: 'post',
					url 	: booked_js_vars.ajax_url,
					data	: $('form.booked-form').serialize(),
					success: function(data) {

						data = date.split('###');

						if (data[0] == 'error'){

							$thisButton.val( booked_js_vars.i18n_create_appointment ).attr('disabled',false);
							$thisButton.parents('form').find('button.cancel').attr('disabled',false);

							$('form.booked-form input').each(function(){
								thisDefault = $(this).attr('title');
								thisVal = $(this).val();
								if (!thisVal){ $(this).val(thisDefault); }
							});

							alert(data[1]);

						} else {

							$.ajax({
								url		: booked_js_vars.ajax_url,
								type	: 'post',
								data	: {'action':'booked_admin_calendar_date','nonce': booked_js_vars.nonce,'date':data[1],'calendar_id':calendar_id},
								success	: function(html){
									$('tr.entryBlock').find('td').html( html );
									$('tr.entryBlock').find('.booked-appt-list').show();
									$('tr.entryBlock').find('.booked-appt-list').addClass('shown');
									$('.bookedAppointmentTab.active').fadeIn(300);
								}
							});

							$.ajax({
								url		: booked_js_vars.ajax_url,
								type	: 'post',
								data	: {'action':'booked_admin_refresh_date_square','nonce': booked_js_vars.nonce,'date':data[1],'calendar_id':calendar_id},
								success	: function(html){
									$activeTD.replaceWith(html);
									adjust_calendar_boxes();
								}
							});

							close_booked_modal();

						}
					}
				});

				return false;

			} else if ( customerType == 'new' && !name || customerType == 'new' && !email || customerType == 'new' && surnameActive && !surname ){

				alert(booked_js_vars.i18n_appt_required_fields);

			}

		});


		$('body').on('click','form#editAppointmentForm input[type=submit]',function(e){

			e.preventDefault();

			var thisForm			= $('#editAppointmentForm'),
				name				= thisForm.find('input[name=name]').val(),
				surname				= thisForm.find('input[name=surname]').val(),
				email				= thisForm.find('input[name=email]').val(),
				date				= thisForm.find('input[name=appt_date]').val(),
				calendar_id			= thisForm.find('input[name=calendar_id]').val(),
				$thisButton			= $(this),
				$activeTD 			= $('table.booked-calendar').find('td.active'),
				showRequiredError	= false;

			thisForm.find('input,select').each(function(i,field){

				var required = $(this).attr('required');
				if ( required && $(field).val() == '' ){
		            showRequiredError = true;
		        }

		    });

		    if (showRequiredError){
			    alert(booked_js_vars.i18n_fill_out_required_fields);
			    return false;
		    }

			$thisButton.val(booked_js_vars.i18n_please_wait).attr('disabled',true);

			booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
				type	: 'post',
				url 	: booked_js_vars.ajax_url,
				data	: thisForm.serializeArray(),
				success: function(result) {

					data = result.split('###');

					if (data[0] == 'error'){

						$thisButton.val( booked_js_vars.i18n_update_appointment ).attr('disabled',false);
						alert(data[1]);

					} else if ( !$('table.booked-calendar').length ) {

						location.reload();

					} else {

						var active_date = $activeTD.data('date');

						$.ajax({
							url		: booked_js_vars.ajax_url,
							type	: 'post',
							data	: {'action':'booked_admin_calendar_date','nonce': booked_js_vars.nonce,'date':active_date,'calendar_id':calendar_id},
							success	: function(html){
								$('tr.entryBlock').find('td').html( html );
								$('tr.entryBlock').find('.booked-appt-list').show();
								$('tr.entryBlock').find('.booked-appt-list').addClass('shown');
								$('.bookedAppointmentTab.active').fadeIn(300);
							}
						});

						$.ajax({
							url		: booked_js_vars.ajax_url,
							type	: 'post',
							data	: {'action':'booked_admin_refresh_date_square','nonce': booked_js_vars.nonce,'date':active_date,'calendar_id':calendar_id},
							success	: function(html){
								$activeTD.replaceWith(html);
								adjust_calendar_boxes();
							}
						});

						if ( active_date != date ){
							$.ajax({
								url		: booked_js_vars.ajax_url,
								type	: 'post',
								data	: {'action':'booked_admin_refresh_date_square','nonce': booked_js_vars.nonce,'inactive':false,'date':date,'calendar_id':calendar_id},
								success	: function(html){
									$( 'table.booked-calendar' ).find( 'td[data-date="' + date + '"]' ).replaceWith(html);
									adjust_calendar_boxes();
								}
							});
						}

						close_booked_modal();

					}

				}

			});

		});


		function init_custom_timeslot_block(thisTimeslot,rand){

			hideAddCustomTimeslotsForm();
			thisTimeslot.find('#vacationDayCheckbox').attr('id','vacationDayCheckbox-'+rand);
			thisTimeslot.find('label[for="vacationDayCheckbox"]').attr('for','vacationDayCheckbox-'+rand);

			var disableApptsValue = thisTimeslot.find('#vacationDayCheckbox-'+rand).is(':checked');
			if (disableApptsValue){
				hideAddCustomTimeslotsForm();
				thisTimeslot.find('button.addSingleTimeslot,button.addBulkTimeslots,.customTimeslotsList').hide();
			} else {
				$('button.addBulkTimeslots').attr('disabled',false);
				$('button.addSingleTimeslot').attr('disabled',false);
				$('#booked-customTimePickerTemplates').find('.customBulk,customSingle').show();
			}

			thisTimeslot.on('change','#vacationDayCheckbox-'+rand,function(){
				thisCheckbox = $(this);
				if (thisCheckbox.is(':checked')){
					hideAddCustomTimeslotsForm();
					thisTimeslot.find('button.addSingleTimeslot,button.addBulkTimeslots,.customTimeslotsList').hide();
				} else {
					$('#booked-customTimePickerTemplates').find('.customBulk,customSingle').show();
					thisTimeslot.find('button.addSingleTimeslot,button.addBulkTimeslots,.customTimeslotsList').show();
				}
			});

			thisTimeslot.find('.booked_custom_start_date').datepicker({
				dateFormat: "yy-mm-dd",
		        beforeShow: function(input, inst) {
					$('#ui-datepicker-div').removeClass();
					$('#ui-datepicker-div').addClass('booked_custom_date_picker');
			    },
		        onSelect: function(selected) {
		        	thisTimeslot.find('.booked_custom_end_date').datepicker("option","minDate", selected);
		        	updateCustomTimeslotEncodedField();
		        	$('#booked-saveCustomTimeslots').prop('disabled',false).addClass('button-primary');
		        },
		        onClose: function(selected) {
		           thisTimeslot.find('.booked_custom_start_date').datepicker("option","maxDate", selected);
		           updateCustomTimeslotEncodedField();
		           $('#booked-saveCustomTimeslots').prop('disabled',false).addClass('button-primary');
		        }
		    });
		    thisTimeslot.find('.booked_custom_end_date').datepicker({
			    dateFormat: "yy-mm-dd",
		        beforeShow: function(input, inst) {
					$('#ui-datepicker-div').removeClass();
					$('#ui-datepicker-div').addClass('booked_custom_date_picker');
			    },
		        onSelect: function(selected) {
		           thisTimeslot.find('.booked_custom_start_date').datepicker("option","maxDate", selected);
		           updateCustomTimeslotEncodedField();
		           $('#booked-saveCustomTimeslots').prop('disabled',false).addClass('button-primary');
		        },
		        onClose: function(selected) {
		           thisTimeslot.find('.booked_custom_start_date').datepicker("option","maxDate", selected);
		           updateCustomTimeslotEncodedField();
		           $('#booked-saveCustomTimeslots').prop('disabled',false).addClass('button-primary');
		        }
		    });

			thisTimeslot.on('click','button.addSingleTimeslot',function(e){
				e.preventDefault();
				$(this).attr('disabled',true);
				$('button.addBulkTimeslots').prop('disabled',false);
				$('#booked-saveCustomTimeslots').prop('disabled',true);
				$('#booked-customTimePickerTemplates').find('input').val('').attr('checked',false);
				$('#booked-customTimePickerTemplates').find('select').prop('selectedIndex',0);
				$('#booked-customTimePickerTemplates').appendTo(thisTimeslot).show();
				$('#booked-customTimePickerTemplates').find('.customBulk').hide();
				$('#booked-customTimePickerTemplates').find('.customSingle').hide().fadeIn(200);
			});

			thisTimeslot.on('click','button.addBulkTimeslots',function(e){
				e.preventDefault();
				$(this).attr('disabled',true);
				$('button.addSingleTimeslot').prop('disabled',false);
				$('#booked-saveCustomTimeslots').prop('disabled',true);
				$('#booked-customTimePickerTemplates').find('input').val('').attr('checked',false);
				$('#booked-customTimePickerTemplates').find('select').prop('selectedIndex',0);
				$('#booked-customTimePickerTemplates').appendTo(thisTimeslot).show();
				$('#booked-customTimePickerTemplates').find('.customBulk').hide().fadeIn(200);
				$('#booked-customTimePickerTemplates').find('.customSingle').hide();
			});

			$('#booked-customTimePickerTemplates').on('click','button.cancel',function(e){
				e.preventDefault();
				hideAddCustomTimeslotsForm();
			});

			thisTimeslot.on('click','.deleteCustomTimeslot',function(e){
				e.preventDefault();
				var confirmDelete = confirm(booked_js_vars.i18n_confirm_cts_delete);
				if (confirmDelete){
					$('#booked-customTimePickerTemplates').find('.customBulk,customSingle').hide();
					$('#booked-customTimePickerTemplates').hide().appendTo('#booked-custom-timeslots');
					$('button.addBulkTimeslots').prop('disabled',false);
					$('button.addSingleTimeslot').prop('disabled',false);
					$('#booked-saveCustomTimeslots').prop('disabled',false);
					$(this).parents('.booked-customTimeslot').remove();

					var lastItem = false;
					var lastItemType = false;

					$('#customTimeslotsContainer > *').each(function(){
						var thisItem = $(this);
						var isH3 = thisItem.is('h3');
						var isDIV = thisItem.is('div');
						if (lastItemType == 'h3' && isH3){
							lastItem.remove();
						}
						if (isH3){ lastItemType = 'h3'; }
						if (isDIV){ lastItemType = 'div'; }
						lastItem = $(this);
					});

					if (lastItemType == 'h3'){
						lastItem.remove();
					}

					updateCustomTimeslotEncodedField();
					$('#booked-saveCustomTimeslots').prop('disabled',false).addClass('button-primary').trigger('click');

				}
			});

			thisTimeslot.on('change','> input, > select',function(){
				updateCustomTimeslotEncodedField();
				$('#booked-saveCustomTimeslots').prop('disabled',false).addClass('button-primary');
			});

			thisTimeslot.on('change','input#all_day_custom',function(){

				var thisCheckbox = $(this);

				if (thisCheckbox.is(':checked')){
					thisCheckbox.parents('.customSingle').find('select[name="startTime"]').val('0000').hide();
					thisCheckbox.parents('.customSingle').find('select[name="endTime"]').val('2400').hide();
				} else {
					thisCheckbox.parents('.customSingle').find('select[name="startTime"]').prop('selectedIndex',0).show();
					thisCheckbox.parents('.customSingle').find('select[name="endTime"]').prop('selectedIndex',0).show();
				}

			});

			thisTimeslot.on('click','.customTimeslotsList .delete',function(e){
				e.preventDefault();

				var confirmDelete = confirm(booked_js_vars.i18n_confirm_cts_delete);
				if (confirmDelete){

					var thisButton			= $(this),
						thisTimeslot		= thisButton.parent();
						deleteTimeslot 		= thisButton.parent().attr('data-timeslot'),
						calendar_id			= thisButton.parents('.booked-customTimeslot').find('[name="booked_custom_calendar_id"]').val(),
						start_date			= thisButton.parents('.booked-customTimeslot').find('[name="booked_custom_start_date"]').val(),
						end_date			= thisButton.parents('.booked-customTimeslot').find('[name="booked_custom_end_date"]').val(),
						currentTimesBox 	= thisButton.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots]'),
						currentArray 		= thisButton.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots]').val(),
						currentTimesBoxDetails 	= thisButton.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots_details]'),
						currentArrayDetails 	= thisButton.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots_details]').val(),
						timeslotList		= thisButton.parents('.booked-customTimeslot').find('.customTimeslotsList');

					booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
						type	: 'post',
						url 	: booked_js_vars.ajax_url,
						data	: {
							'action'     			: 'booked_admin_delete_custom_timeslot',
							'nonce'					: booked_js_vars.nonce,
							'calendar_id'			: calendar_id,
							'start_date'			: start_date,
							'end_date'				: end_date,
							'timeslot'     			: deleteTimeslot,
							'currentArray'			: currentArray,
							'currentArrayDetails'	: currentArrayDetails,
						},
						beforeSend: function(){
							savingState(true);
						},
						success: function(data) {
							response = JSON.parse(data);

							currentTimesBox.val( JSON.stringify(response.timeslot) );
							currentTimesBoxDetails.val( JSON.stringify(response.timeslot_details) );

							$('#booked-customTimePickerTemplates').find('input').val('').attr('checked',false);
							$('#booked-customTimePickerTemplates').find('select').prop('selectedIndex',0);
							$('#booked-customTimePickerTemplates').find('.customBulk,customSingle').hide();
							$('#booked-customTimePickerTemplates').hide().appendTo('#booked-custom-timeslots');
							$('button.addBulkTimeslots').prop('disabled',false);
							$('button.addSingleTimeslot').prop('disabled',false);
							$('#booked-saveCustomTimeslots').prop('disabled',false);
							$('.customSingle').find('select[name="startTime"]').prop('selectedIndex',0).show();
							$('.customSingle').find('select[name="endTime"]').prop('selectedIndex',0).show();
							updateCustomTimeslotEncodedField();
							loadCustomTimeSlots(timeslotList,data);
							thisTimeslot.slideUp(200);
							$('#booked-saveCustomTimeslots').trigger('click');
						}
					});

				}

			});

			var preventMultiClicks;

			thisTimeslot.on('click','.changeCount',function(e){
				e.preventDefault();

				if (!currentlySaving){

					var $button      	= $(this),
						$timeslot		= $button.parents('.timeslot');
						$countText	 	= $button.parent().find('.count'),
						countAdjust  	= $button.attr('data-count'),
						currentTimesBox = $button.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots]'),
						currentArray 	= $button.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots]').val(),
						timeslot 		= $button.parents('.timeslot').attr('data-timeslot'),
						currentCount 	= $countText.find('em').text();

					clearTimeout(preventMultiClicks);

					newCount = parseInt(currentCount) + parseInt(countAdjust);
					if (newCount < 1) {

						newCount = 1;

					} else {

						if (newCount != 1) { slot_text = booked_js_vars.i18n_slots; } else { slot_text = booked_js_vars.i18n_slot; }
						$countText.html('<em>' + newCount + '</em> ' + slot_text);

						preventMultiClicks = setTimeout(function(){

							booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
								type	: 'post',
								url 	: booked_js_vars.ajax_url,
								data	: {
									'action'     	: 'booked_admin_adjust_custom_timeslot_count',
									'nonce'			: booked_js_vars.nonce,
									'newCount'		: newCount,
									'timeslot'     	: timeslot,
									'currentArray'	: currentArray,
								},
								success: function(data) {
									currentTimesBox.val(data);
									updateCustomTimeslotEncodedField();
									$('#booked-saveCustomTimeslots').prop('disabled',false).trigger('click');
									currentlySaving = false;
								}
							});

						},350);

					}

				}

			});

			// Single add
			thisTimeslot.on('click','.addSingleTimeslot_button',function(e){
				e.preventDefault();

				var $button = $(this);
				$button.attr('disabled',true);

				var addTimeslotsFormWrapper = $('#booked-customTimePickerTemplates .customSingle');

				var startTime 		= addTimeslotsFormWrapper.find('select[name=startTime]').val(),
					startTimeText	= addTimeslotsFormWrapper.find('select[name=startTime] :selected').text(),
					endTime 		= addTimeslotsFormWrapper.find('select[name=endTime]').val(),
					endTimeText		= addTimeslotsFormWrapper.find('select[name=endTime] :selected').text(),
					count 			= addTimeslotsFormWrapper.find('select[name=count]').val(),
					countText		= addTimeslotsFormWrapper.find('select[name=count] :selected').text(),
					title 			= addTimeslotsFormWrapper.find('input[name=title]').val(),
					calendar_id		= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('[name="booked_custom_calendar_id"]').val(),
					start_date		= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('[name="booked_custom_start_date"]').val(),
					end_date		= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('[name="booked_custom_end_date"]').val(),
					currentTimesBox	= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots]'),
					currentTimes 	= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots]').val(),
					currentTimesDetailsBox	= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots_details]'),
					currentTimesDetails 	= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots_details]').val(),
					timeslotList	= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('.customTimeslotsList');

				var formData = addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('#single-timeslot-form').serializeObject();
					formData['action'] = 'booked_admin_add_custom_timeslot';
					formData['calendar_id'] = calendar_id;
					formData['currentTimes'] = currentTimes;
					formData['currentTimesDetails'] = currentTimesDetails;
					formData['start_date'] = start_date;
					formData['end_date'] = end_date;
					formData['title'] = title;
					formData['nonce'] = booked_js_vars.nonce;

				if (startTime && endTime && count){

					if (endTime <= startTime && startTime != 'allday'){
						$button.attr('disabled',false);
						alert(booked_js_vars.i18n_time_error);
						return false;
					}

					if (startTime == '0000' && endTime == '2400' || startTime == 'allday' && endTime == '2400'){
						appt_add_confirm = confirm(booked_js_vars.i18n_single_add_confirm + ':\n'+booked_js_vars.i18n_all_day+' x'+count);
					} else {
						appt_add_confirm = confirm(booked_js_vars.i18n_single_add_confirm + ':\n'+startTimeText+' '+booked_js_vars.i18n_to+' '+endTimeText+' x'+count);
					}

					if (appt_add_confirm == true){
						booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
							type	: 'post',
							url 	: booked_js_vars.ajax_url,
							data	: formData,
							beforeSend: function(){
								savingState(true);
							},
							success: function(data) {
								response = JSON.parse(data);

								currentTimesBox.val( JSON.stringify( response.timeslot ) );
								currentTimesDetailsBox.val( JSON.stringify( response.timeslot_details ) );

								$button.attr('disabled',false);
								$('#booked-customTimePickerTemplates').find('input').val('').attr('checked',false);
								$('#booked-customTimePickerTemplates').find('select').prop('selectedIndex',0);
								$('#booked-customTimePickerTemplates').find('.customBulk,customSingle').hide();
								$('#booked-customTimePickerTemplates').hide().appendTo('#booked-custom-timeslots');
								$('button.addBulkTimeslots').prop('disabled',false);
								$('button.addSingleTimeslot').prop('disabled',false);
								$('#booked-saveCustomTimeslots').prop('disabled',false);
								$('.customSingle').find('select[name="startTime"]').prop('selectedIndex',0).show();
								$('.customSingle').find('select[name="endTime"]').prop('selectedIndex',0).show();
								updateCustomTimeslotEncodedField();
								loadCustomTimeSlots(timeslotList,data);
								$('#booked-saveCustomTimeslots').trigger('click');
							}
						});

					}
				} else {
					$button.attr('disabled',false);
					alert(booked_js_vars.i18n_all_fields_required);
					return false;
				}

			});

			// Bulk add
			thisTimeslot.on('click','.addBulkTimeslots_button',function(e){
				e.preventDefault();

				var $button = $(this);
				$button.attr('disabled',true);

				var addTimeslotsFormWrapper = $('#booked-customTimePickerTemplates .customBulk');

				var startTime 		= addTimeslotsFormWrapper.find('select[name=startTime]').val(),
					startTimeText	= addTimeslotsFormWrapper.find('select[name=startTime] :selected').text(),
					endTime 		= addTimeslotsFormWrapper.find('select[name=endTime]').val(),
					endTimeText		= addTimeslotsFormWrapper.find('select[name=endTime] :selected').text(),
					interval 		= addTimeslotsFormWrapper.find('select[name=interval]').val(),
					time_between 	= addTimeslotsFormWrapper.find('select[name=time_between]').val(),
					intervalText	= addTimeslotsFormWrapper.find('select[name=interval] :selected').text(),
					count 			= addTimeslotsFormWrapper.find('select[name=count]').val(),
					countText		= addTimeslotsFormWrapper.find('select[name=count] :selected').text(),
					title 			= addTimeslotsFormWrapper.find('input[name=title]').val(),
					calendar_id		= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('[name="booked_custom_calendar_id"]').val(),
					start_date		= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('[name="booked_custom_start_date"]').val(),
					end_date		= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('[name="booked_custom_end_date"]').val(),
					currentTimesBox	= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots]'),
					currentTimes 	= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots]').val(),
					currentTimesDetailsBox	= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots_details]'),
					currentTimesDetails 	= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('input[name=booked_this_custom_timelots_details]').val(),
					timeslotList	= addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('.customTimeslotsList');

				var formData = addTimeslotsFormWrapper.parents('.booked-customTimeslot').find('#bulk-timeslot-form').serializeObject();
					formData['action'] = 'booked_admin_add_custom_timeslots';
					formData['calendar_id'] = calendar_id;
					formData['currentTimes'] = currentTimes;
					formData['currentTimesDetails'] = currentTimesDetails;
					formData['start_date'] = start_date;
					formData['end_date'] = end_date;
					formData['title'] = title;
					formData['nonce'] = booked_js_vars.nonce;

				if (startTime && endTime && interval && count){

					if (endTime <= startTime){
						$button.attr('disabled',false);
						alert(booked_js_vars.i18n_time_error);
						return false;
					}

					appt_add_confirm = confirm(booked_js_vars.i18n_bulk_add_confirm);
					if (appt_add_confirm == true){

						booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
							type	: 'post',
							url 	: booked_js_vars.ajax_url,
							data	: formData,
							beforeSend: function(){
								savingState(true);
							},
							success: function(data) {
								response = JSON.parse(data);

								currentTimesBox.val( JSON.stringify( response.timeslot ) );
								currentTimesDetailsBox.val( JSON.stringify( response.timeslot_details ) );

								$button.attr('disabled',false);
								$('#booked-customTimePickerTemplates').find('input').val('').attr('checked',false);
								$('#booked-customTimePickerTemplates').find('select').prop('selectedIndex',0);
								$('#booked-customTimePickerTemplates').find('.customBulk,customSingle').hide();
								$('#booked-customTimePickerTemplates').hide().appendTo('#booked-custom-timeslots');
								$('button.addBulkTimeslots').prop('disabled',false);
								$('button.addSingleTimeslot').prop('disabled',false);
								$('#booked-saveCustomTimeslots').prop('disabled',false);
								updateCustomTimeslotEncodedField();
								loadCustomTimeSlots(timeslotList,data);
								$('#booked-saveCustomTimeslots').trigger('click');
							}
						});

					}
				} else {
					$button.attr('disabled',false);
					alert(booked_js_vars.i18n_all_fields_required);
					return false;
				}

			});

		}

		function hideAddCustomTimeslotsForm(){

			$('#booked-customTimePickerTemplates').find('.customBulk,customSingle').hide();
			$('#booked-customTimePickerTemplates').hide().appendTo('#booked-custom-timeslots');
			$('button.addBulkTimeslots').attr('disabled',false);
			$('button.addSingleTimeslot').attr('disabled',false);
			updateCustomTimeslotEncodedField();

		}

		function loadCustomTimeSlots(timeslotsBlock,json_array){
			var parentTimeslotBlock = timeslotsBlock.parents('.booked-customTimeslot'),
				start_date = parentTimeslotBlock.find('[name="booked_custom_start_date"]').val(),
				end_date = parentTimeslotBlock.find('[name="booked_custom_end_date"]').val(),
				calendar_id = parentTimeslotBlock.find('[name="booked_custom_calendar_id"]').val(),
				json_array_jsoned = JSON.parse(json_array);

			$.ajax({
				url		: booked_js_vars.ajax_url,
				type	: 'post',
				data	: {'action':'booked_admin_custom_timeslots_list','nonce': booked_js_vars.nonce,'json_array':JSON.stringify(json_array_jsoned.timeslot),'json_array_detailed':JSON.stringify(json_array_jsoned.timeslot_details),'start_date':start_date,'end_date':end_date,'calendar_id':calendar_id},
				success	: function(html){
					timeslotsBlock.html( html );
				}
			});

		}

		$('#booked-custom-timeslots').on('click','#booked-saveCustomTimeslots',function(e){
			e.preventDefault();
			var custom_timeslots_encoded	        = $('#custom_timeslots_encoded').val();

			booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
				type	: 'post',
				url 	: booked_js_vars.ajax_url,
				data	: {
					'action'     				        : 'booked_admin_save_custom_time_slots',
					'nonce': booked_js_vars.nonce,
					'custom_timeslots_encoded'          : custom_timeslots_encoded
				},
				beforeSend: function(){
					$('#booked-saveCustomTimeslots').attr('disabled',true);
					savingState(true);
				},
				success: function(data) {
					$('#customTimeslotsContainer .booked-customTimeslot').css('border-color','#ddd');
					$('#booked-saveCustomTimeslots').prop('disabled',true).removeClass('button-primary');
				}
			});

		});
		/* END Custom Time Slots */



		if ($('.booked-color-field').length){
			$('.booked-color-field').wpColorPicker();
		}

		// Upload Image Button
		var _custom_media = true,
		_orig_send_attachment = wp.media.editor.send.attachment;

		$('#booked_email_logo_button').on('click', function(e) {
			var send_attachment_bkp = wp.media.editor.send.attachment;
			var button = $(this);
			var id = button.attr('id').replace('_button', '');
			_custom_media = true;
			wp.media.editor.send.attachment = function(props, attachment){
				if ( _custom_media ) {
					$("#"+id).val(attachment.url);
					$("#"+id+"-img").attr('src',attachment.url).show();
					$('#booked_email_logo_button_remove').show();
				} else {
					return _orig_send_attachment.apply( this, [props, attachment] );
				};
			}

			wp.media.editor.open(button);
			return false;
		});

		$('#booked_email_logo_button_remove').on('click', function(e) {
			e.preventDefault();
			$("#booked_email_logo").val('');
			$("#booked_email_logo-img").attr('src','').hide();
			$(this).hide();
		});
		// END Upload Image Button

		// Custom Fields
		var CF_SortablesForm				= $('#booked-cf-sortables-form'),
			CF_SortablesContainer			= $('#booked-cf-sortables'),
			CF_SortablesTemplatesContainer	= $('#booked-cf-sortable-templates'),
			CF_SingleLineTextTemplate		= $('#bookedCFTemplate-single-line-text'),
			CF_ParagraphTextTemplate		= $('#bookedCFTemplate-paragraph-text'),
			CF_CheckboxesTemplate			= $('#bookedCFTemplate-checkboxes'),
			CF_RadioButtonsTemplate			= $('#bookedCFTemplate-radio-buttons'),
			CF_DropDownTemplate				= $('#bookedCFTemplate-drop-down');

		init_booked_custom_fields();

		$('body').on('keyup','#booked-cf-sortables input',function() {
			update_CF_Data(CF_SortablesForm);
		});

		$('body').on('keyup','#booked-cf-sortables textarea',function() {
			update_CF_Data(CF_SortablesForm);
		});

		$('body').on('click','.booked-cf-block .cfButton',function(e){

			e.preventDefault();
			var CF_ButtonType = $(this).attr('data-type');
			appendLocation = $(this).parent().find('ul:first');

			var newSortable = CF_SortablesTemplatesContainer.find('#bookedCFTemplate-'+CF_ButtonType).clone().appendTo(appendLocation);

			// Assign this field a random number
			if (CF_ButtonType == 'plain-text-content'){
				var thisInput = newSortable.find('textarea[name="'+CF_ButtonType+'"]');
				var thisRequiredCheckbox = false;
			} else {
				var thisInput = newSortable.find('input[name="'+CF_ButtonType+'"]');
				var thisRequiredCheckbox = newSortable.find('input[name="required"]');
			}

			if (CF_ButtonType == 'single-radio-button'){
				var thisTextField = $(this).parents('li').find('input[type=text]:first').attr('name');
				thisTextField = thisTextField.split('---');
				var randomNumber = thisTextField[1];
				randomNumber = randomNumber.split('___');
				randomNumber = randomNumber[0];
			} else if (CF_ButtonType == 'single-checkbox'){
				var thisTextField = $(this).parents('li').find('input[type=text]:first').attr('name');
				thisTextField = thisTextField.split('---');
				var randomNumber = thisTextField[1];
				randomNumber = randomNumber.split('___');
				randomNumber = randomNumber[0];
			} else {
				var randomNumber = Math.floor((Math.random() * 9999999) + 1000000);
			}

			if (CF_ButtonType != 'plain-text-content'){
				thisRequiredCheckbox.attr('name','required---'+randomNumber).attr('id','required---'+randomNumber).parent().find('label').attr('for','required---'+randomNumber);
			}

			thisInput.attr('name',CF_ButtonType+'---'+randomNumber);
			thisInput.css('border-color','#FFBA00');

			/*
			 * allow other script to apply their modification before initializing the sortables
			 * $(document).on("booked-on-cbutton-click", function(event, params) { code goes here });
			 */
			$(document).trigger("booked-on-cbutton-click", {
				button_object: $(this),
				this_input: thisInput,
				button_type: CF_ButtonType,
				random_number: randomNumber
			});

			CF_SortablesContainer.show();
			init_booked_cf_sortables();

		});

		$('body').on('change','.booked-cf-block .cf-required-checkbox',function(e){
			var thisCheckboxVal = $(this).attr('checked');
			var thisTextField = $(this).parents('li').find('input[type=text]:first');
			var currentValue = thisTextField.attr('name');
			if (thisCheckboxVal == 'checked'){
				thisTextField.attr('name',currentValue+'___required');
			} else {
				currentValue = currentValue.split('___');
				currentValue = currentValue[0];
				thisTextField.attr('name',currentValue);
			}
			update_CF_Data(CF_SortablesForm);
		});

		$('body').on('click','.booked-cf-block .cf-delete',function(e){
			var confirm_delete = confirm("Are you sure you want to delete this field?");
			if (confirm_delete){
				$(this).parent().remove();
				if ($('#booked-cf-sortables').is(':empty')){
					$('#booked-cf-sortables').hide();
				}
				update_CF_Data(CF_SortablesForm);
			}
		});

		function update_CF_Data(CF_SortablesForm){
			var sortableContent = JSON.stringify(CF_SortablesForm.serializeArray());
			$('#booked_custom_fields').val(sortableContent);
		}

		function init_booked_custom_fields(){

			CF_SortablesForm				= $('#booked-cf-sortables-form');
			CF_SortablesContainer			= $('#booked-cf-sortables');

			if (CF_SortablesContainer.length){

				if (!CF_SortablesContainer.is(':empty')){
					CF_SortablesContainer.show();
				}

				var CF_SortingObject = CF_SortablesContainer.sortable({
					handle: ".main-handle",
					stop: function(){
						update_CF_Data(CF_SortablesForm);
					}
				});

				init_booked_cf_sortables();

			}

		}

		function init_booked_cf_sortables(){

			CF_SortablesForm = $('#booked-cf-sortables-form');

			$('#booked-cf-checkboxes').sortable({
				handle: ".sub-handle",
				stop: function(){
					update_CF_Data(CF_SortablesForm);
				}
			});
			$('#booked-cf-radio-buttons').sortable({
				handle: ".sub-handle",
				stop: function(){
					update_CF_Data(CF_SortablesForm);
				}
			});
			$('#booked-cf-drop-down').sortable({
				handle: ".sub-handle",
				stop: function(){
					update_CF_Data(CF_SortablesForm);
				}
			});

			update_CF_Data(CF_SortablesForm);
		}

		$('#booked-custom-fields').on('click','#booked-cf-saveButton',function(e){
			e.preventDefault();
			var booked_custom_fields	= $('#booked_custom_fields').val(),
				booked_cf_calendar_id	= $('#booked-cfSwitcher select').val();

			booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
				type	: 'post',
				url 	: booked_js_vars.ajax_url,
				data	: {
					'action'     			: 'booked_admin_save_custom_fields',
					'nonce': booked_js_vars.nonce,
					'booked_custom_fields'  : booked_custom_fields,
					'booked_cf_calendar_id'	: booked_cf_calendar_id
				},
				beforeSend: function(){
					$('#booked-cf-saveButton').attr('disabled',true);
					savingState(true);
				},
				success: function(data) {
					console.log(data);
					$('#booked-cf-saveButton').attr('disabled',false);
					$('#booked-cf-sortables input[type=text]').css('border-color','#ccc');
					$('#booked-cf-sortables textarea').css('border-color','#ccc');
				}
			});

		});
		// END Custom Fields



		var checkedClass = 'custom-input-checked';
		var disabledClass = 'custom-input-disabled';

		/* Main Admin Tabs */
		if ($('.booked-admin-tabs').length){

			// Tabs
			$('.tab-content').hide();

			var adminTabs 	= $('.booked-admin-tabs');
			var tabHash 	= window.location.hash;

			if (tabHash){
				var activeTab = tabHash;
				activeTab = activeTab.split('#');
				activeTab = activeTab[1];
				adminTabs.find('li').removeClass('active');
				adminTabs.find('a[href="'+tabHash+'"]').parent().addClass('active');
				$('#booked-'+activeTab).show();
			} else {
				var activeTab = adminTabs.find('.active > a').attr('href');
				activeTab = activeTab.split('#');
				activeTab = activeTab[1];
				$('#booked-'+activeTab).show();
			}

			adminTabs.find('li > a').on('click', function(e) {

				//e.preventDefault();
				$('.tab-content').hide();
				adminTabs.find('li').removeClass('active');

				$(this).parent().addClass('active');
				var activeTab = $(this).attr('href');
				activeTab = activeTab.split('#');
				activeTab = activeTab[1];

				if (activeTab == 'import_export_uninstall'){
					$('.submit-section').hide();
				} else {
					$('.submit-section').show();
				}

				$('#booked-'+activeTab).show();

			});

		}

		/* Admin Sub-Tabs */
		if ($('.booked-admin-subtabs').length){

			// Tabs
			$('.subtab-content').hide();

			var adminSubTabs = $('.booked-admin-subtabs');
			adminSubTabs.each(function(){
				theseSubTabs = $(this);
				var thisWrapper = theseSubTabs.parents('.tab-content');
				var activeSubTab = theseSubTabs.find('.active > a').attr('href');
				activeSubTab = activeSubTab.split('#');
				activeSubTab = activeSubTab[1];
				$('#booked-subtab-'+activeSubTab).show();

				theseSubTabs.find('li > a').on('click', function(e) {

					e.preventDefault();

					thisWrapper.find('.subtab-content').hide();
					theseSubTabs.find('li').removeClass('active');

					$(this).parent().addClass('active');
					var activeSubTab = $(this).attr('href');
					activeSubTab = activeSubTab.split('#');
					activeSubTab = activeSubTab[1];

					$('#booked-subtab-'+activeSubTab).show();

				});

			});

		}

		/* Click the Cancel button */
		$('#bookedTimeslotsWrap').on('click','td.addTimeslot .cancel',function(e){
			e.preventDefault();
			reset_add_timeslot_button();
		});

		function reset_add_timeslot_button(){
			var addButton = $('#bookedTimeslotsWrap').find('.addTimeslot.active').find('a.booked-add-timeslot');
			var dayName = addButton.parent().attr('data-day');
			var clearButton = $('table.booked-timeslots th[data-day="'+dayName+'"]').find('.booked-clear-timeslots');
			$('td.addTimeslot').find('select').show();
			$('td.addTimeslot').removeClass('active');
			$('td.addTimeslot a.button').html( booked_js_vars.i18n_add ).removeClass('button-primary').appendTo('table.booked-timeslots th[data-day="'+dayName+'"]');
			$('#timepickerTemplate').appendTo( '#booked-defaults' ).hide();
			clearButton.show();
		}

		$('#timepickerTemplate .tsSingle,#booked-customTimePickerTemplates .customSingle').on('change','select[name="startTime"]',function(){

			var thisSelectBox = $(this);
			var thisSelectBoxVal = thisSelectBox.val();
			if (thisSelectBoxVal == 'allday'){
				thisSelectBox.parent().find('select[name="endTime"]').val('2400').hide();
			} else {
				thisSelectBox.parent().find('select[name="endTime"]').val('').show();
			}

		});

		/* Click the Clear button */
		$('#bookedTimeslotsWrap').on('click','a.booked-clear-timeslots',function(e){
			e.preventDefault();
			var thisButton = $(this);
			var dayName = thisButton.parent().attr('data-day');
			var parentBlock = $('#bookedTimeslotsWrap').find('td.addTimeslot[data-day="'+dayName+'"]');
			var day	= thisButton.parents('th').attr('data-day');
			var calendar_id	= $('table.booked-timeslots').attr('data-calendar-id');

			clear_timelots_confirm = confirm(booked_js_vars.i18n_clear_timeslots_confirm);
			if (clear_timelots_confirm == true){

				$.ajax({
					url 	: booked_js_vars.ajax_url,
					type	: 'post',
					data	: {'action':'booked_admin_clear_timeslots','nonce': booked_js_vars.nonce,'day':day,'calendar_id':calendar_id},
					beforeSend: function(){
						savingState(true);
					},
					success: function(data) {

						reset_add_timeslot_button();

						$.ajax({
							url		: booked_js_vars.ajax_url,
							type	: 'post',
							data	: {'action':'booked_admin_load_timeslots','nonce': booked_js_vars.nonce,'day':day,'calendar_id':calendar_id},
							success	: function(html){
								$('td.dayTimeslots[data-day="'+day+'"]').html( html );
							}
						});

					}
				});

			}

		});

		/* Click the Add button */
		$('#bookedTimeslotsWrap').on('click','a.booked-add-timeslot',function(e){
			e.preventDefault();
			var thisButton = $(this);
			var clearButton = thisButton.parent().find('.booked-clear-timeslots');
			var dayName = thisButton.parent().attr('data-day');
			var parentBlock = $('#bookedTimeslotsWrap').find('td.addTimeslot[data-day="'+dayName+'"]');
			var allTimeslotParents = $('#bookedTimeslotsWrap td.addTimeslot');

			if (thisButton.hasClass('button-primary')){

				var activeTab = $('.addTimeslotTab.active').attr('href');
				if (activeTab == '#Bulk'){

					// Bulk add
					var $button      	= $(this),
						day	 	 	 	= $button.parents('td').attr('data-day'),
						startTime 		= $('.tsBulk').find('select[name=startTime]').val(),
						startTimeText	= $('.tsBulk').find('select[name=startTime] :selected').text(),
						endTime 		= $('.tsBulk').find('select[name=endTime]').val(),
						endTimeText		= $('.tsBulk').find('select[name=endTime] :selected').text(),
						interval 		= $('.tsBulk').find('select[name=interval]').val(),
						time_between 	= $('.tsBulk').find('select[name=time_between]').val(),
						intervalText	= $('.tsBulk').find('select[name=interval] :selected').text(),
						count 			= $('.tsBulk').find('select[name=count]').val(),
						calendar_id		= $('table.booked-timeslots').attr('data-calendar-id'),
						countText		= $('.tsBulk').find('select[name=count] :selected').text();

					var formData = $('#bulk-timeslot-form').serializeObject();
					formData['action'] = 'booked_admin_add_timeslots';
					formData['day'] = day;
					formData['calendar_id'] = calendar_id;
					formData['nonce'] = booked_js_vars.nonce;

					if (startTime && endTime && interval && count){

						if (endTime <= startTime){
							alert(booked_js_vars.i18n_time_error);
							return false;
						}

						appt_add_confirm = confirm(booked_js_vars.i18n_bulk_add_confirm);
						if (appt_add_confirm == true){

							booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
								type	: 'post',
								url 	: booked_js_vars.ajax_url,
								data	: formData,
								beforeSend: function(){
									savingState(true);
								},
								success: function(data) {

									reset_add_timeslot_button();

									$.ajax({
										url		: booked_js_vars.ajax_url,
										type	: 'post',
										data	: {'action':'booked_admin_load_timeslots','nonce': booked_js_vars.nonce,'day':day,'calendar_id':calendar_id},
										success	: function(html){
											$('td.dayTimeslots[data-day="'+day+'"]').html( html );
										}
									});

								}
							});

						}
					} else {
						alert(booked_js_vars.i18n_all_fields_required);
					}

				} else {

					// Single add
					var $button      	= $(this),
						day	 	 	 	= $button.parents('td').attr('data-day'),
						startTime 		= $('.tsSingle').find('select[name=startTime] :selected').val(),
						startTimeText	= $('.tsSingle').find('select[name=startTime] :selected').text(),
						endTime 		= $('.tsSingle').find('select[name=endTime] :selected').val(),
						endTimeText		= $('.tsSingle').find('select[name=endTime] :selected').text(),
						count 			= $('.tsSingle').find('select[name=count] :selected').val(),
						calendar_id		= $('table.booked-timeslots').attr('data-calendar-id'),
						countText		= $('.tsSingle').find('select[name=count] :selected').text();

					var formData = $('#single-timeslot-form').serializeObject();
					formData['action'] = 'booked_admin_add_timeslot';
					formData['day'] = day;
					formData['calendar_id'] = calendar_id;
					formData['nonce'] = booked_js_vars.nonce;

					if (startTime && endTime && count){

						if (endTime <= startTime && startTime != 'allday'){
							alert(booked_js_vars.i18n_time_error);
							return false;
						}

						if (startTime == '0000' && endTime == '2400' || startTime == 'allday' && endTime == '2400'){
							appt_add_confirm = confirm(booked_js_vars.i18n_single_add_confirm + ':\n'+booked_js_vars.i18n_all_day+' x'+count);
						} else {
							appt_add_confirm = confirm(booked_js_vars.i18n_single_add_confirm + ':\n'+startTimeText+' '+booked_js_vars.i18n_to+' '+endTimeText+' x'+count);
						}

						if (appt_add_confirm == true){

							booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
								type	: 'post',
								url 	: booked_js_vars.ajax_url,
								data	: formData,
								beforeSend: function(){
									savingState(true);
								},
								success: function(data) {

									reset_add_timeslot_button();

									$.ajax({
										url		: booked_js_vars.ajax_url,
										type	: 'post',
										data	: {'action':'booked_admin_load_timeslots','nonce': booked_js_vars.nonce,'day':day,'calendar_id':calendar_id},
										success	: function(html){
											$('td.dayTimeslots[data-day="'+day+'"]').html( html );
										}
									});

								}
							});

						}
					} else {
						alert(booked_js_vars.i18n_all_fields_required);
					}

				}

			} else {
				reset_add_timeslot_button();
				allTimeslotParents.removeClass('active');
				allTimeslotParents.find('a.button').html(booked_js_vars.i18n_add).removeClass('button-primary');
				$('#timepickerTemplate').appendTo('#booked-defaults').hide();
				parentBlock.addClass('active');
				thisButton.html(booked_js_vars.i18n_add).addClass('button-primary');
				init_timeslot_tabs();
				var thisForm = $('#timepickerTemplate').prependTo(parentBlock).show();
				clearButton.hide();
				thisButton.appendTo(parentBlock);
			}
		});

		$('#bookedTimeslotsWrap').on('change','select[name=startTime]',function(e) {
			var endTimeSelect = $(this).parent().find('select[name=endTime]');
			var startTimeVal = $(this).val();
			endTimeSelect.find('option').removeAttr('disabled');
			endTimeSelect.find('option').each(function() {
				var thisVal = $(this).val();
				if (thisVal <= startTimeVal){
					$(this).attr('disabled',true);
				}
			});
		});

		var preventMultiClicks;

		// Change Timeslot Count
		$('#bookedTimeslotsWrap').on('click', '.slotsBlock .changeCount', function(e) {

			e.preventDefault();

			if (!currentlySaving){

				var $button      	= $(this),
					$timeslot		= $button.parents('.timeslot');
					$countText	 	= $button.parent().find('.count'),
					day	 	 	 	= $button.parents('td').attr('data-day'),
					timeslot	 	= $button.parents('.timeslot').attr('data-timeslot'),
					countAdjust  	= $button.attr('data-count'),
					calendar_id		= $('table.booked-timeslots').attr('data-calendar-id'),
					currentCount 	= $countText.find('em').text();

				clearTimeout(preventMultiClicks);

				newCount = parseInt(currentCount) + parseInt(countAdjust);
				if (newCount < 1) {

					newCount = 1;

				} else {

					if (newCount != 1) { slot_text = booked_js_vars.i18n_slots; } else { slot_text = booked_js_vars.i18n_slot; }
					$countText.html('<em>' + newCount + '</em> ' + slot_text);

					preventMultiClicks = setTimeout(function(){

						$timeslot.css({'opacity':0.5});

						savingState(true);
						currentlySaving = true;

						booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
							type	: 'post',
							url 	: booked_js_vars.ajax_url,
							data	: {
								'action'     	: 'booked_admin_adjust_default_timeslot_count',
								'nonce': booked_js_vars.nonce,
								'newCount'		: newCount,
								'calendar_id'	: calendar_id,
								'day'     		: day,
								'timeslot'     	: timeslot
							},
							success: function(data) {
								currentlySaving = false;
								$timeslot.css({'opacity':1});
							}
						});

					},350);

				}

			}

			return false;

		});

		// Delete Timeslot
		$('#bookedTimeslotsWrap').on('click', '.timeslot .delete', function(e) {

			e.preventDefault();

			var $button      	= $(this),
				$timeslot	 	= $button.parents('.timeslot'),
				day	 	 	 	= $button.parents('td').attr('data-day'),
				timeslot	 	= $button.parents('.timeslot').attr('data-timeslot'),
				calendar_id		= $('table.booked-timeslots').attr('data-calendar-id'),
				startText	 	= $timeslot.find('.start').html(),
				endText	 	 	= $timeslot.find('.end').html();

			confirm_ts_delete = confirm(booked_js_vars.i18n_confirm_ts_delete);
			if (confirm_ts_delete == true){

		    	$timeslot.slideUp('fast',function(){
					$(this).remove();
				});

				booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
					type	: 'post',
					url 	: booked_js_vars.ajax_url,
					data	: {
						'action'     	: 'booked_admin_delete_timeslot',
						'nonce': booked_js_vars.nonce,
						'day'     		: day,
						'calendar_id'	: calendar_id,
						'timeslot'     	: timeslot
					},
					beforeSend: function(){
						savingState(true);
					},
					success: function(data) {
						// Do nothing
					}
				});

			}

		});

		// Time Slots Calendar Switcher
		$('#booked-timeslotsSwitcher').on('change','select[name="bookedTimeslotsDisplayed"]',function(e){

			var calendar_id 		= $(this).val(),
				allTimeslotParents 	= $('#bookedTimeslotsWrap td.addTimeslot');

			allTimeslotParents.find('select').val('');
			allTimeslotParents.removeClass('active');
			allTimeslotParents.find('a.button').html(booked_js_vars.i18n_add).removeClass('button-primary');
			$('#timepickerTemplate').appendTo('#booked-defaults').hide();

			savingState(true);
			$('table.booked-timeslots tbody').addClass('faded');

			$.ajax({
				url		: booked_js_vars.ajax_url,
				type	: 'post',
				data	: {'action':'booked_admin_load_full_timeslots','nonce': booked_js_vars.nonce,'calendar_id':calendar_id},
				success	: function(html){
					$('#bookedTimeslotsWrap').html( html );
					init_timeslot_tabs();
					$('table.booked-timeslots tbody').removeClass('faded');
				}
			});

		});

		// Custom Fields Calendar Switcher
		$('#booked-cfSwitcher').on('change','select[name="bookedCustomFieldsDisplayed"]',function(e){

			var calendar_id 		= $(this).val();

			$('#booked_customFields_Wrap').addClass('faded');

			$.ajax({
				url		: booked_js_vars.ajax_url,
				type	: 'post',
				data	: {'action':'booked_admin_load_full_customfields','nonce': booked_js_vars.nonce,'calendar_id':calendar_id},
				success	: function(html){
					$('#booked_customFields_Wrap').html( html );
					$('#booked_customFields_Wrap').removeClass('faded');
					init_booked_custom_fields();
				}
			});

		});

		// Calendar Switcher
		$('.booked-calendarSwitcher').on('change','select[name="bookedCalendarDisplayed"]',function(e){

			var calendar_id = $(this).val(),
				currentMonth = $('table.booked-calendar').attr('data-monthShown');

			savingState(true);

			$.ajax({
				url		: booked_js_vars.ajax_url,
				type	: 'post',
				data	: {'action':'booked_admin_calendar_month','nonce': booked_js_vars.nonce,'gotoMonth':currentMonth,'calendar_id':calendar_id},
				success	: function(html){
					$('.booked-admin-calendar-wrap').html( html );
					adjust_calendar_boxes();
				}
			});

		});

		// Calendar Next/Prev Click
		$('body').on('click', '.booked-admin-calendar-wrap .page-right, .booked-admin-calendar-wrap .page-left, .booked-admin-calendar-wrap .monthName a', function(e) {

			e.preventDefault();

			var $button 		= $(this),
				gotoMonth		= $button.attr('data-goto'),
				calendar_id		= $('table.booked-calendar').attr('data-calendar-id');

			savingState(true);

			$.ajax({
				url		: booked_js_vars.ajax_url,
				type	: 'post',
				data	: {'action':'booked_admin_calendar_month','nonce': booked_js_vars.nonce,'gotoMonth':gotoMonth,'calendar_id':calendar_id},
				success	: function(html){
					$('.booked-admin-calendar-wrap').html( html );
					adjust_calendar_boxes();
				}
			});

			return false;

		});

		// Calendar Date Click
		$('.booked-admin-calendar-wrap').on('click', 'tr.week td', function(e) {

			e.preventDefault();

			var $thisDate 		= $(this),
				$thisRow		= $thisDate.parent(),
				date			= $thisDate.attr('data-date'),
				calendar_id		= $('table.booked-calendar').attr('data-calendar-id'),
				colspanSetting	= $thisRow.find('td').length;

			if ($thisDate.hasClass('blur')){

				// Do nothing.

			} else if ($thisDate.hasClass('active')){

				$thisDate.removeClass('active');
				$('tr.entryBlock').remove();

			} else {

				$('tr.week td').removeClass('active');
				$thisDate.addClass('active');

				$('tr.entryBlock').remove();
				$thisRow.after('<tr class="entryBlock booked-loading"><td colspan="'+colspanSetting+'"></td></tr>');
				$('tr.entryBlock').find('td').spin('booked');

				$.ajax({
					url		: booked_js_vars.ajax_url,
					type	: 'post',
					data	: {'action':'booked_admin_calendar_date','nonce': booked_js_vars.nonce,'date':date,'calendar_id':calendar_id},
					success	: function(html){
						$('tr.entryBlock').find('td').html( html );
						$('tr.entryBlock').removeClass('booked-loading');
						$('tr.entryBlock').find('.booked-appt-list').fadeIn(300);
						$('tr.entryBlock').find('.booked-appt-list').addClass('shown');
						$('.bookedAppointmentTab.active').fadeIn(300);
					}
				});

			}

			return false;

		});

		// Delete Appointment Click from Calendar
		$('.booked-admin-calendar-wrap').on('click', 'tr.entryBlock .delete', function(e) {

			e.preventDefault();

			var $button 		= $(this),
				$thisParent		= $button.parents('.timeslot'),
				$thisTimeslot	= $button.parents('.timeslot'),
				$activeTD		= $('td.active'),
				$addlParent		= $thisTimeslot.parents('.additional-timeslots'),
				appt_id			= $thisTimeslot.attr('data-appt-id'),
				date			= $activeTD.attr('data-date'),
				calendar_id		= $button.attr('data-calendar-id');


			if (!appt_id){
				appt_id			= $button.parents('.appt-block').attr('data-appt-id');
				$thisParent		= $button.parents('.appt-block');
			}

			confirm_appt_delete = confirm(booked_js_vars.i18n_confirm_appt_delete);
	  		if (confirm_appt_delete == true){

		    	$thisParent.slideUp('fast',function(){
					$(this).remove();
					if ($addlParent.length){
		  				if (!$addlParent.find('.timeslot').length){
			  				$addlParent.remove();
		  				}
		  			} else {
			  			if (!$thisTimeslot.find('.appt-block').length){
				  			$thisTimeslot.find('strong').remove();
			  			}
		  			}
				});

				$thisTimeslot.addClass('faded');

				booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
					type	: 'post',
					url 	: booked_js_vars.ajax_url,
					data	: {
						'action'     	: 'booked_admin_delete_appt',
						'nonce': booked_js_vars.nonce,
						'appt_id'     	: appt_id
					},
					success: function(data) {

						$.ajax({
							url		: booked_js_vars.ajax_url,
							type	: 'post',
							data	: {'action':'booked_admin_calendar_date','nonce': booked_js_vars.nonce,'date':date,'calendar_id':calendar_id},
							success	: function(html){
								$('tr.entryBlock').find('td').html( html );
								$('tr.entryBlock').find('.booked-appt-list').show();
								$('tr.entryBlock').find('.booked-appt-list').addClass('shown');
								$('.bookedAppointmentTab.active').fadeIn(300);
							}
						});

						$.ajax({
							url		: booked_js_vars.ajax_url,
							type	: 'post',
							data	: {'action':'booked_admin_refresh_date_square','nonce': booked_js_vars.nonce,'date':date,'calendar_id':calendar_id},
							success	: function(html){
								$activeTD.replaceWith(html);
								adjust_calendar_boxes();
							}
						});

					}
				});

			}

			return false;

		});

		// Approve Appointment in Calendar
		$('.booked-admin-calendar-wrap').on('click', 'tr.entryBlock .approve', function(e) {

			e.preventDefault();

			var $button 		= $(this),
				$thisParent		= $button.parents('.timeslot'),
				appt_id			= $thisParent.attr('data-appt-id');

			if (!appt_id){
				$thisParent		= $button.parents('.appt-block');
				appt_id			= $button.attr('data-appt-id');
			}

			confirm_appt_approve = confirm(booked_js_vars.i18n_confirm_appt_approve);
			if (confirm_appt_approve == true){

				$(document).trigger("booked-on-calendar-approve", appt_id);

		    	$button.remove();
		    	$thisParent.find('.pending-text').remove();

				booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
					type	: 'post',
					url 	: booked_js_vars.ajax_url,
					data	: {
						'action'     	: 'booked_admin_approve_appt',
						'nonce': booked_js_vars.nonce,
						'appt_id'     	: appt_id
					},
					success: function(data) {
						// Do nothing
					}
				});

			}

			return false;

		});

		// User Info Click
		$('body').on('click', '.booked-pending-appt-list .user, .booked-admin-calendar-wrap tr.entryBlock .user', function(e) {

			e.preventDefault();

			var $thisLink 		= $(this),
				user_id			= $thisLink.attr('data-user-id'),
				appt_id			= $thisLink.parent().attr('data-appt-id');

			create_booked_modal();

			$.ajax({
				url		: booked_js_vars.ajax_url,
				type	: 'post',
				data	: {'action':'booked_admin_user_info_modal','nonce': booked_js_vars.nonce,'user_id':user_id,'appt_id':appt_id},
				success	: function(html){

					$('.bm-window').html( html );
					var bookedModal = $('.booked-modal');
					var bmWindow = bookedModal.find('.bm-window');
					bmWindow.css({'visibility':'hidden'});
					bookedModal.removeClass('bm-loading');
					resize_booked_modal();
					bmWindow.hide();

					bmWindow.find('.booked_appt_date').datepicker({
						showOn: "button",
						buttonText: booked_js_vars.i18n_change_date,
						dateFormat: "yy-mm-dd",
						beforeShow: function(input, inst) {
							$('#ui-datepicker-div').removeClass();
							$('#ui-datepicker-div').addClass('booked_custom_date_picker');
					    },
					    onSelect: function(selected) {
					    	bmWindow.find('.ui-datepicker-trigger').attr('disabled',true);
					    	var appt_id = $('#editAppointmentForm').data('appt-id');
					    	booked_update_timeslot_select_field( appt_id, selected );
							booked_datepicker_show_formatted_date( 'booked_appt_date', selected );
				        }
					});

					setTimeout(function(){
						bmWindow.find('.ui-datepicker-trigger').addClass('button');
						bmWindow.css({'visibility':'visible'});
						bmWindow.show();
					},50);

				}
			});

			return false;

		});

		$('.booked-admin-calendar-wrap').on('click', '#bookedAppointmentTabs li a', function(e) {

			e.preventDefault();

			var $thisTab = $(this);
			var tabName	= $thisTab.attr('href').split('#calendar-');
			tabName = tabName[1];

			$('#bookedAppointmentTabs li').removeClass('active');
			$('.bookedAppointmentTab').hide();
			$('.bookedAppointmentTab').removeClass('active');

			$thisTab.parent().addClass('active');

			$('#bookedCalendarAppointmentsTab-'+tabName).fadeIn(100);
			$('#bookedCalendarAppointmentsTab-'+tabName).addClass('active');

			return false;

		});

		// New Appointment Click
		$('.booked-admin-calendar-wrap').on('click', 'tr.entryBlock button.new-appt', function(e) {

			e.preventDefault();

			var $button 		= $(this),
				timeslot		= $button.attr('data-timeslot'),
				title			= $button.attr('data-title'),
				date			= $button.attr('data-date'),
				$thisTimeslot	= $button.parents('.timeslot'),
				calendar_id		= $button.attr('data-calendar-id');

			booked_load_calendar_date_booking_options = {'action':'booked_admin_new_appointment_form','nonce': booked_js_vars.nonce,'date':date,'title':title,'timeslot':timeslot,'calendar_id':calendar_id};

			$(document).trigger("booked-before-loading-calendar-booking-options");

			create_booked_modal();

			$.ajax({
				url		: booked_js_vars.ajax_url,
				type	: 'post',
				data	: booked_load_calendar_date_booking_options,
				success	: function(html){
					$('.bm-window').html( html );
					$('select#userList').chosen();

					var bookedModal = $('.booked-modal');
					var bmWindow = bookedModal.find('.bm-window');
					bmWindow.css({'visibility':'hidden'});
					bookedModal.removeClass('bm-loading');
					$(document).trigger("booked-on-new-app");
					resize_booked_modal();
					bmWindow.hide();

					setTimeout(function(){
						bmWindow.css({'visibility':'visible'});
						bmWindow.show();
					},50);
				}
			});

			return false;

		});

		// New Appointment Click
		$('.booked-admin-calendar-wrap').on('click', 'tr.entryBlock button.disable-slot', function(e) {

			e.preventDefault();

			var $button 		= $(this),
				timeslot		= $button.attr('data-timeslot'),
				title			= $button.attr('data-title'),
				date			= $button.attr('data-date'),
				$thisTimeslot	= $button.parents('.timeslot'),
				$thisDate		= $button.parents('.bookedAppointmentTab'),
				calendar_id		= $button.attr('data-calendar-id');

			$thisDate.find('.disable-slot').attr('disabled',true);

			booked_load_calendar_date_booking_options = {'action':'booked_admin_disable_slot','nonce': booked_js_vars.nonce,'date':date,'title':title,'timeslot':timeslot,'calendar_id':calendar_id};

			$.ajax({
				url		: booked_js_vars.ajax_url,
				type	: 'post',
				data	: booked_load_calendar_date_booking_options,
				success	: function(result){
					if ( result == 'disabled' ){
						$thisTimeslot.addClass('booked-disabled');
						$thisTimeslot.find( '.new-appt' ).attr( 'disabled',true );
						$button.text( booked_js_vars.i18n_enable );
					} else if ( result == 'enabled' ){
						$thisTimeslot.removeClass('booked-disabled');
						$thisTimeslot.find( '.new-appt' ).attr( 'disabled',false );
						$button.text( booked_js_vars.i18n_disable );
					}
					$thisDate.find('.disable-slot').attr('disabled',false);
				}
			});

			return false;

		});

		// Delete Appointment from Pending List
		$('.booked-pending-appt-list').on('click', '.pending-appt .delete', function(e) {

			e.preventDefault();

			var $button 		= $(this),
				$thisParent		= $button.parents('.pending-appt'),
				appt_id			= $thisParent.attr('data-appt-id');

			confirm_appt_delete = confirm(booked_js_vars.i18n_confirm_appt_delete);
			if (confirm_appt_delete == true){

	  			var currentPendingCount = parseInt($('li.toplevel_page_booked-appointments').find('li.current').find('span.update-count').html());
				currentPendingCount = parseInt(currentPendingCount - 1);
				if (currentPendingCount < 1){
					$('li.toplevel_page_booked-appointments').find('li.current').find('span.update-plugins').remove();
					$('.no-pending-message').slideDown('fast');
					$('.booked-pending-cap').slideUp('fast');
				} else {
					$('li.toplevel_page_booked-appointments').find('li.current').find('span.update-count').html(currentPendingCount);
				}

	  			$thisParent.slideUp('fast',function(){
					$(this).remove();
				});

	  			savingState(true);

				booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
					type	: 'post',
					url 	: booked_js_vars.ajax_url,
					data	: {
						'action'     	: 'booked_admin_delete_appt',
						'nonce': booked_js_vars.nonce,
						'appt_id'     	: appt_id
					},
					success: function(data) {
						setTimeout( function(){
							savingState(false);
						},500 );
					}
				});

			}

			return false;

		});

		// Approve Appointment from Pending List
		$('.booked-pending-appt-list').on('click', '.pending-appt .approve', function(e) {

			e.preventDefault();

			var $button 		= $(this),
				$thisParent		= $button.parents('.pending-appt'),
				appt_id			= $thisParent.attr('data-appt-id');

			confirm_appt_approve = confirm(booked_js_vars.i18n_confirm_appt_approve);
			if (confirm_appt_approve == true){

				var currentPendingCount = parseInt($('li.toplevel_page_booked-appointments').find('li.current').find('span.update-count').html());
				currentPendingCount = parseInt(currentPendingCount - 1);
				if (currentPendingCount < 1){
					$('li.toplevel_page_booked-appointments').find('li.current').find('span.update-plugins').remove();
					$('.no-pending-message').slideDown('fast');
					$('.booked-pending-cap').slideUp('fast');
				} else {
					$('li.toplevel_page_booked-appointments').find('li.current').find('span.update-count').html(currentPendingCount);
				}

				$thisParent.slideUp('fast',function(){
					$(this).remove();
				});

	  			savingState(true);

		  		booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
					type	: 'post',
					url 	: booked_js_vars.ajax_url,
					data	: {
						'action'     	: 'booked_admin_approve_appt',
						'nonce': booked_js_vars.nonce,
						'appt_id'     	: appt_id
					},
					success: function(data) {
						setTimeout( function(){
							savingState(false);
						},500 );
					}
				});

			}

			return false;

		});

		// Approve All Appointment from Pending List
		$('.booked-pending-cap').on('click', '.approve-all', function(e) {

			e.preventDefault();

			var $button 		= $(this);

			confirm_appt_approve = confirm(booked_js_vars.i18n_confirm_appt_approve_all);
			if (confirm_appt_approve == true){

	  			savingState(true);

		  		booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
					type	: 'post',
					url 	: booked_js_vars.ajax_url,
					data	: {
						'action' : 'booked_admin_approve_all',
						'nonce': booked_js_vars.nonce,
					},
					success: function(data) {
						$('li.toplevel_page_booked-appointments').find('li.current').find('span.update-plugins').remove();
						$('.booked-pending-appt-list .pending-appt:not(.no-pending-message)').remove();
						$('.booked-pending-appt-list .no-pending-message').slideDown('fast');
						$('.booked-pending-cap').slideUp('fast');
						setTimeout( function(){
							savingState(false);
						},500 );
					}
				});

			}

			return false;

		});

		// Delete All Appointment from Pending List
		$('.booked-pending-cap').on('click', '.delete-all', function(e) {

			e.preventDefault();

			var $button 		= $(this);

			confirm_appt_approve = confirm(booked_js_vars.i18n_confirm_appt_delete_all);
			if (confirm_appt_approve == true){

	  			savingState(true);

		  		booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
					type	: 'post',
					url 	: booked_js_vars.ajax_url,
					data	: {
						'action' : 'booked_admin_delete_all',
						'nonce': booked_js_vars.nonce,
					},
					success: function(data) {
						$('li.toplevel_page_booked-appointments').find('li.current').find('span.update-plugins').remove();
						$('.booked-pending-appt-list .pending-appt:not(.no-pending-message)').remove();
						$('.booked-pending-appt-list .no-pending-message').slideDown('fast');
						$('.booked-pending-cap').slideUp('fast');
						setTimeout( function(){
							savingState(false);
						},500 );
					}
				});

			}

			return false;

		});

		var totalPendings = $('.booked-pending-appt-list').find('.pending-appt').length;
		var passedPendings = $('.booked-pending-appt-list').find('.pending-appt.passed').length;
		if (totalPendings > 1 && !passedPendings){
			$('.booked-pending-cap .button.delete-past').hide();
		} else if (totalPendings == 1) {
			$('.booked-pending-cap').slideUp('fast');
		}

		// Delete Past Appointments from Pending List
		$('.booked-pending-cap').on('click', '.delete-past', function(e) {

			e.preventDefault();

			var $button = $(this);

			confirm_appt_approve = confirm(booked_js_vars.i18n_confirm_appt_delete_past);
			if (confirm_appt_approve == true){

				var currentPendingCount = parseInt($('li.toplevel_page_booked-appointments').find('li.current').find('span.update-count').html());
				var pendingsToRemove = $('.booked-pending-appt-list').find('.pending-appt.passed').length;
				currentPendingCount = parseInt(currentPendingCount - pendingsToRemove);
				if (currentPendingCount < 1){
					$('li.toplevel_page_booked-appointments').find('li.current').find('span.update-plugins').remove();
					$('.booked-pending-appt-list .no-pending-message').slideDown('fast');
					$('.booked-pending-cap').slideUp('fast');
				} else {
					$('li.toplevel_page_booked-appointments').find('li.current').find('span.update-count').html(currentPendingCount);
				}

	  			savingState(true);

		  		booked_js_vars.ajaxRequests.push = $.bookedAjaxQueue({
					type	: 'post',
					url 	: booked_js_vars.ajax_url,
					data	: {
						'action' : 'booked_admin_delete_past',
						'nonce': booked_js_vars.nonce,
					},
					success: function(data) {
						$('.booked-pending-cap .button.delete-past').hide();
						$('.booked-pending-appt-list .pending-appt.passed').remove();
						setTimeout( function(){
							savingState(false);
						},500 );
					}
				});

			}

			return false;

		});

		$('body').on('touchstart click','.bm-overlay, .bm-window .close, form.booked-form .cancel',function(e){
			e.preventDefault();
			close_booked_modal();
			return false;
		});

		$('body').on('change','form.booked-settings-form input,form.booked-form input,form.booked-settings-form select',function(){

			var condition = $(this).attr('data-condition'),
				thisVal = $(this).val();

			if (condition && $('.condition-block').length) {
				if ( $(this).is(':checkbox') && $(this).is(':checked') ){
					$('.condition-block.'+condition).fadeIn(250);
					resize_booked_modal();
				} else if ( $(this).is(':checkbox') && !$(this).is(':checked') ){
					$('.condition-block.'+condition).hide();
					resize_booked_modal();
				} else {
					$('.condition-block.'+condition).hide();
					$('[data-condition-val="'+thisVal+'"]').fadeIn(250);
					resize_booked_modal();
				}
			}

		});

		$('body')
		.on('focusin', 'form.booked-form input', function() {
			if(this.title==this.value) {
				$(this).addClass('hasContent');
				this.value = '';
			}
		}).on('focusout', 'form.booked-form input', function(){
			if(this.value==='') {
				$(this).removeClass('hasContent');
				this.value = this.title;
			}
		});

		// Adjust the calendar sizing when resizing the window
		$win.on('resize',function(){
			adjust_calendar_boxes();
			resize_booked_modal();
		});

		// Adjust the calendar sizing on load
		adjust_calendar_boxes();

		// Saving state updater
		function savingState(show,savingStateObj){
			if (typeof savingStateObj === 'undefined'){
				var savingStateObj = $('li.active .savingState, .topSavingState.savingState, .calendarSavingState, .cf-updater.savingState, .cts-updater.savingState, .cal-updater.savingState');
			}
			var $stuffToHide = $('.monthName');
			var $stuffToTransparent = $('table.booked-calendar tbody');
			if (show){
				savingStateObj.fadeIn(200);
				$stuffToHide.hide();
				$stuffToTransparent.animate({'opacity':0.2},100);
			} else {
				savingStateObj.hide();
				$stuffToHide.show();
				$stuffToTransparent.animate({'opacity':1},0);
			}
		}

		function init_timeslot_tabs(){
			/* Add Timeslot Tabs */
			if ($('.addTimeslotTab').length){

				// Tabs
				var timeslotTabs = $('.timeslotTabs');
				$('.tsTabContent').hide();
				var activeTab = timeslotTabs.find('.active').attr('href');
				activeTab = activeTab.split('#');
				activeTab = activeTab[1];
				$('.tsTabContent.ts'+activeTab).show();

				timeslotTabs.find('a').on('click', function(e) {

					e.preventDefault();
					$('.tsTabContent').hide();
					timeslotTabs.find('a').removeClass('active');

					$(this).addClass('active');
					var activeTab = $(this).attr('href');
					activeTab = activeTab.split('#');
					activeTab = activeTab[1];

					$('.tsTabContent.ts'+activeTab).show();

				});

			}
		}

		function updateCustomTimeslotEncodedField(){
			var formData = JSON.stringify($('form#customTimeslots').serializeObject());
			$('#custom_timeslots_encoded').val(formData);
		}

		$(document).ajaxStop(function() {
			setTimeout( function(){
				savingState(false);
			},500 );
		});

	});

})(jQuery, window, document);

// Reload the timeslots select options
function booked_update_timeslot_select_field( appt_id, selectedDate ){

	jQuery('.bm-window').find('.booked_appt_timeslot').attr('disabled',true);

	booked_js_vars.ajaxRequests.push = jQuery.bookedAjaxQueue({
		type	: 'post',
		url 	: booked_js_vars.ajax_url,
		data	: {
			'action' 	: 'booked_admin_get_timeslots_select',
			'nonce': booked_js_vars.nonce,
			'date'	 	: selectedDate,
			'appt_id'	: appt_id
		},
		success: function(html) {
			jQuery('.bm-window').find('.booked_appt_timeslot').replaceWith(html);
		}
	});

}

// Format the Datepicker Date
function booked_datepicker_show_formatted_date( dateField, selectedDate ){

	var dateFieldObj = jQuery( '.' + dateField );
	formatSpan = jQuery( '.' + dateField + '-formatted' );
	formatSpan.css({ 'opacity':0.5 });
	booked_js_vars.ajaxRequests.push = jQuery.bookedAjaxQueue({
		type	: 'post',
		url 	: booked_js_vars.ajax_url,
		data	: {
			'action' : 'booked_date_formatting',
			'nonce': booked_js_vars.nonce,
			'date'	 : selectedDate
		},
		success: function(date) {
			formatSpan.html(date);
			formatSpan.css({ 'opacity':1 });
			jQuery('.bm-window').find('.ui-datepicker-trigger').attr('disabled',false);
		}
	});

}

// Create Booked Modal
function create_booked_modal(){
	var windowHeight = jQuery(window).height();
	var windowWidth = jQuery(window).width();
	if (windowWidth > 720){
		var maxModalHeight = windowHeight - 295;
	} else {
		var maxModalHeight = windowHeight;
	}

	jQuery('body input, body textarea, body select').blur();
	jQuery('body').addClass('booked-noScroll');
	jQuery('<div class="booked-modal bm-loading"><div class="bm-overlay"></div><div class="bm-window"><div style="height:100px"></div></div></div>').appendTo('body');
	jQuery('.booked-modal .bm-window').spin('booked_white');
	jQuery('.booked-modal .bm-window').css({'max-height':maxModalHeight+'px'});
}

function resize_booked_modal(){

	var windowHeight = jQuery(window).height();
	var windowWidth = jQuery(window).width();
	if (jQuery('.booked-modal .bm-window .booked-scrollable').length){
		var realModalHeight = jQuery('.booked-modal .bm-window .booked-scrollable')[0].scrollHeight;
	} else {
		var realModalHeight = 0;
	}
	var minimumWindowHeight = realModalHeight + 43 + 240;
	var modalScrollableHeight = realModalHeight - 43;
	var maxModalHeight;
	var maxFormHeight;

	if (windowHeight < minimumWindowHeight){
		modalScrollableHeight = windowHeight - 240 - 43;
	} else {
		modalScrollableHeight = realModalHeight;
	}

	if (windowWidth > 720){
		maxModalHeight = modalScrollableHeight - 25;
		maxFormHeight = maxModalHeight - 15;
	} else {
		maxModalHeight = windowHeight - 43;
		maxFormHeight = maxModalHeight - 43 - 60;
	}

	var modalNegMargin = (maxModalHeight + 83) / 2;

	jQuery('.booked-modal').css({'margin-top':'-'+modalNegMargin+'px'});
	jQuery('.booked-modal .bm-window').css({'max-height':maxModalHeight+'px'});
	jQuery('.booked-modal .bm-window .booked-scrollable').css({'max-height':maxFormHeight+'px'});

}

function close_booked_modal(){
	jQuery('.booked-modal').fadeOut(200);
	jQuery('.booked-modal').addClass('bm-closing');
	jQuery('body').removeClass('booked-noScroll');
	setTimeout(function(){
		jQuery('.booked-modal').remove();
	},300);
}

// Function to adjust calendar sizing
function adjust_calendar_boxes(){
	var boxesWidth = jQuery('.booked-calendar tbody tr.week td').width();
	boxesHeight = boxesWidth * 0.75;
	jQuery('.booked-calendar tbody tr.week td').height(boxesHeight);
	jQuery('.booked-calendar tbody tr.week td .date').css('line-height',boxesHeight+'px');

	jQuery('.tooltip:not(.tooltipstered)').tooltipster({
		theme: 		'tooltipster-light',
		animation:	'grow',
		speed:		200,
		delay: 		100,
		offsetY:	-13
	});
}

// Serialize Function
(function($) {

	// jQuery on an empty object, we are going to use this as our Queue
	var bookedAjaxQueue = $({});

	$.bookedAjaxQueue = function( ajaxOpts ) {
	    var jqXHR,
	        dfd = $.Deferred(),
	        promise = dfd.promise();

	    // queue our ajax request
	    bookedAjaxQueue.queue( doRequest );

	    // add the abort method
	    promise.abort = function( statusText ) {

	        // proxy abort to the jqXHR if it is active
	        if ( jqXHR ) {
	            return jqXHR.abort( statusText );
	        }

	        // if there wasn't already a jqXHR we need to remove from queue
	        var queue = bookedAjaxQueue.queue(),
	            index = $.inArray( doRequest, queue );

	        if ( index > -1 ) {
	            queue.splice( index, 1 );
	        }

	        // and then reject the deferred
	        dfd.rejectWith( ajaxOpts.context || ajaxOpts, [ promise, statusText, "" ] );
	        return promise;
	    };

	    // run the actual query
	    function doRequest( next ) {
	        jqXHR = $.ajax( ajaxOpts )
	            .done( dfd.resolve )
	            .fail( dfd.reject )
	            .then( next, next );
	    }

	    return promise;
	};

})(jQuery);
