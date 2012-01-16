#Compose
Compose is a Git based PHP deploy tool for web applications. It is targeted at deploying Symfony 2 projects, although it can be used for any sort of web application.

##Pake
Compose is built on Pake, a PHP build tool similar to Rake or Make. Compose uses Pake to keep everything in PHP - why use other languages when you can do everything with one?

##Deployment
Deployment boils down to:

```
pake deploy {staging/live}
```

This will:

- Push the local branch to the server
- Move the work tree to the site's webroot on the server
- Archive the current version of the site
- Make the new version of the site 'live'

'live' is in inverted commas here because each site has both a `staging` and `live` branch. You can put a site live to the `staging` version, or to the `live` version. This is useful for testing on the live
environment.

##Setup
You can look at the `symfony_compose` to see the basic setup for the directories. This will be implemented soon, so setup will be entirely automated. Config is separated out into `pake_properties.ini`, which mimics a PHP-style .ini file.

##Versioning
The previous version of the site will always be stored, so that it can be rolled back to easily. This is handled by the `rollback` task.

##Requirements and Installation
You'll need to install [Pake](https://github.com/indeyets/pake "Pake") first. Simply clone this Git repo into a directory just above the core Symfony directory, rename or copy sample-pake_properties.ini to pake-properties.ini, then make a Pakefile just above the directory containing Compose (see below). Your resulting directory structure should look like:

```
- my-site
    - Symfony
    - compose
        - compose files…
    - pakefile.php
```

Easy!

##Example Pakefile

A typical Pakefile may look like this, and sit in the directory just above the Compose scripts:

```php
require_once("compose/pake_helpers.php");
require_once("compose/default_pakefile.php");
require_once("compose/symfony_pakefile.php");

// Load properties
pake_properties("compose/pake_properties.ini");

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
```

##Links

- [Pake](https://github.com/indeyets/pake "Pake")
- [Symfony](http://symfony.com/ "Symfony")

