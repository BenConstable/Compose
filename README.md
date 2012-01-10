#Compose
Compose is a Git based PHP deploy tool for web applications. It is targeted and deploying Symfony 2 projects.

##Pake
Compose is built on Pake, a PHP build script similar to Rake or Make. Compose uses Pake to keep everything in PHP - why use other languages when you can do everything with one?

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

##Requirements
You'll need to install [Pake](https://github.com/indeyets/pake "Pake") first. To make sure that the correct permissions are setup for the Symfony cache/ and logs/ directories you'll need to have a system with `chmod +a` setup, or install [setfacl]( "setfacl"). The Symfony website has [more details](http://symfony.com/doc/current/book/installation.html "Symfony Configuration").

##Links

- [Pake](https://github.com/indeyets/pake "Pake")
- [Symfony](http://symfony.com/ "Symfony")

