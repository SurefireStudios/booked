;(function($, window, document, undefined) {

	var $win = $(window);

	$win.on('load', function() {
		
		var ajaxRequests = [];
		
		// Add Pending Count to Tab
		$('.booked-tabs').find('li a div.counter').each(function(){
			var thisCounter = $(this),
				thisTabName = $(this).parent().attr('href');
				thisTabName = thisTabName.split('#');
				thisTabName = thisTabName[1];
				totalAppointments = $('#profile-'+thisTabName).find('.appt-block').length;
			
			if (totalAppointments > 0){
				thisCounter.html(totalAppointments).css({'display':'flex'});
			}
		});
		
		// User Info Click
		$('.booked-fea-appt-list').on('click', '.user', function(e) {

			e.preventDefault();

			var $thisLink 		= $(this),
				user_id			= $thisLink.attr('data-user-id'),
				appt_id			= $thisLink.parent().attr('data-appt-id'),
				booked_ajaxURL	= booked_fea_vars.ajax_url;

			create_booked_modal();
			
			$.ajax({
				url: booked_ajaxURL,
				type: 'post',
				data: {
					action: 'booked_fea_user_info_modal',
					user_id: user_id,
					appt_id: appt_id
				},
				success: function( html ) {
					
					$('.bm-window').html( html );
					
					var bookedModal = $('.booked-modal');
					var bmWindow = bookedModal.find('.bm-window');
					bmWindow.css({'visibility':'hidden'});
					bookedModal.removeClass('bm-loading');
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
		
		// Show Additional Information
		$('.booked-fea-appt-list').on('click', '.booked-show-cf', function(e) {
		
			e.preventDefault();
			var hiddenBlock = $(this).parent().find('.cf-meta-values-hidden');
		
			if(hiddenBlock.is(':visible')){
				hiddenBlock.hide();
			} else {
				hiddenBlock.show();
			}
		
			return false;
		
		});
		
		// Approve Appointment from Appointment List
		$('.booked-fea-appt-list').on('click', '.appt-block .approve', function(e) {

			e.preventDefault();

			var $button 		= $(this),
				$thisParent		= $button.parents('.appt-block'),
				appt_id			= $thisParent.attr('data-appt-id'),
				booked_ajaxURL	= booked_fea_vars.ajax_url;

			confirm_appt_approve = confirm(booked_fea_vars.i18n_confirm_appt_approve);
			if (confirm_appt_approve == true){

				var currentApptCount = parseInt($button.parents('.booked-fea-appt-list').find('h4 span.count').html());
				currentApptCount = parseInt(currentApptCount - 1);
				$button.parents('.booked-fea-appt-list').find('h4 span.count').html(currentApptCount);
				
				if ($button.parents('#profile-fea_pending').length){
					if (currentApptCount < 1){
						$('.booked-tabs').find('li a[href="#fea_pending"] .counter').remove();
					} else {
						$('.booked-tabs').find('li a[href="#fea_pending"] .counter').html(currentApptCount);
					}
				}
				
				$('.appt-block').animate({'opacity':0.4},0);
				$button.remove();

		  		$.ajax({
					'method' : 'post',
					'url' : booked_ajaxURL,
					'data': {
						'action'     	: 'booked_fea_approve_appt',
						'appt_id'     	: appt_id
					},
					success: function(data) {
						$('.appt-block').animate({'opacity':1},150);
					}
				});

			}

			return false;

		});
		
		// Delete Appointment from Appointment List
		$('.booked-fea-appt-list').on('click', '.appt-block .delete', function(e) {

			e.preventDefault();

			var $button 		= $(this),
				$thisParent		= $button.parents('.appt-block'),
				appt_id			= $thisParent.attr('data-appt-id'),
				booked_ajaxURL	= booked_fea_vars.ajax_url;

			confirm_appt_delete = confirm(booked_fea_vars.i18n_confirm_appt_delete);
			if (confirm_appt_delete == true){

				var currentApptCount = parseInt($button.parents('.booked-fea-appt-list').find('h4 span.count').html());
				currentApptCount = parseInt(currentApptCount - 1);
				$button.parents('.booked-fea-appt-list').find('h4 span.count').html(currentApptCount);
				
				if ($button.parents('#profile-fea_pending').length){
					if (currentApptCount < 1){
						$('.booked-tabs').find('li a[href="#fea_pending"] .counter').remove();
					} else {
						$('.booked-tabs').find('li a[href="#fea_pending"] .counter').html(currentApptCount);
					}
				}
				
				$('.appt-block').animate({'opacity':0.4},0);
								
				$thisParent.slideUp('fast',function(){
					$(this).remove();
				});

				$.ajax({
					'method' : 'post',
					'url' : booked_ajaxURL,
					'data': {
						'action'     	: 'booked_fea_delete_appt',
						'appt_id'     	: appt_id
					},
					success: function(data) {
						$('.appt-block').animate({'opacity':1},150);
					}
				});

			}

			return false;

		});
		
	});
	
})(jQuery, window, document);