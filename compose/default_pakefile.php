<?php

pake_desc("Tag the current version for release. Only the live branch can be tagged");
pake_task("tag");
function run_tag($obj,$args) {
	
	$branch = getBranch($args);
	
	if($args[1]) {
		if($branch === "live") {
			$tag = $args[1];
			pake_echo_action("tag","tagging release as '$tag'");
			$result = pake_sh("git tag -a \"$tag\"");	
			pake_echo_comment($result);
		}
		else {
			pake_echo_comment("Found tag, but will not tag staging branch");
		}
	}
}


pake_desc("Push the local site version to the server on the given branch");
pake_task("push_server");
function run_push_server($obj,$args) {
	
	$branch = getBranch($args);
		
	pake_echo_action("checkout","making sure that we're on the dev branch");
	$result = pake_sh("git checkout dev");
	pake_echo_comment($result);
	
		
	pake_echo_action("push","pushing to '$branch' on server");
	$result = pake_sh("git push server dev:$branch");
	pake_echo_comment($result);
}


pake_desc("Push the local site version to Github on the given branch");
pake_task("push_github");
function run_push_github($obj,$args) {
	
	$branch = getBranch($args);
	
	pake_echo_action("checkout","making sure that we're on the dev branch");
	$result = pake_sh("git checkout dev");
	pake_echo_comment($result);
	
	pake_echo_action("push","pushing to '$branch' on Github");
	$result = pake_sh("git push github dev:$branch");
	pake_echo_comment($result);
}


pake_desc("Remove the oldest version of a site from .versions/ directory");
pake_task("remove_old_version");
function run_remove_old_version($obj,$args) {
	
	$branch 	= getBranch($args);
	$ssh    	= getProp("ssh");
	$site		= getProp("site");
	$remoteRoot = getProp("remote_sites_root");
	
	$versions = getVersions($args);
	
	if($versions !== null) {
		if(count($versions) > 1) {
		
			date_default_timezone_set("Europe/London");
			
			$oldest = "";
			$oldestDate = new DateTime();
			foreach($versions as $ver) {	
				if($ver["date"] < $oldestDate) {
					$oldestDate = $ver["date"];
					$oldest = $ver["name"];
				}
			}
			
			pake_echo_action("removing","removing version dated ".$oldestDate->format("Y-m-d H:i:s"));
			$result = pake_sh("ssh $ssh rm -R {$remoteRoot}{$site}/{$branch}/.versions/{$oldest}");
			pake_echo_comment($result);
		}
		else {
			pake_echo_error("Only one version found, will not remove");
		}
	}
}


pake_desc("Rollback to the previous version, stored in .versions/");
pake_task("rollback");
function run_rollback($obj,$args) {
	
	$branch 	= getBranch($args);
	$ssh    	= getProp("ssh");
	$site		= getProp("site");
	$remoteRoot = getProp("remote_sites_root");
	
	$versions = getVersions($args);
	
	if($versions !== null) {
		if(count($versions) > 1) {
		
			// remove the current symlink
			pake_echo_action("remove","removing current symlink");
			$result = pake_sh("ssh $ssh rm {$remoteRoot}{$site}/{$branch}/current");
			pake_echo_comment($result);
			
			date_default_timezone_set("Europe/London");
			
			// find latest version
			$latest = "";
			$latestDate = "";
			$latestIndex = -1;
			foreach($versions as $ver) {
				if($ver["date"] >= $latestDate || $latestDate === "") {
					$latest = $ver["name"];
					$latestDate = $ver["date"];
				}
				$latestIndex++;
			}
			
			// remove latest from $versions
			unset($versions[$latestIndex]);
			
			// find previous version
			$previous = "";
			$previousDate = "";
			foreach($versions as $ver) {
				if($ver["date"] >= $previousDate || $previousDate === "") {
					$previous = $ver["name"];
					$previousDate = $ver["date"];
				}
			}
			
			// remove latest
			pake_echo_action("remove","removing latest version");
			$result = pake_sh("ssh $ssh rm -R {$remoteRoot}{$site}/{$branch}/.versions/$latest");
			pake_echo_comment($result);
			
			// create new current from previous
			pake_echo_action("symlink","creating symlink to previous version");
			$result = pake_sh("ssh $ssh ln -s {$remoteRoot}{$site}/{$branch}/.versions/$previous {$remoteRoot}{$site}/{$branch}/current");
			pake_echo_comment($result);
		}
		else {
			pake_echo_error("Cannot roll back, only one version found");
		}
	}
}

 
pake_desc("Jump on to the server and checkout new working tree to webroot");
pake_task("default_deploy");
function run_default_deploy($obj,$args) {
	
	//vars
	$branch   = getBranch($args);
	$ssh      = getProp("ssh");
	$site     = getProp("site");
	$sitePath = getProp("remote_sites_root").$site."/{$branch}/";
	$gitPath  = getProp("remote_git_root").$site;
	
	//new directory for latest
	date_default_timezone_set("Europe/London");
	$version = "site~".date("Ymd\TH:i:s");
	
	//create new directory in .versions/
	pake_echo_action("new dir","creating new directory for latest version");
	$result = pake_sh("ssh $ssh mkdir {$sitePath}.versions/$version");
	pake_echo_comment($result);
	
	//checkout working tree to latest
	pake_echo_action("checkout","checking out working tree of '$branch' to $site");
	$result = pake_sh("ssh $ssh git --git-dir=\"{$gitPath}\" --work-tree=\"{$sitePath}.versions/$version\" checkout -f $branch");
	pake_echo_comment($result);
	
	//symlink to current
	pake_echo_action("symlink","updating current symlink to point to latest version");
	$result = pake_sh("ssh $ssh rm -R {$sitePath}current");
	$result .= pake_sh("ssh $ssh ln -s {$sitePath}.versions/$version {$sitePath}current");
	pake_echo_comment($result);
	
	//sort out permissions (read and execute)
	pake_echo_action("permissions","cleaning up permissions");
	$result = pake_sh("ssh $ssh chgrp -R www-data {$sitePath}.versions/$version");
	$result .= pake_sh("ssh $ssh chmod -R g=rx {$sitePath}.versions/$version");
	pake_echo_comment($result);
}

?>
