<?php

//$product = Booked_WC_Product::get( $product_id );

if ( !$product->variations ) {
	return;
}

$field_name = isset($field_name) ? $field_name : '';
$calendar_id = isset($calendar_id) ? $calendar_id : 0;

if ( $field_name ): ?>
	<select data-calendar-id="<?php echo $calendar_id ?>" name="<?php echo esc_attr($field_name); ?>" >
<?php else: ?>
	<select data-calendar-id="<?php echo $calendar_id ?>" >
<?php endif ?>
	<?php foreach ($product->variations as $variation_id => $variation_data): ?>
		<option value="<?php echo $variation_id ?>" ><?php echo $variation_data['variation_title'] ?></option>
	<?php endforeach ?>
</select>
