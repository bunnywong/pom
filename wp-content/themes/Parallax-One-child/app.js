jQuery(document).ready(function($) {
  console.log('this is app.js');
  // Date arrange
  $( ".date input" ).datepicker({dateFormat: "yy-mm-dd"});
  $(".date input").datepicker("setDate", new Date()); // Default as today

  // Clean up for normal user
  // - keep last 2 option
  $('body.user-is-client select[name="transaction_class"] option:lt(-2)').remove()
  // - Remove detail field
  $('body.user-is-client .transaction_detail').hide();
  // - Add PENDING to remark field
  $('body.user-is-client .transaction_detail textarea').text('PENDING');
  $('body.user-is-client #transaction_contact').val('自己').prop('disabled', true).addClass('disabled');

  // Lock for name field
  $('select[name="transaction_class"]').change(function() {
    // clean up value init
    if ($('#transaction_contact').val() === '自己') {
      $('#transaction_contact').val('');
    }
    if ($(this).val() === '提取(股息轉出)') {
      $('#transaction_contact').val('自己').prop('disabled', true).addClass('disabled');
    }
    else {
      console.log('no');
      $('#transaction_contact').prop('disabled', false).removeClass('disabled');
    }
  })
});

