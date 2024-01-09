<?php
/*
	ex: www-data:www-data
*/
$usergroup = $margs[0];

if(!$usergroup){
	$guess = trim(`ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`);

    print "please give a user:group
	optional: path

	osx add user to group: sudo dseditgroup -o edit -a USERNAME -t user GROUPNAME
		ex: sudo dseditgroup -o edit -a rw -t user _www

	ubuntu add user to group: sudo usermod -a -G GROUPNAME USERNAME
		ex: usermod -a -G sudo rw

	*** i'm guessing it's $guess:$guess ***

";
	exit;
}


$dirs = $margs[1];
if(!$dirs) $dirs = array('var');
else $dirs = array($dirs);

/*
https://de.wikipedia.org/wiki/Setgid
https://github.com/magento/magento2/issues/2525

sudo chown -R www-data:dev-mysitenamehere .
sudo find . -type f -exec chmod 0460 {} \;
sudo find . -type d -exec chmod 2570 {} \;

sudo find storage -type f -exec chmod 0660 {} \;
sudo find storage -type d -exec chmod 2770 {} \;

find var/generation -type d -exec chmod g+s {} \;
*/

foreach($dirs as $d){

	$dirname = $d;
	if(!file_exists($dirname)) $dirname = Xorcapp::$inst->approot."/$d";

	print "setting file/dir owner/permissions [$usergroup]for $dirname\n";

	// user + gruppe setzen
	`sudo chown -R $usergroup $dirname`;

	// verzeichnisse mit "setgid bit"
	`sudo find $dirname -type d -exec chmod 2770 {} \;`;

	// vorhandene dateien
	`sudo find $dirname -type f -exec chmod 0660 {} \;`;
}