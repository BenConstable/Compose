<?php

/**
 * Compose helper functions.
 * 
 * @author Ben Constable <ben@benconstable.co.uk>
 * @version 1.0
 */
 
/*
 * Get a property from the loaded properties file.
 *
 * @param string $key Property name
 * @return mixed Property value or null
 */
function get_prop($key)
{
    $props = pakeApp::get_instance()->get_properties();
    return $props[$key];
}

/*
 * Get the correct environment from the command line arguments.
 * This is a very simple wrapper, but the code is used frequently.
 *
 * @param &array $args Pake command line arguments
 */
function get_environment(&$args)
{
    return count($args) > 0 ? $args[0] : "staging";
}

/*
 * Get an array containing the names and dates of all versions for
 * an environment.
 *
 * @param &array $args Command line arguments
 * @return array Array of version details, or null if none are found
 *				 Each version is an array with following data:
 *				 	name => full version folder name
 *					date => DateTime object, containing the version date 
 */
function get_versions(&$args)
{
    $env         = get_environment($args);
    $ssh         = get_prop("ssh");
    $site        = get_prop("site");
    $remote_root = get_prop("remote_sites_root");
    
    pake_echo_action("versions", "getting current versions in '$env' environment for $site");
    $version_list = pake_sh("ssh $ssh ls $remote_root/$site/$env/.versions/");
    
    if ($version_list !== "") {
    
        $version_names = explode("\n", trim($version_list));
        $versions = array();
        date_default_timezone_set("Europe/London");
        
        foreach ($version_names as $name) {
            $versions[] = array(
                "name" => $name,
                "date" => new DateTime(substr(strstr($name, "~"), 1))
            );
        }
        
        pake_echo_comment("Found " . count($versions) . " versions");
        return $versions;
    }
    else {
        pake_echo_error("No versions found");
        return null;
    }
}

?>