<?php

if ( $value ):
    $value = intval( $value );
    $product = Booked_WC_Product::get( $value );
    ?><option value="<?php echo htmlentities($value, ENT_QUOTES | ENT_IGNORE, "UTF-8"); ?>"><?php echo esc_html($product->title); ?></option><?php
endif;