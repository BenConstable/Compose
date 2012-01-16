<?php

/*
 * Default deploy tasks.
 * 
 * These tasks can be used for general deployment, independent of
 * the Symfony framework.
 *
 * @author Ben Constable <ben@benconstable.co.uk>
 * @version 1.0
 */

pake_desc("Setup directory structure, permissions and Git remote for a Compose site environment");
pake_task("pake_setup_environment");
function run_setup_environment($obj, $args)
{
    $env              = $args[0];
    $ssh              = get_prop("ssh");
    $site             = get_prop("site");
    $remote_git_root  = get_prop("remote_git_root") . "/$site";
    $remote_site_root = get_prop("remote_sites_root") . "/$site";
    
    if ($env) {
        // Create new remote branch
        pake_echo_comment(pake_sh("ssh $ssh git --git-dir=\"$remote_git_root\" branch $env"));
        
        // Create directory structure
        pake_echo_comment(pake_sh("ssh $ssh mkdir $remote_site_root/$env"));
        pake_echo_comment(pake_sh("ssh $ssh mkdir $remote_site_root/$env/.versions"));
        pake_echo_comment(pake_sh("ssh $ssh mkdir $remote_site_root/$env/shared"));
        pake_echo_comment(pake_sh("ssh $ssh touch $remote_site_root/$env/current"));
        
        // Setup permissions
        pake_echo_comment(pake_sh("shh $ssh chgrp -R $remote_site_root/$env/shared www-data"));
        pake_echo_comment(pake_sh("shh $ssh chmod -R g=rx $remote_site_root/$env/shared"));
        pake_echo_comment(pake_sh("shh $ssh chmod -R u=rwx $remote_site_root/$env/shared"));
        pake_echo_comment(pake_sh("shh $ssh chmod -R u=rwx $remote_site_root/$env/.versions"));
        pake_echo_comment(pake_sh("shh $ssh chmod -R u=rwx $remote_site_root/$env/current"));
    }
    else {
        pake_echo_error("No environment name given");
    }
}

/*
 * Tag the current commit.
 *
 * Wraps the 'git tag' command. For sensibility, on releases to the live
 * evironment can be tagged using this task.
 *
 * @param string (1st arg) Environment name
 * @param string (2nd arg) Tag name
 */
pake_desc("Tag the current version for release. Only the live environment can be tagged");
pake_task("tag");
function run_tag($obj, $args)
{
    $env = get_environment($args);
    
    if ($args[1]) {
        if($env === "live") {
            $tag = $args[1];
            pake_echo_action("tag","tagging release as '$tag'");
            pake_echo_comment(pake_sh("git tag -a \"$tag\""));
        }
        else {
            pake_echo_comment("Found tag, but will only tag live environment");
        }
    }
}

/*
 * Checkout the local branch defined in pake_properties.ini. This is called before
 * every attempt to push to a remote, in order to make sure that incorrect branches
 * aren't accidentally pushed. 
 */
pake_desc("Ensure the correct local branch is checked out before pushing");
pake_task("checkout_local_branch");
function run_checkout_local_branch($obj, $args)
{
    $branch = get_prop("local_branch");
    pake_echo_action("checkout", "checking out local branch to push");
    pake_echo_comment(pake_sh("git checkout $branch"));
}

/*
 * Push the local site version to the given server environment.
 *
 * @param string (1st arg) Environment name (optional, 'staging' by default)
 */
pake_desc("Push the local site version to the server on the given environment");
pake_task("push_server", "checkout_local_branch");
function run_push_server($obj, $args)
{	
    $env    = get_environment($args);
    $server = get_prop("server_remote_name");
    
    pake_echo_action("push", "pushing to '$env' environment on the server");
    pake_echo_comment(pake_sh("git push $server dev:$env"));
}

/*
 * Remove the oldest version for the given environment from the server.
 *
 * @param string (1st arg) Environment name (optional, 'staging' by default)  
 */
pake_desc("Remove the oldest version of a site from .versions/ directory");
pake_task("remove_old_version");
function run_remove_old_version($obj, $args)
{
    $env 	     = get_environment($env);
    $ssh    	 = get_prop("ssh");
    $site		 = get_prop("site");
    $remote_root = get_prop("remote_sites_root");
    
    $versions = get_versions($args);
    
    if ($versions !== null) {
        if (count($versions) > 1) {
        
            date_default_timezone_set("Europe/London");
            
            $oldest = "";
            $oldest_date = new DateTime();
            foreach ($versions as $ver) {	
                if ($ver["date"] < $oldest_date) {
                    $oldest_date = $ver["date"];
                    $oldest = $ver["name"];
                }
            }
            
            pake_echo_action("removing", "removing version dated " . $oldest_date->format("Y-m-d H:i:s"));
            pake_echo_comment("ssh $ssh rm -R $remote_root/$site/$env/.versions/$oldest");
        }
        else {
            pake_echo_error("Only one version found, will not remove");
        }
    }
}

/*
 * Rollback to the previously pushed site version.
 *
 * @param string (1st arg) Environment name (optional, 'staging' by default)  
 */
pake_desc("Rollback to the previous version, stored in .versions/");
pake_task("rollback");
function run_rollback($obj, $args)
{	
    $env 	     = get_environment($args);
    $ssh    	 = get_prop("ssh");
    $site		 = get_prop("site");
    $remote_root = get_prop("remote_sites_root");
    
    $versions = get_versions($args);
    
    if ($versions !== null) {
        if (count($versions) > 1) {
            
            // remove the current symlink
            pake_echo_action("remove", "removing current symlink");
			pake_echo_comment(pake_sh("ssh $ssh rm $remote_root/$site/$env/current"));
			
			date_default_timezone_set("Europe/London");
			
			// find latest version
			$latest = "";
			$latest_date = "";
			$latest_index = -1;
			foreach ($versions as $ver) {
                if ($ver["date"] >= $latest_date || $latest_date === "") {
                    $latest = $ver["name"];
                    $latest_date = $ver["date"];
                }
                $latest_index++;
            }
            
            // remove latest from $versions
            unset($versions[$latest_index]);
            
            // find previous version
            $previous = "";
            $previous_date = "";
            foreach ($versions as $ver) {
                if ($ver["date"] >= $previous_date || $previous_date === "") {
                    $previous = $ver["name"];
                    $previous_date = $ver["date"];
                }
            }
            
            // remove latest
            pake_echo_action("remove","removing latest version");
            pake_echo_comment(pake_sh("ssh $ssh rm -R $remote_root/$site/$env/.versions/$latest"));
            
            // create new current from previous
            pake_echo_action("symlink","creating symlink to previous version");
            pake_echo_comment(pake_sh("ssh $ssh ln -s $remote_root/$site/$env/.versions/$previous $remote_root/$site/$env/current"));
        }
        else {
            pake_echo_error("Cannot roll back, only one version found");
        }
    }
}

/*
 * Push the local version of the site to the server, and check out the work tree to
 * the given environment.
 *
 * @param string (1st arg) Environment name (optional, 'staging' by default)  
 */
pake_desc("Jump on to the server and checkout new working tree to webroot");
pake_task("default_deploy");
function run_default_deploy($obj, $args)
{	
    $env       = get_environment($args);
    $ssh       = get_prop("ssh");
    $site      = get_prop("site");
    $site_path = get_prop("remote_sites_root")."/$site/$env";
    $git_path  = get_prop("remote_git_root")."/$site";
    
    //new directory for latest
    date_default_timezone_set("Europe/London");
    $version = "site~".date("Ymd\TH:i:s");
    
    //create new directory in .versions/
    pake_echo_action("new dir", "creating new directory for latest version");
    pake_echo_comment(pake_sh("ssh $ssh mkdir $site_path/.versions/$version"));
    
    //checkout working tree to latest
    pake_echo_action("checkout", "checking out working tree of '$env' to $site");
    pake_echo_comment(pake_sh("ssh $ssh git --git-dir=\"$git_path\" --work-tree=\"$site_path/.versions/$version\" checkout -f $env"));
    
    //symlink to current
    pake_echo_action("symlink", "updating current symlink to point to latest version");
    $result = pake_sh("ssh $ssh rm -R $site_path/current");
    $result .= pake_sh("ssh $ssh ln -s $site_path/.versions/$version $site_path/current");
    pake_echo_comment($result);
    
    //sort out permissions (read and execute)
    pake_echo_action("permissions", "cleaning up permissions");
    $result = pake_sh("ssh $ssh chgrp -R www-data $site_path/.versions/$version");
    $result .= pake_sh("ssh $ssh chmod -R g=rx $site_path/.versions/$version");
    pake_echo_comment($result);
}

?>
