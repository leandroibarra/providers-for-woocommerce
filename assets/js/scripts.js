jQuery('#provider_woocommerce_product_tab').on('change', '#purchase_price, #purchase_discount, #profit_margin', function() {
    var max = parseInt(jQuery(this).attr('max'));
    var min = parseInt(jQuery(this).attr('min'));
    var val = jQuery(this).val();

    if (val > max) {
        jQuery(this).val(max);
    } else if (val < min) {
        jQuery(this).val(min);
    }
})