<?php
// Default cache path is the WP upload dir
define( 'WOWCD_CACHEPATH', str_replace( 'plugins/wow-character-display/../../', '', dirname(__FILE__) . "/../../uploads/" ) );

// The main plugin class
class wowcd_Admin
{
	var $wowcd_AdminOptionsName;
	var $wowcd_AdminOptions;
	var $armory_reader;

	// Construct
	function __construct()
	{
		$this -> wowcd_AdminOptionsName	= WOWCD_PLUGIN_OPTION_NAME;
		$this -> wowcd_AdminOptions	= $this -> wowcd_GetAdminOptions();
		// Create armory reader
		$this -> armory_reader = new wow_armory_reader( $this -> wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ], $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ], $this -> wowcd_AdminOptions[ 'wowcdOptionCacheTime' ] );
		$this -> armory_reader -> set_prof_image_locale( 'Jewelcrafting', $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleJewelcrafting' ] );
		$this -> armory_reader -> set_prof_image_locale( 'Inscription', $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleInscription' ] );
		$this -> armory_reader -> set_prof_image_locale( 'Leatherworking', $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleLeatherworking' ] );
		$this -> armory_reader -> set_prof_image_locale( 'Skinning', $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleSkinning' ] );
		$this -> armory_reader -> set_prof_image_locale( 'Herbalism', $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleHerbalism' ] );
		$this -> armory_reader -> set_prof_image_locale( 'Alchemy', $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleAlchemy' ] );
		$this -> armory_reader -> set_prof_image_locale( 'Blacksmithing', $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleBlacksmithing' ] );
		$this -> armory_reader -> set_prof_image_locale( 'Engineering', $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleEngineering' ] );
		$this -> armory_reader -> set_prof_image_locale( 'Enchanting', $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleEnchanting' ] );
		$this -> armory_reader -> set_prof_image_locale( 'Mining', $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleMining' ] );
		$this -> armory_reader -> set_prof_image_locale( 'Tailoring', $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleTailoring' ] );
	}
	// PHP4 compatibe construct (please update to PHP 5 soon! ;) )
	function wowcd_Admin() { $this -> __construct(); }

	// Get options for this plugin
	function wowcd_GetAdminOptions()
	{
		// Set default options
		$wowcdOptions = array(	'wowcdOptionCacheTime' => 86400,
					'wowcdOptionCachePath' => WOWCD_CACHEPATH,
					'wowcdOptionIncludeCSS' => 'true',
					'wowcdOptionArmoryLang' => 'en_gb',
					'wowcdOptionLocaleJewelcrafting' => 'Jewelcrafting',
					'wowcdOptionLocaleInscription' => 'Inscription',
					'wowcdOptionLocaleLeatherworking' => 'Leatherworking',
					'wowcdOptionLocaleSkinning' => 'Skinning',
					'wowcdOptionLocaleHerbalism' => 'Herbalism',
					'wowcdOptionLocaleAlchemy' => 'Alchemy',
					'wowcdOptionLocaleBlacksmithing' => 'Blacksmithing',
					'wowcdOptionLocaleEngineering' => 'Engineering',
					'wowcdOptionLocaleEnchanting' => 'Enchanting',
					'wowcdOptionLocaleMining' => 'Mining',
					'wowcdOptionLocaleTailoring' => 'Tailoring',
					'wowcdOptionCharacters' => array(),
					'wowcdOptionGuilds' => array(),
					'wowcdOptionPreviewType' => 'long'
				);
		// Load existing options
		$_wowcdOptions = get_option( $this -> wowcd_AdminOptionsName );
		// Overwrite defaults
		$update = false;
		if( count( $_wowcdOptions ) )
		{
			foreach( $_wowcdOptions AS $oKey => $oVal )
			{
				if( $oKey == 'wowcdOptionCachePath' && empty( $oVal ) ) {
					$oVal = WOWCD_CACHEPATH;
					$update = true;
				}
				$wowcdOptions[ $oKey ] = $oVal;
			}
		}
		// Set default options to wp db if no existing options or new options are found
		if( !count( $_wowcdOptions ) || count( $_wowcdOptions ) != count( $wowcdOptions ) || $update )
		{
			update_option( $this -> wowcd_AdminOptionsName, $wowcdOptions );
		}
		// Return options
		return $wowcdOptions;
	}

	// Adminpage
	function wowcd_AdminPage()
	{
		if( function_exists( 'load_plugin_textdomain' ) ) {
			load_plugin_textdomain( 'wowcd', false, 'wow-character-display' );
		}
		if( is_array( $this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) && count( $this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) )
			$open = 4;
		else
			$open = 0;

		// Set config
		if( $_POST[ 'wowcd_admin_action' ] == 'set' )
		{
			$wowcdOptionsNew = array();
			foreach( array_keys( $this -> wowcd_AdminOptions ) AS $oKey )
			{
				if( isset( $_POST[ $oKey ] ) )
				{
					$wowcdOptionsNew[ $oKey ] = (!eregi( "[^0-9,.]", $_POST[ $oKey ] ) ? str_replace( ',', '.', $_POST[ $oKey ] ) : $_POST[ $oKey ]);
					if( $oKey == 'wowcdOptionCachePath' )
					{
						if( !empty( $_POST[ $oKey ] ) )
							$wowcdOptionsNew[ $oKey ] = (substr( $_POST[ $oKey ], -1 ) != '/' ? $_POST[ $oKey ] . '/' : $_POST[ $oKey ]);
						else
							$wowcdOptionsNew[ $oKey ] = WOWCD_CACHEPATH;
					}
				}
				// Save variables not submitted via post
				else
				{
					$wowcdOptionsNew[ $oKey ] = $this -> wowcd_AdminOptions[ $oKey ];
				}

			}
			update_option( $this -> wowcd_AdminOptionsName, $wowcdOptionsNew );
			$this -> wowcd_AdminOptions = $wowcdOptionsNew;
			if( $_POST[ 'createdir' ] == 'yes' )
			{
				if( !file_exists( $_POST[ 'wowcdOptionCachePath' ] ) )
					mkdir( $_POST[ 'wowcdOptionCachePath' ] );
			}
			print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "Options updated.", "wowcd" ) . "</strong></p></div>";
			$open = 1;
		}

		// Set character options
		if( $_POST[ 'wowcd_admin_action' ] == 'set_character_options' )
		{
			foreach( $this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ] AS $character => $options )
			{
				$characterfieldname = preg_replace( '/[^a-z0-9]/i', '', $character );

				// Get old cache file
				$this -> armory_reader -> _cache_set_hash( $options -> rt, $options -> wl, $options -> server, $options -> character, $options -> display[ 'display_basic_stats' ], $options -> display[ 'display_resistances' ], $options -> display[ 'display_melee' ], $options -> display[ 'display_range' ], $options -> display[ 'display_spell' ], $options -> display[ 'display_caster_stats' ], $options -> display[ 'display_defense' ], $options -> display[ 'display_pvp' ], $options -> display[ 'display_titles' ], $options -> display[ 'display_gear' ], $options -> display[ 'display_professions' ], $options -> display[ 'display_achievments' ], $options -> display[ 'display_statistics' ], $options -> display[ 'display_reputation' ], $options -> display[ 'display_last_update_string' ], $options -> display[ 'display_glyphs' ] );
				$old_cache_file = $this -> armory_reader -> _cache_get_filename();

				// Set new values
				$display = array( 'display_basic_stats' => intval( $_POST[ "$characterfieldname"."_display_basic_stats" ] ), 'display_resistances' => intval( $_POST[ "$characterfieldname"."_display_resistances" ] ), 'display_melee' => intval( $_POST[ "$characterfieldname"."_display_melee" ] ), 'display_range' => intval( $_POST[ "$characterfieldname"."_display_range" ] ), 'display_spell' => intval( $_POST[ "$characterfieldname"."_display_spell" ] ), 'display_caster_stats' => intval( $_POST[ "$characterfieldname"."_display_caster_stats" ] ), 'display_defense' => intval( $_POST[ "$characterfieldname"."_display_defense" ] ), 'display_pvp' => intval( $_POST[ "$characterfieldname"."_display_pvp" ] ), 'display_titles' => intval( $_POST[ "$characterfieldname"."_display_titles" ] ), 'display_gear' => intval( $_POST[ "$characterfieldname"."_display_gear" ] ), 'display_professions' => intval( $_POST[ "$characterfieldname"."_display_professions" ] ), 'display_achievments' => intval( $_POST[ "$characterfieldname"."_display_achievments" ] ), 'display_statistics' => intval( $_POST[ "$characterfieldname"."_display_statistics" ] ), 'display_reputation' => intval( $_POST[ "$characterfieldname"."_display_reputation" ] ), 'display_last_update_string' => intval( $_POST[ "$characterfieldname"."_display_last_update_string" ] ), 'display_glyphs' => intval( $_POST[ "$characterfieldname"."_display_glyphs" ] ), 'display_3darmory' => intval( $_POST[ "$characterfieldname"."_display_3darmory" ] ), 'display_3darmory_width' => intval( $_POST[ "$characterfieldname"."_display_3darmory_width" ] ), 'display_3darmory_height' => intval( $_POST[ "$characterfieldname"."_display_3darmory_height" ] ) );

				// Delete old cache file if options has changed
				if( md5( serialize( $display ) ) != md5( serialize( $options -> display ) ) && file_exists( $old_cache_file ) )
					unlink( $old_cache_file );

				// Store new options
				$this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ][ $character ] -> display = $display;
			}
			update_option( $this -> wowcd_AdminOptionsName, $this -> wowcd_AdminOptions );
			print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "Character options updated.", "wowcd" ) . "</strong></p></div>";
			$open = 4;
		}

		// Set guild options
		if( $_POST[ 'wowcd_admin_action' ] == 'set_guild_options' )
		{
			foreach( $this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ] AS $guild => $options )
			{
				$guildfieldname = preg_replace( '/[^a-z0-9]/i', '', $guild );

				// Get old cache file
				$this -> armory_reader -> _cache_set_hash_guild( $options -> rt, $options -> wl, $options -> server, $options -> guild, $options -> display[ 'from_level' ], $options -> display[ 'to_level' ], $options -> display[ 'display_member_count' ], $options -> display[ 'gfx_width' ] );
				$old_cache_file = $this -> armory_reader -> _cache_get_filename();

				// Set new values
				$display = array( 'from_level' => intval( $_POST[ "$guildfieldname"."_from_level" ] ), 'to_level' => intval( $_POST[ "$guildfieldname"."_to_level" ] ), 'display_member_count' => intval( $_POST[ "$guildfieldname"."_display_member_count" ] ), 'gfx_width' => intval( $_POST[ "$guildfieldname"."_gfx_width" ] ) );

				// Delete old cache file if options has changed
				if( md5( serialize( $display ) ) != md5( serialize( $options -> display ) ) && file_exists( $old_cache_file ) )
					unlink( $old_cache_file );

				// Store new options
				$this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ][ $guild ] -> display = $display;
			}
			update_option( $this -> wowcd_AdminOptionsName, $this -> wowcd_AdminOptions );
			print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "Guild options updated.", "wowcd" ) . "</strong></p></div>";
			$open = 5;
		}

		// Add character
		if( $_POST[ 'wowcd_admin_action' ] == 'add_character' )
		{
			$wl = $_POST[ 'wl' ];
			$rt = $_POST[ 'rt' ];
			$server = $_POST[ 'server' ];
			$character = $_POST[ 'character' ];
			if( !empty( $wl ) && !empty( $rt ) && !empty( $server ) && !empty( $character ) )
			{
				// Try to get infos about that character
				if( $this -> armory_reader -> get_character_information( $rt, $wl, $server, $character, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 0 ) )
				{
					$this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ][ $character ] -> wl = $wl;
					$this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ][ $character ] -> rt = $rt;
					$this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ][ $character ] -> server = $server;
					$this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ][ $character ] -> character = $character;
					$this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ][ $character ] -> display = array( 'display_basic_stats' => 1, 'display_resistances' => 0, 'display_melee' => 0, 'display_range' => 0, 'display_spell' => 0, 'display_caster_stats' => 0, 'display_defense' => 0, 'display_pvp' => 0, 'display_titles' => 0, 'display_gear' => 1, 'display_professions' => 1, 'display_achievments' => 0, 'display_statistics' => 0, 'display_reputation' => 0, 'display_last_update_string' => 0, 'display_glyphs' => 0 );
					update_option( $this -> wowcd_AdminOptionsName, $this -> wowcd_AdminOptions );
					print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "Success! Character added!", "wowcd" ) . "</strong></p></div>";
					$open = 4;
				}
				else
				{
					print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "The character information could not be loaded. Here the failure message:", "wowcd" ) . "</strong> {$this->armory_reader->error}<br/><br/><a href=\"javascript:void(0);\" onclick=\"window.location.reload()\">" . __( "Try again", "wowcd" ) . "</a></p></div>";
					$open = 2;
				}
			}
			else
			{
				print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "Please enter correct values. You have not filled all fields.", "wowcd" ) . "</strong></p></div>";
				$open = 2;
			}
		}

		// Add guild
		if( $_POST[ 'wowcd_admin_action' ] == 'add_guild' )
		{
			$wl = $_POST[ 'wl' ];
			$rt = $_POST[ 'rt' ];
			$server = $_POST[ 'server' ];
			$guild = $_POST[ 'guild' ];
			if( !empty( $wl ) && !empty( $rt ) && !empty( $server ) && !empty( $guild ) )
			{
				// Try to get infos about that character
				if( $this -> armory_reader -> get_guild_information( $rt, $wl, $server, $guild, 70, 80, 1, 48 ) )
				{
					$this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ][ $guild ] -> wl = $wl;
					$this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ][ $guild ] -> rt = $rt;
					$this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ][ $guild ] -> server = $server;
					$this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ][ $guild ] -> guild = $guild;
					$this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ][ $guild ] -> display = array( 'from_level' => 70, 'to_level' => 80, 'display_member_count' => 1, 'gfx_width' => 48 );
					update_option( $this -> wowcd_AdminOptionsName, $this -> wowcd_AdminOptions );
					print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "Success! Guild added!", "wowcd" ) . "</strong></p></div>";
					$open = 5;
				}
				else
				{
					print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "The guild information could not be loaded. Here the failure message:", "wowcd" ) . "</strong> {$this->armory_reader->error}<br/><br/><a href=\"javascript:void(0);\" onclick=\"window.location.reload()\">" . __( "Try again", "wowcd" ) . "</a></p></div>";
					$open = 3;
				}
			}
			else
			{
				print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "Please enter correct values. You have not filled all fields.", "wowcd" ) . "</strong></p></div>";
				$open = 3;
			}
		}

		// Refresh guild
		if( $_GET[ 'wowcd_admin_action' ] == 'refresh_guild' )
		{
			$refresh_guild = $_GET[ 'guild' ];
			if( array_key_exists( $refresh_guild, $this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ] ) )
			{
				$this -> _cache_clear_all( true, $refresh_guild );
				print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "All cache files for this guild were deleted.", "wowcd" ) . "</strong></p></div>";
			}
			$open = 5;
		}

		// Refresh guild HTML only
		if( $_GET[ 'wowcd_admin_action' ] == 'refresh_guild_html' )
		{
			$refresh_guild = $_GET[ 'guild' ];
			if( array_key_exists( $refresh_guild, $this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ] ) )
			{
				$this -> _cache_clear_all( true, $refresh_guild, 'html' );
				print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "All cache files for this guild were deleted.", "wowcd" ) . "</strong></p></div>";
			}
			$open = 5;
		}

		// Remove guild
		if( $_GET[ 'wowcd_admin_action' ] == 'remove_guild' )
		{
			$remove_guild = $_GET[ 'guild' ];
			if( array_key_exists( $remove_guild, $this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ] ) )
			{
				unset( $this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ][ $remove_guild ] );
				$this -> _cache_clear_all( true, $remove_guild );
			}
			update_option( $this -> wowcd_AdminOptionsName, $this -> wowcd_AdminOptions );
			$open = 5;
		}

		// Refresh character
		if( $_GET[ 'wowcd_admin_action' ] == 'refresh_char' )
		{
			$refresh_character = $_GET[ 'character' ];
			if( array_key_exists( $refresh_character, $this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) )
			{
				$this -> _cache_clear_all( true, $refresh_character );
				print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "All cache files for this character were deleted.", "wowcd" ) . "</strong></p></div>";
			}
			$open = 4;
		}

		// Refresh character HTML only
		if( $_GET[ 'wowcd_admin_action' ] == 'refresh_char_html' )
		{
			$refresh_character = $_GET[ 'character' ];
			if( array_key_exists( $refresh_character, $this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) )
			{
				$this -> _cache_clear_all( true, $refresh_character, 'html' );
				print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "All html cache files for this character were deleted.", "wowcd" ) . "</strong></p></div>";
			}
			$open = 4;
		}

		// Remove character
		if( $_GET[ 'wowcd_admin_action' ] == 'remove_char' )
		{
			$remove_character = $_GET[ 'character' ];
			if( array_key_exists( $remove_character, $this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) )
			{
				unset( $this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ][ $remove_character ] );
				$this -> _cache_clear_all( true, $remove_character );
			}
			update_option( $this -> wowcd_AdminOptionsName, $this -> wowcd_AdminOptions );
			$open = 4;
		}

		// Delete single file from cache
		if( $_GET[ 'wowcd_admin_action' ] == 'deletecachefile' )
		{
			$cachefile = str_replace( '..', '', urldecode( $_GET[ 'cachefile' ] ) );
			if( file_exists( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] . $cachefile ) )
				unlink( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] . $cachefile );
			$open = 6;
			print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "Cache file removed.", "wowcd" ) . "</strong></p></div>";
		}

		// Delete expired cache files
		if( $_GET[ 'wowcd_admin_action' ] == 'cache_delete_expired' )
		{
			if( $this -> _cache_clear_all( false ) )
			{
				print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "All expired cache files deleted.", "wowcd" ) . "</strong></p></div>";
				$open = 6;
			}
			else	print __( "Sorry, the cache directory is not writeable. Please chmod the directory to a permission that allows the webserver to write into that directory.", "wowcd" );
		}

		// Delete cache
		if( $_POST[ 'wowcd_admin_action' ] == 'cache_delete' )
		{
			if( $this -> _cache_clear_all( true ) )
			{
				print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "All cache files deleted.", "wowcd" ) . "</strong></p></div>";
				$open = 6;
			}
			else	print "<div id=\"message\" class=\"updated fade\"><p><strong>" . __( "Sorry, the cache directory is not writeable. Please chmod the directory to a permission that allows the webserver to write into that directory.", "wowcd" ) . "</strong></p></div>";
		}

		// Container
		print "<div class=\"wrap\">\n";
		print "<h2>" . __( "WoW Character Display admin page", "wowcd" ) . "</h2>\n";
		print "<br class=\"clear\"/>";

		// Output setup form
		print "<div id=\"poststuff\">\n";
		print "<div class=\"postbox\">\n";
		print "<form name=\"wowcdAdminPage\" method=\"POST\" action=\"options-general.php?page=wow-character-display.php\">\n";
		print "<input type=\"hidden\" name=\"wowcd_admin_action\" value=\"set\"/>\n";
		print "<h3>" . __( "WoW Character Display basic options", "wowcd" ) . "</h3>\n";
		print "<div class=\"inside\">\n";
		// wowcdOptionCacheTime: set time to cache html file before recreation, or zero too turn off
		print "<h4>" . __( "Cache options", "wowcd" ) . "</h4>\n";
		print "<table class=\"form-table\">\n";
		print "<tr valign=\"top\"><td width=\"50%\">";
		print __( "Enter the absolute path to the directory where the cache files were stored. Only modify if you got problems with the default value (try '/tmp/' if nothing works). Important: The directory MUST be writeable by the webserver.", "wowcd" );
		print "</td><td><input type=\"text\" name=\"wowcdOptionCachePath\" size=\"40\" maxlength=\"400\" value=\"" . $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] . "\"/></td></tr>\n";
		if( is_writeable( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] ) )
		{
			print "<tr valign=\"top\"><td width=\"50%\">";
			print __( "Try to create this directory on change? (e.g. if your enter something like '[...]/wp-content/uploads/wow' what is always a good choice!)", "wowcd" );
			print "</td><td>";
			print "<input type=\"checkbox\" name=\"createdir\" value=\"yes\"/> " . __( "Yes, do that!", "wowcd" ) . "\n";
			print "</td></tr>\n";
			print "<tr valign=\"top\"><td width=\"50%\">";
			print __( "Enter the time in seconds that pages are delivered from cache before they are recreated. Enter 0 to deactivate cacheing.", "wowcd" );
			print "</td><td>";
			print "<input type=\"text\" name=\"wowcdOptionCacheTime\" size=\"8\" maxlength=\"16\" value=\"" . $this -> wowcd_AdminOptions[ 'wowcdOptionCacheTime' ] . "\"/><br/>\n";
			print __( "Popular values: 3600 = 1 hour, 21600 = 6 hours, 86400 = 1 day", "wowcd" );
			print "</td></tr>\n";
		}
		else
		{
			print "<tr valign=\"top\"><td colspan=\"2\">";
			print __( "Sorry, the cache directory is not writeable. Please chmod the directory to a permission that allows the webserver to write into that directory.", "wowcd" );
			print "</td></tr>\n";
			print "<input type=\"hidden\" name=\"wowcdOptionCacheTime\" value=\"0\"/>\n";
		}
		print "<tr valign=\"top\"><td width=\"50%\">";
		print __( "Include default CSS styles into your blog?", "wowcd" );
		print "</td><td>";
		print "<select name=\"wowcdOptionIncludeCSS\">\n";
			print "<option value=\"false\"" . ($this -> wowcd_AdminOptions[ 'wowcdOptionIncludeCSS' ] == 'false' ? "selected=\"selected\"" : "") . ">" . __( "No, I will use my own CSS", "wowcd" ) . "</option>\n";
			print "<option value=\"true\"" . ($this -> wowcd_AdminOptions[ 'wowcdOptionIncludeCSS' ] == 'true' ? "selected=\"selected\"" : "") . ">" . __( "Yes, do that!", "wowcd" ) . "</option>\n";
		print "</select>\n";
		print "</td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">";
		print __( "Short or long character display preview by default?", "wowcd" );
		print "</td><td>";
		print "<select name=\"wowcdOptionPreviewType\">\n";
			print "<option value=\"short\"" . ($this -> wowcd_AdminOptions[ 'wowcdOptionPreviewType' ] == 'short' ? "selected=\"selected\"" : "") . ">" . __( "Short", "wowcd" ) . "</option>\n";
			print "<option value=\"long\"" . ($this -> wowcd_AdminOptions[ 'wowcdOptionPreviewType' ] == 'long' ? "selected=\"selected\"" : "") . ">" . __( "Long", "wowcd" ) . "</option>\n";
		print "</select>\n";
		print "</td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">";
		print __( "Select the output language for armory data", "wowcd" );
		print "</td><td>";
		print "<select name=\"wowcdOptionArmoryLang\">\n";
			print "<option value=\"en-US\" " . ($this -> wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ] == 'en-US' ? "selected=\"selected\"" : "") . ">English (US)</option>\n";
			print "<option value=\"en-GB\" " . ($this -> wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ] == 'en-GB' ? "selected=\"selected\"" : "") . ">English (GB)</option>\n";
			print "<option value=\"de-DE\" " . ($this -> wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ] == 'de-DE' ? "selected=\"selected\"" : "") . ">Deutsch</option>\n";
			print "<option value=\"fr-FR\" " . ($this -> wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ] == 'fr-FR' ? "selected=\"selected\"" : "") . ">Français</option>\n";
			print "<option value=\"es-ES\" " . ($this -> wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ] == 'es-ES' ? "selected=\"selected\"" : "") . ">Español (EU)</option>\n";
			print "<option value=\"es-MX\" " . ($this -> wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ] == 'es-MX' ? "selected=\"selected\"" : "") . ">Español (AL)</option>\n";
			print "<option value=\"ru-RU\" " . ($this -> wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ] == 'ru-RU' ? "selected=\"selected\"" : "") . ">Русский</option>\n";
			print "<option value=\"kr-KR\" " . ($this -> wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ] == 'kr-KR' ? "selected=\"selected\"" : "") . ">Korea</option>\n";
			print "<option value=\"zh-CN\" " . ($this -> wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ] == 'zh-CN' ? "selected=\"selected\"" : "") . ">China</option>\n";
			print "<option value=\"zh-TW\" " . ($this -> wowcd_AdminOptions[ 'wowcdOptionArmoryLang' ] == 'zh-TW' ? "selected=\"selected\"" : "") . ">Taiwan</option>\n";
		print "</select>\n";
		print "</td></tr>\n";
		print "</table>\n";
		// Locales for professions
		print "<h4>" . __( "Locale strings for the professions", "wowcd" ) . "</h4>\n";
		print "<p>" . __( "You have to enter the locale profession strings, because the armory images where build on the correct locale. For example: Jewelcrafting is in german language Juwelenschleifen.", "wowcd" ) . "</p>";
		print "<table class=\"form-table\">\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the locale string for Jewelcrafting", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"wowcdOptionLocaleJewelcrafting\" size=\"40\" maxlength=\"400\" value=\"" . $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleJewelcrafting' ] . "\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the locale string for Inscription", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"wowcdOptionLocaleInscription\" size=\"40\" maxlength=\"400\" value=\"" . $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleInscription' ] . "\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the locale string for Leatherworking", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"wowcdOptionLocaleLeatherworking\" size=\"40\" maxlength=\"400\" value=\"" . $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleLeatherworking' ] . "\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the locale string for Skinning", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"wowcdOptionLocaleSkinning\" size=\"40\" maxlength=\"400\" value=\"" . $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleSkinning' ] . "\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the locale string for Herbalism", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"wowcdOptionLocaleHerbalism\" size=\"40\" maxlength=\"400\" value=\"" . $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleHerbalism' ] . "\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the locale string for Alchemy", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"wowcdOptionLocaleAlchemy\" size=\"40\" maxlength=\"400\" value=\"" . $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleAlchemy' ] . "\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the locale string for Blacksmithing", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"wowcdOptionLocaleBlacksmithing\" size=\"40\" maxlength=\"400\" value=\"" . $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleBlacksmithing' ] . "\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the locale string for Engineering", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"wowcdOptionLocaleEngineering\" size=\"40\" maxlength=\"400\" value=\"" . $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleEngineering' ] . "\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the locale string for Enchanting", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"wowcdOptionLocaleEnchanting\" size=\"40\" maxlength=\"400\" value=\"" . $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleEnchanting' ] . "\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the locale string for Mining", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"wowcdOptionLocaleMining\" size=\"40\" maxlength=\"400\" value=\"" . $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleMining' ] . "\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the locale string for Tailoring", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"wowcdOptionLocaleTailoring\" size=\"40\" maxlength=\"400\" value=\"" . $this -> wowcd_AdminOptions[ 'wowcdOptionLocaleTailoring' ] . "\"/></td></tr>\n";
		print "</table>\n";
		// Submit options
		print "<div class=\"submit\"><input type=\"submit\" name=\"update_wowcdAdminOptions\" value=\"" . __( "Update options", "wowcd" ) . "\"/></div>\n";
		print "</form>\n";
		print "</div>\n";
		print "</div>\n";
		print "</div>\n";

		// Add characters
		$mostusedopts = array();
		if( is_array( $this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) && count( $this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) )
		{
			foreach( $this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ] AS $character => $options )
			{
				$mostusedopts[ 'rt' ][ $options -> rt ]++;
				$mostusedopts[ 'wl' ][ $options -> wl ]++;
				$mostusedopts[ 'server' ][ $options -> server ]++;
			}
			asort( $mostusedopts[ 'rt' ] );
			asort( $mostusedopts[ 'wl' ] );
			asort( $mostusedopts[ 'server' ] );
			$rt = current( array_keys( $mostusedopts[ 'rt' ] ) );
			$wl = current( array_keys( $mostusedopts[ 'wl' ] ) );
			$server = current( array_keys( $mostusedopts[ 'server' ] ) );
		}
		print "<div id=\"poststuff\">\n";
		print "<div class=\"postbox\">\n";
		print "<form name=\"wowcdAdminPageAddChar\" method=\"POST\" action=\"options-general.php?page=wow-character-display.php\">\n";
		print "<input type=\"hidden\" name=\"wowcd_admin_action\" value=\"add_character\"/>\n";
		print "<h3>" . __( "Add character", "wowcd" ) . "</h3>\n";
		print "<div class=\"inside\">\n";
		print "<h4>" . __( "Enter the correct values to add a character to the plugin", "wowcd" ) . "</h4>";
		print "<table class=\"form-table\">\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "US or EU realm?", "wowcd" ) . "</td>";
		print "<td><select name=\"rt\">\n";
			print "<option value=\"www\"" . ($rt == 'www' ? ' selected="selected"' : '') . ">USA</option>\n";
			print "<option value=\"eu\"" . ($rt == 'eu' ? ' selected="selected"' : '') . ">Europe</option>\n";
			print "<option value=\"kr\"" . ($rt == 'kr' ? ' selected="selected"' : '') . ">Korea</option>\n";
			print "<option value=\"cn\"" . ($rt == 'cn' ? ' selected="selected"' : '') . ">China</option>\n";
			print "<option value=\"tw\"" . ($rt == 'tw' ? ' selected="selected"' : '') . ">Taiwan</option>\n";
		print "</select></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Which wowhead.com local subdomain should be used?<br/>(www = international and us, de = Germany, es = Spain, fr = France, ...)", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"wl\" size=\"8\" maxlength=\"8\" value=\"$wl\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the name of the realm, e.g. Blackmoore.", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"server\" size=\"30\" maxlength=\"60\" value=\"$server\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the name of the character.", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"character\" size=\"20\" maxlength=\"60\" value=\"\"/></td></tr>\n";
		print "</table>\n";
		// Submit
		print "<div class=\"submit\"><input type=\"submit\" name=\"update_wowcdAdminAddChar\" value=\"" . __( "Add character", "wowcd" ) . "\"/></div>\n";
		print "</form>\n";
		print "</div>\n";
		print "</div>\n";
		print "</div>\n";

		// Add Guild
		print "<div id=\"poststuff\">\n";
		print "<div class=\"postbox\">\n";
		print "<form name=\"wowcdAdminPageAddGuild\" method=\"POST\" action=\"options-general.php?page=wow-character-display.php\">\n";
		print "<input type=\"hidden\" name=\"wowcd_admin_action\" value=\"add_guild\"/>\n";
		print "<h3>" . __( "Add guild", "wowcd" ) . "</h3>\n";
		print "<div class=\"inside\">\n";
		print "<h4>" . __( "Enter the correct values to add a guild to the plugin", "wowcd" ) . "</h4>";
		print "<table class=\"form-table\">\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "US or EU realm?", "wowcd" ) . "</td>";
		print "<td><select name=\"rt\">\n";
			print "<option value=\"www\"" . ($rt == 'www' ? ' selected="selected"' : '') . ">USA</option>\n";
			print "<option value=\"eu\"" . ($rt == 'eu' ? ' selected="selected"' : '') . ">Europe</option>\n";
			print "<option value=\"kr\"" . ($rt == 'kr' ? ' selected="selected"' : '') . ">Korea</option>\n";
			print "<option value=\"cn\"" . ($rt == 'cn' ? ' selected="selected"' : '') . ">China</option>\n";
			print "<option value=\"tw\"" . ($rt == 'tw' ? ' selected="selected"' : '') . ">Taiwan</option>\n";
		print "</select></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Which wowhead.com local subdomain should be used?<br/>(www = international and us, de = Germany, es = Spain, fr = France, ...)", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"wl\" size=\"8\" maxlength=\"8\" value=\"$wl\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the name of the realm, e.g. Blackmoore.", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"server\" size=\"30\" maxlength=\"60\" value=\"$server\"/></td></tr>\n";
		print "<tr valign=\"top\"><td width=\"50%\">" .  __( "Enter the name of the guild.", "wowcd" ) . "</td>";
		print "<td><input type=\"text\" name=\"guild\" size=\"20\" maxlength=\"60\" value=\"\"/></td></tr>\n";
		print "</table>\n";
		// Submit
		print "<div class=\"submit\"><input type=\"submit\" name=\"update_wowcdAdminAddGuild\" value=\"" . __( "Add guild", "wowcd" ) . "\"/></div>\n";
		print "</form>\n";
		print "</div>\n";
		print "</div>\n";
		print "</div>\n";

		// Display and configure existing characters
		if( is_array( $this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) && count( $this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ] ) )
		{
			print "<div id=\"poststuff\">\n";
			print "<div class=\"postbox\">\n";
			print "<form name=\"wowcdAdminPageConfigureChar\" method=\"POST\" action=\"options-general.php?page=wow-character-display.php\">\n";
			print "<input type=\"hidden\" name=\"wowcd_admin_action\" value=\"set_character_options\"/>\n";
			print "<h3>" . __( "Your characters", "wowcd" ) . "</h3>\n";
			print "<div class=\"inside\">\n";
			print "<h4>" . __( "Configure what informations were displayed for your characters", "wowcd" ) . "</h4>";
			// Edit CSS to shorten preview
			if( $this -> wowcd_AdminOptions[ 'wowcdOptionPreviewType' ] == 'short' )
				echo "<style type=\"text/css\">.wow_character { overflow:hidden; height:85px }</style>";
			print "<table class=\"form-table\">\n";
			foreach( $this -> wowcd_AdminOptions[ 'wowcdOptionCharacters' ] AS $character => $options )
			{
				$characterfieldname = preg_replace( '/[^a-z0-9]/i', '', $character );

				// Set default values if entity not exists
				if( !isset( $options -> display ) )
					$options -> display = array( 'display_basic_stats' => 1, 'display_resistances' => 0, 'display_melee' => 0, 'display_range' => 0, 'display_spell' => 0, 'display_caster_stats' => 0, 'display_defense' => 0, 'display_pvp' => 0, 'display_titles' => 0, 'display_gear' => 1, 'display_professions' => 1, 'display_achievments' => 1, 'display_statistics' => 0, 'display_reputation' => 0, 'display_last_update_string' => 0, 'display_glyphs' => 0 );
				// Set options for character
				print "<tr valign=\"top\" style=\"border-bottom:1px solid #999\"><td width=\"50%\"><a name=\"$characterfieldname\"></a><strong>$character</strong><br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_basic_stats' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_basic_stats\"/> " . __( "Display basic stats", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_resistances' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_resistances\"/> " . __( "Display resistances", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_melee' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_melee\"/> " . __( "Display melee damage", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_range' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_range\"/> " . __( "Display range damage", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_spell' ] ? "checked=\"checked\"" : "") . " name=\"$character"."_display_spell\"/> " . __( "Display spell damage", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_caster_stats' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_caster_stats\"/> " . __( "Display caster stats", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_defense' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_defense\"/> " . __( "Display defense values", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_pvp' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_pvp\"/> " . __( "Display PvP honor", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_titles' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_titles\"/> " . __( "Display titles", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_gear' ] ? "checked=\"checked\"" : "") . " name=\"$character"."_display_gear\"/> " . __( "Display gear", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_professions' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_professions\"/> " . __( "Display professions", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_achievments' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_achievments\"/> " . __( "Display recent achievments", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_statistics' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_statistics\"/> " . __( "Display statistics", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_reputation' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_reputation\"/> " . __( "Display reputation", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_last_update_string' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_last_update_string\"/> " . __( "Display date and time of last update", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_glyphs' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_glyphs\"/> " . __( "Display glyphs", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options ->display[ 'display_3darmory' ] ? "checked=\"checked\"" : "") . " name=\"$characterfieldname"."_display_3darmory\"/> " . __( "Display 3D Armory flash model", "wowcd" ) . "<br/>";
				print "<input type=\"text\" size=\"5\" value=\"" . $options ->display[ 'display_3darmory_width' ] . "\" name=\"$characterfieldname"."_display_3darmory_width\"/> " . __( "3D Armory flash width", "wowcd" ) . " ";
				print "<input type=\"text\" size=\"5\" value=\"" . $options ->display[ 'display_3darmory_height' ] . "\" name=\"$characterfieldname"."_display_3darmory_height\"/> " . __( "3D Armory flash height", "wowcd" ) . "<br/>";
				// Output shortcode
				print "<br/>" . __( "To display this character in a page or post, use the following shortcode:", "wowcd" ) . "<br/><strong>[wowcd character=\"$character\"]</strong>";
				// Refresh, remove buttons
				print "<div class=\"submit\">";
				print "<input type=\"button\" name=\"refresh_wowcdAdminDelHtmlChar\" value=\"" . __( "Refresh HTML", "wowcd" ) . "\" onclick=\"window.location.href='options-general.php?page=wow-character-display.php&wowcd_admin_action=refresh_char_html&character=$character&r=" . rand() . "#$characterfieldname';\"/>\n";
				print "<input type=\"button\" name=\"refresh_wowcdAdminDelCacheChar\" value=\"" . __( "Refresh HTML+XML", "wowcd" ) . "\" onclick=\"if(confirm('" . __( "Really refresh?", "wowcd" ) . "'))window.location.href='options-general.php?page=wow-character-display.php&wowcd_admin_action=refresh_char&character=$character&r=" . rand() . "#$characterfieldname';\"/>\n";
				print "<input type=\"button\" name=\"remove_wowcdAdminDelChar\" value=\"" . __( "Remove", "wowcd" ) . "\" onclick=\"if(confirm('" . __( "Really delete?", "wowcd" ) . "'))window.location.href='options-general.php?page=wow-character-display.php&wowcd_admin_action=remove_char&character=$character';\"/>\n";
				print "</div>\n";
				print "</td>";
				// Display preview
				if( $this -> armory_reader -> get_character_information( $options -> rt, $options -> wl, $options -> server, $options -> character, $options -> display[ 'display_basic_stats' ], $options -> display[ 'display_resistances' ], $options -> display[ 'display_melee' ], $options -> display[ 'display_range' ], $options -> display[ 'display_spell' ], $options -> display[ 'display_caster_stats' ], $options -> display[ 'display_defense' ], $options -> display[ 'display_pvp' ], $options -> display[ 'display_titles' ], $options -> display[ 'display_gear' ], $options -> display[ 'display_professions' ], $options -> display[ 'display_achievments' ], $options -> display[ 'display_statistics' ], $options -> display[ 'display_reputation' ], $options -> display[ 'display_last_update_string' ], $options -> display[ 'display_glyphs' ], $options -> display[ 'display_3darmory' ], $options -> display[ 'display_3darmory_width' ], $options -> display[ 'display_3darmory_height' ] ) )
					$preview = $this -> armory_reader -> get_content();
				else
					$preview = __( "Error:", "wowcd" ) . " " . $this -> armory_reader -> error;
				if( $this -> wowcd_AdminOptions[ 'wowcdOptionPreviewType' ] == 'short' )
					$preview .= "<a href=\"javascript:void(0);\" onclick=\"jQuery('.wow_character-$character').css({overflow:'visible',height:'auto'}); this.style.display='none';\">" . __( "Display full preview", "wowcd" ) . "</a>";
				print "<td>$preview</td></tr>\n";
			}
			print "</table>\n";
			// Submit
			print "<div class=\"submit\"><input type=\"submit\" name=\"update_wowcdAdminAddChar\" value=\"" . __( "Save configuration", "wowcd" ) . "\"/></div>\n";
			print "</form>\n";
			print "</div>\n";
			print "</div>\n";
			print "</div>\n";
		}

		// Display and configure existing guilds
		if( is_array( $this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ] ) && count( $this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ] ) )
		{
			print "<div id=\"poststuff\">\n";
			print "<div class=\"postbox\">\n";
			print "<form name=\"wowcdAdminPageConfigureGuild\" method=\"POST\" action=\"options-general.php?page=wow-character-display.php\">\n";
			print "<input type=\"hidden\" name=\"wowcd_admin_action\" value=\"set_guild_options\"/>\n";
			print "<h3>" . __( "Your guilds", "wowcd" ) . "</h3>\n";
			print "<div class=\"inside\">\n";
			print "<h4>" . __( "Configure what informations were displayed for your guilds", "wowcd" ) . "</h4>";
			print "<table class=\"form-table\">\n";
			foreach( $this -> wowcd_AdminOptions[ 'wowcdOptionGuilds' ] AS $guild => $options )
			{
				$guildfieldname = preg_replace( '/[^a-z0-9]/i', '', $guild );
				// Set default values if entity not exists
				if( !isset( $options -> display ) )
					$options -> display = array( 'from_level' => 70, 'to_level' => 80, 'display_member_count' => 1, 'gfx_width' => 48 );
				// Set options for guild
				print "<tr valign=\"top\" style=\"border-bottom:1px solid #999\"><td width=\"50%\"><a name=\"$guildfieldname\"></a><strong>$guild</strong><br/>";
				print "<input type=\"text\" size=\"5\" value=\"" . $options -> display[ 'from_level' ] . "\" name=\"$guildfieldname"."_from_level\"/> " . __( "Display only character equal or above this level.", "wowcd" ) . "<br/>";
				print "<input type=\"text\" size=\"5\" value=\"" . $options -> display[ 'to_level' ] . "\" name=\"$guildfieldname"."_to_level\"/> " . __( "Display only character equal or under this level.", "wowcd" ) . "<br/>";
				print "<input type=\"text\" size=\"5\" value=\"" . $options -> display[ 'gfx_width' ] . "\" name=\"$guildfieldname"."_gfx_width\"/> " . __( "Set width of the character class image.", "wowcd" ) . "<br/>";
				print "<input type=\"checkbox\" value=\"1\" " . ($options -> display[ 'display_member_count' ] ? "checked=\"checked\"" : "") . " name=\"$guildfieldname"."_display_member_count\"/> " . __( "Display total count of members", "wowcd" ) . "<br/>";
				// Output shortcode
				print "<br/>" . __( "To display this guild in a page or post, use the following shortcode:", "wowcd" ) . "<br/><strong>[wowcd guild=\"$guild\"]</strong>";
				// Refresh, remove buttons
				print "<div class=\"submit\">";
				print "<input type=\"button\" name=\"refresh_wowcdAdminDelHTMLCacheGuild\" value=\"" . __( "Refresh HTML", "wowcd" ) . "\" onclick=\"window.location.href='options-general.php?page=wow-character-display.php&wowcd_admin_action=refresh_guild_html&guild=$guild&r=" . rand() . "#$guildfieldname';\"/>\n";
				print "<input type=\"button\" name=\"refresh_wowcdAdminDelCacheGuild\" value=\"" . __( "Refresh HTML+XML", "wowcd" ) . "\" onclick=\"if(confirm('" . __( "Really refresh?", "wowcd" ) . "'))window.location.href='options-general.php?page=wow-character-display.php&wowcd_admin_action=refresh_guild&guild=$guild&r=" . rand() . "#$guildfieldname';\"/>\n";
				print "<input type=\"button\" name=\"remove_wowcdAdminDelGuild\" value=\"" . __( "Remove", "wowcd" ) . "\" onclick=\"if(confirm('" . __( "Really delete?", "wowcd" ) . "'))window.location.href='options-general.php?page=wow-character-display.php&wowcd_admin_action=remove_guild&guild=$guild';\"/>\n";
				print "</div>\n";
				print "</td>";
				// Display preview
				if( $this -> armory_reader -> get_guild_information( $options -> rt, $options -> wl, $options -> server, $options -> guild, $options -> display[ 'from_level' ], $options -> display[ 'to_level' ], $options -> display[ 'display_member_count' ], $options -> display[ 'gfx_width' ] ) )
					$preview = $this -> armory_reader -> get_content();
				else
					$preview = __( "Error:", "wowcd" ) . " " . $this -> armory_reader -> error;
				print "<td>$preview</td></tr>\n";
			}
			print "</table>\n";
			// Submit
			print "<div class=\"submit\"><input type=\"submit\" name=\"update_wowcdAdminSetGuilds\" value=\"" . __( "Save configuration", "wowcd" ) . "\"/></div>\n";
			print "</form>\n";
			print "</div>\n";
			print "</div>\n";
			print "</div>\n";
		}

		// Cache status
		if( !empty( $_GET[ 'cache_sort_by' ] ) )
			$open = 6;
		print "<div id=\"poststuff\">\n";
		print "<div class=\"postbox\">\n";
		print "<form name=\"wowcdAdminPage\" method=\"POST\" action=\"options-general.php?page=wow-character-display.php\">\n";
		print "<input type=\"hidden\" name=\"wowcd_admin_action\" value=\"cache_delete\"/>\n";
		print "<h3>" . __( "Cache status", "wowcd" ) . "</h3>\n";
		print "<div class=\"inside\">\n";
		print "<h4>" . __( "List of cached files (red: expired and queued for refresh, green: page is delivered from cache)", "wowcd" ) . "</h4>";
		print "<table class=\"form-table\">\n";
		print "<tr valign=\"top\"><td>";
		print __( "Sort by:", "wowcd" ) . " [<a href=\"options-general.php?page=wow-character-display.php&cache_sort_by=state\">" . __( "state", "wowcd" ) . "</a>] [<a href=\"options-general.php?page=wow-character-display.php&cache_sort_by=name\">" . __( "name", "wowcd" ) . "</a>]<br/><br/>";
		// Display cache files
		$cache_counter_green = 0;
		$cache_counter_red = 0;
		$cache_counter = 0;
		$size_total = 0;
		$size_red = 0;
		$size_green = 0;
		if( !empty( $this -> wowcd_AdminOptions[ 'wowcdOptionCacheTime' ] ) )
		{
			if( is_writeable( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] ) )
			{
				if( $d = opendir( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] ) )
				{
					echo "<strong>" . __( "Important: Don't delete XML cache files if not really necessary!", "wowcd" ) . "</strong><br/><br/>";
					$cache_content = array();
					while( false !== ($cf = readdir( $d ) ) )
					{
						$fSuffix_Arr = explode( ".", $cf );
						if( (end( $fSuffix_Arr ) == 'html' || end( $fSuffix_Arr ) == 'xml') && $fSuffix_Arr[ 0 ] == 'wowcd' && $fSuffix_Arr[ 1 ] == 'cache' )
						{
							if( filemtime( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] . $cf ) < (time() - $this -> wowcd_AdminOptions[ 'wowcdOptionCacheTime' ]) )
							{
								$cache_counter_red++;
								$col = 'red';
							}
							else
							{
								$cache_counter_green++;
								$col = 'green';
							}
							$cache_content[ $cf ] = array( $col, end( $fSuffix_Arr ) );
						}
					}
					$cache_counter = $cache_counter_green + $cache_counter_red;
					if( $_GET[ 'cache_sort_by' ] == 'name' || empty( $_GET[ 'cache_sort_by' ] ) )
						ksort( $cache_content );
					else
						arsort( $cache_content );
					foreach( $cache_content AS $cf => $col_type_arr )
					{
						$size = filesize( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] . $cf );
						$size_total += $size;
						$col = $col_type_arr[ 0 ];
						if( $col == 'red' )
						{
							$size_red += $size;
						}
						else
						{
							$size_green += $size;
						}
						if( $col_type_arr[ 1 ] == 'html' )
						{
							$font_weight = 'bold';
							$font_size = '1.2em;';
							if( strpos( $cf, 'guildroster-' ) === false )
								$ftype = __( "Character sheet", "wowcd" );
							else
								$ftype = __( "Guild roster", "wowcd" );
						}
						else
						{
							$font_weight = 'normal';
							$font_size = '1em;';
							$ftype = __( "XML cache file", "wowcd" );
						}
						echo "<div class=\"postbox\" style=\"padding:4px\">";
						echo "<span>$ftype:</span> <span style=\"color:$col;font-weight:$font_weight;font-size:$font_size\">$cf</span> (" . date( "d.m.y H:i:s", filemtime( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] . $cf ) ) . ", " . size_format( $size ) . ") [<a style=\"color:$col;font-weight:$font_weight;font-size:$font_size\" href=\"options-general.php?page=wow-character-display.php&wowcd_admin_action=deletecachefile&cachefile=$cf\" onclick=\"return confirm( '" . __( "Really delete?", "wowcd" ) . "');\">" . __( "delete", "wowcd" ) . "</a>]<br/>\n";
						echo "</div>";
					}
				}
				if( !$cache_counter )
					echo __( "Sorry, no files are cached at the moment.", "wowcd" );
			}
			else	print __( "Sorry, the cache directory is not writeable. Please chmod the directory to a permission that allows the webserver to write into that directory.", "wowcd" );
		}
		else	echo __( "Cacheing is disabled.", "wowcd" );
		print "</td></tr>\n";
		print "</table>\n";
		if( $cache_counter )
		{
			echo "<strong>";
			echo "<span style=\"color:green\">$cache_counter_green</span> " . __( "files are cached.", "wowcd" ) . (!empty( $size_green ) ? " (" . size_format( $size_green ) . ")" : '') . "<br/>";
			echo "<span style=\"color:red\">$cache_counter_red</span> " . __( "files are cached and outdated.", "wowcd" ) . (!empty( $size_red ) ? " (" . size_format( $size_red ) . ")" : '') . "<br/><br/>";
			echo "$cache_counter " . __( "files are cached total.", "wowcd" ) . (!empty( $size_total ) ? " (" . size_format( $size_total ) . ")" : '');
			echo "</strong><br/>";
		}
		print "<div class=\"submit\"><input type=\"submit\" name=\"update_wowcdAdminOptions\" value=\"" . __( "Delete all cache files and force recreation", "wowcd" ) . "\"/> <input type=\"button\" onclick=\"window.document.location.href='options-general.php?page=wow-character-display.php&wowcd_admin_action=cache_delete_expired';\" value=\"" . __( "Delete all expired cache files", "wowcd" ) . "\"/></div>\n";
		print "</form>\n";
		print "</div>\n";
		print "</div>\n";
		print "</div>\n";

		// Donate link and support informations
		print "<div id=\"poststuff\">\n";
		print "<div class=\"postbox\">\n";
		print "<h3>" . __( "Donate &amp; support", "wowcd" ) . "</h3>\n";
		print "<div class=\"inside\" style=\"line-height:160%;font-size:1em;\">\n";
		print __( "Please", "wowcd" ) . " <a href=\"https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=m_schieferdecker%40hotmail%2ecom&item_name=WoWCDPlugin%20wp%20plugin&no_shipping=0&no_note=1&tax=0&currency_code=EUR&bn=PP%2dDonationsBF&charset=UTF%2d8\">" . __( "DONATE", "wowcd" ) . "</a> " . __( "if you like this plugin.", "wowcd" ) . "<br/>";
		print "<br/>" . __( "If you need support, want to report bugs or suggestions, drop me an ", "wowcd" ) . " <a href=\"mailto:m_schieferdecker@hotmail.com\">" . __( "email", "wowcd" ) . "</a> " . __( "or visit the", "wowcd" ) . " <a href=\"http://www.das-motorrad-blog.de/meine-wordpress-plugins\">" . __( "plugin homepage", "wowcd" ) . "</a>.<br/>";
		print "<br/>" . __( "Translations: ", "wowcd" ) . " Marc Schieferdecker (Deutsch)<br/>";
		print "<br/>" . __( "And this persons I thank for a donation:", "wowcd" ) . " none yet! ;)<br/>";
		print "<br/>" . __( "Final statements: Code is poetry. Motorcycles are cooler than cars.", "wowcd" );
		print "</div>";
		print "</div>\n";
		print "</div>\n";

		// Close container
		print "</div>\n";

		// Nice display
		if( version_compare( substr($wp_version, 0, 3), '2.6', '<' ) )
		{
?>
		<script type="text/javascript">
		//<!--
			var wowcd_openPanel = <?php print $open; ?>;
			var wowcd_PanelCounter = 1;
			jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
			jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
			jQuery('.postbox h3').each(function() {
				if( (wowcd_PanelCounter++) != wowcd_openPanel )
					jQuery(jQuery(this).parent().get(0)).toggleClass('closed');
			});
		//-->
		</script>
		<style type="text/css">
			h4 {
				margin-bottom:0em;
			}
		</style>
<?php
		}
	}

	/**
	 * Cache remove functions
	 */
	// Cache: Clear all cache files
	function _cache_clear_all( $all_or_expired = true, $only_with_string = '', $suffixes = 'both' )
	{
		if( is_writeable( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] ) )
		{
			if( $d = opendir( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] ) )
			{
				$only_with_string1 = str_replace( array( ' ', '+' ), '', $only_with_string );
				$only_with_string2 = preg_replace( '/[^0-9a-z]/i', '-', strtolower( $only_with_string ) );
				while( false !== ($cf = readdir( $d ) ) )
				{
					$fSuffix_Arr = explode( ".", $cf );
					if( $suffixes == 'both' )
					{
						if( (end( $fSuffix_Arr ) == 'html' || end( $fSuffix_Arr ) == 'xml') && $fSuffix_Arr[ 0 ] == 'wowcd' && $fSuffix_Arr[ 1 ] == 'cache' )
						{
							if( ($all_or_expired !== false || filemtime( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] . $cf ) < (time() - $this -> wowcd_AdminOptions[ 'wowcdOptionCacheTime' ])) && (empty( $only_with_string ) || strpos( strtolower( $cf ), strtolower( $only_with_string1 ) ) !== false || strpos( strtolower( $cf ), strtolower( $only_with_string2 ) ) !== false) )
								unlink( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] . $cf );
						}
					}
					else
					if( $suffixes == 'html' )
					{
						if( end( $fSuffix_Arr ) == 'html' && $fSuffix_Arr[ 0 ] == 'wowcd' && $fSuffix_Arr[ 1 ] == 'cache' )
						{
							if( ($all_or_expired !== false || filemtime( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] . $cf ) < (time() - $this -> wowcd_AdminOptions[ 'wowcdOptionCacheTime' ])) && (empty( $only_with_string ) || strpos( strtolower( $cf ), strtolower( $only_with_string1 ) ) !== false || strpos( strtolower( $cf ), strtolower( $only_with_string2 ) ) !== false) )
								unlink( $this -> wowcd_AdminOptions[ 'wowcdOptionCachePath' ] . $cf );
						}
					}
				}
			}
			return true;
		}
		else	return false;
	}
}

?>