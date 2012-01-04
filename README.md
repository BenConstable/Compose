#My Personal Site
The source code for my personal site, built using Symfony. This is a bit of an experiment into 
deployment using Git and Pake, as well as the Symfony2 PHP framework.

##Deployment
Deployment boils down to:

```
pake deploy {branch}
```

This will:

- Push the local branch to GitHub
- Push the local branch to the server
- Move the work tree to the site's webroot on the server
- Archive the current version of the site
- Make the new version of the site 'live'

'live' is in inverted commas here because each site has both a `dev` and `live` branch. You can put
a site live to the `dev` version, or to the `live` version. This is useful for testing on the live
environment.

##Setup
My local setup is a standard git repo with two braches: `live` and `dev`. On the server, I have a
bare git repo for each site in the `/var/git/` directory. This is referenced as a remote git repo called
`server`. Keeping git in it's own directory means that version control is nicely separated from
the actual site files.

My server uses Ubuntu running Apache. Each site sits in `/var/www/sitename/` and has two subfolders;
`live` and `dev`, to correspond to each git branch. Deploying a branch will push local changes
to the `server` remote, and then checkout the branch to the correct webroot subfolder. Simple.

##Versioning
Each branch subfolder contains a hidden directory called `.versions` and a visible directory called
`current`. `.versions` will usually contain two versions of the site: the current one and it's
predecessor. `current` is simply a symbolic link to the latest version in this folder. The versions
are named `site~timestamp` and are easily referenced.

I keep a previous version so that if there are any errors with deployment, or any unforseen issues with new
code, the `current` directory can easily be linked back whilst the issues are resolved.

##Using Pake
`pake deploy {branch}` is divided into 3 subtasks. These are `push {branch}`, `remove_old_version {branch}`
and `deploy {branch}`. Dividing everything up in this way means I can, if I want to, just push everything or just
remove an old version.

Config is separated out into `pake_properties.ini`, so that the script remains independent of the site I'm working on.

##Links

- [Pake](https://github.com/indeyets/pake "Pake")
- [Symfony](http://symfony.com/ "Symfony")

