<?php
/*
Plugin Name: Who's Hacking What
Plugin URI: http://fullthrottledevelopment.com/whos-hacking-what
Description: A simple interface that notifies other administrators if you are hacking files on a live server.
Author: Michael Torbert
Version: 0.3
Author URI: http://semperfiwebdesign.com
*/

//Version
define('FT_WHW_Version','0.3');

/*
 * Changelog
 * 
 * 0.3 fixed post/db sanitization, fixed cap/role issue, should have fixed screenshots, updated licensing info, updated for 4.4
 * 0.2 - updated to reflect wp 2.7 urls
 * 0.1 - Initial Release
 * 
 */

// Define plugin path
if ( !defined('WP_CONTENT_DIR') ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content');
}
define('FT_WHW_Plugin_Path', WP_CONTENT_DIR.'/plugins/'.plugin_basename(__FILE__));

	$ft_whw_admin_link = get_option('siteurl')."/wp-admin/tools.php?page=whos-hacking-what.php";

// Plugin Form Security
if ( !function_exists('wp_nonce_field') ) {
    function ft_whw_nonce_field($action = -1) { return; }
    $ft_whw_nonce = -1;
} else {
	if( !function_exists( 'ft_whw_nonce_field' ) ) {
	function ft_whw_nonce_field($action = -1,$name = 'ft_whw-update-checkers') { return wp_nonce_field($action,$name); }
	define('FT_WHW_NONCE' , 'ft_whw-update-checkers');
	}
}

// Add Management Page
if ( !function_exists('ft_whw_admin_menu') ) {
	function ft_whw_admin_menu(){
		$ft_whw_settings_page = add_management_page( 'Who\'s Hacking What' , 'Who\'s Hacking What' , 'manage_options' , FT_WHW_Plugin_Path , 'ft_whw_settings_page');
	}
}

//This function prints the settings page
if ( !function_exists('ft_whw_settings_page') ){
	function ft_whw_settings_page(){
		global $ft_whw_nonce;
		ft_whw_process_form();
		?>
		<div class='wrap'>
			<h2 style='margin-bottom:10px;'>Who's Hacking What?</h2>
			<?php ft_whw_existing_hackers();?>
			<div style="width:210px;margin-left:750px;background:#e4f2fd;border:1px solid #dddddd;padding:3px 10px;">
			<h3>New Hacker:</h3>
			<form name="ft_whw" action="" method="post">
				<p>If you are currently hacking content on this server, please specifiy your name and the file(s) you are editing below.</p>
				<p>Your Name:<br /><input type="text" name="ft_whw_name" /></p>
				<p>Your Sex: He <input type="radio" name="ft_whw_sex" value="He" /> She <input type="radio" name="ft_whw_sex" value="She" /></p>
				<p>What you're hacking:<br /><input type="text" name="ft_whw_files" /></p>
				<p>When you'll be finished:<br /><input type="text" name="ft_whw_finished" /></p>
				<p>Your email:<br /><input type="text" name="ft_whw_contact" /></p>
				<p><input type="submit" class="button-primary" value="Submit" /></p>
				<input type='hidden' name='ft_whw_new_submitted' value='1' />
				<?php ft_whw_nonce_field($ft_whw_nonce, FT_WHW_NONCE); ?>
			</form>
			</div>
		</div>
		<?php
	}
}

//This function prints the list of existing hackers
if ( !function_exists('ft_whw_existing_hackers') ){
	function ft_whw_existing_hackers(){
		global $ft_whw_nonce;
		?>
		<div class='existing_hackers' style='width:700px;float:left;'>
		<h3>Existing Hackers:</h3>
		<?php
		if ( $hackers = ft_whw_get_hackers() ) {
			?>
			<form name="ft_whw" action="" method="post">
				<ol>
				<?php
				$count = (int) 0;
				foreach( $hackers as $key => $value ){
					?>
					<li><input type="checkbox" name="delete_<?php echo $key;?>"> <?php echo $value;?></li>
					<?php
					$count++;
				}
				?>
				</ol>
				<input type='hidden' name='ft_whw_delete_submitted' value='1' />
				<?php ft_whw_nonce_field($ft_whw_nonce, FT_WHW_NONCE); ?>
				<p><input type="submit" name="deleteChecked" value="Delete Checked" class="button-secondary delete" /></p>
			</form>
			<?php
		}else{
			?>
			<p>No one is currently working on this server.</p>
			<?php
		}
		?></div><?php
	}
}

// This function process the form data on submission
if ( !function_exists('ft_whw_process_form') ) {
	function ft_whw_process_form(){
		global $wpdb,$ft_whw_nonce;
		if ( isset($_POST['ft_whw_new_submitted']) ){
			check_admin_referer( $ft_whw_nonce, FT_WHW_NONCE );
			if ( isset($_POST['ft_whw_new_submitted']) ){
				( isset( $_POST['ft_whw_name']) && !empty($_POST['ft_whw_name']) && ($_POST['ft_whw_name']) != '' ) ? $new_name =  sanitize_text_field( $_POST['ft_whw_name'] ) : $new_name = 'Somebody' ;
				( isset( $_POST['ft_whw_files']) && !empty($_POST['ft_whw_files']) && ($_POST['ft_whw_files']) != '' ) ? $new_files =  sanitize_text_field( $_POST['ft_whw_files'] ) : $new_files = 'files' ;
				( isset( $_POST['ft_whw_sex']) && !empty($_POST['ft_whw_sex']) && ($_POST['ft_whw_sex']) != '' ) ? $new_sex =  sanitize_text_field( $_POST['ft_whw_sex'] ) : $new_sex = 'They' ;
				( isset( $_POST['ft_whw_finished']) && !empty($_POST['ft_whw_finished']) && ($_POST['ft_whw_finished']) != '' ) ? $new_finished = "by ".  sanitize_text_field( $_POST['ft_whw_finished'] ) : $new_finished = 'eventually' ;
				( isset( $_POST['ft_whw_contact']) && !empty($_POST['ft_whw_contact']) && ($_POST['ft_whw_contact']) != '' ) ? $new_contact =  sanitize_email( $_POST['ft_whw_contact'] ) : $new_contact = 'nada' ;
				$insert = $new_name." is currently working on ".$new_files." and will be finished ".$new_finished.". ".$new_sex." left the following email address: ".$new_contact;
				
				ft_whw_add_hacker( $insert );
			}
		}elseif( isset($_POST['ft_whw_delete_submitted']) ){
			check_admin_referer( $ft_whw_nonce, FT_WHW_NONCE );
			foreach($_POST as $key => $value ){
				if ( substr($key , 0 , 7) == 'delete_' ){
					$hacker_id = substr($key,7);

					ft_whw_delete_hacker( $hacker_id );
				}
			}
		}
	}
}

// This function pulls down the current hackers list
if ( !function_exists('ft_whw_get_hackers') ){
	function ft_whw_get_hackers(){
		if ( $hackers = get_option('ft_whw_hackers') ){
			return $hackers;
		}
	}
}

// This function adds a hacker to the current hackers list
if ( !function_exists('ft_whw_add_hacker') ){
	function ft_whw_add_hacker( $hacker ){
		if ( $hackers = ft_whw_get_hackers() ){
			$hackers[uniqid(23)] = $hacker;
		}else{
			$hackers[uniqid(23)] = $hacker;
		}
		
		update_option( 'ft_whw_hackers' , $hackers );
	}
}

// This function deletes a hacker from the current hackers list
if ( !function_exists('ft_whw_delete_hacker') ){
	function ft_whw_delete_hacker( $hacker_id ){


		if ( $hackers = ft_whw_get_hackers() ){
			unset( $hackers[$hacker_id ] );
		}
		
		update_option( 'ft_whw_hackers' , $hackers );
	
	}
}

// This function gives me the combined strlen of current hackers
if ( !function_exists('ft_whw_hackers_strlen') ){
	function ft_whw_hackers_strlen(){

		$string = 0;
		
		if ( $hackers = ft_whw_get_hackers() ){
			foreach ( $hackers as $key => $value ){
				$string .= $value;
			}
		}
		
		return strlen($string);
	
	}
}

// This function loads my notice if the logged in user is an administrator
if ( !function_exists('ft_whw_notifier') ){
	function ft_whw_notifier(){
		global $ft_whw_admin_link;
		if ( current_user_can('manage_options') && $hackers = get_option('ft_whw_hackers') ){
			$hackers_changed = '#224466';
			if ( !isset($_COOKIE['ft_whw_hackers_changed']) || $_COOKIE['ft_whw_hackers_changed'] != ft_whw_hackers_strlen() ){ $hackers_changed = 'yellow'; }
			?>
			<script type="text/javascript" >
				var ft_whw_value = '<?php echo ft_whw_hackers_strlen();?>';
				//alert(ft_whw_value);
				if ( ft_get_cookie( 'ft_whw_hackers_changed') != ft_whw_value ){
					ft_set_cookie('ft_whw_hackers_changed',ft_whw_value);
				}

				function ft_set_cookie( id, value) {
					document.cookie = id+'='+value+';path=/;expires='+ft_cookieTime(365);
				}
				
				function ft_get_cookie( id, defaultValue ) {
					var re = new RegExp(id+'=(.*)');
					var value = re.exec(document.cookie);
					return (value) ? value[1].split(';')[0] : defaultValue;
				}
				
				function ft_cookieTime(days){
					var now = new Date();
					var exp = new Date();
					var x = Date.parse(now) + days*24*60*60*1000;
					exp.setTime(x);
					str = exp.toUTCString();
					re = '/(\d\d)\s(\w\w\w)\s\d\d(\d\d))/';
					return str.replace(re,"$1-$2-$3");
				}

				function ft_findPos(obj) {
				    var curleft = curtop = 0;
				    
				    if (obj.offsetParent)
				    {
				        do
				        {
				            curleft += obj.offsetLeft;
				            curtop += obj.offsetTop;
				        }
				        while (obj = obj.offsetParent);
				    }
				    return [curleft, curtop];
				}
			</script>
			<?php if ( isset( $_COOKIE['ft_whw_pos']) ) { $ft_whw_pos = explode(',',$_COOKIE['ft_whw_pos']); }else{ $ft_whw_pos = ''; }?>
			<div id="whos_hacking_what" style="font-size:12px;font-weigh:normal;position:absolute;left:<?php echo $ft_whw_pos[0];?>px;top:<?php echo $ft_whw_pos[1];?>px;text-align:left;padding:10px;width:400px;background:white;border:2px solid <?php echo $hackers_changed;?>;z-index:1000;">
				<div id="ft_whw_move" onMouseOver="document.body.style.cursor = 'move';" onMouseOut="document.body.style.cursor = 'default';" onmouseup="ft_set_cookie('ft_whw_pos',ft_findPos(getElementById('whos_hacking_what')));" style="width:100%;color:#c74f33;border-bottom:1px solid black;padding-bottom:5px;margin-bottom:5px;"><strong>Who's Hacking What</strong></div>
				<p style="margin-bottom:5px;"><strong>The following people are currently working on this site.</strong><br /><a href="<?php echo $ft_whw_admin_link ?>">Edit List</a></p>
				<?php
				$row = 0;
				foreach ( $hackers as $key => $value ){
					?>
					<p style="background:<?php if ($row === 0){echo 'fff';}else{echo '#eee';};?>">
						<?php echo "&#149; ".$value;?>
					</p>
					<?php
					$row = 1 - $row;
				}
				?>
			<script type="text/javascript">
				new Draggable('whos_hacking_what', { scroll: window , handle: 'ft_whw_move' } );
			</script>
			</div>

			<?php
		}
	}
}

if ( !function_exists('ft_whw_front_scripts') ) {
	function ft_whw_front_scripts(){
		wp_enqueue_script('scriptaculous-root');
		wp_enqueue_script('scriptaculous-dragdrop');
	}
}

// Load Admin Page
add_action('admin_menu','ft_whw_admin_menu');
add_action('wp_print_scripts','ft_whw_front_scripts');
add_action('wp_head','ft_whw_notifier');