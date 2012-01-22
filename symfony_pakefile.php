<?php

/*
 * Symfony-specific deploy tasks.
 * 
 * @package Compose
 * @author Ben Constable <ben@benconstable.co.uk>
 * @version 1.0
 */

/*
 * Add Symfony files to an existing Compose environment.
 *
 * Requires rsync to send over some Symfony files. May take a few minutes
 * as `vendors install` is run.
 *
 * @param string (1st arg) Environment name (optional, 'staging' by default)  
 */
pake_desc("Add Symfony files to an environment");
pake_task("symfony_setup_environment");
function run_symfony_setup_environment($obj, $args)
{
    $env                = get_environment($args);
    $ssh                = get_prop("ssh");
    $site               = get_prop("site");
    $symfony_uploads    = get_prop("symfony_uploads_dir");
    $remote_site_shared = get_prop("remote_sites_root") . "/$site/$env/shared";
    
    if ($env) {
        
        pake_echo_action("SETUP SYMFONY ENV", "** Setting up Symfony files in the '$env' environment **");
        
        // Create Symfony directories
        pake_echo_comment(pake_sh("ssh $ssh mkdir $remote_site_shared/Symfony"));
        pake_echo_comment(pake_sh("ssh $ssh mkdir $remote_site_shared/Symfony/app"));
        pake_echo_comment(pake_sh("ssh $ssh mkdir $remote_site_shared/Symfony/app/cache"));
        pake_echo_comment(pake_sh("ssh $ssh mkdir $remote_site_shared/Symfony/app/logs"));
        pake_echo_comment(pake_sh("ssh $ssh mkdir $remote_site_shared/Symfony/bin"));
        pake_echo_comment(pake_sh("ssh $ssh mkdir $remote_site_shared/Symfony/web"));
        pake_echo_comment(pake_sh("ssh $ssh mkdir $remote_site_shared/Symfony/web/$symfony_uploads"));
        
        // Copy over local Symfony files
        pake_echo_comment(pake_sh("rsync -vaz Symfony/bin/vendors $ssh:$remote_site_shared/Symfony/bin"));
        pake_echo_comment(pake_sh("rsync -vaz Symfony/deps $ssh:$remote_site_shared/Symfony"));
        pake_echo_comment(pake_sh("rsync -vaz Symfony/deps.lock $ssh:$remote_site_shared/Symfony"));
        pake_echo_comment(pake_sh("rsync -vaz Symfony/web/.htaccess $ssh:$remote_site_shared/Symfony/web"));
        
        // Install Symfony vendors
        pake_echo_comment(pake_sh("ssh $ssh php $remote_site_shared/Symfony/bin/vendors install"));
        
        // Update permissions
        pake_echo_comment(pake_sh("ssh $ssh chgrp -R www-data $remote_site_shared"));
        pake_echo_comment(pake_sh("ssh $ssh chmod -R g=rx $remote_site_shared"));
        pake_echo_comment(pake_sh("ssh $ssh chmod -R u=rwx $remote_site_shared"));
        pake_echo_comment(pake_sh("ssh $ssh chmod -R g=rwx $remote_site_shared/Symfony/web/$symfony_uploads"));
    }
    else {
        pake_echo_error("No environment name given");
    }
}

/*
 * Setup Compose for use with a Symfony project.
 *
 * Creates two environments - 'live' and 'staging'. See the `setup_compose` task in
 * default_pakefile.php for more info.
 */
pake_desc("Setup Compose for a Symfony project");
pake_task("symfony_setup_compose", "setup_compose");
function run_symfony_setup_compose($obj, $args)
{
    pake_echo_action("SETUP SYMFONY", "** Setting up Compose for Symfony **");
    
    run_symfony_setup_environment(false, array("staging"));
    run_symfony_setup_environment(false, array("live"));
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
    $symfony_uploads   = get_prop("symfony_uploads_dir");
    $site_shared_path  = get_prop("remote_sites_root") . "/$site/$env/shared/Symfony";
    $site_symfony_path = get_prop("remote_sites_root") . "/$site/$env/current/Symfony";
    
    pake_echo_action("LINKING", "** Creating Symfony symlinks from the shared/ directory to the new site version **");
    
    // create symlinks for vendor/, app/cache, app/logs, web/user-uploads folder and web/.htaccess
    pake_echo_comment(pake_sh("ssh $ssh ln -s $site_shared_path/vendor/ $site_symfony_path/vendor"));
    pake_echo_comment(pake_sh("ssh $ssh ln -s $site_shared_path/app/cache $site_symfony_path/app/cache"));	
    pake_echo_comment(pake_sh("ssh $ssh ln -s $site_shared_path/app/logs $site_symfony_path/app/logs"));
    pake_echo_comment(pake_sh("ssh $ssh ln -s $site_shared_path}/web/user-uploads $site_symfony_path/web/$symfony_uploads"));
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
    
    // Clear the cache
    pake_echo_action("CLEARING CACHE", "** Clearing the Symfony project cache **");
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
    
    pake_echo_action("INSTALLING ASSETS", "** Installing Assetic assets, ready for production **");
    
    // Make assets directory writable
    pake_echo_comment(pake_sh("ssh $ssh chmod -R g=rwx $site_symfony_path/web"));
    
    // Install assets
    pake_echo_comment(pake_sh("ssh $ssh php $site_symfony_path/app/console assets:install --symlink $site_symfony_path/web"));
    
    // Dump assets
    pake_echo_comment(pake_sh("ssh $ssh php $site_symfony_path/app/console assetic:dump --env=prod --no-debug"));
    
    // Make assets directory read only
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
    pake_echo_action("DEPLOYED", "** Symfony has finished deploying **");
}

?>
