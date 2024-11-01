=== WoW-Character-Display ===
Contributors: Marc Schieferdecker, raufaser
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=m_schieferdecker%40hotmail%2ecom&item_name=article2pdf%20wp%20plugin&no_shipping=0&no_note=1&tax=0&currency_code=EUR&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: wow,armory,world of warcraft,character,blizzard,char,widget,post,3d,flash
Requires at least: 2.6
Tested up to: 2.8
Stable tag: 1.12.1

The WoW-Character-Display plugin shows many informations from the WoW armory in a widget or post. High configurable for single characters and guilds!

== Description ==

Basic features:

* New: Now with support for 3D Armory flash model viewer! Show your character like he look in World of Warcraft!
* Displays armory informations for **multiple World of Warcraft (WoW) characters** in posts, pages and in the sidebar (multi widget support)
* Display **multiple World of Warcraft (WoW) guild rosters** in posts, pages or in the sidebar (multi widget support)
* You can configure very detailed which informations were displayed for which character
* All armory regions are supported (EU/US/China/Korea/Taiwan)
* All armory output can be done with the available armory languages
* Multiple language support for the plugin is included, please help to translate and contact me
* Wowhead tooltips via javascript were included (DE/EN/RU/FR/ES)
* HTML code is cached to reduce the requests to the WoW armory
* Default CSS style is included, but you can turn that off by option and use own CSS code to format the output
* Integrated button for the TinyMCE html editor to add characters or guilds to a page or post

Demo output page: http://www.das-motorrad-blog.de/meine-world-of-warcraft-charaktere/

Usage (it's so easy):

1. Configure the basic options of the plugin to your needs (don't forget to enter the localised names for the professions!)
2. Add a wow character or a guild in the plugin options (just enter EU or US realm and character name)
3. Configure what is displayed for that character or guild
4. Show that character sheet or guild roster in a widget (add widget, select character/guild) or in a post (via shortcode)
5. If you want to add another guild or char goto 2! :-)

The plugin reads the informations from the armory (EU or US) and displayes it in a widget or in a post. The information from the armory is cached (XML data and the generated character sheet or guild roster), if you configure that, so that we don't have to a request to the armory every click on your blog.

The plugin also adds the wowhead javascript to show tooltips for items and achievments.

Important: Only characters with a minimum level of 10 are available in the wow armory.

Details on which information can be displayed with the current version if configured:

* Basic character informations (name, race, level, image, guild, title, 1st and 2nd talent specs, ...)
* Basic stats (hp, mana, strength, agi, ...)
* Resistances (arcane, fire, ...)
* Melee damage (2h, 1h, dps, hit, crit, power, ...)
* Range damage (damage, speed, dps, ...)
* Spell damage (arcance, frost, ..., crit, ...)
* Caster stats (+heal, manareg, haste, hit, penetration)
* Defense (armor, def, dodge, parry, block, ...)
* PvP (lifetime honorable kills, arena currency)
* Titles (complete list of your titles)
* Gear (all items you wear)
* Glyphs (glyphs you use)
* Professions (your profession skill)
* Achievements (your last achievements)
* Statistics (highest damage, damage total, ...)
* Reputation (your positive reputations by all factions)
* Date and time when the character in the armory was last updated

For the guild roster you can set the following options

* Display total member count of the guild
* Only display members with level higher than x
* Only display members with lowwer higher than y
* Choose image size of the character class images

All displayed informations were output localised like in the armory!

This plugin is inspired by the wow-armory plugin, but I want to write an own armory plugin, 'cause I want a cache for the armory data, I don't wan't to install the PHP cURL extension and I want a highly configurable plugin for multiple purposes and a guild roster.
Now I put this plugin into the repository and I hope you can participate.

Languages: English, Deutsch, Chinese


**FOR THE HORDE!**

Marc aka "raufaser"
(EU/Blackmoore/Horde/Lost Prophets)

== Installation ==

Just install the plugin and activate it. Then navigate to the plugin configuration page (you'll find it in the WordPress configuration menu) and do a basic setup.

After setting up the correct path for caching, the output language for armory data and the localized strings for the profession, add a character or guild by entering realm and guild-/charactername. If you entered all data correct and the character or guild is found in the armory a preview is printed.

Then configure what is displayed when the character or guild is shown in a post or a page by selecting the checkboxes just as you need it. That is very usefull, because maybe if you are a tank you wont display your healing bonus or you don't want to display all your bankchars in your guild roster. ;)

If you want to display the character or guild in your sidebar navigate to the widget menu and add the WoW character display widget to your sidebar. You can also add multiple widgets to the sidebar. Then choose a character or guild to display and select what is displayed by clicking the checkboxes just as you need it.

If you have problems, please respond here in the plugin repository or drop me an email.

Feature requests and bug reports welcome!

== Frequently Asked Questions ==

= Sorry the WoW armory server did not respond? =

Blizzard limit the amount of request one IP adress can send to the armory in a spezific time. I can understand that a bit: They don't want, that some scripts sucking masses of character data out of the armory. I can't change that. So if you got that error, wait a few minutes and then delete the character sheet cache (if file exists) file to try again. If valid XML data is returned by the armory, the data will be cached so that you can change the display options of your character without the need to reload the XML data from the armory.

Suggestion: Maybe it helps writing an E-Mail to the armory support and tell them that there is this cool WordPress plugin that needs to be able to do some more requests than a normal user. If just write enough people... :)

= Why should I not delete XML data from the cache? =

Read the topic above. So if you delete XML data in the cache and then delete your character sheet cache file the armory is contacted again to reload the XML from the armory server. Another request is done. But if you don't delete the XML cache file and then delete your character sheet cache file the armory is not contacted again. The character sheet then will be rebuild with the XML data in your cache. This helps to reduce the HTTP requests to the armory.

= I can't import a character, I get a character not found message? =

Only characters with a minimum level of 10 are available in the wow armory. Maybe that is your problem?

= How do I put a character sheet in a post? =

Edit the post and type in the following shortcode: [wowcd character="yourcharname"]
(yourcharname is an example, please use YOUR characters name)
Hint: You can also use the TinyMCE HTML Editor Button. Just click on the WoW Icon.

= How do I put a guild roster in a post? =

Edit the post and type in the following shortcode: [wowcd guild="yourguildname"]
(yourguildname is an example, please use YOUR guilds name)
Hint: You can also use the TinyMCE HTML Editor Button. Just click on the WoW Icon.

= Can I have more than one character sheet in my sidebar? =

Yes, we can! WoW Character Display is a multi widget plugin.

= Can I have more than one guild roster in my sidebar? =

Yup! WoW Character Display is a multi widget plugin. Also for guild rosters.

= I changed some options but the changes won't show? =

Flush the cache please.

= I got errors due to file_get_contents or curl? =

Hm, that's a problem. On some servers PHP is configured not to load data from external servers. That's not my business please consult your provider to change that option if it's possible.

= I got errors due to file_put_contents or something? =

Maybe your selected caching directory is not writeable to the webserver. Connect via FTP and use "chown wwwrun.www" (or whatever your webserver uses as user and group) to ensure that the server can create files in that directory. If nothing helps try a "chmod 777" to grant full access for all.

== Screenshots ==

1. This screenshot show something of the configuration options.
2. This screeny shows how a character is added into the plugin.
3. This shows that you can configure very many display options for each character.
4. Here you see the cache status overview.
5. This is one WoW Character Display multi widget.
