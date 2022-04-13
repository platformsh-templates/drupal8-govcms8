
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Frequently Asked Questions (FAQ)
 * Known Issues
 * How Can You Contribute?


INTRODUCTION
------------

Maintainer: hass <https://drupal.org/user/85918>
Project Page: https://drupal.org/project/robotstxt

Use this module when you are running multiple Drupal sites from a single code
base (multisite) and you need a different robots.txt file for each one. This
module generates the robots.txt file dynamically and gives you the chance to
edit it, on a per-site basis.

For developers, you can automatically add paths to the robots.txt file by
implementing hook_robotstxt(). See robotstxt.api.php for more documentation.


INSTALLATION
------------

See https://drupal.org/getting-started/install-contrib for instructions on
how to install or update Drupal modules.

Once you have the RobotsTxt modules installed, make sure to delete or rename
the robots.txt file in the root of your Drupal installation. Otherwise, the
module cannot intercept requests for the /robots.txt path.


FREQUENTLY ASKED QUESTIONS
--------------------------

Q: Can this module work if I have clean URLs disabled?
A: Yes it can! In the .htaccess file of your Drupal's root directory, add the
   following two lines to the mod_rewrite section, immediately after the line
   that says "RewriteEngine on":

   # Add redirection for the robots.txt path for use with the RobotsTxt module.
   RewriteRule ^(robots.txt)$ index.php?q=$1

Q: Does this module work together with Drupal Core "Fast 404 pages" feature?
A: Yes, but you need to add robots.txt to the 'exclude_paths' of your 
   settings.php.
   
   Default Drupal:
   $config['system.performance']['fast_404']['exclude_paths'] = '/\/(?:styles)|(?:system\/files)\//';

   Drupal with RobotsTxt module:
   $config['system.performance']['fast_404']['exclude_paths'] = '/\/(?:styles)|(?:system\/files)\/|(?:robots.txt)/';

Q: How can I install the module with custom default robots.txt?
A: The module allows adding a default.robots.txt to the defaults folder.

   1. Remove the robots.txt from site root.
   2. Save your custom robots.txt to "/sites/default/default.robots.txt"
   3. Run the module installation.

Q: Is there a way to automatically delete robots.txt provided by Drupal core?
A: Yes, if you are using composer to build the site, you can add a command
   into your composer.json that will make sure the file gets deleted. Depending
   on your project's structure, you will need to add one of the two following
   sections into the composer.json of your root folder:

   If the drupal site root folder is the same as your repository root folder:

   "scripts": {
       "post-install-cmd": [
           "test -e robots.txt && rm robots.txt || echo robots already deleted"
       ],
       "post-update-cmd": [
           "test -e robots.txt && rm robots.txt || echo robots already deleted"
       ]
   }

   or, if the drupal site root folder is web/ :

   "scripts": {
       "post-install-cmd": [
           "test -e web/robots.txt && rm web/robots.txt || echo robots already deleted"
       ],
       "post-update-cmd": [
           "test -e web/robots.txt && rm web/robots.txt || echo robots already deleted"
       ]
   }

   The script will run every time you do a composer install or composer update.

   Please note: Only scripts defined on composer.json on the root folder will be
   executed. See https://getcomposer.org/doc/articles/scripts.md

KNOWN ISSUES
------------

There are no known issues at this time.

To report new bug reports, feature requests, and support requests, visit
https://drupal.org/project/issues/robotstxt.


HOW CAN YOU CONTRIBUTE?
---------------------

- Report any bugs, feature requests, etc. in the issue tracker.
  https://drupal.org/project/issues/robotstxt
