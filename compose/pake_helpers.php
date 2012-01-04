<?php

/**
 * Get a property from the loaded properties file.
 *
 * @param string $key Property name
 * @return mixed Property value or null
 */
function getProp($key) {
	$props = pakeApp::get_instance()->get_properties();
	return $props[$key];
}


/**
 * Get the correct branch from the command line arguments.
 * This is a very simple wrapper, but the code is used frequently.
 *
 * @param &array $args Pake command line arguments
 */
function getBranch(&$args) {
	return count($args) > 0 ? $args[0] : "staging";
}


/**
 * Get an array containing the names and dates of all versions for
 * a branch.
 *
 * @param &array $args Command line arguments
 * @return array Array of version details, or null if none are found
 *				 Each version is an array with following data:
 *				 	name => full version folder name
 *					date => DateTime object, containing the version date 
 */
function getVersions(&$args) {
	
	$branch 	= getBranch($args);
	$ssh    	= getProp("ssh");
	$site		= getProp("site");
	$remoteRoot = getProp("remote_sites_root");
	
	pake_echo_action("versions","getting current versions in '$branch' for $site");
	$versionList = pake_sh("ssh $ssh ls {$remoteRoot}{$site}/{$branch}/.versions/");
	
	if($versionList !== "") {
		$versionNames = explode("\n",trim($versionList));
		
		$versions = array();
		date_default_timezone_set("Europe/London");
		
		foreach($versionNames as $name) {
			$versions[] = array(
				"name" => $name,
				"date" => new DateTime(substr(strstr($name,"~"),1))
			);
		}
		
		pake_echo_comment("Found ".count($versions)." versions");
		return $versions;
	}
	else {
		pake_echo_error("No versions found");
		return null;
	}
}

?>