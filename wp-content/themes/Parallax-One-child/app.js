jQuery(document).ready(function($) {
  console.log('this is app.js');
  // Date arrange
  $( ".date input" ).datepicker({dateFormat: "yy-mm-dd"});
  $(".date input").datepicker("setDate", new Date()); // Default as today

  // Clean up for user, keep last 2 value only
  $('body.user-is-client select[name="類別"] option:lt(-2)').remove()

  // Lock for name field
  $('select[name="類別"]').change(function() {
    // clean up value init
    if ($('#收款人手機或電郵').val() === '自己') {
      $('#收款人手機或電郵').val('');
    }
    if ($(this).val() === '提取(股息轉出)') {
      console.log('hi');
      $('#收款人手機或電郵').val('自己').prop('disabled', true).addClass('disabled');
    }
    else {
      console.log('no');
      $('#收款人手機或電郵').prop('disabled', false).removeClass('disabled');
    }
  })
});

