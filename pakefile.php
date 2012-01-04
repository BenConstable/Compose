<?php

/**
 * Pake deploy script.
 * 
 * @author Ben Constable
 * @version 0.1
 */

//Useful task libraries
require_once("build/pake_helpers.php");
require_once("build/default_pakefile.php");
require_once("build/symfony_pakefile.php");

//Load properties
pake_properties("build/pake_properties.ini");

/**
 * The main deploy task.
 *
 * This task should be used to do your deployment.
 * build/default_pakefile.php provides a number of
 * useful tasks that you can depend on in this task.
 *
 * @param $obj  Task object
 * @param $args Command line arguments
 */
pake_desc("Deploy!");
pake_task(
	'deploy', // this task
	// dependencies
	'tag',
	'push_github',
	'push_server',
	'remove_old_version',
	'default_deploy',
	'symfony_deploy'
);
function run_deploy($obj,$args) {
	pake_echo_action("deploy","finished!");
}

?>