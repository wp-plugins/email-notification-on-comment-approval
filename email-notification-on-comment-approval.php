<?php
/*
Plugin Name: Email Notification On Comment Approval
Plugin URI: http://www.thewebexpert.in
Description: This Plugin Notifies a comment author by email on approval of his/her comment.
Author: Piyush Ranjan
Version: 0.1
Author URI: http://www.thewebexpert.in/
*/

//Adding Default value on Plugin activation
register_activation_hook(__FILE__,'enocp_add_default');

// function to add default value on plugin activation
function enocp_add_default(){

	update_option('enocp_from',get_settings('admin_email'));
	update_option('enocp_cc','');
	update_option('enocp_subject','Your Comment has been Approved');
	$email_text = " <strong>Congratulations !</strong><br/>
					Your comment has been approved by [site_url]<br/>
					See your comment in showing here [comment_url]<br/><br/>
					Thanks,<br/>
					[site_name]<br/>
					[site_url]"; 
	update_option('enocp_email_content',$email_text);
	
}


//Adding Setting page
function enocp_setting_menu() {
	add_menu_page( 'Comment Notificatin', 'Comment Notificatin', 'administrator', 'enocp_settings', 'enocp_setting_fn');
	
} 
add_action( 'admin_menu', 'enocp_setting_menu' );

//Function for email notification seeting
function enocp_setting_fn(){
	
	$status=0;
	$error=0;
	if(isset($_POST['submit'])){
		
		//Validation Before data storag
		
		// Set up regular expression strings to evaluate the value of email variable against
		$regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/'; 
		// Run the preg_match() function on regex against the email address
		$msg = '';
		if (!preg_match($regex, $_POST['enocp_from'])){ 
			$error=1;
			$msg .= $_POST['enocp_from'] . " is an invalid email. Please try again.<br>";
		} 
		if (!preg_match($regex, $_POST['enocp_cc']) && $_POST['enocp_cc']!==''){ 
			$error=1;
			$msg .= $_POST['enocp_cc'] . " is an invalid email. Please try again.<br>";
		}
		if (!preg_match($regex, $_POST['enocp_bcc']) && $_POST['enocp_bcc']!==''){ 
			$error=1;
			$msg .= $_POST['enocp_bcc'] . " is an invalid email. Please try again.<br>";
		}
		if($_POST['enocp_subject']==''){
			$error=1;
			$msg .= "Blank Subject can't be accepted.\n";
		}
		if($_POST['enocp_email_content']==''){
			$error=1;
			$msg .= "Please Enter some email content.\n";
		}
		if(!$error){
		
			//Data Insertion in database
			update_option('enocp_from',$_POST['enocp_from']);
			update_option('enocp_cc',$_POST['enocp_cc']);
			update_option('enocp_bcc',$_POST['enocp_bcc']);
			update_option('enocp_subject',$_POST['enocp_subject']);
			update_option('enocp_email_content',$_POST['enocp_email_content']);
			$status=1;
		}
	}

?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br></div>
		<h2>Email Notification On Comment Approval Settings</h2>
		<?php
			//If setting Updated.
			if($status){
			?>
				<div class="updated settings-error" id="setting-error-settings_updated"> 
					<p><strong>Settings saved.</strong></p>
				</div>
			<?php
			}
			//If have some error in data insertion
			if($error){
			?>
				<div class="error settings-error" id="setting-error-invalid_siteurl"> 
					<p><strong><?php echo $msg; ?></strong></p>
				</div>
			<?php
			}
		?>
		<form action="" method="POST">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="From">From</label></th>
					<td><input type="text" class="regular-text" value="<?php echo get_option('enocp_from'); ?>" id="enocp_from" name="enocp_from"></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="CC">CC</label></th>
					<td><input type="text" class="regular-text" value="<?php echo get_option('enocp_cc'); ?>" id="enocp_cc" name="enocp_cc"></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="BCC">BCC</label></th>
					<td><input type="text" class="regular-text" value="<?php echo get_option('enocp_bcc'); ?>" id="enocp_bcc" name="enocp_bcc"></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="Subject">Subject</label></th>
					<td><input type="text" class="regular-text" value="<?php echo get_option('enocp_subject'); ?>" id="enocp_subject" name="enocp_subject"></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="email_content">Email Content</label></th>
					<td><?php  the_editor(get_option('enocp_email_content'), 'enocp_email_content' ); ?></td>
				</tr>
				<tr valign="top">
					<th scope="row"><input type="submit" value="Save Changes" class="button button-primary" id="submit" name="submit"></th>
					<td></td>
				</tr>
			</table>
		</form>
	</div>
<?php
}
//Function to send notification
function approve_comment_callback($new_status, $old_status, $comment) {

	$notifiy_flag = get_comment_meta ( $comment->comment_ID, 'allow_notification', true );
	if($notifiy_flag=='1'){
	
		if($old_status != $new_status) {
			
			if($new_status == 'approved' || $new_status==1) {
			
				if($comment->comment_author_email!=''){
					
					$to = $comment->comment_author_email;
					$subject = get_option('enocp_subject');
					$text = get_option('enocp_email_content');
					$text = str_replace("[site_url]", get_bloginfo('home'), $text);
					$text = str_replace("[comment_url]", get_permalink($comment->comment_post_ID), $text);
					$text = str_replace("[site_name]", get_bloginfo('name'), $text);
					$headers = "From: ".get_bloginfo('name')." < ".get_option('enocp_from')." >\r\n";
					$headers .= "Cc: ".get_option('enocp_cc')."\r\n";
					$headers .= "Bcc: ".get_option('enocp_bcc')."\r\n";
					add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
					wp_mail( $to, $subject, $text, $headers);
					
				}
			 
			}
		}
	
	}
}
 
add_action('transition_comment_status', 'approve_comment_callback', 10,3);

//Hooking function in comment form creation
add_filter('comment_form_default_fields', 'enocp_custom_fields');

//Function to add checkbox in comment form	
function enocp_custom_fields($fields) {
	$fields['allow_notification'] = '<p class="notification"><label for="notification">Send me email on comment approval</label> <input type="checkbox" name="allow_notification" value="1"></p>';
	return $fields;
}

//Hooking function on comment save	
add_action( 'comment_post', 'enocp_save_comment_meta_data' );

//Function to save checkbox value on comment save
function enocp_save_comment_meta_data( $comment_id ) {

	if(isset($_POST['allow_notification'])){
	
		add_comment_meta($comment_id, 'allow_notification', 1, $unique = false);
		
	}else{
	
		add_comment_meta($comment_id, 'allow_notification', 0, $unique = false);
		
	}
	
}
?>