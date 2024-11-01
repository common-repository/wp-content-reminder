jQuery(document).ready(function ($) {
	$('.EPCR-timepicker').datetimepicker({
		datepicker:false,
		format:'H:i',
		step:15,
		formatTime:'g:i A'
		
	});
	$('.EPCR-datepicker').datetimepicker({
		timepicker:false,
		format:'Y-m-d',
		formatDate:'Y/m/d',
		minDate:'-1970/01/01'
	});
});

function EPCR_Content_Slide(EPCR_pid)
{
	jQuery('.EPCR-input').val('');
	jQuery('.EPCR-submit').prop("disabled", false);
	jQuery('#EPCR-content-'+EPCR_pid).slideToggle();
}

function EPCR_Content_Remind(EPCR_pid)
{
	var clickedButton;
	var currentForm;
	
	if (EPCR_Validate(EPCR_pid))
	{
		var _remind_date 	= jQuery('#EPCR-date-'+EPCR_pid).val();
		var _remind_time 	= jQuery('#EPCR-time-'+EPCR_pid).val();
		var _comment 		= jQuery('#EPCR-comment-'+EPCR_pid).val();
		var _reminder_name 	= jQuery('#EPCR-name-'+EPCR_pid).val();
		var _reminder_email = jQuery('#EPCR-email-'+EPCR_pid).val();
		jQuery('#EPCR-subbtn-'+EPCR_pid).attr("disabled", true);
		jQuery('#EPCR-loading-img-'+EPCR_pid).show();
		jQuery.ajax({
			type:'POST',
			url: EPCRajaxhandler.ajaxurl,
			data: {
				action: 'EPCR_add_remind',
				id: EPCR_pid,
				reminder_date: _remind_date,
				reminder_time: _remind_time,
				comment: _comment,
				reminder_name: _reminder_name,
				reminder_email: _reminder_email	
			},
			success: function(data,textStatus,XMLHttpRequest) {
				jQuery('#EPCR-loading-img-'+EPCR_pid).hide();
				data = jQuery.parseJSON(data);
				if (data.success) {
					jQuery('#EPCR-message-'+EPCR_pid).html(data.message).addClass('success');
					jQuery('#EPCR-form-'+EPCR_pid).remove();
				}
				else {
					jQuery('#EPCR-subbtn-'+EPCR_pid).attr("disabled", false);
					jQuery('#EPCR-message-'+EPCR_pid).html(data.message).addClass('error');
				}
			},
			error: function(XMLHttpRequest,textStatus,errorThrown) {
				alert(errorThrown);
			}
		});
	}
}

 function EPCR_Validate(EPCR_pid)                                   
	{     
		var EPCR_email	 = jQuery('#EPCR-email-'+EPCR_pid);   
		var EPCR_date 	 = jQuery('#EPCR-date-'+EPCR_pid); 
		var EPCR_time 	 = jQuery('#EPCR-time-'+EPCR_pid); 
		var EPCR_name 	 = jQuery('#EPCR-name-'+EPCR_pid); 
		var EPCR_comment	 = jQuery('#EPCR-comment-'+EPCR_pid); 

		if (EPCR_name.val() == "" && EPCR_name.hasClass("EPCR-check") )                          
		{
			jQuery('#EPCR-name-error-'+EPCR_pid).html("Please enter a name.");
			jQuery(EPCR_name).addClass("EPCR-required");
			EPCR_name.focus();
			return false;
		}
		else
		{
			jQuery('#EPCR-name-error-'+EPCR_pid).html(""); 
			jQuery(EPCR_name).removeClass("EPCR-required");
		}
	  
		if (EPCR_email.val() == "" )                                  
		{
			jQuery('#EPCR-email-error-'+EPCR_pid).html("Please enter an e-mail address.")
			jQuery(EPCR_email).addClass("EPCR-required");
			EPCR_email.focus();
			return false;
		}
		
		var EPCR_emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;

		if (EPCR_emailReg.test(EPCR_email.val()) == false)                
		{
			jQuery('#EPCR-email-error-'+EPCR_pid).html("Please enter a valid e-mail address.");
			jQuery(EPCR_email).addClass("EPCR-required");
			EPCR_email.focus();
			return false;
		}
		else
		{
			jQuery('#EPCR-email-error-'+EPCR_pid).html(""); 
			jQuery(EPCR_email).removeClass("EPCR-required");
		}
		
		if (EPCR_date.val()== "")                          
		{
			jQuery('#EPCR-date-error-'+EPCR_pid).html("Please enter a date.");
			jQuery(EPCR_date).addClass("EPCR-required");
			EPCR_date.focus();
			return false;
		}
		else
		{
			jQuery('#EPCR-date-error-'+EPCR_pid).html(""); 
			jQuery(EPCR_date).removeClass("EPCR-required");
		}
	  
		if (EPCR_time.val() == "")                       
		{
			jQuery('#EPCR-time-error-'+EPCR_pid).html("Please enter a time.");
			jQuery(EPCR_time).addClass("EPCR-required");
			EPCR_time.focus();
			return false;
		}
		else
		{
			jQuery('#EPCR-time-error-'+EPCR_pid).html(""); 
			jQuery(EPCR_time).removeClass("EPCR-required");
		}
		
		if (EPCR_comment.val() == "" && EPCR_comment.hasClass("EPCR-check"))                          
		{
			jQuery('#EPCR-comment-error-'+EPCR_pid).html("Please enter a Comment.");
			jQuery(EPCR_comment).addClass("EPCR-required");
			EPCR_comment.focus();
			return false;
		}
		else
		{
			jQuery('#EPCR-comment-error-'+EPCR_pid).html(""); 
			jQuery(EPCR_comment).removeClass("EPCR-required");
		}
	
		return true;
	}
	
	
