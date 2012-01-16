#Compose
Compose is a Git based PHP deploy tool for web applications. It is targeted at deploying Symfony 2 projects, although it can be used for any sort of web application.

##Pake
Compose is built on Pake, a PHP build tool similar to Rake or Make. Compose uses Pake to keep everything in PHP - why use other languages when you can do everything with one?

##Deployment
Deployment boils down to:

```
pake deploy {staging/live/custom_environment}
```

This will:

- Push the local branch to the server
- Move the work tree to the site's webroot on the server
- Archive the current version of the site
- Make the new version of the site 'live'

'live' is in inverted commas here because each site has both a `staging` and `live` environment. You can put a site live to the `staging` environment, or to the `live` environment. It's up to you to point your webroot to the correct environment root.

##Setup
Simply run `pake setup_compose` to set everything up. Symfony specific setup is not yet implemented, but will be very shortly. Config is separated out into `pake_properties.ini`, which mimics a PHP-style .ini file.

##Versioning
The previous version of the site will always be stored, so that it can be rolled back to easily. This is handled by the `rollback` task.

##Requirements and Installation

You'll need:

- To install [Pake](https://github.com/indeyets/pake "Pake")
- To have Git setup locally and on your server

Simply clone this Git repo into your project root (just above the Symfony directory), rename or copy sample_pake_properties.ini to pake_properties.ini, then make a Pakefile just above the directory containing Compose (see below). Your resulting directory structure should look like:

```
- my-site
    - Symfony
    - Compose
        - compose files…
    - pakefile.php
```

Easy!

##Example Pakefile

A typical Pakefile may look like this, and sit in the directory just above the Compose scripts:

```php
<?php

require_once("Compose/pake_helpers.php");
require_once("Compose/default_pakefile.php");
require_once("Compose/symfony_pakefile.php");

// Load properties
pake_properties("Compose/pake_properties.ini");

/*
 * The main deploy task.
 *
 * This task should be used to do your deployment.
 * compose/default_pakefile.php provides a number of
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
```

##Links

- [Pake](https://github.com/indeyets/pake "Pake")
- [Symfony](http://symfony.com/ "Symfony")

