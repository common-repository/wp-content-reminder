<?php
global $post;
$form_options = get_option('EPCR_form_settings');
$permissions = get_option('EPCR_permissions_settings');
$required_fields = $form_options['required_fields'];
?>	
<form action="#" id="rmdfrm" method="post" name="myForm" onsubmit="return(EPCR_Validate());">
<div class="EPCR-container <?php echo $form_options['color_scheme']; ?>">
	<button type="button" class="EPCR-switch" id="EPCR-remind-btn-<?php echo $post->ID; ?>" onClick="EPCR_Content_Slide(<?php echo $post->ID; ?>);"><?php echo $form_options['slidedown_button_text']; ?></button>
	<div class="EPCR-content" id="EPCR-content-<?php echo $post->ID; ?>">
		<div class="EPCR-message" id="EPCR-message-<?php echo $post->ID; ?>">
		</div>
		<div class="EPCR-form" id="EPCR-form-<?php echo $post->ID; ?>">
			<?php if ($permissions['login_required'] && !is_user_logged_in()): ?>
				To remind this post you need to <a href="<?php echo wp_login_url(); ?>" title="Login">login</a> first.
			<?php else: ?>
			<div class="left-section">
				<li class="list-item-name">
					<?php if (!empty($form_options['active_fields']['reminder_name'])): ?>
						<input type="text" name="name" placeholder="Name" id="EPCR-name-<?php echo $post->ID; ?>" class="input-name EPCR-input <?php if ($required_fields['reminder_name']): ?> EPCR-check<?php endif; ?> "/><span id="EPCR-name-error-<?php echo $post->ID; ?>" class="EPCR-error-msg"></span><br>
					<?php endif; ?>
				</li>
				<li class="list-item-email">
						<input type="text" name="email" placeholder="Email" id="EPCR-email-<?php echo $post->ID; ?>" class="input-email EPCR-input" value="<?php echo esc_attr( $reminder_email); ?>"/>
						<span id="EPCR-email-error-<?php echo $post->ID; ?>" class="EPCR-error-msg"></span><br>
						
				</li>
				<li class="list-item-date">
						<input type="text" name="date" Placeholder="Date" id="EPCR-date-<?php echo $post->ID; ?>" class="EPCR-datepicker input-date EPCR-input" value="<?php echo esc_attr( $remind_date ); ?>"/>
						 <span id="EPCR-date-error-<?php echo $post->ID; ?>" class="EPCR-error-msg"></span> <br>
				</li>
				<li class="list-item-time">
						<input type="text" name="time" placeholder="Time" id="EPCR-time-<?php echo $post->ID; ?>" class="EPCR-timepicker input-time EPCR-input" value="<?php echo esc_attr( $remind_time ); ?>"/>
						<span id="EPCR-time-error-<?php echo $post->ID; ?>" class="EPCR-error-msg"></span>
				</li>
			</div>
			<div class="right-section">
				<li class="list-item-details">
					<?php if (!empty($form_options['active_fields']['comment'])): ?>
						<textarea id="EPCR-comment-<?php echo $post->ID; ?>" name="comment" placeholder="Comment" class="input-details EPCR-input <?php if ($required_fields['comment']): ?> EPCR-check <?php endif; ?>"></textarea><span id="EPCR-comment-error-<?php echo $post->ID; ?>" class="EPCR-error-msg"></span><br>
					<?php endif; ?>
				</li>
			</div>
			<div class="clear"></div>
			<input type="hidden" class="post-id" value="<?php echo $post->ID; ?>">
			<button type="button" class="EPCR-submit" id="EPCR-subbtn-<?php echo $post->ID; ?>" onClick="EPCR_Content_Remind(<?php echo $post->ID; ?>);"><?php echo $form_options['submit_button_text'] ?></button>
			<img id="EPCR-loading-img-<?php echo $post->ID; ?>" class="loading-img" style="display:none;"
				 src="<?php echo plugins_url('static/img/loading.gif', dirname(__FILE__)); ?>"/>
		</div>
		<?php endif; ?>
	</div>
</div>
</form>
