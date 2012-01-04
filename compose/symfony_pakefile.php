<?php

pake_desc("Create symlinks to draw from the core Symfony library");
pake_task("symfony_create_symlinks");
function run_symfony_create_symlinks($obj,$args) {
	
	$branch   		 = getBranch($args);
	$ssh      		 = getProp("ssh");
	$site     		 = getProp("site");
	$siteSharedPath  = getProp("remote_sites_root").$site."/{$branch}/shared/Symfony";
	$siteSymfonyPath = getProp("remote_sites_root").$site."/{$branch}/current/Symfony";
	
	//create symlinks for vendor/, app/cache, app/logs, web/uploads and web/.htaccess
	pake_echo_action("linking","creating symlink for vendor directory");
	$result = pake_sh("ssh $ssh ln -s {$siteSharedPath}/vendor/ $siteSymfonyPath/vendor");
	pake_echo_comment($result);
				
	pake_echo_action("linking","creating symlink for app/cache directory");
	$result = pake_sh("ssh $ssh ln -s {$siteSharedPath}/app/cache $siteSymfonyPath/app/cache");
	pake_echo_comment($result);
		
	pake_echo_action("linking","creating symlink for app/logs");
	$result = pake_sh("ssh $ssh ln -s {$siteSharedPath}/app/logs $siteSymfonyPath/app/logs");
	pake_echo_comment($result);
	
	pake_echo_action("linking","creating symlink for web/uploads");
	$result = pake_sh("ssh $ssh ln -s {$siteSharedPath}/web/uploads $siteSymfonyPath/web/uploads");
	pake_echo_comment($result);
	
	pake_echo_action("linking","creating symlink for web/.htaccess");
	$result = pake_sh("ssh $ssh ln -s {$siteSharedPath}/web/.htaccess $siteSymfonyPath/web/.htaccess");
	pake_echo_comment($result);
}


pake_desc("Clear the cache for the production environment on the server");
pake_task("symfony_clear_cache");
function run_symfony_clear_cache($obj,$args) {
	
	$branch   		 = getBranch($args);
	$ssh      		 = getProp("ssh");
	$site     		 = getProp("site");
	$siteSymfonyPath = getProp("remote_sites_root").$site."/{$branch}/current/Symfony";
	$siteSharedPath  = getProp("remote_sites_root").$site."/{$branch}/shared/Symfony";
	
	// clear the cache
	pake_echo_action("clear cache","clearing the cache for the production environment");
	$result = pake_sh("ssh $ssh rm -Rf $siteSharedPath/app/cache/*");
	pake_echo_comment($result);
}


pake_desc("Install assets, ready for production");
pake_task("symfony_install_assets");
function run_symfony_install_assets($obj,$args) {
	
	$branch   		 = getBranch($args);
	$ssh      		 = getProp("ssh");
	$site     		 = getProp("site");
	$siteSymfonyPath = getProp("remote_sites_root").$site."/{$branch}/current/Symfony";
	$siteSharedPath  = getProp("remote_sites_root").$site."/{$branch}/shared/Symfony";
	
	// make assets directory writable
	pake_echo_action("changing permissions","allowing Symfony to write to the web directory");
	$result = pake_sh("ssh $ssh chmod -R g=rwx $siteSymfonyPath/web");
	pake_echo_comment($result);	
	 
	// install assets
	pake_echo_action("installing assets","installing assets from bundles to web/");
	$result = pake_sh("ssh $ssh php $siteSymfonyPath/app/console assets:install --symlink $siteSymfonyPath/web");
	pake_echo_comment($result);
	
	// dump assets
	pake_echo_action("dumping assets","dumping assets to web/");
	$result = pake_sh("ssh $ssh php $siteSymfonyPath/app/console assetic:dump --env=prod --no-debug");
	pake_echo_comment($result);
	
	// make assets directory read only
	pake_echo_action("changing permissions","disallowing Symfony to write to the web/assets directory");
	$result = pake_sh("ssh $ssh chgrp -R www-data $siteSymfonyPath/web");
	$result .= pake_sh("ssh $ssh chmod -R g=rx $siteSymfonyPath/web");
	pake_echo_comment($result);	
}


pake_desc("Do all the necessary things to deploy Symfony");
pake_task("symfony_deploy","symfony_create_symlinks","symfony_clear_cache","symfony_install_assets");
function run_symfony_deploy($obj,$args) {

	pake_echo_comment("Symfony deployed!");
}

?>
