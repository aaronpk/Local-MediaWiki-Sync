Local MediaWiki Sync
====================

The purpose of this project is to support archiving a MediaWiki installation as simple text and image files.

This works by downloading a copy of all the pages in a MediaWiki installation to local files.


Setup
=====

Copy config.template.php to a file named config.php. Fill in your wiki URL, username and
password for the wiki. Set the local path for where to save the files.

The first time you install this, you'll need to run import.php, which will crawl all wikis defined 
in the config file and get the list of all pages in the wiki. They will be downloaded into the
folder you previously defined.

The naming convention for files is:

```
/P/Page Title.txt
```

Pages are put into a subfolder based on their first character since very large wikis will cause too many
files to be in the parent folder otherwise, making navigating the directory challenging.

Any special characters will be URL-encoded so they don't conflict with the filesystem's conventions,
with the exception of slashes which will cause the file to be put into a folder.

Images will be downloaded into an "images" folder, following the same naming convention.

Next, set up the sync.php script to run as a cron job every 1-5 minutes. You can do this by adding
something like this to your crontab file:

```
* * * * * /usr/bin/php /path/to/sync.php >> /var/log/mediawiki-sync.log 2>&1
```

The script will begin looking for changes to the remote wiki and will update the local files accordingly.

