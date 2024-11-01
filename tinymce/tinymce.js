function init() {
	tinyMCEPopup.resizeToInnerSize();
}

function insertWoWCDLink() {
	
	var tagtext;
	
	var character = document.getElementById('character_panel');
	var guild = document.getElementById('guild_panel');
	
	// who is active ?
	if (character.className.indexOf('current') != -1) {
		var charactername = document.getElementById('charactertag').value;
		if (charactername != '')
			tagtext = '[wowcd character="' + charactername + '"]';
		else
			tinyMCEPopup.close();
	}
	if (guild.className.indexOf('current') != -1) {
		var guildname = document.getElementById('guildtag').value;
		if (guildname != '')
			tagtext = '[wowcd guild="' + guildname + '"]';
		else
			tinyMCEPopup.close();
	}
	if(window.tinyMCE) {
		//TODO: For QTranslate we should use here 'qtrans_textarea_content' instead 'content'
		window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, tagtext);
		//Repaints the editor. Sometimes the browser has graphic glitches. 
		tinyMCEPopup.editor.execCommand('mceRepaint');
		tinyMCEPopup.close();
	}
	return;
}
