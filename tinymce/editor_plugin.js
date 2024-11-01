// Docu : http://wiki.moxiecode.com/index.php/TinyMCE:Create_plugin/3.x#Creating_your_own_plugins
(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('WoWCD');
	
	tinymce.create('tinymce.plugins.WoWCD', {
		init : function(ed, url) {
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
			ed.addCommand('mceWoWCD', function() {
				ed.windowManager.open({
					file : url + '/window.php',
					width : 360 + ed.getLang('WoWCD.delta_width', 0),
					height : 210 + ed.getLang('WoWCD.delta_height', 0),
					inline : 1
				}, {
					plugin_url : url // Plugin absolute URL
				});
			});
			// Register example button
			ed.addButton('WoWCD', {
				title : 'WoWCD.desc',
				cmd : 'mceWoWCD',
				image : url + '/wowcd.gif'
			});
		},
		createControl : function(n, cm) {
			return null;
		},
		getInfo : function() {
			return {
					longname  : 'WoWCD',
					author 	  : 'Marc Schieferdecker',
					authorurl : 'http://www.das-motorrad-blog.de',
					infourl   : 'http://www.das-motorrad-blog.de',
					version   : "1.0"
			};
		}
	});
	// Register plugin
	tinymce.PluginManager.add('WoWCD', tinymce.plugins.WoWCD);
})();
