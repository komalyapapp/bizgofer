jQuery(document).ready(function ($) {
    $( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded',function(){



        $('.wc_deposits_override_product_settings').change(function(){

            var loop = $(this).data('loop');
            $('.wc_deposits_field'+loop).slideToggle();
        });



    } );


});