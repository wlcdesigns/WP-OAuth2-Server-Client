(function($){
  
	$("#wpoauth-add-new-client").click(function(){
		var site_url = $(this).data("site_url");
		
		swal({
			title: 'Create Client',
			html:
			'Create a new client using the form below: ' +
			'<input id="client-name" class="swal2-input" autofocus placeholder="Client Name"> ' +
			'<input id="redirect-uri" class="swal2-input" autofocus placeholder="Redirect URI e.g. '+site_url+'..." /> ' +
			'<textarea id="client-description" class="swal2-textarea" placeholder="Client Description"></textarea>',
			showCloseButton: false,
			showCancelButton: true,
			showLoaderOnConfirm: true,
			confirmButtonText: 'Save',
			cancelButtonText: 'Cancel',
			preConfirm: function(e) {
				return new Promise(function(resolve, reject) {
					//reject('You need to write something!');
					if(document.getElementById('client-name').value.length === 0){
						reject('You need to add a client name!');
					}else if(document.getElementById('redirect-uri').value.length === 0){
						reject('You need to add a redirect uri!');
					}else{
						console.log("go");
					}
					
					
					
					if (true) {
/*
						resolve([
							document.getElementById('client-name').value,
							document.getElementById('redirect-uri').value
						]);
*/
					}
				});
			},
			allowOutsideClick: false
		}).then(function(result) {
			console.log(result);
			
			swal({
				type: 'success',
				title: 'Ajax request finished!',
				html: 'Submitted email: '
			});
  
		});
	});
  
  /** intiate jQuery tabs */
  $("#wo_tabs").tabs({
    beforeActivate: function (event, ui) {
      var scrollTop = $(window).scrollTop();
      window.location.hash = ui.newPanel.selector;
      $(window).scrollTop(scrollTop);
    }
  });

  /**
   * Create New Client Form Submission Hook
   * @param  {[type]} e [description]
   * @return {[type]}   [description]
   */
  $('#create-new-client').submit(function(e){
    e.preventDefault();
    var formData = $(this).serialize();
    var data = {
      'action': 'wo_create_new_client',
      'data': formData
    };
    jQuery.post(ajaxurl, data, function(response) {
      if(response != '1')
      {
        alert(response);
      }
      else
      {
        /** reload for the time being */
        location.reload();
      }
    });
  });
  

})(jQuery);

/**
 * Remove a Client
 */
function wo_remove_client (client_id)
{
  if(!confirm("Are you sure you want to delete this client?"))
    return;
  
  var data = {
    'action': 'wo_remove_client',
    'data': client_id
  };
  jQuery.post(ajaxurl, data, function(response) {
    if(response != '1')
    {
      alert(response);
    }
    else
    {
      jQuery("#record_"+client_id+"").remove();
    }
  });
}

/**
 * Update a Client
 * @param  {[type]} form [description]
 * @return {[type]}      [description]
 */
function wo_update_client(form){
  alert('Submit the form');
}