jQuery(document).ready(function($) {
  var options = wc_deposits_add_to_cart_options;


  var form = $('#wc-deposits-options-form ,#basic-wc-deposits-options-form');
  var deposit = form.find('#pay-deposit');
  var full = form.find('#pay-full-amount');
  var msg = form.find('#wc-deposits-notice');
  var amount = form.find('#deposit-amount');
  var original_amount = amount.html();
  var update_message = function() {
    if (deposit.is(':checked')) {
      msg.html(options.message.deposit);
    } else if (full.is(':checked')) {
      msg.html(options.message.full);
    }
  };


  //hide deposit form initially in variable product
  var product_elem = form.closest('.product');
  if(product_elem.hasClass('product-type-variable')){
      form.slideUp();

  }
  console.log(options.variations);

  var update_variation = function(event, variation) {
    var id = variation.variation_id;
    if (typeof options.variations !== typeof undefined) {
      if (typeof options.variations[id] !== typeof undefined) {

        if(options.variations[id].forced == true) {
            full.attr('disabled','disabled');
            deposit.attr('checked','checked');
        } else {
          full.removeAttr('disabled');
        }
        amount.html(options.variations[id].amount);
          form.slideDown();

          return;
      }
    }
    form.slideUp();

  };
  $('.cart').on('change', 'input, select', update_message);


  $('.variations_form')
    .on('show_variation', update_variation)
    .on('click', '.reset_variations', function() { amount.html(original_amount); });
  update_message();
});
