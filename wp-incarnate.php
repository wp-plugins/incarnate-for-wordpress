<?php
/*
Plugin Name: Incarnate for WordPress
Plugin URI: http://www.visitmix.com/labs/incarnate
Description: Pull in profiles from the web to enhance your blog's commenting experience.
Author: MIX ONLINE
Version: 1.0
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

define ( "WP_INCARNATE_VERSION", "0.1" );

// basic API hooks
add_action('comment_form', "add_form_field", 9999);
add_action('comment_post', "check_comment_post");
add_action('wp_head', "wp_incarnate_add_scriptlet");
add_action('admin_head', 'wp_incarnate_add_scriptlet');
add_action('wp_print_scripts', "wp_incarnate_add_scripts");

// options API hooks
add_action('admin_menu', 'wp_incarnate_options'); // this is the master options page

// plug-in page filter
add_filter( 'plugin_action_links', 'wp_incarnate_actionlinks', 10, 2 ); // Settings Button on Plugins Panel

// install and uninstall
register_activation_hook(__FILE__,'wp_incarnate_install');
register_deactivation_hook(__FILE__,'wp_incarnate_uninstall');

/**
 * Custom callback for use in wp_list_comments('callback=wp_incarnate_comment');
function wp_incarnate_comment($comment, $args, $depth) {
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
		}
		
		if ( ! $comment ) {
			global $comment; // this will be a global set from our custom wp_incarnate_comment function
			if ( ! $comment ) return false; // skip out if this isn't our call
		}
		if ( ! get_option('show_avatars') ) return false;
		if ( is_admin() ) return false; /* admin doesn't get comment objects, just id/email */

		if ( !is_numeric($size) )
			$size = '96';

		$out = wp_incarnate_get_avatar_by_commentid($comment->comment_ID);
		$avatar = "<img src='{$out}' class='avatar avatar-{$size} photo'  width='{$size}' />";
		
		return apply_filters('get_avatar', $avatar, $id_or_email, $size, $default, $alt);
	}
}

/**
 * This function can be used inside of wp_comment_list on really weird themes that override
 * the comment functionality.
 */
function wp_incarnate_insert_avatar($comment) {
	if ( ! $comment ) {
		global $comment; // this will be a global set from our custom wp_incarnate_comment function
		if ( ! $comment ) {
			echo("We're sorry but the call you added to 'wp_incarnate_insert_avatar' can't understand which comment you were trying to display.");
			return false; // skip out if this isn't our call
		}
	}

	$size = '32';
	$out = wp_incarnate_get_avatar_by_commentid($comment->comment_ID);
	$avatar = "<img src='{$out}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
	
	echo (apply_filters('get_avatar', $avatar, $id_or_email, $size, $default, $alt));
	
	return;
}

/**
 * Called when the plug-in is activated from the Plugin admin page
 */
function wp_incarnate_install() {
	// @todo Build database table to associate users/avatars
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
    update_option('wp_incarnate_version', WP_INCARNATE_VERSION);
	add_option('wp_incarnate_endpoint', 'http://incarnate.visitmix.com/incarnate.svc');
	add_option('wp_incarnate_autoinsert', true);
	
    add_option('wp_incarnate_uninstall_flag', '');
}

/**
 * Called when the plug-in is deactivated from the Plugin admin page
 */
function wp_incarnate_uninstall() {
	if(get_option('wp_incarnate_uninstall_flag') !== FALSE) {
		delete_option('wp_incarnate_uninstall_flag');
		delete_option('wp_incarnate_endpoint');
		delete_option('wp_incarnate_autoinsert');
	}
}

/**
 * Plug-in action links will add the "Settings" link on the Manage Plug-ins page
 */
function wp_incarnate_actionlinks($links, $file) {
	static $this_plugin;
	if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);

	if ($file == $this_plugin){
		array_unshift( $links, '<a href="options-general.php?page=wp-incarnate-options">Settings</a>' );
	}
	return $links;
}
 
/**
 * Calls the add_options_page function from the admin_menu action
 */
function wp_incarnate_options() {
	if( function_exists( 'is_site_admin' ) ) {
		if( is_site_admin() ) {
			add_options_page('Incarnate Options', 'Incarnate', 'manage_options', 'wp-incarnate-options', 'wp_incarnate_options_html');
			add_submenu_page('wp-incarnate.php', 'Incarnate', 'Incarnate', 'manage_options', 'wp-incarnate-options', 'wp_incarnate_options_html');
		}
	} else {
		add_options_page('Incarnate Options', 'Incarnate', 'manage_options', 'wp-incarnate-options', 'wp_incarnate_options_html');
	}
}

/**
 * Displays the HTML for the admin options page
 */
function wp_incarnate_options_html() {
	if (isset($_POST['submit'])) {
		update_option('wp_incarnate_endpoint', $_POST['incarnate_endpoint']);
		update_option('wp_incarnate_autoinsert', ($_POST['incarnate_autoinsert'] == "on"));
	}
	?>
	<div class='wrap'>
		<div id='icon-tools' class='icon32'><br /></div>
		<h2>Incarnate Options</h2>

		<form name="incarnateform" method="post" action="<?php echo $_SERVER['REDIRECT_SCRIPT_URI'] ?>?page=wp-incarnate-options&updated=true">
		<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="incarnate_endpoint">Webservice URL:</label></th>
			<td>
				<input name="incarnate_endpoint" id="incarnate_endpoint" size="64" value="<?php echo get_option('wp_incarnate_endpoint'); ?>" /><br />
				<span class="description">You can host your own Incarnate webservice on Azure or use a publicly available one.</span>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="incarnate_autoinsert">Automatically Add To Comments:</label></th>
			<td>
				<input type="checkbox" name="incarnate_autoinsert" id="incarnate_autoinsert" <?php echo (get_option('wp_incarnate_autoinsert')) ? "checked" : ""; ?> />
				<span class="description">Use JavaScript for zero-config installation.  Power Users will want to do read on below.</span>
			</td>
		</tr>
		</table>
		</form>
				
		<div class="submit">
			<input type="submit" name="submit" value="Update Options" />
		</div>
				
		<div class="themehelp">
			<h3>Power Users - Manual Installation</h3>
			The Incarnate plug-in has two parts.  First is the comment form.  This will allow a commentor to 
			select an avatar.  The second part is the avatar display.
			
			<h4>Comment Form</h4>
			<p>Normally "Automatically Add To Comments" will add the form using JavaScript.  Some themes will 
			make this impossible.  For these you will need to add the comment form manually.</p>
			
			<p>First, uncheck the box for "Automatically Add To Comments" and save the changes.  Next: add this code to your comment template right before the "Author" input field.</p>
			
			<p>
			<code>
			<?php
echo <<<CODEEXAMPLE
&lt;?php if(function_exists('wp_incarnate_insert_ui')) { wp_incarnate_insert_ui(); } ?&gt;
CODEEXAMPLE
			?>
			</code>
			</p>
			
			<h4>Avatar Display</h4>
			<p>Normally a theme will call "get_avatar" to display the avatar next to each comment.  If that's not
			the case then you need to add the code below inside of each comment.</p>
			
			<p>
			<code>
			<?php
echo <<<CODEEXAMPLE
&lt;?php if(function_exists('wp_incarnate_insert_avatar')) { wp_incarnate_insert_avatar(\$comment); } ?&gt;
CODEEXAMPLE
			?>
			</code>
			</p>

		</div> <!-- /themehelp -->
	</div> <!-- /wrap -->
	<?php
}

/**
 * This is the handler for the action:comment_form API hooks, it will add a form
 * field and any HTML/JS we need to activate the Incarnate UI
 */
function add_form_field($id) {
	// only if the user has set the auto option
	if(get_option("wp_incarnate_autoinsert") !== FALSE) {
		wp_incarnate_render_ui();
	}
}
	
/**
 * This function will handle all the comment data that was added by the incarnate
 * UI from add_form_field.  If it fails the validity check it will javascript redirect
 * back to the comment with the fields populated.
 */
function check_comment_post($id) {
	$avatar_url = $_POST['IncarnateImgSrc'];
	$comment_valid = (wp_incarnate_add_avatar($id, $avatar_url) !== FALSE);
	
	// we've got a good comment so we'll keep it and return the $id
	if($comment_valid) return $id; 
	
	// the comment was not valid so we fall through to delete the comment and javascript redirect...
	wp_set_comment_status($id, 'delete');
	?>
	<html>
	    <head><title>Invalid Code</title></head>
		<body>
			<form name="data" action="<?php echo $_SERVER['HTTP_REFERER']; ?>#respond" method="post">
				<input type="hidden" name="wp_incarnate_err" value="1" />
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
function wp_incarnate_add_scriptlet() {
	?>
	<script type="text/javascript">
	//<!CDATA[
		// used to find the service and ajax images in the javascript
		function getIncarnateImageRoot() { return "<?php echo(WP_PLUGIN_URL); ?>/wp-incarnate/images/"; }
		function getIncarnateWebservice() { return "<?php echo(get_option('wp_incarnate_endpoint')); ?>"; }
		function getIncarnateLoggedIn() { return <?php echo(is_user_logged_in() ? "true" : "false"); ?>; }
		function getIncarnateDefaultImage() { 
			return "<?php echo(wp_incarnate_get_default_avatar()); ?>"; 
		}
		function getIncarnateCurrentUserImage() {
			return "<?php echo(wp_incarnate_get_currentuser_avatar()); ?>"; 
		}
		
		<?php if(isset($_POST['wp_incarnate_err'])) : ?>
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
function wp_incarnate_add_scripts() {
   // Queues up the jQuery library
   wp_enqueue_script('incarnate', WP_PLUGIN_URL . '/wp-incarnate/incarnate.js', array('jquery'));
}

/**
 * This is a simple function that can be called from the template to include the Incarnate UI
 */
function wp_incarnate_insert_ui() {
	wp_incarnate_render_ui(false);
} 

/**
 * This function will render the manual or the automatic incarnate UI
 */
function wp_incarnate_render_ui($auto = true) {
	$avatar_img = ( is_user_logged_in() ) ? wp_incarnate_get_currentuser_avatar() : wp_incarnate_get_default_avatar();
	
	?>
	<script type="text/javascript">
		var html = '<input type="hidden" name="IncarnateImgSrc" id="IncarnateImgSrc" value="<?php echo($avatar_img) ; ?>" />';
		html += '<div style="float:left; margin:5px;">'
		html += '<img id="IncarnateImg" class="avatar avatar-64 photo" width="64" src="<?php echo($avatar_img); ?>" />';
		html += '</div>\n';
		html += '<p>';

		<?php if(isset($_POST['wp_incarnate_err'])) : ?>
		html += "Sorry, there was an error while submitting your Incarnated avatar.  Please try again.<br />";
		<?php endif; ?>
		
		<?php if ( is_user_logged_in() ) : ?>
		html += '<input style="visibility: hidden;" id="IncarnateUserName" disabled="disabled" type="text" /><img style="display: none;" id="IncarnateLoader" src="<?php echo(WP_PLUGIN_URL); ?>/wp-incarnate/images/loader.gif" />';
		html += '<input id="IncarnateActivate" style="visibility: hidden;" type="button" value="Find" />';
		html += '<label><br />';
		html += '<small>Your Gravatar</small></label>';
		<?php else : ?>
		html += '<input id="IncarnateUserName" type="text" />';
		html += '<input id="IncarnateActivate" style="width: 35px;" type="button" value="Find" />';
		html += '<img style="display: none;" id="IncarnateLoader" src="<?php echo(WP_PLUGIN_URL); ?>/wp-incarnate/images/loader.gif" />';
		html += '<label><br />';
		html += '<small>Enter your name, handle, alias, or email.<br />';
		html += 'We\'ll find you from the services below.<br /><img src="<?php echo(WP_PLUGIN_URL); ?>/wp-incarnate/images/Twitter.png" /><img src="<?php echo(WP_PLUGIN_URL); ?>/wp-incarnate/images/Facebook.png" /><img src="<?php echo(WP_PLUGIN_URL) ?>/wp-incarnate/images/MySpace.png" /><img src="<?php echo(WP_PLUGIN_URL); ?>/wp-incarnate/images/YouTube.png" /><img src="<?php echo(WP_PLUGIN_URL); ?>/wp-incarnate/images/Gravatar.png" /><img src="<?php echo(WP_PLUGIN_URL); ?>/wp-incarnate/images/XBoxLive.png" /></small></label>';
		html += '</p>';
		html += '<div id="IncarnateResultsContainer" style="display: none;background:#fff;position:absolute;border:1px solid #999;width:100px"></div>';
		<?php endif; ?>
		
		<?php if($auto) : ?>
		jQuery("form[@action*=wp-comments-post] p:first-child").parent().prepend("<p>" + html + "</p>");
		<?php else : ?>
		document.write(html);
		<?php endif; ?>
	</script>
	<?php
}

/**
 * This function will insert a record into the database table for the avatar.
 */
function wp_incarnate_add_avatar($commentid, $avatarurl) {
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
function wp_incarnate_get_avatar_by_commentid($commentid) {
	global $wpdb;
	
	if(!is_numeric($commentid)) return null;

	$table_name = $wpdb->prefix . "incarnate_avatars";
	$insert = "SELECT avatar FROM " . $table_name . " WHERE commentid=" . $commentid;
	$avatarurl = $wpdb->get_var( $insert );
	
	if(!$avatarurl) return wp_incarnate_get_default_avatar($commentid);
	
	return $avatarurl;
}

/**
 * @todo add function for getting default avatar
 */
function wp_incarnate_get_default_avatar() {
	$email = "";
	$size = 64;

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
	
	return $default;
}

/**
 * @todo add function for getting default avatar
 */
function wp_incarnate_get_currentuser_avatar() {
	$email = "";
	$size = 64;

	global $user_login , $user_email;
	get_currentuserinfo();

	if ($user_email) {  $email = $user_email; } else return wp_incarnate_get_default_avatar();

	if ( is_ssl() ) { $host = 'https://secure.gravatar.com'; } else { $host = 'http://www.gravatar.com'; }

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