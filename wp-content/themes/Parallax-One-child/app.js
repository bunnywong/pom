(function($) {
   app = {
    attach:function() {
      console.log('app.js init');
      var self = this;
      jQuery(document).ready(function($) {
        self.formInit();
      });
    },
    formInit:function() {
      this.getCalendar();
      this.permissionAlter();
      this.formAlter();
    },
    formAlter:function() {
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
      });
    },
    permissionAlter:function() {
      // Clean up for normal user
      // - keep last 2 option
      $('body.user-is-client select[name="transaction_class"] option:lt(-2)').remove()
      // - Add PENDING to remark field
      $('body.user-is-client .transaction_detail textarea').text('PENDING');
      // Initial value
      $('body.user-is-client #transaction_contact').val('自己').prop('disabled', true).addClass('disabled');
    },
    getCalendar:function() {
      $( ".date input" ).datepicker({dateFormat: "yy-mm-dd"});
      $(".date input").datepicker("setDate", new Date()); // Default as today
    },
  }
  app.attach();
}(jQuery));
