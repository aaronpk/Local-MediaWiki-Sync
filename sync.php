<?php
chdir(dirname(__FILE__));

include('config.php');
include('include.php');

$db = new WikiSyncDB();

/*
  From Mediawiki:
  	Read the RecentChanges feed and find articles that have been updated
  	Fetch the raw article text, and save to a file named wiki.example.com -- PageTitle.txt in the dropbox folder
*/


foreach($config as $wikiDomain=>$wikiConfig) {
  $mw = logInToMediaWiki($wikiDomain);
  
  $feed = $mw->getRecentChanges();
  $xml = simplexml_load_string($feed);
  
  foreach($xml->entry as $entry) {
    if(preg_match('/--changed-in-dropbox--/', (string)$entry->summary) == FALSE) {
      if(preg_match('/diff=(\d+)/', $entry->id, $match)) {
        $oldid = $match[1];
        $title = $entry->title;
        
        $filename = $dropboxPath . $wikiDomain . ' -- ' . pageTitleToFilename($title) . '.txt';
        
        if(preg_match('/^File:/', $title)) {
          echo "$title is a file, skipping…\n";
          continue;
        }
        
        # If the remote wiki article was updated after the last date we downloaded the article
        if(strtotime($entry->updated) > $db->get($wikiDomain, $title . ' WikiUpdated')
          # and if the remote wiki article was updated after our local file was modified
          && (!file_exists($filename) || strtotime($entry->updated) > filemtime($filename))
        ) {
          # ...then download the new wiki article
          $db->set($wikiDomain, $title . ' WikiUpdated', strtotime($entry->updated));
          echo $title . "\n";
          echo "\t" . $filename . "\n";
          echo "\tWiki page updated: " . date('Y-m-d H:i:s', strtotime($entry->updated)) . "\n";
          echo "\tLocal page updated: " . date('Y-m-d H:i:s', $db->get($wikiDomain, $title . ' WikiUpdated')) . "\n";
          if(file_exists($filename))
            echo "\tFile updated: " . date('Y-m-d H:i:s', filemtime($filename)) . "\n";
          else
            echo "\tFile not found on disk\n";
          echo "\twriting new file\n";
          echo "\n";
          $source = $mw->getPage($oldid);
          file_put_contents($filename, $source);
          touch($filename, strtotime($entry->updated), strtotime($entry->updated));
        }
      }
    }
  }
}

/*
  From Dropbox:
  	Find files modified after the last time the script was run
  	Parse the page name out of the filename
  	Post to MediaWiki with the raw page content
*/

$files = scandir($dropboxPath);
foreach($files as $f) {
  if(preg_match('/([a-z0-9\.-_]+) -- (.+)\.txt/i', $f, $match)) {
    $domain = $match[1];
    $file = $match[2];
    $filename = $dropboxPath . $match[0];
    $title = filenameToPageTitle($file);
    
    if(array_key_exists($domain, $config)) {

      # If the local file was modified after the last wiki update, upload the new page contents
      $synctime = $db->get($domain, $title . ' WikiUpdated');
      if(filemtime($filename) > $synctime) {
        $mw = logInToMediaWiki($domain);
        
        echo $f . "\n";
        echo "\t" . $title . "\n";
        echo "\tFile updated: " . date('Y-m-d H:i:s', filemtime($filename)) . "\n";
        echo "\tWiki updated: " . date('Y-m-d H:i:s', $synctime) . "\n";
        echo "\tUploading new article content...";
        
        $mw->editPage($title, file_get_contents($filename));
        $db->set($domain, $title . ' WikiUpdated', filemtime($filename));
        echo "done\n\n";
      }
      
    }
  }
}


$db->write();

