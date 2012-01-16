<?php

/**
 * Symfony-specific deploy tasks.
 * 
 * @author Ben Constable <ben@benconstable.co.uk>
 * @version 1.0
 */

pake_desc("Setup the correct directory structure for Compose");
pake_task("symfony_compose");
function run_symfony_compose($obj, $args)
{
	// Make git repo folder
        // git init --bare
    
	// Make Site folder
	
	// Make live folder
	   // .versions
	   // shared
	       // Symfony
	           // app/
	               // cache
	               // logs
	           // deps
	           // deps.lock
	           // bin/vendors
	           // web/
	               // user-uploads
	   // current (file)
    // Run vendors install
    // Chgrp of shared to www-data
    // make user-uploads directory writable
    // use acl or +a on cache and logs?
    
    // Do exactly the same as above for staging folder
    
    // Setup 'server' remote
    
    // Done
}

/*
 * Create symlinks to all of the core Symfony code that's stored in the shared directory.
 *
 * @param string (1st arg) Environment name (optional, 'staging' by default)  
 */
pake_desc("Create symlinks to draw from the shared directory, where the Symfony vendors/ directory is stored");
pake_task("symfony_create_symlinks");
function run_symfony_create_symlinks($obj, $args)
{ 	
    $env   		       = get_environment($args);
    $ssh      		   = get_prop("ssh");
    $site     		   = get_prop("site");
    $site_shared_path  = get_prop("remote_sites_root") . "/$site/$env/shared/Symfony";
    $site_symfony_path = get_prop("remote_sites_root") . "/$site/$env/current/Symfony";
    
    // create symlinks for vendor/, app/cache, app/logs, web/user-uploads and web/.htaccess
    pake_echo_action("linking", "creating symlink for vendor directory");
    pake_echo_comment(pake_sh("ssh $ssh ln -s $site_shared_path/vendor/ $site_symfony_path/vendor"));
    
    pake_echo_action("linking", "creating symlink for app/cache directory");
    pake_echo_comment(pake_sh("ssh $ssh ln -s $site_shared_path/app/cache $site_symfony_path/app/cache"));	
    
    pake_echo_action("linking", "creating symlink for app/logs");
    pake_echo_comment(pake_sh("ssh $ssh ln -s $site_shared_path}/app/logs $site_symfony_path/app/logs"));
    
    pake_echo_action("linking", "creating symlink for web/user-uploads");
    pake_echo_comment(pake_sh("ssh $ssh ln -s $site_shared_path}/web/user-uploads $site_symfony_path/web/user-uploads"));
    
    pake_echo_action("linking", "creating symlink for web/.htaccess");
    pake_echo_comment(pake_sh("ssh $ssh ln -s $site_shared_path/web/.htaccess $site_symfony_path/web/.htaccess"));
}

/*
 * Clear the Symfony cache.
 *
 * Doesn't use the builtin Symfony clear cache command as there can be some permissions
 * issues.
 *
 * @param string (1st arg) Environment name (optional, 'staging' by default)  
 */
pake_desc("Clear the cache for all Symfony environments on the server");
pake_task("symfony_clear_cache");
function run_symfony_clear_cache($obj, $args)
{
    $env              = get_environment($args);
    $ssh              = get_prop("ssh");
    $site             = get_prop("site");
    $site_shared_path = get_prop("remote_sites_root"). "/$site/{$env}/shared/Symfony";
    
    // clear the cache
    pake_echo_action("clear cache", "clearing the cache for all environments");
    pake_echo_comment(pake_sh("ssh $ssh rm -Rf $site_shared_path/app/cache/*"));
}

/*
 * Install Assetic assets.
 *
 * First calls assets:install to move Bundle assets to the web directory, then calls
 * assetic:dump to dump Assetic assets.
 *
 * Write permissions are temporarily given to the web directory.
 *
 * @param string (1st arg) Environment name (optional, 'staging' by default)  
 */
pake_desc("Install assets, ready for production");
pake_task("symfony_install_assets");
function run_symfony_install_assets($obj, $args)
{
    $env               = get_environment($args);
    $ssh               = get_prop("ssh");
    $site              = get_prop("site");
    $site_symfony_path = get_prop("remote_sites_root") . "/$site/$env/current/Symfony";
    
    // make assets directory writable
    pake_echo_action("changing permissions", "allowing Symfony to write to the web directory");
    pake_echo_comment(pake_sh("ssh $ssh chmod -R g=rwx $site_symfony_path/web"));
    
    // install assets
    pake_echo_action("installing assets", "installing assets from bundles to web/");
    pake_echo_comment(pake_sh("ssh $ssh php $site_symfony_path/app/console assets:install --symlink $site_symfony_path/web"));
    
    // dump assets
    pake_echo_action("dumping assets", "dumping assets to web/");
    pake_echo_comment(pake_sh("ssh $ssh php $site_symfony_path/app/console assetic:dump --env=prod --no-debug"));
    
    // make assets directory read only
    pake_echo_action("changing permissions", "disallowing Symfony to write to the web/assets directory");
    $result = pake_sh("ssh $ssh chgrp -R www-data $site_symfony_path/web");
    $result .= pake_sh("ssh $ssh chmod -R g=rx $site_symfony_path/web");
    pake_echo_comment($result);
}

/*
 * Run all of the required tasks one by one in order to completely deploy a Symfony
 * site.
 *
 * @param string (1st arg) Environment name (optional, 'staging' by default) 
 */
pake_desc("Do all the necessary things to deploy Symfony");
pake_task("symfony_deploy", "symfony_create_symlinks", "symfony_clear_cache", "symfony_install_assets");
function run_symfony_deploy($obj, $args)
{
    pake_echo_comment("Symfony deployed!");
}

?>
