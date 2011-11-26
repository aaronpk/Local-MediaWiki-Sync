Local MediaWiki Editing
=======================

The purpose of this project is to support local editing of a MediaWiki installation.

This works by downloading a copy of all the pages in a MediaWiki installation to local files.
Then, using a plaintext editor such as NotationalVelocity, the files can be edited on a
local machine. The sync script will upload any local changes to the wiki when it is run.

This is designed to be used with Notational Velocity and Dropbox. 


Notational Velocity
-------------------

Notational Velocity is a simple editor designed to quickly edit large volumes of simple documents.
It can optionally store its documents in plain text files in a folder of your choosing. 


Dropbox
-------

Dropbox is an excellent file sync tool that lets you share a folder between multiple computers
or people. It is free for accounts that can store up to 2GB and they have paid plans for more
storage. You can read more here: http://aaron.pk/dropbox

We take advantage of Dropbox's excellent synchronization capability to handle the cases where
you may be editing the local files with no Internet connection. Dropbox will upload the files 
once you have an Internet connection again.


Overview
========

The ideal setup is this:

* You use Notational Velocity to edit the files on your local computer
* Notational Velocity saves its files to a folder that is managed by Dropbox
* Dropbox will upload changes, which will be received by a Linux client on a server somewhere
* The Linux server will run this script, making changes to the wiki content
* If the wiki is updated, the Linux client will download changes into its Dropbox folder, which will be synced back to your local machine via Dropbox


Setup
=====

There are two ways of running this script, with or without Dropbox. If you do not use Dropbox, you
may have to add a bit of error checking code to handle the cases when you try to sync but are offline.

If you don't wish to use Dropbox, you can simply run this script on the local machine that you 
want to edit the files on. If you do want to use Dropbox, you will need to install this script on
a computer that has a permanent Internet connection for optimal performance.

My preferred setup is running this script on a Linux server using the Linux Dropbox client.

Sync Setup
----------

Copy config.template.php to a file named config.php. Fill in your wiki URL, username and
password you'd like to use for the edits that will be made from local files. Set your Dropbox
path to the base URL where you'd like the files to be saved.

When you run import.php, the script will crawl all wikis defined in the config file and get the list
of all pages in the wiki. They will be downloaded into the dropbox folder you previously defined.
The naming convention for files is:

```
wiki.example.com -- Page Title.txt
```

Any special characters will be URL-encoded so they don't conflict with the filesystem's conventions,
with the exception of slashes which are converted to a double-hyphen (--).

Next, set up the sync.php script to run as a cron job every 1-5 minutes. You can do this by adding
something like this to your crontab file:

```
* * * * * /usr/bin/php /path/to/sync.php >> /var/log/mediawiki-sync.log 2>&1
```

The script will begin looking for changes to the remote wiki and will update the local files accordingly.
When the local files are modified, the script will attempt to update the remote MediaWiki pages
with the contents of the local file. 



Notes
=====

It is not possible to rename pages in MediaWiki from the text files. The sync script only looks
for changes to the file contents, not files that have been moved.

Avoid naming pages with characters that aren't allowed in filenames. Sticking to the alphabet and
spaces and hyphens is safe. 

This script errs on the side of caution, and will try to avoid overwriting your local files with
remote changes if it's unsure of who has the latest version. MediaWiki has good tools for examining
diffs and reverting changes, so it's usually safe to make changes there rather than potentially
losing changes you made to your local copy.

