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
      this.putUserId();
      this.pagePost();
    },
    putUserId:function() {
      if ($.trim($('#user_id').val()) === '') {
        $('#user_id').val(app.userId);
      }
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
    pagePost:function() {
      // Add [Back] button
      $('body.single-post .entry-content').html('<button type="button" class="post-refresh btn btn-primary btn-lg" onClick="window.history.back();"">Back</button>');
    },
    _getUserId:function() {
      var query = window.location.search.substring(1);
      var vars = query.split("&");
      for (var i=0; i<vars.length; i++) {
        var pair = vars[i].split("=");
        if(pair[0] == 'user_id'){
          return pair[1];
        }
      }
      return false;
    },
  }
  app.attach();
}(jQuery));
