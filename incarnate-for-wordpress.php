<?php
/*
Plugin Name: Incarnate for WordPress
Plugin URI: http://www.visitmix.com/labs/incarnate
Description: An alternative to gravatar. People leaving comments can choose a profile image or avatar from Twitter, Facebook, MySpace, YouTube or XBoxLive.
Author: MIX ONLINE
Version: 1.2
Author URI: http://www.visitmix.com/labs/incarnate
*/

/***********************************************************************************************************
   Microsoft Public License (Ms-PL)

   This license governs use of the accompanying software. If you use the software, you accept this license. 
   If you do not accept the license, do not use the software.

   1. Definitions
      The terms "reproduce," "reproduction," "derivative works," and "distribution" have the same meaning 
	  here as under U.S. copyright law. A "contribution" is the original software, or any additions or 
	  changes to the software. A "contributor" is any person that distributes its contribution under this 
	  license. "Licensed patents" are a contributor's patent claims that read directly on its contribution.
   2. Grant of Rights
      (A) Copyright Grant- Subject to the terms of this license, including the license conditions and 
	  limitations in section 3, each contributor grants you a non-exclusive, worldwide, royalty-free 
	  copyright license to reproduce its contribution, prepare derivative works of its contribution, and 
	  distribute its contribution or any derivative works that you create.
      (B) Patent Grant- Subject to the terms of this license, including the license conditions and 
	  limitations in section 3, each contributor grants you a non-exclusive, worldwide, royalty-free 
	  license under its licensed patents to make, have made, use, sell, offer for sale, import, and/or 
	  otherwise dispose of its contribution in the software or derivative works of the contribution in 
	  the software.
   3. Conditions and Limitations
      (A) No Trademark License- This license does not grant you rights to use any contributors' name, logo,
	  or trademarks.
      (B) If you bring a patent claim against any contributor over patents that you claim are infringed by 
	  the software, your patent license from such contributor to the software ends automatically.
      (C) If you distribute any portion of the software, you must retain all copyright, patent, trademark, 
	  and attribution notices that are present in the software.
      (D) If you distribute any portion of the software in source code form, you may do so only under this 
	  license by including a complete copy of this license with your distribution. If you distribute any 
	  portion of the software in compiled or object code form, you may only do so under a license that 
	  complies with this license.
      (E) The software is licensed "as-is." You bear the risk of using it. The contributors give no 
	  express warranties, guarantees, or conditions. You may have additional consumer rights under your 
	  local laws which this license cannot change. To the extent permitted under your local laws, the 
	  contributors exclude the implied warranties of merchantability, fitness for a particular purpose and 
	  non-infringement.
***********************************************************************************************************/

define ( "INCARNATE_FOR_WORDPRESS_VERSION", "1.1" );

// basic API hooks
add_action('comment_form', "add_form_field", 9999);
add_action('comment_post', "check_comment_post");
add_action('wp_head', "incarnate_for_wordpress_add_scriptlet");
add_action('admin_head', 'incarnate_for_wordpress_add_scriptlet');
add_action('wp_print_scripts', "incarnate_for_wordpress_add_scripts");

// options API hooks
add_action('admin_menu', 'incarnate_for_wordpress_options'); // this is the master options page

// plug-in page filter
add_filter( 'plugin_action_links', 'incarnate_for_wordpress_actionlinks', 10, 2 ); // Settings Button on Plugins Panel

// install and uninstall
register_activation_hook(__FILE__,'incarnate_for_wordpress_install');
register_deactivation_hook(__FILE__,'incarnate_for_wordpress_uninstall');

/**
 * Custom callback for use in wp_list_comments('callback=incarnate_for_wordpress_comment');
function incarnate_for_wordpress_comment($comment, $args, $depth) {
	$GLOBALS['comment'] = $comment; ?>
	<li <?php comment_class(); ?> id="li-comment-<?php comment_ID() ?>">
	<div id="comment-<?php comment_ID(); ?>">
	<div class="comment-author vcard">
	<?php echo get_avatar(($comment->user_id > 0) ? ($comment->user_id) : ($comment->comment_author_email),$size='48'); ?>
	<?php printf(__('<cite class="fn">%s</cite> <span class="says">says:</span>'), get_comment_author_link()) ?>
	</div>
	<?php if ($comment->comment_approved == '0') : ?>
	<em><?php _e('Your comment is awaiting moderation.') ?></em>
	<br />
	<?php endif; ?>
	<div class="comment-meta commentmetadata"><a href="<?php echo htmlspecialchars( get_comment_link( $comment->comment_ID ) ) ?>"><?php printf(__('%1$s at %2$s'), get_comment_date(), get_comment_time()) ?></a><?php edit_comment_link(__('(Edit)'),' ','') ?></div>
	<?php comment_text() ?>
	<div class="reply">
	<?php comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth']))) ?>
	</div>
	</div>
	<?php
}
*/

/**
 * This is a pluggable function that we can override.  We'll override it to get our
 * custom saved avatar out of the database.
 */
if( !function_exists('get_avatar') ) {
	function get_avatar($id_or_email_or_comment,$size) {
		if( is_object($id_or_email_or_comment) ) {
			$comment = $id_or_email_or_comment;
		} else {
			global $comment;
		}
		
		if ( ! get_option('show_avatars') ) return false;
		if ( is_admin() ) return false; /* admin doesn't get comment objects, just id/email */

		if ( !is_numeric($size) )
			$size = '96';

		if(!$comment) {
			if(is_numeric($id_or_email_or_comment)) {
				// a user id, load that data
				$out = incarnate_for_wordpress_get_avatar_byuserid($id_or_email_or_comment, $size);
			} else {
				// probably an email...?
				$out = incarnate_for_wordpress_get_avatar_byemail($id_or_email_or_comment, $size);
			}
		} else {
			$out = incarnate_for_wordpress_get_avatar_by_comment($comment);
		}
		$avatar = "<img src='{$out}' class='avatar avatar-{$size} photo'  width='{$size}' />";
		
		return apply_filters('get_avatar', $avatar, $id_or_email, $size, $default, $alt);
	}
}
 
/**
 * This function can be used inside of wp_comment_list on really weird themes that override
 * the comment functionality.
 */
function incarnate_for_wordpress_insert_avatar($comment) {
	if ( ! $comment ) {
		global $comment; // this will be a global set from our custom incarnate_for_wordpress_comment function
		if ( ! $comment ) {
			echo("We're sorry but the call you added to 'incarnate_for_wordpress_insert_avatar' can't understand which comment you were trying to display.");
			return false; // skip out if this isn't our call
		}
	}

	$size = '32';
	$out = incarnate_for_wordpress_get_avatar_by_comment($comment);
	$avatar = "<img src='{$out}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
	
	echo (apply_filters('get_avatar', $avatar, $id_or_email, $size, $default, $alt));
	
	return;
}

/**
 * Called when the plug-in is activated from the Plugin admin page
 */
function incarnate_for_wordpress_install() {
	global $wpdb;
	$table_name = $wpdb->prefix . "incarnate_avatars";
	
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		// the table doesn't exist yet
		$sql = "CREATE TABLE " . $table_name . " (
			  commentid bigint(11) NOT NULL,
			  avatar tinytext NOT NULL,
			  UNIQUE KEY commentid (commentid)
			);";

		// this function will analyze the table structure and update where appropriate
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	// create and update all applicable options
    update_option('incarnate_for_wordpress_version', INCARNATE_FOR_WORDPRESS_VERSION);
	add_option('incarnate_for_wordpress_autoinsert', true);
	
    add_option('incarnate_for_wordpress_uninstall_flag', '');
}

/**
 * Called when the plug-in is deactivated from the Plugin admin page
 */
function incarnate_for_wordpress_uninstall() {
	if(get_option('incarnate_for_wordpress_uninstall_flag') !== FALSE) {
		delete_option('incarnate_for_wordpress_uninstall_flag');
		delete_option('incarnate_for_wordpress_autoinsert');
	}
}

/**
 * Plug-in action links will add the "Settings" link on the Manage Plug-ins page
 */
function incarnate_for_wordpress_actionlinks($links, $file) {
	static $this_plugin;
	if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);

	if ($file == $this_plugin){
		array_unshift( $links, '<a href="options-general.php?page=incarnate-for-wordpress-options">Settings</a>' );
	}
	return $links;
}
 
/**
 * Calls the add_options_page function from the admin_menu action
 */
function incarnate_for_wordpress_options() {
	if( function_exists( 'is_site_admin' ) ) {
		if( is_site_admin() ) {
			add_options_page('Incarnate Options', 'Incarnate', 'manage_options', 'incarnate-for-wordpress-options', 'incarnate_for_wordpress_options_html');
			add_submenu_page('incarnate-for-wordpress.php', 'Incarnate', 'Incarnate', 'manage_options', 'incarnate-for-wordpress-options', 'incarnate_for_wordpress_options_html');
		}
	} else {
		add_options_page('Incarnate Options', 'Incarnate', 'manage_options', 'incarnate-for-wordpress-options', 'incarnate_for_wordpress_options_html');
	}
}

/**
 * Displays the HTML for the admin options page
 */
function incarnate_for_wordpress_options_html() {
	echo("<div class='wrap'>");
	
	if(isset($_POST['incarnateswitching'])) {
		update_option("incarnate_for_wordpress_autoinsert", (($_POST['incarnatemode'] == "true") ? TRUE : FALSE));
	} 
	
	$automatic = get_option("incarnate_for_wordpress_autoinsert") != FALSE;
	
	if (isset($_POST['gofinal'])) {
		/**** FINAL SUBMISSION PAGE ****************************************************************/
	?>
		<div id='icon-tools' class='icon32'><br /></div>
		<h2>Incarnate Manual Setup</h2>
		
		<p>You're switched!  Thanks for setting up Incarnate for WordPress.  If you need more help, please consult the documentation.  It's always up to date at the <a href="http://wordpress.org/extend/plugins/incarnate-for-wordpress/installation/">WordPress Plug-in Directory</a>.</p>
		
		<p><a href="?page=incarnate-for-wordpress-options">Back to Settings</a></p>
	<?php
	} else {
		/**** DEFAULT OPTIONS PAGE *****************************************************************/
	?>
		<div id='icon-tools' class='icon32'><br /></div>
		<h2>Incarnate Options</h2>

		<div>
		Incarnate works automatically with most themes.  
		
		<script type="text/javascript">
		jQuery(document).ready(function () {
			jQuery("#show-help").click(function () {
				jQuery('#manual-help').hide();	
				jQuery('#incarnate-help').toggle();
			});
			
			jQuery("#switch-to-manual").click(function () {
				jQuery('#incarnate-help').hide();	
				jQuery('#manual-help').toggle();
			});

			jQuery("#switch-to-automatic").click(function () {
				jQuery('#automatic-help').show();
				jQuery('#switch-to-automatic').hide();
			});
		});
		</script> 
		<small><a id="show-help" href="#">Not working?</a></small>
		</div>
		
		<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="incarnate_autoinsert"><strong>Configuration:</strong></label></th>
			<td>
			<?php if($automatic) { ?>
				<div id='incarnate-auto-yes'>Automatic</div>
				<div><a id="switch-to-manual" href="#">Switch to Manual</a></div>
			<?php } else { ?>
				<div id='incarnate-auto-no'>Manual</div>
				<div><a id="switch-to-automatic" href="#">Switch to Automatic</a></div>
			<?php } ?>
			</td>
		</tr> 
		</table>

		<div id="incarnate-help" style="display: none;">
		<h3>Help!</h3>
			Incarnate has two major parts:
			<ul style="list-style-type: square; margin: 15px;">
			<li>Incarnate Avatar Form</li>
			<li>Comment Avatars <i>(Note: please be sure to turn on "Show Avatars" in your WordPress <a href="options-discussion.php">Discussion settings</a>)</i></li>
			</ul> 
			If they don't show up automatically, we can guide you through adding each piece to your theme.  Just click "Switch To Manual".
		</div>
	
		<div id="automatic-help" style="display: none;">
		<h3>Switch To Automatic</h3>
		If you'd like to switch back to automatic mode (for the Incarnate form and the avatars) click below.
		
			<form name="incarnateform" method="post" action="<?php echo $_SERVER['REDIRECT_SCRIPT_URI'] ?>?page=incarnate-for-wordpress-options&updated=true">
			<div class="submit">
					<input type="hidden" name="incarnatemode" value="true" />
					<input type="hidden" name="incarnateswitching" value="true" />
					<input type="submit" name="submit" value="Switch To Manual & Continue" />
			</div>
			</form> 
		</div>
		
		<div id="manual-help" style="display: none;">
		<h3>Switch To Manual</h3>
		When you switch to manual you'll need to do three things.
			<h4>Step 1) Incarnate Avatar Form</h4>
			
			<p>First you need to modify the <b>comments.php</b> page of your theme so that the Incarnate form appears.  You can make this change by editing the comments.php file using the 		<a href="theme-editor.php">WordPress theme editor</a>.</p>
			
			<p>You'll need to look carefully through your comments.php file and find the start of the comment form.  It looks like this: &lt;form action=&quot;&lt;?php echo get_option('siteurl'); ?&gt;/wp-comments-post.php&quot; method=&quot;post&quot; id=&quot;commentform&quot; onsubmit=&quot;return validatecomment(this);&quot;&gt;.  Add this line just below it:</p>
			
			<p>
			<code>
			<?php
echo <<<CODEEXAMPLE
&lt;?php if(function_exists('incarnate_for_wordpress_insert_ui')) { incarnate_for_wordpress_insert_ui(); } ?&gt;
CODEEXAMPLE
			?>
			</code>
			</p>
			
			<p>To test the avatars you'll need to log out and try submitting a comment. If you pick an avatar but it doesn't display by the comment you'll need to do step 2. Please note: Avatars must be turned on in your <a href="options-discussion.php">discussion settings</a>.</p>
			
			<h4>Step 2) Avatar Display</h4>
			<p>
			Now that you've completed step 1 you can add the avatars to your comments.  Look carefully through your comments.php and find the comment list.  The HTML should be near the line containing &lt;?php comment_ID() ?&gt; or &lt;?php $comment->comment_ID; ?&gt;.
			</p>
			<code>
			<?php 
echo <<<CODEEXAMPLE
&lt;?php if(function_exists('incarnate_for_wordpress_insert_avatar')) { incarnate_for_wordpress_insert_avatar(\$comment); } ?&gt;
CODEEXAMPLE
			?>
			</code>
			</p>

			<h4>Step 3) Turn off automatic mode</h4>
			<p>
			Now that you're all set up, click below to turn off automatic mode.  
			</p>
			
			<form name="incarnateform" method="post" action="<?php echo $_SERVER['REDIRECT_SCRIPT_URI'] ?>?page=incarnate-for-wordpress-options&updated=true">
			<div class="submit">
					<input type="hidden" name="incarnatemode" value="false" />
					<input type="hidden" name="incarnateswitching" value="true" />
					<input type="submit" name="submit" value="Switch To Manual" />
			</div>
			</form> 

		</div> <!-- /themehelp -->

	<?php 
	}
	?>
	</div> <!-- /wrap -->
	<?php
}

/**
 * This is the handler for the action:comment_form API hooks, it will add a form
 * field and any HTML/JS we need to activate the Incarnate UI
 */
function add_form_field($id) {
	// only if the user has set the auto option
	if(get_option("incarnate_for_wordpress_autoinsert") != FALSE) {
		incarnate_for_wordpress_render_ui();
	}
}
	
/**
 * This function will handle all the comment data that was added by the incarnate
 * UI from add_form_field.  If it fails the validity check it will javascript redirect
 * back to the comment with the fields populated.
 */
function check_comment_post($id) {
	$avatar_url = $_POST['IncarnateImgSrc'];
	$comment_valid = (incarnate_for_wordpress_add_avatar($id, $avatar_url) !== FALSE);
	
	// we've got a good comment so we'll keep it and return the $id
	if($comment_valid) return $id; 
	
	// the comment was not valid so we fall through to delete the comment and javascript redirect...
	wp_set_comment_status($id, 'delete');
	?>
	<html>
	    <head><title>Invalid Code</title></head>
		<body>
			<form name="data" action="<?php echo $_SERVER['HTTP_REFERER']; ?>#respond" method="post">
				<input type="hidden" name="incarnate_for_wordpress_err" value="1" />
				<input type="hidden" name="author_pre" value="<?php echo htmlspecialchars($_POST['author']); ?>" />
				<input type="hidden" name="email_pre" value="<?php echo htmlspecialchars($_POST['email']); ?>" />
				<input type="hidden" name="url_pre" value="<?php echo htmlspecialchars($_POST['url']); ?>" />
				<textarea style="display:none;" name="comment_pre"><?php echo htmlspecialchars($_POST['comment']); ?></textarea>
			</form>
			<script type="text/javascript">
			<!--
			document.forms[0].submit();
			//-->
			</script>
		</body> 
	</html>
	<?php
	exit();
}

/**
 * This hook will add our scriptlet to the wp_head.
 */
function incarnate_for_wordpress_add_scriptlet() {
	?>
	<script type="text/javascript">
	//<!CDATA[
		// used to find the service and ajax images in the javascript
		function getIncarnateImageRoot() { return "<?php echo(WP_PLUGIN_URL); ?>/incarnate-for-wordpress/images/"; }
		function getIncarnateWebservice() { return "http://incarnate.visitmix.com/incarnate.svc"; }
		function getIncarnateLoggedIn() { return <?php echo(is_user_logged_in() ? "true" : "false"); ?>; }
		function getIncarnateDefaultImage() { 
			return "<?php echo(incarnate_for_wordpress_get_default_avatar()); ?>"; 
		}
		function getIncarnateCurrentUserImage() {
			return "<?php echo(incarnate_for_wordpress_get_currentuser_avatar()); ?>"; 
		}
		
		<?php if(isset($_POST['incarnate_for_wordpress_err'])) : ?>
		jQuery(document).ready(function () {
			// Copy back the data into the form
			ff = document.getElementById("commentform");
			ff.author.value = "<?php echo htmlspecialchars($_POST['author_pre']); ?>";
			ff.email.value = "<?php echo htmlspecialchars($_POST['email_pre']); ?>";
			ff.url.value = "<?php echo htmlspecialchars($_POST['url_pre']); ?>";
			ff.comment.value = "<?php $trans = array("\r" => '\r', "\n" => '\n');
			echo strtr(htmlspecialchars($_POST['comment_pre']), $trans); ?>";
		});
		<?php endif; ?>
	//]]>
	</script>
	<?php
}

/**
 * This hook will add our scripts to the script queue
 */
function incarnate_for_wordpress_add_scripts() {
   // Queues up the jQuery library
   wp_enqueue_script('incarnate', WP_PLUGIN_URL . '/incarnate-for-wordpress/incarnate.js', array('jquery'));
}

/**
 * This is a simple function that can be called from the template to include the Incarnate UI
 */
function incarnate_for_wordpress_insert_ui() {
	incarnate_for_wordpress_render_ui(false);
} 

/**
 * This function will render the manual or the automatic incarnate UI
 */
function incarnate_for_wordpress_render_ui($auto = true) {
	$avatar_img = ( is_user_logged_in() ) ? incarnate_for_wordpress_get_currentuser_avatar() : incarnate_for_wordpress_get_default_avatar();
	
	?>
	<script type="text/javascript">
		var html = '<input type="hidden" name="IncarnateImgSrc" id="IncarnateImgSrc" value="<?php echo($avatar_img) ; ?>" />';
		html += '<div style="float:left; margin:5px;">'
		html += '<img id="IncarnateImg" class="avatar avatar-64 photo" width="64" src="<?php echo($avatar_img); ?>" />';
		html += '</div>\n';
		html += '<p>';

		<?php if(isset($_POST['incarnate_for_wordpress_err'])) : ?>
		html += "Sorry, there was an error while submitting your Incarnated avatar.  Please try again.<br />";
		<?php endif; ?>
		
		<?php if ( is_user_logged_in() ) : ?>
		html += '<input style="visibility: hidden;" id="IncarnateUserName" disabled="disabled" type="text" /><img style="display: none;" id="IncarnateLoader" src="<?php echo(WP_PLUGIN_URL); ?>/incarnate-for-wordpress/images/loader.gif" />';
		html += '<input id="IncarnateActivate" style="visibility: hidden;" type="button" value="Find" />';
		html += '<label><br />';
		html += '<small>Your Gravatar</small></label>';
		<?php else : ?>
		html += '<input id="IncarnateUserName" type="text" size="48" style="width: 225px" />';
		html += '<input id="IncarnateActivate" style="margin-left: 20px; width: 45px;" type="button" value="Find" />';
		html += '<img style="display: none;" id="IncarnateLoader" src="<?php echo(WP_PLUGIN_URL); ?>/incarnate-for-wordpress/images/loader.gif" />';
		html += '<div style="display: block; float: left; width: 250px;"';
		html += '<label>';
		html += '<small>Enter your name, handle, alias, or email. ';
		html += 'We\'ll find you from the services below.<br /><img src="<?php echo(WP_PLUGIN_URL); ?>/incarnate-for-wordpress/images/Twitter.png" /><img src="<?php echo(WP_PLUGIN_URL); ?>/incarnate-for-wordpress/images/Facebook.png" /><img src="<?php echo(WP_PLUGIN_URL) ?>/incarnate-for-wordpress/images/MySpace.png" /><img src="<?php echo(WP_PLUGIN_URL); ?>/incarnate-for-wordpress/images/YouTube.png" /><img src="<?php echo(WP_PLUGIN_URL); ?>/incarnate-for-wordpress/images/Gravatar.png" /><img src="<?php echo(WP_PLUGIN_URL); ?>/incarnate-for-wordpress/images/XBoxLive.png" /></small>';
		html += '</label></div>';
		html += '</p>';
		html += '<div id="IncarnateResultsContainer" style="display: none;background:#fff;position:absolute;border:1px solid #999;width:100px"></div>';
		<?php endif; ?>
		html += '<div style="clear: both;">&nbsp;</div>';
		   
		<?php if($auto) : ?> 
		if(incarnateFormHasRendered == false) {
			// pull in the tag type from the children of the form (some people use 'div', some 'p')
			var tagType = jQuery("form[action*=wp-comments-post]").children(":first").get(0).tagName.toLowerCase() == "p" ? "p" : "div";
			jQuery("form[action*=wp-comments-post]").prepend("<" + tagType + " style=\"text-align: left;\">" + html + "</" + tagType + ">");
			incarnateFormHasRendered = true;
		}
		<?php else : ?>
		document.write(html);
		<?php endif; ?>
	</script>
	<?php
}

/**
 * This function will insert a record into the database table for the avatar.
 */
function incarnate_for_wordpress_add_avatar($commentid, $avatarurl) {
	global $wpdb;
	
	$table_name = $wpdb->prefix . "incarnate_avatars";

	if(!preg_match("/^http\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\\S*)?$/", $avatarurl)) return null;
	if(!is_numeric($commentid)) return null;
  
	$insert = "INSERT INTO " . $table_name .
			" (commentid, avatar) " .
			"VALUES (" . $commentid . ",'" . $avatarurl . "')";

	$results = $wpdb->query( $insert );
	
	if($results !== FALSE) return TRUE;
	
	return FALSE;
}

/**
 * Retrieves the avatar url from the database for a given commentid
 */
function incarnate_for_wordpress_get_avatar_by_comment($comment) {
	global $wpdb;
	
	if(!is_object($comment)) return null;
	if(!is_numeric($comment->comment_ID)) return null;

	$table_name = $wpdb->prefix . "incarnate_avatars";
	$insert = "SELECT avatar FROM " . $table_name . " WHERE commentid=" . $comment->comment_ID;
	$avatarurl = $wpdb->get_var( $insert );
	
	if(!$avatarurl) return incarnate_for_wordpress_get_default_avatar($comment);
	
	return $avatarurl;
}

function incarnate_for_wordpress_get_default_avatar($comment = FALSE) {
	$email = "";
	$size = 64;

	$comment_gravatar = incarnate_for_wordpress_get_gravatar_by_comment($comment);
	if($comment_gravatar !== FALSE) return $comment_gravatar;
	
	$avatar_default = get_option('avatar_default');
	if ( empty($avatar_default) ) { $default = 'mystery'; } else { $default = $avatar_default; }

	if ( is_ssl() ) { $host = 'https://secure.gravatar.com'; } else { $host = 'http://www.gravatar.com'; }

	if ( 'mystery' == $default )
		$default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}"; // ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
	elseif ( 'blank' == $default )
		$default = includes_url('images/blank.gif');
	elseif ( !empty($email) && 'gravatar_default' == $default )
		$default = '';
	elseif ( 'gravatar_default' == $default )
		$default = "$host/avatar/s={$size}";
	elseif ( empty($email) )
		$default = "$host/avatar/?d=$default&amp;s={$size}";
	elseif ( strpos($default, 'http://') === 0 )
		$default = add_query_arg( 's', $size, $default );
	
	// adding in Gravatar basic functionality - this will show the Gravatar but use the default they desired.
	
	 
	return $default;
}

function incarnate_for_wordpress_get_gravatar_by_comment($comment = FALSE) {
	if(!is_object($comment)) return FALSE;
	if(!is_numeric($comment->comment_ID)) return FALSE;
	
	$id_or_email = $comment->user_id;
	if($id_or_email <= 0) { 
		$id_or_email = $comment->comment_author_email;
	}
	
	$email = '';
	if ( is_numeric($id_or_email) ) {
		$id = (int) $id_or_email;
		$user = get_userdata($id);
		if ( $user )
			$email = $user->user_email;
	} elseif ( is_object($id_or_email) ) {
		if ( isset($id_or_email->comment_type) && '' != $id_or_email->comment_type && 'comment' != $id_or_email->comment_type )
			return false; // No avatar for pingbacks or trackbacks

		if ( !empty($id_or_email->user_id) ) {
			$id = (int) $id_or_email->user_id;
			$user = get_userdata($id);
			if ( $user)
				$email = $user->user_email;
		} elseif ( !empty($id_or_email->comment_author_email) ) {
			$email = $id_or_email->comment_author_email;
		}
	} else {
		$email = $id_or_email;
	}
	
	if ( empty($default) ) {
		$avatar_default = get_option('avatar_default');
		if ( empty($avatar_default) )
			$default = 'mystery';
		else
			$default = $avatar_default;
	}

 	if ( is_ssl() )
		$host = 'https://secure.gravatar.com';
	else
		$host = 'http://www.gravatar.com';

	if ( 'mystery' == $default )
		$default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}"; // ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
	elseif ( 'blank' == $default )
		$default = includes_url('images/blank.gif');
	elseif ( !empty($email) && 'gravatar_default' == $default )
		$default = '';
	elseif ( 'gravatar_default' == $default )
		$default = "$host/avatar/s={$size}";
	elseif ( empty($email) )
		$default = "$host/avatar/?d=$default&amp;s={$size}";
	elseif ( strpos($default, 'http://') === 0 )
		$default = add_query_arg( 's', $size, $default );

	if ( !empty($email) ) {
		$out = "$host/avatar/";
		$out .= md5( strtolower( $email ) );
		$out .= '?s='.$size;
		$out .= '&amp;d=' . urlencode( $default );

		$rating = get_option('avatar_rating');
		if ( !empty( $rating ) )
			$out .= "&amp;r={$rating}";

		return $out;
	} 
	 
	return FALSE;
}

function incarnate_for_wordpress_get_currentuser_avatar() {
	$email = "";

	$size = 64;

	global $user_login , $user_email;
	get_currentuserinfo();

	if ($user_email) {  $email = $user_email; } else return incarnate_for_wordpress_get_default_avatar();
  
	return incarnate_for_wordpress_get_avatar_byemail($email, $size);
}

function incarnate_for_wordpress_get_avatar_byuserid($id, $size) {
	$email = "";
	 
	global $user_login , $user_email;
	$user = get_userdata($id);
	
	if ($user->user_email) {  $email = $user->user_email; } else return incarnate_for_wordpress_get_default_avatar();

	return incarnate_for_wordpress_get_avatar_byemail($email, $size);
}

function incarnate_for_wordpress_get_avatar_byemail($email, $size) {
	if ( is_ssl() ) { $host = 'https://secure.gravatar.com'; } else { $host = 'http://www.gravatar.com'; }

	if ( empty($default) ) {
		$avatar_default = get_option('avatar_default');
		if ( empty($avatar_default) )
			$default = 'mystery';
		else
			$default = $avatar_default;
	}

	if ( 'mystery' == $default )
		$default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}"; // ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
	elseif ( 'blank' == $default )
		$default = includes_url('images/blank.gif');
	elseif ( !empty($email) && 'gravatar_default' == $default )
		$default = '';
	elseif ( 'gravatar_default' == $default )
		$default = "$host/avatar/s={$size}";
	elseif ( empty($email) )
		$default = "$host/avatar/?d=$default&amp;s={$size}";
	elseif ( strpos($default, 'http://') === 0 )
		$default = add_query_arg( 's', $size, $default );

	$out = "$host/avatar/";
	$out .= md5( strtolower( $email ) );
	$out .= '?s='.$size;
	$out .= '&amp;d=' . urlencode( $default );

	$rating = get_option('avatar_rating');
	if ( !empty( $rating ) )
		$out .= "&amp;r={$rating}";

	return $out;
}
?>