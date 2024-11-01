<?php
/*
Plugin Name: WoW-Character-Display
Plugin URI: http://www.das-motorrad-blog.de/meine-wordpress-plugins/
Version: 1.12.1
Author: Marc Schieferdecker
Author URI: http://www.das-motorrad-blog.de
Description: The WoW-Character-Display plugin shows many informations from the WoW armory in a widget or post. High configurable for single characters and guilds!
License: GPL
*/

// Define plugin path
define( 'WOWCD_PLUGIN_PATH', ABSPATH . 'wp-content/plugins/wow-character-display' );
// Define option name
define( 'WOWCD_PLUGIN_OPTION_NAME', 'wowcdPlugin_AdminOptions' );

// Include WoW Armory Reader Class
// Example usage:
// $armory_reader = new wow_armory_reader( $cache_dir, $cache_timeout );
// $armory_reader = get_character_information( 'eu', 'de', 'Blackmoore', 'Raufaser' );
// print $armory_reader -> get_content();
require( WOWCD_PLUGIN_PATH . '/wow_armory_reader.class.php' );


/**
 * Admin hooks if on admin page
 */
if( strpos( $_SERVER[ 'REQUEST_URI' ], 'wp-admin' ) !== false )
{
	// Include Admin Class
	require( WOWCD_PLUGIN_PATH . '/wowcd_admin.class.php' );
	$wowcd_Admin = new wowcd_Admin();
	// Admin wrapper
	function wrapper_wowcd_AdminPage()
	{
		global $wowcd_Admin;
		add_options_page( 'WoW Character Display', 'WoW Character Display', 9, basename(__FILE__), array( &$wowcd_Admin, 'wowcd_AdminPage' ) );
	}
	add_action( 'admin_menu', 'wrapper_wowcd_AdminPage' );

	// Add wowhead js to admin head if on plugin admin page
	if( strpos( $_SERVER[ 'REQUEST_URI' ], 'wow-character-display' ) !== false )
	{
		function add_wowhead_js()
		{
			$siteurl = get_option( 'siteurl' );
			print "\t" . '<script type="text/javascript" src="http://www.wowhead.com/widgets/power.js"></script>' . "\n";
			print "\t" . '<link rel="stylesheet" type="text/css" media="all" href="' . $siteurl . '/wp-content/plugins/wow-character-display/wowcd_admin.css"/>' . "\n";
		}
		add_action( 'admin_head', 'add_wowhead_js' );
	}

	/**
	 * Add tinyMCE button
	 */
	require( WOWCD_PLUGIN_PATH . '/tinymce/tinymce.php' );
	$tinymce_button_wowcd = new add_wowcd_button();
}


/**
 * Shortcode hook for posts
 */
function wowcd_shortcode( $atts )
{
	extract( shortcode_atts( array( 'character' => '', 'guild' => '' ), $atts ) );
	if( !empty( $character ) )
	{
		$wowcd_AdminOptions = get_option( WOWCD_PLUGIN_OPTION_NAME );
		// Create armory reader
		$armory_reader = new wow_armory_reader( $wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ], $wowcd_AdminOptions[ 'wowcdOptionCachePath' ], $wowcd_AdminOptions[ 'wowcdOptionCacheTime' ] );
		$armory_reader -> set_prof_image_locale( 'Jewelcrafting', $wowcd_AdminOptions[ 'wowcdOptionLocaleJewelcrafting' ] );
		$armory_reader -> set_prof_image_locale( 'Inscription', $wowcd_AdminOptions[ 'wowcdOptionLocaleInscription' ] );
		$armory_reader -> set_prof_image_locale( 'Leatherworking', $wowcd_AdminOptions[ 'wowcdOptionLocaleLeatherworking' ] );
		$armory_reader -> set_prof_image_locale( 'Skinning', $wowcd_AdminOptions[ 'wowcdOptionLocaleSkinning' ] );
		$armory_reader -> set_prof_image_locale( 'Herbalism', $wowcd_AdminOptions[ 'wowcdOptionLocaleHerbalism' ] );
		$armory_reader -> set_prof_image_locale( 'Alchemy', $wowcd_AdminOptions[ 'wowcdOptionLocaleAlchemy' ] );
		$armory_reader -> set_prof_image_locale( 'Blacksmithing', $wowcd_AdminOptions[ 'wowcdOptionLocaleBlacksmithing' ] );
		$armory_reader -> set_prof_image_locale( 'Engineering', $wowcd_AdminOptions[ 'wowcdOptionLocaleEngineering' ] );
		$armory_reader -> set_prof_image_locale( 'Enchanting', $wowcd_AdminOptions[ 'wowcdOptionLocaleEnchanting' ] );
		$armory_reader -> set_prof_image_locale( 'Mining', $wowcd_AdminOptions[ 'wowcdOptionLocaleMining' ] );
		$armory_reader -> set_prof_image_locale( 'Tailoring', $wowcd_AdminOptions[ 'wowcdOptionLocaleTailoring' ] );
		// Replace shortcode if character found
		foreach( $wowcd_AdminOptions[ 'wowcdOptionCharacters' ] AS $char => $options )
		{
			if( strtolower( $char ) == strtolower( $character ) )
			{
				if( $armory_reader -> get_character_information( $options -> rt, $options -> wl, $options -> server, $options -> character, $options -> display[ 'display_basic_stats' ], $options -> display[ 'display_resistances' ], $options -> display[ 'display_melee' ], $options -> display[ 'display_range' ], $options -> display[ 'display_spell' ], $options -> display[ 'display_caster_stats' ], $options -> display[ 'display_defense' ], $options -> display[ 'display_pvp' ], $options -> display[ 'display_titles' ], $options -> display[ 'display_gear' ], $options -> display[ 'display_professions' ], $options -> display[ 'display_achievments' ], $options -> display[ 'display_statistics' ], $options -> display[ 'display_reputation' ], $options -> display[ 'display_last_update_string' ], $options -> display[ 'display_glyphs' ], $options -> display[ 'display_3darmory' ], $options -> display[ 'display_3darmory_width' ], $options -> display[ 'display_3darmory_height' ] ) )
					return $armory_reader -> get_content();
				else
					return "<!-- " . __( "Error:", "wowcd" ) . ' ' . $armory_reader -> error . " -->";
			}
		}
		return "Character $character not configured.";
	}
	else
	if( !empty( $guild ) )
	{
		$wowcd_AdminOptions = get_option( WOWCD_PLUGIN_OPTION_NAME );
		// Create armory reader
		$armory_reader = new wow_armory_reader( $wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ], $wowcd_AdminOptions[ 'wowcdOptionCachePath' ], $wowcd_AdminOptions[ 'wowcdOptionCacheTime' ] );
		$armory_reader -> set_prof_image_locale( 'Jewelcrafting', $wowcd_AdminOptions[ 'wowcdOptionLocaleJewelcrafting' ] );
		$armory_reader -> set_prof_image_locale( 'Inscription', $wowcd_AdminOptions[ 'wowcdOptionLocaleInscription' ] );
		$armory_reader -> set_prof_image_locale( 'Leatherworking', $wowcd_AdminOptions[ 'wowcdOptionLocaleLeatherworking' ] );
		$armory_reader -> set_prof_image_locale( 'Skinning', $wowcd_AdminOptions[ 'wowcdOptionLocaleSkinning' ] );
		$armory_reader -> set_prof_image_locale( 'Herbalism', $wowcd_AdminOptions[ 'wowcdOptionLocaleHerbalism' ] );
		$armory_reader -> set_prof_image_locale( 'Alchemy', $wowcd_AdminOptions[ 'wowcdOptionLocaleAlchemy' ] );
		$armory_reader -> set_prof_image_locale( 'Blacksmithing', $wowcd_AdminOptions[ 'wowcdOptionLocaleBlacksmithing' ] );
		$armory_reader -> set_prof_image_locale( 'Engineering', $wowcd_AdminOptions[ 'wowcdOptionLocaleEngineering' ] );
		$armory_reader -> set_prof_image_locale( 'Enchanting', $wowcd_AdminOptions[ 'wowcdOptionLocaleEnchanting' ] );
		$armory_reader -> set_prof_image_locale( 'Mining', $wowcd_AdminOptions[ 'wowcdOptionLocaleMining' ] );
		$armory_reader -> set_prof_image_locale( 'Tailoring', $wowcd_AdminOptions[ 'wowcdOptionLocaleTailoring' ] );
		// Replace shortcode if character found
		foreach( $wowcd_AdminOptions[ 'wowcdOptionGuilds' ] AS $gld => $options )
		{
			if( strtolower( $gld ) == strtolower( $guild ) )
			{
				if( $armory_reader -> get_guild_information( $options -> rt, $options -> wl, $options -> server, $options -> guild, $options -> display[ 'from_level' ], $options -> display[ 'to_level' ], $options -> display[ 'display_member_count' ], $options -> display[ 'gfx_width' ] ) )
					return $armory_reader -> get_content();
				else
					return "<!-- " . __( "Error:", "wowcd" ) . ' ' . $armory_reader -> error . " -->";
			}
		}
		return "Guild $guild not configured.";
	}
	return 'No character or guild set.';
}
add_shortcode( 'wowcd', 'wowcd_shortcode' );


/**
 * Page hooks (css, wowhead.js)
 */
function add_wowhead_js_to_page()
{
	$siteurl = get_option( 'siteurl' );
	print "\t" . '<script type="text/javascript" src="http://www.wowhead.com/widgets/power.js"></script>' . "\n";
	print "\t" . '<link rel="stylesheet" type="text/css" media="all" href="' . $siteurl . '/wp-content/plugins/wow-character-display/wowcd_blog.css"/>' . "\n";
}
$wowcd_AdminOptions = get_option( WOWCD_PLUGIN_OPTION_NAME );
if( $wowcd_AdminOptions[ 'wowcdOptionIncludeCSS' ] == 'true' )
	add_action( 'wp_head', 'add_wowhead_js_to_page' );


/**
 * WoWCD MultiWidget
 */
function wowcdwidget_multi_register()
{
	$prefix = 'wowcdwidget-multi';
	$name = 'WoW Character Display';
	$widget_ops = array( 'classname' => 'wowcdwidget_multi', 'description' => __( "Display one or more Word of Warcraft characters in your sidebar.", "wowcd" ) );
	$control_ops = array('width' => 200, 'height' => 200, 'id_base' => $prefix );

	$options = get_option( 'wowcdwidget_multi' );
	if( isset( $options[ 0 ] ) ) unset( $options[0] );
	if( !empty( $options ) )
	{
		foreach( array_keys( $options ) AS $widget_number )
		{
			wp_register_sidebar_widget( $prefix . '-' . $widget_number, $name, 'wowcdwidget_multi', $widget_ops, array( 'number' => $widget_number ) );
			wp_register_widget_control( $prefix . '-' . $widget_number, $name, 'wowcdwidget_multi_control', $control_ops, array( 'number' => $widget_number ) );
		}
	}
	else
	{
		$options = array();
		$widget_number = 1;
		wp_register_sidebar_widget( $prefix . '-' . $widget_number, $name, 'wowcdwidget_multi', $widget_ops, array( 'number' => $widget_number ) );
		wp_register_widget_control( $prefix . '-' . $widget_number, $name, 'wowcdwidget_multi_control', $control_ops, array( 'number' => $widget_number ) );
	}
}
function wowcdwidget_multi( $args )
{
	$prefix = 'wowcdwidget-multi';
	$widget_number = 0;
	extract( $args );
	print $before_widget; print $before_title;
	// Get multip widget options
	$opts = get_option( 'wowcdwidget_multi' );
	if( preg_match( '/' . $id_prefix . '-([0-9]+)/i', $widget_id, $match ) )
		$widget_number = $match[ 1 ];
	if( !empty( $widget_number ) )
	{
		$opts = $opts[ $widget_number ];
		print $opts[ 'title' ];
		print $after_title;
		$wowcd_AdminOptions = get_option( WOWCD_PLUGIN_OPTION_NAME );
		// Create armory reader
		$armory_reader = new wow_armory_reader( $wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ], $wowcd_AdminOptions[ 'wowcdOptionCachePath' ], $wowcd_AdminOptions[ 'wowcdOptionCacheTime' ] );
		$armory_reader -> set_prof_image_locale( 'Jewelcrafting', $wowcd_AdminOptions[ 'wowcdOptionLocaleJewelcrafting' ] );
		$armory_reader -> set_prof_image_locale( 'Inscription', $wowcd_AdminOptions[ 'wowcdOptionLocaleInscription' ] );
		$armory_reader -> set_prof_image_locale( 'Leatherworking', $wowcd_AdminOptions[ 'wowcdOptionLocaleLeatherworking' ] );
		$armory_reader -> set_prof_image_locale( 'Skinning', $wowcd_AdminOptions[ 'wowcdOptionLocaleSkinning' ] );
		$armory_reader -> set_prof_image_locale( 'Herbalism', $wowcd_AdminOptions[ 'wowcdOptionLocaleHerbalism' ] );
		$armory_reader -> set_prof_image_locale( 'Alchemy', $wowcd_AdminOptions[ 'wowcdOptionLocaleAlchemy' ] );
		$armory_reader -> set_prof_image_locale( 'Blacksmithing', $wowcd_AdminOptions[ 'wowcdOptionLocaleBlacksmithing' ] );
		$armory_reader -> set_prof_image_locale( 'Engineering', $wowcd_AdminOptions[ 'wowcdOptionLocaleEngineering' ] );
		$armory_reader -> set_prof_image_locale( 'Enchanting', $wowcd_AdminOptions[ 'wowcdOptionLocaleEnchanting' ] );
		$armory_reader -> set_prof_image_locale( 'Mining', $wowcd_AdminOptions[ 'wowcdOptionLocaleMining' ] );
		$armory_reader -> set_prof_image_locale( 'Tailoring', $wowcd_AdminOptions[ 'wowcdOptionLocaleTailoring' ] );
		// Display character widget
		if( !empty( $opts[ 'character' ] ) )
		{
			foreach( $wowcd_AdminOptions[ 'wowcdOptionCharacters' ] AS $char => $options )
			{
				if( strtolower( $char ) == strtolower( $opts[ 'character' ] ) )
				{
					if( $armory_reader -> get_character_information( $options -> rt, $options -> wl, $options -> server, $options -> character, $opts[ 'display_basic_stats' ], $opts[ 'display_resistances' ], $opts[ 'display_melee' ], $opts[ 'display_range' ], $opts[ 'display_spell' ], $opts[ 'display_caster_stats' ], $opts[ 'display_defense' ], $opts[ 'display_pvp' ], $opts[ 'display_titles' ], $opts[ 'display_gear' ], $opts[ 'display_professions' ], $opts[ 'display_achievments' ], $opts[ 'display_statistics' ], $opts[ 'display_reputation' ], $opts[ 'display_last_update_string' ], $opts[ 'display_glyphs' ], $opts[ 'display_3darmory' ], $opts[ 'display_3darmory_width' ], $opts[ 'display_3darmory_height' ] ) )
						print $armory_reader -> get_content();
					else
						print "<!-- " . __( "Error:", "wowcd" ) . ' ' . $armory_reader -> error . " -->";
				}
			}
		}
		else
		// Display guild roster
		if( !empty( $opts[ 'guild' ] ) )
		{
			foreach( $wowcd_AdminOptions[ 'wowcdOptionGuilds' ] AS $gld => $options )
			{
				if( strtolower( $gld ) == strtolower( $opts[ 'guild' ] ) )
				{
					if( $armory_reader -> get_guild_information( $options -> rt, $options -> wl, $options -> server, $options -> guild, $opts[ 'from_level' ], $opts[ 'to_level' ], $opts[ 'display_member_count' ], $opts[ 'gfx_width' ] ) )
						print $armory_reader -> get_content();
					else
						print "<!-- " . __( "Error:", "wowcd" ) . ' ' . $armory_reader -> error . " -->";
				}
			}
		}
	}
	// Output
	print $after_widget;
}
function wowcdwidget_multi_control( $args )
{
	if( function_exists( 'load_plugin_textdomain' ) ) {
		load_plugin_textdomain( 'wowcd', false, 'wow-character-display' );
	}

	$prefix = 'wowcdwidget-multi';

	$options = get_option( 'wowcdwidget_multi' );
	if( empty( $options ) ) $options = array();
	if( isset( $options[ 0 ] ) ) unset( $options[ 0 ] );

	// Update options array
	if( !empty( $_POST[ $prefix ] ) && is_array( $_POST ) )
	{
		foreach( $_POST[ $prefix ] AS $widget_number => $values )
		{
			if( empty( $values ) && isset( $options[ $widget_number ] ) ) // user clicked cancel
				continue;

			if( !isset( $options[ $widget_number ] ) && $args[ 'number' ] == -1 )
			{
				$args[ 'number' ] = $widget_number;
				$options[ 'last_number' ] = $widget_number;
			}
			$options[ $widget_number ] = $values;
		}

		// Update number
		if( $args['number'] == -1 && !empty( $options[ 'last_number' ] ) )
		{
			$args[ 'number' ] = $options[ 'last_number' ];
		}

		// Clear unused options and update options in DB. return actual options array
		$options = wowcd_smart_multiwidget_update( $prefix, $options, $_POST[ $prefix ], $_POST[ 'sidebar' ], 'wowcdwidget_multi' );
	}
	$number = ( $args[ 'number' ] == -1 ) ? '%i%' : $args[ 'number' ];
	$opts = @$options[$number];
	$title = @$opts['title'];
	$character = @$opts['character'];
	$guild = @$opts['guild'];
	print "<strong>" . __( "Title:", "wowcd" ) . "</strong><br/>";
 	print "<input type=\"text\" name=\"$prefix"."[$number][title]\" value=\"$title\"/><br/><br>";
	$wowcd_AdminOptions = get_option( WOWCD_PLUGIN_OPTION_NAME );
	if( is_array( $wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) && count( $wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) )
	{
		print "<strong>" . __( "Character:", "wowcd" ) . "</strong><br/>";
 		print "<select name=\"$prefix"."[$number][character]\"/>";
		print "<option value=\"\">--</option>";
		foreach( array_keys( $wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) AS $char )
			print "<option value=\"$char\"" . ($char == $character ? " selected=\"selected\"" : "") . ">$char</option>";
	 	print "</select><br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_basic_stats' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_basic_stats]\"/> " . __( "Display basic stats", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_resistances' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_resistances]\"/> " . __( "Display resistances", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_melee' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_melee]\"/> " . __( "Display melee damage", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_range' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_range]\"/> " . __( "Display range damage", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_spell' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_spell]\"/> " . __( "Display spell damage", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_caster_stats' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_caster_stats]\"/> " . __( "Display caster stats", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_defense' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_defense]\"/> " . __( "Display defense values", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_pvp' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_pvp]\"/> " . __( "Display PvP honor", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_titles' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_titles]\"/> " . __( "Display titles", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_gear' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_gear]\"/> " . __( "Display gear", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_professions' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_professions]\"/> " . __( "Display professions", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_achievments' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_achievments]\"/> " . __( "Display recent achievments", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_statistics' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_statistics]\"/> " . __( "Display statistics", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_reputation' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_reputation]\"/> " . __( "Display reputation", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_last_update_string' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_last_update_string]\"/> " . __( "Display date and time of last update", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_glyphs' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_glyphs]\"/> " . __( "Display glyphs", "wowcd" ) . "<br/><br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_3darmory' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_3darmory]\"/> " . __( "Display 3D Armory flash model", "wowcd" ) . "<br/>";
		print "<input type=\"text\" size=\"5\" value=\"" . $opts[ 'display_3darmory_width' ] . "\" name=\"$prefix"."[$number][display_3darmory_width]\"/> " . __( "3D Armory flash width", "wowcd" ) . "<br/>";
		print "<input type=\"text\" size=\"5\" value=\"" . $opts[ 'display_3darmory_height' ] . "\" name=\"$prefix"."[$number][display_3darmory_height]\"/> " . __( "3D Armory flash height", "wowcd" ) . "<br/><br/>";
	}
	if( is_array( $wowcd_AdminOptions[ 'wowcdOptionGuilds' ] ) && count( $wowcd_AdminOptions[ 'wowcdOptionGuilds' ] ) )
	{
		print "<strong>" . __( "Or display a guild roster:", "wowcd" ) . "</strong><br/>";
 		print "<select name=\"$prefix"."[$number][guild]\"/>";
		print "<option value=\"\">--</option>";
		foreach( array_keys( $wowcd_AdminOptions[ 'wowcdOptionGuilds' ] ) AS $gld )
			print "<option value=\"$gld\"" . ($gld == $guild ? " selected=\"selected\"" : "") . ">$gld</option>";
	 	print "</select><br/>";
		print "<input type=\"text\" size=\"5\" value=\"" . $opts[ 'from_level' ] . "\" name=\"$prefix"."[$number][from_level]\"/> " . __( "Minimum level to display", "wowcd" ) . "<br/>";
		print "<input type=\"text\" size=\"5\" value=\"" . $opts[ 'to_level' ] . "\" name=\"$prefix"."[$number][to_level]\"/> " . __( "Maximum level to display", "wowcd" ) . "<br/>";
		print "<input type=\"text\" size=\"5\" value=\"" . $opts[ 'gfx_width' ] . "\" name=\"$prefix"."[$number][gfx_width]\"/> " . __( "Character class image width", "wowcd" ) . "<br/>";
		print "<input type=\"checkbox\" value=\"1\" " . ($opts[ 'display_member_count' ] ? "checked=\"checked\"" : "") . " name=\"$prefix"."[$number][display_member_count]\"/> " . __( "Display total count of members", "wowcd" ) . "<br/>";
	}
}
function wowcd_smart_multiwidget_update( $id_prefix, $options, $post, $sidebar, $option_name = '' )
{
	global $wp_registered_widgets;
	static $updated = false;
	// Get active sidebar
	$sidebars_widgets = wp_get_sidebars_widgets();
	if( isset( $sidebars_widgets[ $sidebar ] ) )
		$this_sidebar =& $sidebars_widgets[ $sidebar ];
	else
		$this_sidebar = array();
	// Search unused options
	foreach( $this_sidebar AS $_widget_id )
	{
		if( preg_match( '/' . $id_prefix . '-([0-9]+)/i', $_widget_id, $match ) )
		{
			$widget_number = $match[ 1 ];
			// $_POST['widget-id'] contain current widgets set for current sidebar
			// $this_sidebar is not updated yet, so we can determine which was deleted
			if( !in_array( $match[ 0 ], $_POST[ 'widget-id' ] ) )
				unset( $options[ $widget_number ] );
		}
	}
	// update database
	if( !empty( $option_name ) )
	{
		update_option( $option_name, $options );
		$updated = true;
	}
	// return updated array
	return $options;
}
add_action( 'init', 'wowcdwidget_multi_register' );

?>