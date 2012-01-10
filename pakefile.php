<?php

/**
 * Pake deploy script.
 *
 * Use this as an example to get started!
 * 
 * @author Ben Constable <ben@benconstable.co.uk>
 * @version 0.9
 */

require_once("compose/pake_helpers.php");
require_once("compose/default_pakefile.php");
require_once("compose/symfony_pakefile.php");

// Load properties
pake_properties("compose/pake_properties.ini");

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
pake_task('deploy'/*, dependencies… */);
function run_deploy($obj, $args)
{
	pake_echo_action("deploy", "finished!");
}

?>