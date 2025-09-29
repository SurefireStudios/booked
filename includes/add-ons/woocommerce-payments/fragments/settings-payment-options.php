<div class="booked-settings-prewrap">
	<div class="booked-settings-wrap booked-payment-settings-wrap wrap">
		<div class="booked-settings-title"><?php echo __('WooCommerce Settings', 'booked') ?></div>

		<div id="booked-admin-panel-container">

			<div class="form-wrapper">

				<form action="options.php" method="post">

					<div id="booked-general" class="tab-content">
						<?php
						settings_fields(BOOKED_WC_PLUGIN_PREFIX . 'payment_options');
						do_settings_sections(BOOKED_WC_PLUGIN_PREFIX . 'payment_options'); 	//pass slug name of page
						?>

						<div class="section-row submit-section" style="padding:0;">
							<?php submit_button(); ?>
						</div><!-- /.section-row -->
					</div>
				</form>
			</div>
		</div>
	</div>
</div>