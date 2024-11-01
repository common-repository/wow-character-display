<?php

/**
 * l10n hack, if function "__" not extists.
 */
if( !function_exists( '__' ) )
{
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

/**
 * The World of Warcraft armory read class
 *
 * By Marc Schieferdecker, part of the WoW-Char-Display plugin for WordPress.
 * Can be used stand alone without the rest of the plugin.
 *
 * License: GPL
 */
class wow_armory_reader
{
	var $locale;
	var $prof_img;
	var $class_natural;
	var $content;
	var $error;
	var $cache_dir;
	var $cache_timeout;
	var $cache_hash;
	var $sleep_counter;
	var $http_error;

	/**
	 * Constuctor
	 */
	function __construct( $locale = 'en_gb', $cache_dir = '', $cache_timeout = 0 )
	{
		// Constants
		$this -> locale				= $locale;
		// Default: profession images by key language en_EN
		$this -> prof_img[ 'Jewelcrafting' ] 	= 'inv_misc_gem_02';
		$this -> prof_img[ 'Inscription' ] 	= 'inv_inscription_tradeskill01';
		$this -> prof_img[ 'Leatherworking' ]	= 'inv_misc_armorkit_17';
		$this -> prof_img[ 'Skinning' ]		= 'inv_misc_pelt_wolf_01';
		$this -> prof_img[ 'Herbalism' ]	= 'spell_nature_naturetouchgrow';
		$this -> prof_img[ 'Alchemy' ]		= 'trade_alchemy';
		$this -> prof_img[ 'Blacksmithing' ]	= 'trade_blacksmithing';
		$this -> prof_img[ 'Engineering' ]	= 'trade_engineering';
		$this -> prof_img[ 'Enchanting' ]	= 'trade_engraving';
		$this -> prof_img[ 'Mining' ]		= 'trade_mining';
		$this -> prof_img[ 'Tailoring' ]	= 'trade_tailoring';
		// Default: Set natural class names for images etc
		$this -> class_natural			= array( 1 => 'warrior', 2 => 'paladin', 3 => 'hunter', 4 => 'rogue', 5 => 'priest', 6 => 'deathknight', 7 => 'shaman', 8 => 'mage', 9 => 'warlock', 11 => 'druid' );
		// Empty cache hash
		$this -> cache_hash			= '';
		$this -> cache_dir			= $cache_dir;
		$this -> cache_timeout			= $cache_timeout;
		// Empty content and error string variables
		$this -> content			= '';
		$this -> error				= '';
		// Empty http helpers
		$this -> sleep_counter			= 1000000;
		$this -> http_error			= false;
	}
	// Update to PHP 5 soon!
	function wow_armory_reader(){$this->__construct();}

	/**
	 * Get the content
	 */
	function get_content( $clean = true )
	{
		// Store in cache
		if( $this -> http_error == false && !empty( $this -> cache_timeout ) && $this -> _cache_recreate( $this -> _cache_get_filename() ) )
			$this -> _cache_store();

		$c = $this -> content;
		if( $clean )
			$this -> content = '';
		return $c;
	}

	/**
	 * Get html code for guild roster
	 *
	 * Usage:
	 *  RT = www | eu
	 *  WL = www | de | es | ...
	 *  SERVER = Blackmoore (not url encoded!)
	 *  GUILD = Lost Prophets (not url encoded!)
	 *
	 */
	function get_guild_information( $rt, $wl, $server, $guild, $from_level = 70, $to_level = 80, $display_member_count = 1, $gfx_width = 48 )
	{
		// Reset http helpers
		$this -> sleep_counter	= 1000000;
		$this -> http_error	= false;

		// Load textdomain
		if( function_exists( 'load_plugin_textdomain' ) ) {
			load_plugin_textdomain( 'wowcd', false, 'wow-character-display' );
		}

		// Conversions
		$from_level = intval( $from_level );
		$to_level = intval( $to_level );
		$gfx_width = intval( $gfx_width );
		$server = urlencode( stripslashes( $server ) );
		$guild = urlencode( $guild );
		$display_member_count = intval( $display_member_count );

		// Set cache hash
		$this -> _cache_set_hash_guild( $rt, $wl, $server, $guild, $from_level, $to_level, $display_member_count, $gfx_width );

		// Set Host and URI
		$host = "$rt.wowarmory.com";
		$uri_guild = "/guild-info.xml?r=$server&n=$guild";

		// Read from armory
		if( $body_g = $this -> _http_read( $host, $uri_guild ) )
		{
			// Get and parse XML
			if( preg_match( '#<guildInfo.*>.*</guildInfo>#siU', $body_g, $match ) )
			{
				$parser = xml_parser_create();
				xml_parse_into_struct( $parser, $match[ 0 ], $vals, $index );
				xml_parser_free( $parser );
				unset( $body_g );
				unset( $match );
				if( $index )
				{
					$member_count = $vals[ $index[ 'MEMBERS' ][ 0 ] ][ 'attributes' ][ 'MEMBERCOUNT' ];
					// Get members
					$this -> content .= "<div class=\"armory_guild_roster\"><ul class=\"armory_guild_list\">";
					// Sort by level
					$charidbylevel = array();
					foreach( $index[ 'CHARACTER' ] AS $charid )
						$charidbylevel[ $charid ] = $vals[ $charid ][ 'attributes' ][ 'LEVEL' ] . '_' . $vals[ $charid ][ 'attributes' ][ 'CLASS' ];
					arsort( $charidbylevel );
					$charidbylevel = array_keys( $charidbylevel );
					foreach( $charidbylevel AS $charid )
					{
						$char_attr = $vals[ $charid ][ 'attributes' ];
						if( $char_attr[ 'LEVEL' ] >= $from_level && $char_attr[ 'LEVEL' ] <= $to_level )
						{
							$this -> content .= "<li class=\"armory_guild_item\">";
							$this -> content .= "<div class=\"armory_char_image\"><img src=\"http://$rt.wowarmory.com/images/portraits/wow" . ($char_attr[ 'LEVEL' ] < 70 ? '' : ($char_attr[ 'LEVEL' ] < 80 ? '-70' : '-80')) . "/" . $char_attr[ 'GENDERID' ] . "-" . $char_attr[ 'RACEID' ] . "-" . $char_attr[ 'CLASSID' ] . ".gif\" width=\"$gfx_width\" class=\"armory_char_prof\" /></div>";
							$this -> content .= "<span class=\"armory_char_name\"><a href=\"http://$rt.wowarmory.com/character-sheet.xml?r=$server&n=" . $char_attr[ 'NAME' ] . "\">" . ($char_attr[ 'PREFIX' ] ? $char_attr[ 'PREFIX' ] . ' ' : '') . $char_attr[ 'NAME' ] . ($char_attr[ 'SUFFIX' ] ? $char_attr[ 'SUFFIX' ] . ' ' : '') . "</a></span><br/>";
							$this -> content .= "<span class=\"armory_char_levelraceclass\">" . __( "Level", "wowcd" ) . " " . $char_attr[ 'LEVEL' ] . " " . $char_attr[ 'RACE' ] . " " . $char_attr[ 'CLASS' ] . "</span><br/>";
							$this -> content .= "<span class=\"armory_char_achpoints\">" . __( "Achievement points", "wowcd" ) . ": " . $char_attr[ 'ACHPOINTS' ] . "</span><br/>";
							$this -> content .= "</li>";
						}
					}
					$this -> content .= "</ul>";
					if( $display_member_count )
						$this -> content .= "<div class=\"armory_member_count\">" . __( "Members total", "wowcd" ) . ": $member_count</div>";
					$this -> content .= "</div>";
					// Send everything is okay
					return true;
				}
				else
				{
					$this -> error .= __( "The returned XML data is not well formed.", "wowcd" );
					return false;
				}
			}
			else
			{
				$this -> error .= __( "No guild information returned or guild not found.", "wowcd" );
				$this -> error .= "<br/><br/><a href=\"http://$host$uri_guild\" target=\"_blank\">" . __( "Test armory display", "wowcd" ) . "</a>";
				return false;
			}
		}
		else
		{
			$this -> error .= __( "Sorry the WoW armory server didn't respond. The amount of requests you can do in a specific time period is restricted by Blizzard. Please wait a bit then delete the cache file for this character and try it again. Sorry, but that's a thing I can't change. Maybe write an email to Blizzard.", "wowcd" );
			$this -> error .= "<br/><br/><a href=\"http://$host$uri_guild\" target=\"_blank\">" . __( "Test armory connection", "wowcd" ) . "</a>";
			return false;
		}
	}

	/**
	 * Get html code for a character
	 *
	 * Usage:
	 *  RT = www | eu
	 *  WL = www | de | es | ...
	 *  SERVER = Blackmoore (not url encoded!)
	 *  CHARACTER = Raufaser (not url encoded!)
	 *
	 */
	function get_character_information( $rt, $wl, $server, $character, $display_basic_stats = 1, $display_resistances = 0, $display_melee = 0, $display_range = 0, $display_spell = 0, $display_caster_stats = 0, $display_defense = 0, $display_pvp = 0, $display_titles = 0, $display_gear = 1, $display_professions = 1, $display_achievments = 1, $display_statistics = 0, $display_reputation = 0, $display_last_update_string = 0, $display_glyphs = 0, $display_3darmory = 0, $display_3darmory_width = 150, $display_3darmory_height = 150 )
	{
		// Reset http helpers
		$this -> sleep_counter	= 1000000;
		$this -> http_error	= false;

		// Load textdomain
		if( function_exists( 'load_plugin_textdomain' ) ) {
			load_plugin_textdomain( 'wowcd', false, 'wow-character-display' );
		}

		// Conversions
		$server = urlencode( stripslashes( $server ) );
		$character = urlencode( $character );

		// Set cache hash
		$this -> _cache_set_hash( $rt, $wl, $server, $character, $display_basic_stats, $display_resistances, $display_melee, $display_range, $display_spell, $display_caster_stats, $display_defense, $display_pvp, $display_titles, $display_gear, $display_professions, $display_achievments, $display_statistics, $display_reputation, $display_last_update_string, $display_glyphs );

		// Check for cachefile
		$cachefile = $this -> _cache_get_filename();
		if( file_exists( $cachefile ) && !$this -> _cache_recreate( $cachefile ) && !empty( $this -> cache_timeout ) )
		{
			$this -> content = file_get_contents( $cachefile );
			return true;
		}

		// Set Host and URIs
		$host = "$rt.wowarmory.com";
		$uri_character = "/character-sheet.xml?r=$server&n=$character";
		$uri_achievments = "/character-achievements.xml?r=$server&n=$character";
		$uri_reputation = "/character-reputation.xml?r=$server&n=&character";
		$uri_statistics = "/character-statistics.xml?r=$server&n=&character&c=141";

		// Read from armory
		if( $body_c = $this -> _http_read( $host, $uri_character ) )
		{
			// Get and parse XML
			if( preg_match( '#<characterInfo.*>.*</characterInfo>#siU', $body_c, $match ) )
			{
				$parser = xml_parser_create();
				xml_parse_into_struct( $parser, $match[ 0 ], $vals, $index );
				xml_parser_free( $parser );
				unset( $body_c );
				unset( $match );
				if( $index )
				{
					$char_info = $vals[ $index[ 'CHARACTERINFO' ][ 0 ] ][ 'attributes' ];
					if( $char_info[ 'ERRCODE' ] != 'noCharacter' )
					{
						// 2hand or 1hand?
						$twohand = true;
						foreach( $index[ 'ITEM' ] AS $items )
						{
							if( $vals[ $items ][ 'attributes' ][ 'SLOT' ] == '16' )
							{
								$twohand = false;
								break;
							}
						}

						// Surrounding div
						$this -> content .= "<div class=\"wow_character wow_character-$character\">";

						// 3D Armory flash object
						if( $display_3darmory )
						{
							$display_3darmory_width = empty( $display_3darmory_width ) ? 150 : $display_3darmory_width;
							$display_3darmory_height = empty( $display_3darmory_height ) ? 150 : $display_3darmory_height;
							// eu,us,tw,kr,cn
							$zone = $rt == 'www' ? 'us' : $rt;
							$this -> content .= "<div class=\"3darmory_object\">";
							$this -> content .= "<script type=\"text/javascript\" src=\"http://www.3darmory.com/api/toon/$zone/$server/$character/$display_3darmory_width/$display_3darmory_height\"></script>";
							$this -> content .= "</div>";
							$this -> content .= "\n<style type=\"text/css\">.toon_layer a { display:none }</style>\n";
						}

						// Basic infos
						$char_attr = $vals[ $index[ 'CHARACTER' ][ 0 ] ][ 'attributes' ];
						$spec_attr1 = $vals[ $index[ 'TALENTSPEC' ][ 0 ] ][ 'attributes' ];
						if( count( $index[ 'TALENTSPEC' ] ) > 1 )
							$spec_attr2 = $vals[ $index[ 'TALENTSPEC' ][ 1 ] ][ 'attributes' ];
						else
							$spec_attr2 = false;
						$this -> content .= "<div class=\"armory_char_image\"><img src=\"http://$rt.wowarmory.com/images/portraits/wow" . ($char_attr[ 'LEVEL' ] < 70 ? '' : ($char_attr[ 'LEVEL' ] < 80 ? '-70' : '-80')) . "/" . $char_attr[ 'GENDERID' ] . "-" . $char_attr[ 'RACEID' ] . "-" . $char_attr[ 'CLASSID' ] . ".gif\" width=\"48\" class=\"armory_char_prof\" /></div>";
						$this -> content .= "<div class=\"armory_char_name\"><a href=\"http://$rt.wowarmory.com/character-sheet.xml?r=$server&n=$character\">" . ($char_attr[ 'PREFIX' ] ? $char_attr[ 'PREFIX' ] . ' ' : '') . $char_attr[ 'NAME' ] . ($char_attr[ 'SUFFIX' ] ? $char_attr[ 'SUFFIX' ] . ' ' : '') . "</a></div>";
						$this -> content .= "<div class=\"armory_char_guildname\"><a href=\"http://$rt.wowarmory.com/guild-info.xml?" . $char_attr[ 'GUILDURL' ] . "\">" . $char_attr[ 'GUILDNAME' ] . "</a></div>";
						$this -> content .= "<div class=\"armory_char_faction\">" . $char_attr[ 'FACTION' ] . "</div>";
						$this -> content .= "<div class=\"armory_char_realm\">" . $char_attr[ 'REALM' ] . " (" . ($rt == 'eu' ? 'EU' : 'US') . ")</div>";
						$this -> content .= "<div class=\"armory_char_levelraceclass\">" . __( "Level", "wowcd" ) . " " . $char_attr[ 'LEVEL' ] . " " . $char_attr[ 'RACE' ] . " " . $char_attr[ 'CLASS' ] . "</div>";
						if( $spec_attr2 == false )
						{
							$this -> content .= "<div class=\"armory_char_specced\">" . __( "Talents", "wowcd" ) . ": " . $spec_attr1[ 'TREEONE' ] . "/" . $spec_attr1[ 'TREETWO' ] . "/" . $spec_attr1[ 'TREETHREE' ] . " (" . $spec_attr1[ 'PRIM' ] . ")</div>";
						}
						else
						{
							// Order specs by talent spec group
							if( $spec_attr1[ 'GROUP' ] == '2' )
							{
								$tmp_spec_attr = $spec_attr2;
								$spec_attr2 = $spec_attr1;
								$spec_attr1 = $tmp_spec_attr;
								unset( $tmp_spec_attr );
							}
							$this -> content .= "<div class=\"armory_char_specced\">" . __( "1st talents", "wowcd" ) . ": " . $spec_attr1[ 'TREEONE' ] . "/" . $spec_attr1[ 'TREETWO' ] . "/" . $spec_attr1[ 'TREETHREE' ] . " (" . $spec_attr1[ 'PRIM' ] . ")</div>";
							$this -> content .= "<div class=\"armory_char_specced\">" . __( "2nd talents", "wowcd" ) . ": " . $spec_attr2[ 'TREEONE' ] . "/" . $spec_attr2[ 'TREETWO' ] . "/" . $spec_attr2[ 'TREETHREE' ] . " (" . $spec_attr2[ 'PRIM' ] . ")</div>";
						}
						if( $display_basic_stats )
						{
							// Base stats
							$this -> content .= "<div class=\"armory_basestats\">";
							$this -> content .= "<h4>" . __( "Base stats", "wowcd" ) . "</h4>";
							$this -> content .= "<ul class=\"armory_basestat_list\">";
							$health = $vals[ $index[ 'HEALTH' ][ 0 ] ][ 'attributes' ];
							$mana = $vals[ $index[ 'SECONDBAR' ][ 0 ] ][ 'attributes' ];
							$strength = $vals[ $index[ 'STRENGTH' ][ 0 ] ][ 'attributes' ];
							$agility = $vals[ $index[ 'AGILITY' ][ 0 ] ][ 'attributes' ];
							$stamina = $vals[ $index[ 'STAMINA' ][ 0 ] ][ 'attributes' ];
							$intellect = $vals[ $index[ 'INTELLECT' ][ 0 ] ][ 'attributes' ];
							$spirit = $vals[ $index[ 'SPIRIT' ][ 0 ] ][ 'attributes' ];
							$armor = $vals[ $index[ 'ARMOR' ][ 0 ] ][ 'attributes' ];
							$agility = $vals[ $index[ 'AGILITY' ][ 0 ] ][ 'attributes' ];
							$this -> content .= "<li class=\"armory_basestat_item\">" . __( "Health", "wowcd" ) . ": " . $health[ 'EFFECTIVE' ] . "</li>";
							if( $mana[ 'TYPE' ] == 'm' ) // Only display mana, if mana class (energy and runepower is not worth to know)
								$this -> content .= "<li class=\"armory_basestat_item\">" . __( "Mana", "wowcd" ) . ": " . $mana[ 'EFFECTIVE' ] . "</li>";
							$this -> content .= "<li class=\"armory_basestat_item\">" . __( "Agility", "wowcd" ) . ": " . $agility[ 'EFFECTIVE' ] . "</li>";
							$this -> content .= "<li class=\"armory_basestat_item\">" . __( "Stamina", "wowcd" ) . ": " . $stamina[ 'EFFECTIVE' ] . "</li>";
							$this -> content .= "<li class=\"armory_basestat_item\">" . __( "Intellect", "wowcd" ) . ": " . $intellect[ 'EFFECTIVE' ] . "</li>";
							$this -> content .= "<li class=\"armory_basestat_item\">" . __( "Spirit", "wowcd" ) . ": " . $spirit[ 'EFFECTIVE' ] . "</li>";
							$this -> content .= "<li class=\"armory_basestat_item\">" . __( "Armor", "wowcd" ) . ": " . $armor[ 'EFFECTIVE' ] . "</li>";
							$this -> content .= "<li class=\"armory_basestat_item\">" . __( "Agility", "wowcd" ) . ": " . $agility[ 'EFFECTIVE' ] . "</li>";
							$this -> content .= "</ul>";
							$this -> content .= "</div>";
						}
						if( $display_resistances )
						{
							// Resistances
							$this -> content .= "<div class=\"armory_resistances\">";
							$this -> content .= "<h4>" . __( "Resistances", "wowcd" ) . "</h4>";
							$this -> content .= "<ul class=\"armory_resistance_list\">";
							$arcane = $vals[ $index[ 'ARCANE' ][ 0 ] ][ 'attributes' ];
							$fire = $vals[ $index[ 'FIRE' ][ 0 ] ][ 'attributes' ];
							$frost = $vals[ $index[ 'FROST' ][ 0 ] ][ 'attributes' ];
							$holy = $vals[ $index[ 'HOLY' ][ 0 ] ][ 'attributes' ];
							$nature = $vals[ $index[ 'NATURE' ][ 0 ] ][ 'attributes' ];
							$shadow = $vals[ $index[ 'SHADOW' ][ 0 ] ][ 'attributes' ];
							$this -> content .= "<li class=\"armory_resistance_item\">" . __( "Arcane", "wowcd" ) . ": " . $arcane[ 'VALUE' ] . "</li>";
							$this -> content .= "<li class=\"armory_resistance_item\">" . __( "Fire", "wowcd" ) . ": " . $fire[ 'VALUE' ] . "</li>";
							$this -> content .= "<li class=\"armory_resistance_item\">" . __( "Frost", "wowcd" ) . ": " . $frost[ 'VALUE' ] . "</li>";
							$this -> content .= "<li class=\"armory_resistance_item\">" . __( "Holy", "wowcd" ) . ": " . $holy[ 'VALUE' ] . "</li>";
							$this -> content .= "<li class=\"armory_resistance_item\">" . __( "Nature", "wowcd" ) . ": " . $nature[ 'VALUE' ] . "</li>";
							$this -> content .= "<li class=\"armory_resistance_item\">" . __( "Shadow", "wowcd" ) . ": " . $shadow[ 'VALUE' ] . "</li>";
							$this -> content .= "</ul>";
							$this -> content .= "</div>";
						}
						if( $display_melee )
						{
							// Melee damage
							$this -> content .= "<div class=\"armory_melee\">";
							$this -> content .= "<h4>" . __( "Melee", "wowcd" ) . "</h4>";
							$this -> content .= "<ul class=\"armory_melee_list\">";
							$mainhanddamage = $vals[ $index[ 'MAINHANDDAMAGE' ][ 0 ] ][ 'attributes' ];
							$offhanddamage = $vals[ $index[ 'OFFHANDDAMAGE' ][ 0 ] ][ 'attributes' ];
							$mainhandspeed = $vals[ $index[ 'MAINHANDSPEED' ][ 0 ] ][ 'attributes' ];
							$offhandspeed = $vals[ $index[ 'OFFHANDSPEED' ][ 0 ] ][ 'attributes' ];
							$power = $vals[ $index[ 'POWER' ][ 0 ] ][ 'attributes' ];
							$hitrating = $vals[ $index[ 'HITRATING' ][ 0 ] ][ 'attributes' ];
							$critchance = $vals[ $index[ 'CRITCHANCE' ][ 0 ] ][ 'attributes' ];
							$expertise = $vals[ $index[ 'EXPERTISE' ][ 0 ] ][ 'attributes' ];
							$this -> content .= "<li class=\"armory_melee_item\">" . __( "Main hand", "wowcd" ) . ": " . $mainhanddamage[ 'MAX' ] . " (" . __( "max", "wowcd" ) . ") " . $mainhanddamage[ 'MIN' ] . " (" . __( "min", "wowcd" ) . ") " . $mainhanddamage[ 'DPS' ] . " (" . __( "dps", "wowcd" ) . ") " . $mainhanddamage[ 'SPEED' ] . " (" . __( "speed", "wowcd" ) . ")</li>";
							if( !$twohand )
								$this -> content .= "<li class=\"armory_melee_item\">" . __( "Off hand", "wowcd" ) . ": " . $offhanddamage[ 'MAX' ] . " (" . __( "max", "wowcd" ) . ") " . $offhanddamage[ 'MIN' ] . " (" . __( "min", "wowcd" ) . ") " . $offhanddamage[ 'DPS' ] . " (" . __( "dps", "wowcd" ) . ") " . $offhanddamage[ 'SPEED' ] . " (" . __( "speed", "wowcd" ) . ")</li>";
							$this -> content .= "<li class=\"armory_melee_item\">" . __( "Main hand speed", "wowcd" ) . ": " . $mainhandspeed[ 'VALUE' ] . ", " . $mainhandspeed[ 'HASTEPERCENT' ] . "% " . __( "haste", "wowcd" ) . "</li>";
							if( !$twohand )
								$this -> content .= "<li class=\"armory_melee_item\">" . __( "Off hand speed", "wowcd" ) . ": " . $offhandspeed[ 'VALUE' ] . ", " . $offhandspeed[ 'HASTEPERCENT' ] . "% " . __( "haste", "wowcd" ) . "</li>";
							$this -> content .= "<li class=\"armory_melee_item\">" . __( "Power", "wowcd" ) . ": " . $power[ 'EFFECTIVE' ] . "</li>";
							$this -> content .= "<li class=\"armory_melee_item\">" . __( "Hit rating", "wowcd" ) . ": " . $hitrating[ 'INCREASEDHITPERCENT' ] . "% (" . $hitrating[ 'VALUE' ] . ")</li>";
							$this -> content .= "<li class=\"armory_melee_item\">" . __( "Critical chance", "wowcd" ) . ": " . $critchance[ 'PERCENT' ] . "%</li>";
							$this -> content .= "<li class=\"armory_melee_item\">" . __( "Expertise rating", "wowcd" ) . ": " . $expertise[ 'PERCENT' ] . "%</li>";
							$this -> content .= "</ul>";
							$this -> content .= "</div>";
						}
						if( $display_range )
						{
							// Range damage
							$this -> content .= "<div class=\"armory_range\">";
							$this -> content .= "<h4>" . __( "Range", "wowcd" ) . "</h4>";
							$this -> content .= "<ul class=\"armory_range_list\">";
							$weaponskill = $vals[ $index[ 'WEAPONSKILL' ][ 0 ] ][ 'attributes' ];
							$damage = $vals[ $index[ 'DAMAGE' ][ 0 ] ][ 'attributes' ];
							$speed = $vals[ $index[ 'SPEED' ][ 0 ] ][ 'attributes' ];
							$power = $vals[ $index[ 'POWER' ][ 0 ] ][ 'attributes' ];
							$hitrating = $vals[ $index[ 'HITRATING' ][ 1 ] ][ 'attributes' ];
							$critchance = $vals[ $index[ 'CRITCHANCE' ][ 1 ] ][ 'attributes' ];
							$this -> content .= "<li class=\"armory_range_item\">" . __( "Weapon skill", "wowcd" ) . ": " . $weaponskill[ 'VALUE' ] . "</li>";
							$this -> content .= "<li class=\"armory_melee_item\">" . __( "Damage", "wowcd" ) . ": " . $damage[ 'MAX' ] . " (" . __( "max", "wowcd" ) . ") " . $damage[ 'MIN' ] . " (" . __( "min", "wowcd" ) . ") " . $damage[ 'DPS' ] . " (" . __( "dps", "wowcd" ) . ") " . $damage[ 'SPEED' ] . " (" . __( "speed", "wowcd" ) . ")</li>";
							$this -> content .= "<li class=\"armory_range_item\">" . __( "Speed", "wowcd" ) . ": " . $speed[ 'VALUE' ] . "</li>";
							$this -> content .= "<li class=\"armory_range_item\">" . __( "Power", "wowcd" ) . ": " . $power[ 'EFFECTIVE' ] . "</li>";
							$this -> content .= "<li class=\"armory_range_item\">" . __( "Hit rating", "wowcd" ) . ": " . $hitrating[ 'INCREASEDHITPERCENT' ] . "% (" . $hitrating[ 'VALUE' ] . ")</li>";
							$this -> content .= "<li class=\"armory_range_item\">" . __( "Critical chance", "wowcd" ) . ": " . $critchance[ 'PERCENT' ] . "%</li>";
							$this -> content .= "</ul>";
							$this -> content .= "</div>";
						}
						if( $display_spell )
						{
							// Spell damage
							$this -> content .= "<div class=\"armory_spelldamage\">";
							$this -> content .= "<h4>" . __( "Spell damage", "wowcd" ) . "</h4>";
							$this -> content .= "<ul class=\"armory_spelldamage_list\">";
							$arcane = $vals[ $index[ 'ARCANE' ][ 1 ] ][ 'attributes' ];
							$fire = $vals[ $index[ 'FIRE' ][ 1 ] ][ 'attributes' ];
							$frost = $vals[ $index[ 'FROST' ][ 1 ] ][ 'attributes' ];
							$holy = $vals[ $index[ 'HOLY' ][ 1 ] ][ 'attributes' ];
							$nature = $vals[ $index[ 'NATURE' ][ 1 ] ][ 'attributes' ];
							$shadow = $vals[ $index[ 'SHADOW' ][ 1 ] ][ 'attributes' ];
							$crit_arcane = $vals[ $index[ 'ARCANE' ][ 2 ] ][ 'attributes' ];
							$crit_fire = $vals[ $index[ 'FIRE' ][ 2 ] ][ 'attributes' ];
							$crit_frost = $vals[ $index[ 'FROST' ][ 2 ] ][ 'attributes' ];
							$crit_holy = $vals[ $index[ 'HOLY' ][ 2 ] ][ 'attributes' ];
							$crit_nature = $vals[ $index[ 'NATURE' ][ 2 ] ][ 'attributes' ];
							$crit_shadow = $vals[ $index[ 'SHADOW' ][ 2 ] ][ 'attributes' ];
							// Group +dmg by value
							$spelldmg = array();
							$spelldmg[ $arcane[ 'VALUE' ] ][] = array( 'dmgtype' => 'Arcane', 'dmg' => $arcane[ 'VALUE' ], 'crit' => $crit_arcane[ 'PERCENT' ] );
							$spelldmg[ $fire[ 'VALUE' ] ][] = array( 'dmgtype' => 'Fire', 'dmg' => $fire[ 'VALUE' ], 'crit' => $crit_fire[ 'PERCENT' ] );
							$spelldmg[ $frost[ 'VALUE' ] ][] = array( 'dmgtype' => 'Frost', 'dmg' => $frost[ 'VALUE' ], 'crit' => $crit_frost[ 'PERCENT' ] );
							$spelldmg[ $holy[ 'VALUE' ] ][] = array( 'dmgtype' => 'Holy', 'dmg' => $holy[ 'VALUE' ], 'crit' => $crit_holy[ 'PERCENT' ] );
							$spelldmg[ $nature[ 'VALUE' ] ][] = array( 'dmgtype' => 'Nature', 'dmg' => $nature[ 'VALUE' ], 'crit' => $crit_nature[ 'PERCENT' ] );
							$spelldmg[ $shadow[ 'VALUE' ] ][] = array( 'dmgtype' => 'Shadow', 'dmg' => $shadow[ 'VALUE' ], 'crit' => $crit_shadow[ 'PERCENT' ] );
							foreach( $spelldmg AS $plusdmg => $damages )
							{
								// If damage bonus is all the same
								if( count( $damages ) == 6 )
								{
									$this -> content .= "<li class=\"armory_spelldamage_item\">" . __( "Damagebonus", "wowcd" ) . ": " . $arcane[ 'VALUE' ] . " (" . $crit_arcane[ 'PERCENT' ] . "% " . __( "crit", "wowcd" ) . ")</li>";
								}
								// If damage bonus is not all the same
								else
								{
									$this -> content .= "<li class=\"armory_spelldamage_item\">";
									$damagetypes = array();
									foreach( $damages AS $dtypekey => $dtypearr )
									{
										$damagebonus = $dtypearr[ 'dmg' ];
										$damagecrit = $dtypearr[ 'crit' ];
										if( $dtypearr[ 'dmgtype' ] == 'Arcane' )
											$damagetypes[] = __( "Arcane", "wowcd" );
										if( $dtypearr[ 'dmgtype' ] == 'Fire' )
											$damagetypes[] = __( "Fire", "wowcd" );
										if( $dtypearr[ 'dmgtype' ] == 'Frost' )
											$damagetypes[] = __( "Frost", "wowcd" );
										if( $dtypearr[ 'dmgtype' ] == 'Holy' )
											$damagetypes[] = __( "Holy", "wowcd" );
										if( $dtypearr[ 'dmgtype' ] == 'Nature' )
											$damagetypes[] = __( "Nature", "wowcd" );
										if( $dtypearr[ 'dmgtype' ] == 'Shadow' )
											$damagetypes[] = __( "Shadow", "wowcd" );
									}
									$this -> content .= implode( ", ", $damagetypes );
									$this -> content .=  ": " . $damagebonus . " (" . $damagecrit . "% " . __( "crit", "wowcd" ) . ")";
									$this -> content .= "</li>";
								}
							}
							$this -> content .= "</ul>";
							$this -> content .= "</div>";
						}
						if( $display_caster_stats )
						{
							// Caster stats
							$this -> content .= "<div class=\"armory_casterstats\">";
							$this -> content .= "<h4>" . __( "Caster stats", "wowcd" ) . "</h4>";
							$this -> content .= "<ul class=\"armory_casterstats_list\">";
							$bonushealing = $vals[ $index[ 'BONUSHEALING' ][ 0 ] ][ 'attributes' ];
							$manaregen = $vals[ $index[ 'MANAREGEN' ][ 0 ] ][ 'attributes' ];
							$hasterating = $vals[ $index[ 'HASTERATING' ][ 0 ] ][ 'attributes' ];
							$hitrating = $vals[ $index[ 'HITRATING' ][ 2 ] ][ 'attributes' ];
							$penetration = $vals[ $index[ 'PENETRATION' ][ 0 ] ][ 'attributes' ];
							$this -> content .= "<li class=\"armory_casterstats_item\">" . __( "Bonus heal", "wowcd" ) . ": " . $bonushealing[ 'VALUE' ] . " </li>";
							$this -> content .= "<li class=\"armory_casterstats_item\">" . __( "Mana reg", "wowcd" ) . ": " . $manaregen[ 'NOTCASTING' ] . " (" . __( "not casting", "wowcd" ) . ") " . $manaregen[ 'CASTING' ] . " (" . __( "casting", "wowcd" ) . ")</li>";
							$this -> content .= "<li class=\"armory_casterstats_item\">" . __( "Haste rating", "wowcd" ) . ": " . $hasterating[ 'HASTERATING' ] . "</li>";
							$this -> content .= "<li class=\"armory_casterstats_item\">" . __( "Hit rating", "wowcd" ) . ": " . $hitrating[ 'INCREASEDHITPERCENT' ] . "% (" . $hitrating[ 'VALUE' ] . ")</li>";
							$this -> content .= "<li class=\"armory_casterstats_item\">" . __( "Penetrating rating", "wowcd" ) . ": " . $penetration[ 'VALUE' ] . "</li>";
							$this -> content .= "</ul>";
							$this -> content .= "</div>";
						}
						if( $display_defense )
						{
							// Defense
							$this -> content .= "<div class=\"armory_defenses\">";
							$this -> content .= "<h4>" . __( "Defense", "wowcd" ) . "</h4>";
							$this -> content .= "<ul class=\"armory_defense_list\">";
							$armor = $vals[ $index[ 'ARMOR' ][ 1 ] ][ 'attributes' ];
							$defense = $vals[ $index[ 'DEFENSE' ][ 0 ] ][ 'attributes' ];
							$dodge = $vals[ $index[ 'DODGE' ][ 0 ] ][ 'attributes' ];
							$parry = $vals[ $index[ 'PARRY' ][ 0 ] ][ 'attributes' ];
							$block = $vals[ $index[ 'BLOCK' ][ 0 ] ][ 'attributes' ];
							$resilience = $vals[ $index[ 'RESILIENCE' ][ 0 ] ][ 'attributes' ];
							$this -> content .= "<li class=\"armory_defense_item\">" . __( "Armor", "wowcd" ) . ": " . $armor[ 'EFFECTIVE' ] . "</li>";
							$this -> content .= "<li class=\"armory_defense_item\">" . __( "Defense", "wowcd" ) . ": " . $defense[ 'VALUE' ] . "</li>";
							$this -> content .= "<li class=\"armory_defense_item\">" . __( "Dodge", "wowcd" ) . ": " . $dodge[ 'PERCENT' ] . "</li>";
							$this -> content .= "<li class=\"armory_defense_item\">" . __( "Parry", "wowcd" ) . ": " . $parry[ 'PERCENT' ] . "</li>";
							$this -> content .= "<li class=\"armory_defense_item\">" . __( "Block", "wowcd" ) . ": " . $block[ 'PERCENT' ] . "</li>";
							$this -> content .= "<li class=\"armory_defense_item\">" . __( "Resilience", "wowcd" ) . ": " . $resilience[ 'VALUE' ] . "</li>";
							$this -> content .= "</ul>";
							$this -> content .= "</div>";
						}
						if( $display_pvp )
						{
							// PvP
							$this -> content .= "<div class=\"armory_pvps\">";
							$this -> content .= "<h4>" . __( "PvP", "wowcd" ) . "</h4>";
							$this -> content .= "<ul class=\"armory_pvp_list\">";
							$lifetimehonorablekills = $vals[ $index[ 'LIFETIMEHONORABLEKILLS' ][ 0 ] ][ 'attributes' ];
							$arenacurrency = $vals[ $index[ 'ARENACURRENCY' ][ 0 ] ][ 'attributes' ];
							$this -> content .= "<li class=\"armory_pvp_item\">" . __( "Honorable kills", "wowcd" ) . ": " . $lifetimehonorablekills[ 'VALUE' ] . "</li>";
							$this -> content .= "<li class=\"armory_pvp_item\">" . __( "Arena currency", "wowcd" ) . ": " . $arenacurrency[ 'VALUE' ] . "</li>";
							$this -> content .= "</ul>";
							$this -> content .= "</div>";
						}
						if( $display_titles )
						{
							// Titles
							$this -> content .= "<div class=\"armory_titles\">";
							$this -> content .= "<h4>" . __( "Known titles", "wowcd" ) . "</h4>";
							$this -> content .= "<ul class=\"armory_title_list\">";
							$tcount = 0;
							foreach( $index[ 'TITLE' ] AS $title )
							{
								if( $tcount > 0 )
									$this -> content .= "<li class=\"armory_title_item\">" . str_replace( ' %s', '', $vals[ $title ][ 'attributes' ][ 'VALUE' ] ) . "</li>";
								$tcount++;
							}
							if( $tcount == 1 )
								$this -> content .= "<li class=\"armory_title_item\">" . __( "none", "wowcd" ) . "</li>";
							$this -> content .= "</ul>";
							$this -> content .= "</div>";
						}
						if( $display_gear )
						{
							// Gear
							$this -> content .= "<div class=\"armory_gear\">";
							$this -> content .= "<h4>" . __( "Equipment", "wowcd" ) . "</h4>";
							$this -> content .= "<ul class=\"armory_gear_list\">";
							foreach( $index[ 'ITEM' ] AS $items )
							{
								$item = $vals[ $items ][ 'attributes' ];
								if( $item[ 'SLOT' ] >= 0 )
								{
									$enchants = "lvl=" . $char_attr[ 'LEVEL' ];
									if( $item[ 'PERMANENTENCHANT' ] != '0' )
										$enchants .= ";ench=" . $item[ 'PERMANENTENCHANT' ];
									if ($item[ 'RANDOMPROPERTIESID' ] != '0' )
										$enchants .= ";rand=" . $item[ 'RANDOMPROPERTIESID' ];
									if ($item[ 'GEM0ID' ] != '0' || $item[ 'GEM1ID' ] != "0" || $item[ 'GEM1ID' ] != '0' )
									{
										$enchants .= ";gems=";
										if ($item[ 'GEM0ID' ] != '0' )
											$enchants .= $item[ 'GEM0ID' ];
										if ($item[ 'GEM1ID' ] != '0' )
											$enchants .= ":" . $item[ 'GEM1ID' ];
										if ($item[ 'GEM2ID' ] != '0' )
											$enchants .= ":" . $item[ 'GEM2ID' ];
									}
									$this -> content .= "<li class=\"armory_gear_item\">";
									$this -> content .= "<a href=\"http://$wl.wowhead.com/?item=" . $item[ 'ID' ] . "\"" . (($enchants) ? " rel=\"" . $enchants . "\"" : "") . ">";
									$this -> content .= "<img src=\"http://$rt.wowarmory.com/wow-icons/_images/51x51/" . $item[ 'ICON' ] . ".jpg\" width=\"26\" border=\"0\" class=\"armory_item_icon\"/>";
									$this -> content .= "</a>";
									$this -> content .= "</li>";
								}
							}
							$this -> content .= "</ul>";
							$this -> content .= "</div>";
						}
						if( $display_glyphs )
						{
							if( is_array( $index[ 'GLYPH' ] ) )
							{
								$this -> content .= "<div class=\"armory_glyph\">";
								$this -> content .= "<h4>" . __( "Glyphs", "wowcd" ) . "</h4>";
								$this -> content .= "<ul class=\"armory_glyph_list\">";
								// Glyphs grouped by type
								$gtypes = array( 'major', 'minor' );
								foreach( $gtypes AS $gtype )
								foreach( $index[ 'GLYPH' ] AS $glyphs )
								{
									$glyph = $vals[ $glyphs ][ 'attributes' ];
									if( $glyph[ 'TYPE' ] == $gtype )
									{
										$this -> content .= "<li class=\"armory_glyph_item\">";
										$this -> content .= "<a href=\"http://www.google.com/cse?cx=013064850417053219827:k8henqhurj4&q=site:$wl.wowhead.com " . urlencode( $glyph[ 'NAME' ] ) . "\" title=\"" . $glyph[ 'EFFECT' ] . "\" style=\"text-decoration:none\">";
										$this -> content .= "<img src=\"http://$rt.wowarmory.com/wow-icons/_images/21x21/inv_glyph_" . $gtype . $this -> class_natural[ $char_attr[ 'CLASSID' ] ] . ".png\" width=\"18\" border=\"0\" class=\"armory_item_icon\"/>" . $glyph[ 'NAME' ];
										$this -> content .= "</a>";
										$this -> content .= "</li>";
									}
								}
								$this -> content .= "</ul>";
								$this -> content .= "</div>";
							}
						}
						if( $display_professions )
						{
							// Professions
							$prof_1 = $vals[ $index[ 'SKILL' ][ 0 ] ][ 'attributes' ];
							$prof_2 = $vals[ $index[ 'SKILL' ][ 1 ] ][ 'attributes' ];
							if( $prof_1 != NULL || $prof_2 != NULL )
							{
								$this -> content .= "<div class=\"armory_professions\">";
								$this -> content .= "<h4>" . __( "Professions", "wowcd" ) . "</h4>";
								$this -> content .= "<ul class=\"armory_profession_list\">";
								if( $prof_1 != NULL )
								{
									$this -> content .= "<li class=\"armory_profession_item\">";
									$this -> content .= "<img src=\"http://static.wowhead.com/images/icons/small/" . $this -> prof_img[ $prof_1[ 'NAME' ] ] . ".jpg\" width=\"20\" />";
									$this -> content .= "<span class=\"armory_profession_name\">" . $prof_1[ 'NAME' ] . ":</span> " . $prof_1[ 'VALUE' ] . "/" . $prof_1[ 'MAX' ];
									$this -> content .= "</li>";
								}
								if( $prof_2 != NULL )
								{
									$this -> content .= "<li class=\"armory_profession_item\">";
									$this -> content .= "<img src=\"http://static.wowhead.com/images/icons/small/" . $this -> prof_img[ $prof_2[ 'NAME' ] ] . ".jpg\" width=\"20\" />";
									$this -> content .= "<span class=\"armory_profession_name\">" . $prof_2[ 'NAME' ] . ":</span> " . $prof_2[ 'VALUE' ] . "/" . $prof_2[ 'MAX' ];
									$this -> content .= "</li>";
								}
								$this -> content .= '</ul>';
								$this -> content .= "</div>";
							}
						}
						if( $display_achievments )
						{
							// Achievements
							if( $body_a = $this -> _http_read( $host, $uri_achievments ) )
							{
								// Get and parse XML
								if( preg_match( '#<achievements.*>.*</achievements>#siU', $body_a, $match ) )
								{
									$parser_achiev = xml_parser_create();
									xml_parse_into_struct( $parser_achiev, $match[ 0 ], $vals_a, $index_a );
									xml_parser_free( $parser_achiev );
									unset( $body_a );
									unset( $match );
									$this -> content .= "<div class=\"armory_achievements\">";
									$this -> content .= "<h4>" . __( "Recent Achievements", "wowcd" ) . "</h4>";
									$this -> content .= "<ul class=\"armory_achievement_list\">";
									if( count( $index_a[ 'ACHIEVEMENT' ] ) )
									{
										foreach( $index_a[ 'ACHIEVEMENT' ] AS $achs )
										{
											$ach = $vals_a[ $achs ][ 'attributes' ];
											$this -> content .= "<li class=\"armory_achievment_item\"><a href=\"http://$wl.wowhead.com/?achievement=" . $ach[ 'ID' ] . "\"><img src=\"http://$rt.wowarmory.com/wow-icons/_images/51x51/" . $ach[ 'ICON' ] . ".jpg\" width=\"20\" border=\"0\" class=\"armory_achievment_icon\"/>" . $ach[ 'TITLE' ] . "</a></li>";
										}
									}
									else	$this -> content .= "<li class=\"armory_achievment_item\">" . __( "none", "wowcd" ) . "</li>";
									$this -> content .= "</ul>";
									$this -> content .= "<div class=\"armory_achievement_points\">" . __( "Points", "wowcd" ) . ": " . $char_attr[ 'POINTS' ] . "</div>";
									$this -> content .= "</div>";
								}
							}
						}
						if( $display_statistics )
						{
							// Statistics
							if( $body_s = $this -> _http_read( $host, $uri_statistics ) )
							{
								// Get and parse XML
								if( preg_match( '#<category.*>.*</category>#siU', $body_s, $match ) )
								{
									$parser_stats = xml_parser_create();
									xml_parse_into_struct( $parser_stats, $match[ 0 ], $vals_s, $index_s );
									xml_parser_free( $parser_stats );
									unset( $body_s );
									unset( $match );
									$this -> content .= "<div class=\"armory_fightstats\">";
									$this -> content .= "<h4>" . __( "Fighting stats", "wowcd" ) . "</h4>";
									$this -> content .= "<ul class=\"armory_fightstat_list\">";
									foreach( $index_s[ 'STATISTIC' ] AS $stats )
									{
										$stat = $vals_s[ $stats ][ 'attributes' ];
										$this -> content .= "<li class=\"armory_fightstat_item\">" . $stat[ 'NAME' ] . ": " . $stat[ 'QUANTITY' ] . "</li>";
									}
									$this -> content .= "</ul>";
									$this -> content .= "</div>";
								}
							}
						}
						if( $display_reputation )
						{
							// Reputation
							if( $body_r = $this -> _http_read( $host, $uri_reputation ) )
							{
								// Get and parse XML
								if( preg_match( '#<reputationTab.*>.*</reputationTab>#siU', $body_r, $match ) )
								{
									$parser_reput = xml_parser_create();
									xml_parse_into_struct( $parser_reput, $match[ 0 ], $vals_r, $index_r );
									xml_parser_free( $parser_reput );
									unset( $body_r );
									unset( $match );
									$this -> content .= "<div class=\"armory_reputations\">";
									$this -> content .= "<h4>" . __( "Reputation", "wowcd" ) . "</h4>";
									$this -> content .= "<ul class=\"armory_reputation_list\">";
									// Load categorys
									$categorys = array();
									foreach( $index_r[ 'FACTIONCATEGORY' ] AS $category )
										if( !empty( $vals_r[ $category ][ 'attributes' ][ 'NAME' ] ) )
											$categorys[] = $vals_r[ $category ][ 'attributes' ][ 'NAME' ];
									$categorykey = 0;
									$categorieinit = true;
									$categorybreakkeys = array( 'thunderbluff', 'undercity', 'warsongoffensive', 'warsongoutriders', 'lowercity', 'ratchet', 'thrallmar' );
									foreach( $index_r[ 'FACTION' ] AS $faction )
									{
										$reput = $vals_r[ $faction ][ 'attributes' ];
										if( $reput[ 'REPUTATION' ] >= 0 )
										{
											if( ($categorykey == 0 && $categorieinit) || $reput[ 'KEY' ] == $categorybreakkeys[ $categorykey ] )
											{
												if( !$categorieinit != 0 )
													$this -> content .= "</ul></li>";
												if( $reput[ 'KEY' ] == $categorybreakkeys[ $categorykey ] )
													$categorykey++;
												$categorieinit = false;
												$this -> content .= "<li class=\"armory_reputation_item\">" . $categorys[ $categorykey ];
												$this -> content .= "<ul class=\"armory_reputation_list\">";
											}
											$this -> content .= "<li class=\"armory_reputation_item\">" . $reput[ 'NAME' ] . ": " . $reput[ 'REPUTATION' ] . "</li>";
										}
									}
									$this -> content .= "</ul></li>";
									$this -> content .= "</ul>";
									$this -> content .= "</div>";
								}
							}
						}
						// Last updated string
						if( $display_last_update_string )
							$this -> content .= "<div class=\"armory_char_lastmodified\">" . __( "Last update", "wowcd" ) . ": " . $char_attr[ 'LASTMODIFIED' ] . "</div>";
						// Close surrounding div
						$this -> content .= "</div>";
						// Send everything is okay
						return true;
					}
					else
					{
						$this -> error .= __( "Character not found.", "wowcd" );
						return false;
					}
				}
				else
				{
					$this -> error .= __( "The returned XML data is not well formed.", "wowcd" );
					return false;
				}
			}
			else
			{
				$this -> error .= __( "No character information returned.", "wowcd" );
				$this -> error .= "<br/><br/><a href=\"http://$host$uri_character\" target=\"_blank\">" . __( "Test armory display", "wowcd" ) . "</a>";
				return false;
			}
		}
		else
		{
			$this -> error .= __( "Sorry the WoW armory server didn't respond. The amount of requests you can do in a specific time period is restricted by Blizzard. Please wait a bit then delete the cache file for this character and try it again. Sorry, but that's a thing I can't change. Maybe write an email to Blizzard.", "wowcd" );
			$this -> error .= "<br/><br/><a href=\"http://$host$uri_character\" target=\"_blank\">" . __( "Test armory connection", "wowcd" ) . "</a>";
			return false;
		}
	}

	/**
	 * This method set a new locale profession key to the correct image
	 *
	 * Use UTF-8 for the new locale key!
	 */
	function set_prof_image_locale( $en_key, $locale_key )
	{
		$this -> prof_img[ $locale_key ] = $this -> prof_img[ $en_key ];
	}

	/**
	 * Do a valid http get request
	 *
	 * Requires PHP >= 4.3.0
	 */
	function _http_read( $host, $uri )
	{
		// Timeout
		$timeout = 10;

		// Check for cachefile
		$xml_cachefile = $this -> _cache_xml_get_filename( $uri );
		if( file_exists( $xml_cachefile ) && !$this -> _cache_recreate( $xml_cachefile ) && !empty( $this -> cache_timeout ) )
			return file_get_contents( $xml_cachefile );

		// Use cURL lib or make the request with file_get_contents
		if( function_exists( 'curl_init' ) )
		{
			$curl = curl_init();
			curl_setopt( $curl, CURLOPT_URL, "http://$host$uri" );
			curl_setopt( $curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_HTTPHEADER, array(	"Accept: text/xml,application/xml,application/xhtml+xml,*/*;q=0.1",
									"Accept-Language: " . strtolower( $this -> locale ),
									"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7"
								)
			);
			curl_setopt( $curl, CURLOPT_REFERER, "http://$host$uri&rnd=" . rand() );
			curl_setopt( $curl, CURLOPT_COOKIE, "cookieLangId=" . str_replace( '-', '_', strtolower( $this -> locale ) ) );
			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, false );
			curl_setopt( $curl, CURLOPT_FORBID_REUSE, true );
			curl_setopt( $curl, CURLOPT_LOW_SPEED_LIMIT, 5 );
			curl_setopt( $curl, CURLOPT_LOW_SPEED_TIME, $timeout );
			curl_setopt( $curl, CURLOPT_TIMEVALUE, $timeout * 3 );
			curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, $timeout );
			curl_setopt( $curl, CURLOPT_HEADER, false );
			$body = @curl_exec( $curl );
			curl_close( $curl );
			if( !$body )
				$this -> http_error = true;
			else
			if( !empty( $this -> cache_timeout ) && $this -> _cache_recreate( $xml_cachefile ) )
				$this -> _cache_xml_store( $uri, $body );
			usleep( $this -> sleep_counter );
			$this -> sleep_counter += 500000;
			return $body;
		}
		// Stream context + file_get_contents request
		else
		{
			$r = array(	'http' => array(
					'method' => "GET",
					'header' =>	"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.0.6) Gecko/2009011913 Firefox/3.0.6\r\n" .
							"Accept: text/xml,application/xml,application/xhtml+xml,*/*;q=0.1\r\n" .
							"Accept-Language: " . strtolower( $this -> locale ) . "\r\n" .
							"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n" .
							"Referer: http://$host$uri&rnd=" . rand() .
							"Cookie: cookieLangId=" . str_replace( '-', '_', strtolower( $this -> locale ) ) . "\r\n" .
							"Connection: close\r\n",
					'max_redirects' => 0,
					'ignore_errors' => true,
					'protocol_version' => 1.1,
					'timeout' => $timeout
				)
			);
			$c = stream_context_create( $r );
			$body = @file_get_contents( 'http://' . $host . $uri, false, $c );
			if( !$body )
				$this -> http_error = true;
			else
			if( !empty( $this -> cache_timeout ) && $this -> _cache_recreate( $xml_cachefile ) )
				$this -> _cache_xml_store( $uri, $body );
			usleep( $this -> sleep_counter );
			$this -> sleep_counter += 500000;
			return $body;
		}
	}

	/**
	 * Cache functions
	 */
	// Set cache hash for guild roster
	function _cache_set_hash_guild( $rt, $wl, $server, $guild, $from_level, $to_level, $display_member_count, $gfx_width )
	{
		// Set cache hash
		$this -> cache_hash = 'guildroster-' . str_replace( array( ' ', '+' ), '', $rt . '-' . $wl . '-' . $server . '-' . $guild . '-' . $from_level . '-' . $to_level . '-' . $gfx_width . '-' . $display_member_count );
	}

	// Set cache hash
	function _cache_set_hash( $rt, $wl, $server, $character, $display_basic_stats = 1, $display_resistances = 0, $display_melee = 0, $display_range = 0, $display_spell = 0, $display_caster_stats = 0, $display_defense = 0, $display_pvp = 0, $display_titles = 0, $display_gear = 1, $display_professions = 1, $display_achievments = 1, $display_statistics = 0, $display_reputation = 0, $display_last_update_string = 0, $display_glyphs = 0 )
	{
		// Set cache hash
		$this -> cache_hash = str_replace( array( ' ', '+' ), '', $rt . '-' . $wl . '-' . $server . '-' . $character . '-' . substr( md5( $display_basic_stats . '-' . $display_resistances . '-' . $display_melee . '-' . $display_range . '-' . $display_spell . '-' . $display_caster_stats . '-' . $display_defense . '-' . $display_pvp . '-' . $display_titles . '-' . $display_gear . '-' . $display_professions . '-' . $display_achievments . '-' . $display_statistics . '-' . $display_reputation . '-' . $display_last_update_string . '-' . $display_glyphs ), 0, 5 ) );
	}

	// Cache: Write cachefile
	function _cache_store()
	{
		if( is_writable( $this -> cache_dir ) )
			file_put_contents( $this -> _cache_get_filename(), $this -> content );
	}

	// Cache: Get cachefile path
	function _cache_get_filename()
	{
		$fname = 'wowcd.cache.' . $this -> cache_hash . '.html';
		return $this -> cache_dir . $fname;
	}

	// Cache: recreate html or deliver cached version
	function _cache_recreate( $cachefile )
	{
		if( file_exists( $cachefile ) && !empty( $this -> cache_timeout ) && is_writable( $this -> cache_dir ) )
			return ((filemtime( $cachefile ) < (time() - $this -> cache_timeout)) ? true : false);
		else
			return true;
	}

	/**
	 * Cache functions for XML data
	 */
	// XML cache: Get filename for cache file
	function _cache_xml_get_filename( $uri )
	{
		$uri = str_replace( array( '/', 'r=', 'n=', 'c=', 'p=' ), '', $uri );
		$fname = 'wowcd.cache.' . preg_replace( '/[^0-9a-z]/i', '-', strtolower( $uri ) ) . '.xml';
		return $this -> cache_dir . $fname;
	}

	// XML cache: store content
	function _cache_xml_store( $uri, $xml )
	{
		if( is_writable( $this -> cache_dir ) )
			file_put_contents( $this -> _cache_xml_get_filename( $uri ), $xml );
	}
}

?>