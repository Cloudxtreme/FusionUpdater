# FusionUpdater
A script that will update FusionInvoice.

## Use at your own risk!!
This works on my server with my setup. Before you download this and run it please please please pleeeaaassseee set up a testing install of fusioninvoice and run it in there. Otherwise you might lose data or get permissions messed up or anything - really anything could happen. Your server could start farting unicorns... So please test this in a safe environment before installing it to your base fusioninvoice install.

#### TL;DR: Set up testing FI install and test this before running it on your production install.

## Readme too!
So you need to set up the config with your username and password and stuff see `update-config.php`. There are only a handfull of things you gotta set up first and then it's good to go - of course test the script before running it.

## Issues/suggestions/doesn't work on your server
Let me know in the issue tracker: https://github.com/blakethepatton/FusionUpdater/issues

## How it works
* Connects to https://www.fusioninvoice.com
* Logs into your account with provided credentials
* Sorts your active products by expiration date
  * Only pulls products titled 'FusionInvoice'
* Downloads the zip archive
* Logs out of your account
* Extracts the following folders to your base FI folder (see update-config.php to set that)
  * app
  * assets
  * database
  * resources
* Deletes all of its temp files
* Pauses, then iff no errors...
* Redirects you to the setup script (migration step)
