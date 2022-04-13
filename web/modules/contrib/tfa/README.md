## Two-factor Authentication (TFA) module for Drupal

TFA is a base module for providing two-factor authentication for your Drupal
site. As a base module, TFA handles the Drupal integration work,
providing flexible and well tested interfaces to enable seamless, and
configurable, choice of various two-factor authentication solutions like
Time-based One Time Passwords, SMS-delivered codes, recovery codes, or
integrations with third-party suppliers like Authy, Duo and others.

Read more about the features and use of TFA at its Drupal.org project page at
https://drupal.org/project/tfa

### Installation and use

TFA module can be installed like other Drupal modules by placing this directory
in the Drupal file system (for example, under modules/) and enabling on
the Drupal modules page.

TFA module does not come with any plugins of its own so refer to the project
page for contributed plugins or read the section on Plugin development.

### Configuration

TFA can be configured on your Drupal site at Administration - Configuration -
People - Two-factor Authentication. Available plugins will be listed along with
their type and configured use, if set.

Additionally, a permission is exposed to Drupal roles allowing them to skip the
TFA process -- regardless of plugins and the "require TFA" setting.

#### Default validation plugin

The plugin that will be used by default during user authentication. The plugin
must be ready for use by the authenticating account. If "Require TFA" is marked
then an account that has not setup TFA with the validation plugin will be unable
to log in.

### Plugin development

TFA plugins provide the form and validation handling for 2nd factor
authentication of a user. The TFA module will interrupt a successful username
and password authentication and begin the TFA process (see Configuration for
exceptions to this statement), passing off the form control and validation to
the active plugin.

#### Getting started

This module requires a few dependencies to be setup before it can be configured.

##### Encryption Method - [Real AES](https://www.drupal.org/project/real_aes)

An encryption method module is required to be able to use the Key and Encrypt
modules. Real AES is not the only method available, another is [Sodium](https://www.drupal.org/project/sodium).

* Install an encryption method module according to their instructions.

##### [Key](https://www.drupal.org/project/key)

The key module provides Drupal access to an encryption key you create. Setting
up the key module:

* Install the Key module.
* Generate a new key on the command line in OSX:
    `dd if=/dev/urandom bs=32 count=1 | base64 -i - > path/to/my/encrypt.key`
* Visit the Keys module's configuration page and "Add Key"
    * Name your Key
    * Key type: "Encryption"
    * Provider: "File"
    * File location: `path/to/my/encrypt.key` as generated above.
    * Save

##### [Encrypt](https://www.drupal.org/project/encrypt)

The encrypt module allows the site owner to define encryption profiles that
can be reused throughout Drupal. The TFA module requires an encryption profile
to be defined to be configured properly.

* Install the Encrypt module
* Visit the Encrypt module's configuration page and "Add Encryption Profile"
    * Label your Encryption Profile
    * Encryption method: "Authenticated AES (Real AES)" - or the encryption
      method of your choice.
    * Encryption Key: Select the Key you created in the previous step.
    * Save

##### TFA Configuration

Now you should be ready to configure the TFA module.

* Install the TFA module
* Install a validation plugin (example: [GA Login](https://www.drupal.org/project/ga_login))
* Visit the TFA module's configuration page.
    * Enable TFA
    * Select your desired Validation Plugin(s).
    * Encryption Profile: Select the Encryption Profile you created in the
     previous step.
    * Adjust other settings as desired.
    * Save
* Grant "Set up TFA for account" to "Authenticated user"
    * Consider granting "Require TFA process" for some roles
* Visit your account's Security tab: `user/[uid]/security/tfa`
    * Configure the selected Validation Plugins as desired for your account.

##### TFA, Testing, and Development

It can be hard to test user authentication in automated tests with the TFA
module enabled. Development environments also will likely struggle to login
unless they disable TFA or reset the secrets for an account. One solution is
to disable the module in the development and testing environment. To quickly
disable the module you can run these drush commands to set some config:

* Disable TFA with `drush config-set tfa.settings enabled 0`
* Enable TFA with `drush config-set tfa.settings enabled 1`
