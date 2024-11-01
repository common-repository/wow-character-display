<?php
/**
 * Bootstrap file for getting the ABSPATH constant to wp-load.php
 * This is requried when a plugin requires access not via the admin screen.
 *
 * If the wp-load.php file is not found, then an error will be displayed
 *
 * @package WordPress
 * @since Version 2.6
 */
 
/** Define the server path to the file wp-config here, if you placed WP-CONTENT outside the classic file structure */

$path  = ''; // It should be end with a trailing slash    

/** That's all, stop editing from here **/
if ( !defined('WP_LOAD_PATH') ) {

	/** classic root path if wp-content and plugins is below wp-config.php */
	$classic_root = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/' ;
	
	if (file_exists( $classic_root . 'wp-load.php') )
		define( 'WP_LOAD_PATH', $classic_root);
	else
		if (file_exists( $path . 'wp-load.php') )
			define( 'WP_LOAD_PATH', $path);
		else
			exit("Could not find wp-load.php");
}

// let's load WordPress
require_once( WP_LOAD_PATH . 'wp-load.php');

if( function_exists( 'load_plugin_textdomain' ) ) {
	load_plugin_textdomain( 'wowcd', 'wp-content/plugins/wow-character-display' );
}

// check for rights
if ( !is_user_logged_in() || !current_user_can('edit_posts') ) 
	wp_die(__("You are not allowed to be here"));

global $wpdb;

?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>WoW Character Display Plugin</title>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/utils/mctabs.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo get_option('siteurl') ?>/wp-includes/js/tinymce/utils/form_utils.js"></script>
	<script language="javascript" type="text/javascript" src="<?php echo WP_PLUGIN_URL ?>/wow-character-display/tinymce/tinymce.js"></script>
	<base target="_self" />
</head>
<body id="link" onload="tinyMCEPopup.executeOnLoad('init();');document.body.style.display='';document.getElementById('charactertag').focus();" style="display: none">
<!-- <form onsubmit="insertLink();return false;" action="#"> -->
	<form name="WoWCD" action="#">
	<div class="tabs">
		<ul>
			<li id="character_tab" class="current"><span><a href="javascript:mcTabs.displayTab('character_tab','character_panel');" onmousedown="return false;"><?php echo __("Character", "wowcd"); ?></a></span></li>
			<li id="guild_tab"><span><a href="javascript:mcTabs.displayTab('guild_tab','guild_panel');" onmousedown="return false;"><?php echo __("Guild", "wowcd"); ?></a></span></li>
		</ul>
	</div>
	
	<div class="panel_wrapper">
		<!-- character panel -->
		<div id="character_panel" class="panel current">
		<br />
	<table border="0" cellpadding="4" cellspacing="0">
         <tr>
            <td nowrap="nowrap"><label for="charactertag"><?php echo __("Select character", "wowcd"); ?></label></td>
            <td><select id="charactertag" name="charactertag" style="width: 200px">
                <option value="">--</option>
		<?php
			$wowcd_AdminOptions = get_option( 'wowcdPlugin_AdminOptions' );
			if( is_array( $wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) && count( $wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) )
			foreach( array_keys( $wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) AS $character )
				echo "<option value=\"$character\">$character</option>";
		?>
            </select></td>
          </tr>
        </table>
		</div>
		<!-- character panel -->
		
		<!-- guild panel -->
		<div id="guild_panel" class="panel">
		<br />
	<table border="0" cellpadding="4" cellspacing="0">
         <tr>
            <td nowrap="nowrap"><label for="guildtag"><?php echo __("Select guild", "wowcd"); ?></label></td>
            <td><select id="guildtag" name="guildtag" style="width: 200px">
                <option value="">--</option>
		<?php
			if( is_array( $wowcd_AdminOptions[ 'wowcdOptionGuilds' ] ) && count( $wowcd_AdminOptions[ 'wowcdOptionGuilds' ] ) )
			foreach( array_keys( $wowcd_AdminOptions[ 'wowcdOptionGuilds' ] ) AS $guild )
				echo "<option value=\"$guild\">$guild</option>";
		?>
            </select></td>
          </tr>
        </table>
		</div>
		<!-- guild panel -->
	<!-- panel wrapper -->
	</div>

	<div class="mceActionPanel">
		<div style="float: left">
			<input type="button" id="cancel" name="cancel" value="<?php echo __("Cancel", "wowcd"); ?>" onclick="tinyMCEPopup.close();" />
		</div>

		<div style="float: right">
			<input type="submit" id="insert" name="insert" value="<?php echo __("Insert", "wowcd"); ?>" onclick="insertWoWCDLink();" />
		</div>
	</div>
</form>
</body>
</html>