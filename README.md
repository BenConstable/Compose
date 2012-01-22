#Compose - Symfony and general PHP application deployment
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

##Versioning
The previous version of the site will always be stored, so that it can be rolled back to easily. This is handled by the `rollback` task.

##Setup and usage

###Symfony and everything else
- Install everything (see below)
- Copy sample_pake_properties.ini to pake_properties.ini
- Change the settings in your newly copied file to get everything ready
- Make a custom Pakefile (pakefile.php) in your project root (see below for an example)
- Your local directory should now look like:

```
- Compose
    - compose files…
- pakefile.php
- Your website files… 
```

Once you've run the appropriate setup commands (see below), just point your vhost web root to your desired environment files and you're away!

###Everything else
Simply run `pake setup_compose` in your project root to set everything up. When you want to deploy, commit all your changes with Git and run `pake deploy {environment}`.

###Symfony
Run `pake symfony_setup_compose` in your project to do almost all the setup you'll need. When this has finished (which may take a couple of minutes), you'll need to jump on the server and use `chmod +a` or `setfacl` ([see here](http://symfony.com/doc/current/book/installation.html#configuration-and-setup "Symfony configuration")) to make sure that your `cache` and `log` directories (they're found in `your-environment/shared/Symfony/app`) are writeable by both your web and ssh users. You'll also need to make any appropriate changes to your .htaccess file (`your-environment/shared/Symfony/web`).

Finally, change your .gitignore file to include the following:

```
/Symfony/vendor*
/Symfony/app/cache*
/Symfony/app/logs*
/Symfony/bin*
/Symfony/deps*
/Symfony/web/.htaccess
/Syfmony/web/bundles*
```

After this, commit all your changes with Git and run `pake deploy {environment}` and you'll be up and running.

##Requirements and installation

You'll need:

- To install [Pake](https://github.com/indeyets/pake "Pake")
- To have Git setup locally and on your server
- rsync if you're going to be using Compose to deploy Symfony applications

Simply clone this Git repo into your project root (just above the Symfony directory).

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
 * 
 * This example shows the dependencies you'll need to deploy
 * a Symfony project.
 *
 * @param $obj  Task object
 * @param $args Command line arguments
 */
pake_desc("Deploy!");
pake_task('deploy', 'default_deploy', 'symfony_deploy' /* omit the symfony deploy dependency for a non-Symfony application */);
function run_deploy($obj, $args)
{
    pake_echo_action("deploy", "finished!");
}

?>
```

##Links

- [Pake](https://github.com/indeyets/pake "Pake")
- [Symfony](http://symfony.com/ "Symfony")

