<div class="field"<?php echo ( get_option('users_can_register') ? ' style="margin-top:0;"' : '' ); ?>>
	<label class="field-label"<?php echo ( get_option('users_can_register') ? ' style="padding-top:0;"' : '' ); ?>><?php esc_html_e("Welcome back, please sign in:","booked"); ?></label>
</div>
	
<div class="field">
	<input value="" placeholder="<?php esc_html_e('Email Address','booked'); ?> ..." class="textfield large" id="username" name="username" type="text" >
</div>
<div class="field">
	<input value="" placeholder="<?php esc_html_e('Password','booked'); ?> ..." class="textfield large" id="password" name="password" type="password" >
</div>