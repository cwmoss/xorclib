<?
function __($t){

	if(isset($GLOBALS['l10n'])){
//		print "emu running";
		#print_r($GLOBALS['l10n']);
		return $GLOBALS['l10n']->translate($t);
	}else{
//	        print "gettext";
//			setlocale(LC_MESSAGES, 'de');
//			putenv("LANG=de");
//			bindtextdomain("alottalog", "/home/data/alottalog/lib/alotta/locale");
//			textdomain("alottalog");

		return gettext($t);
	}
	
}

?>
