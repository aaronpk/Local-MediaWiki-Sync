<?php
chdir(dirname(__FILE__));

include('config.php');
include('include.php');

/*
  From Mediawiki:
  	Read the RecentChanges feed and find articles that have been updated
  	Fetch the raw article text, and save to a file
  	Any files that return 404 will be deleted on disk
*/

$since = last_synced_date();

foreach($config as $wikiDomain=>$wikiConfig) {
	chdir($wikiConfig['local']);
  
  $mw = logInToMediaWiki($wikiDomain);
  
  $namespaces = array_flip($wikiConfig['namespaces']);
  
  $changedPages = array();
  $changes = $mw->getRecentChanges($since);
  if($changes && property_exists($changes, 'query') && property_exists($changes->query, 'recentchanges')) {
	  foreach($changes->query->recentchanges as $change) {
  	  $ns = $change->ns;
  	  $title = $change->title;
  	  
      if(array_key_exists($ns, $namespaces)) {
        $relativefilename = pageTitleToFilename($title, $namespaces[$ns]) . '.txt';
  	    $filename = $wikiConfig['local'] . $relativefilename;
  	    echo $filename . "\n";
    		$url = $wikiConfig['baseurl'] . pageTitleToURL($title);
    		$result = download_page($title, $filename, $url, $wikiDomain, $mw);
    		if($result == -1) {
    			echo "\tdeleted\n";
      		$cmd = '/usr/bin/git rm '.escapeshellarg($relativefilename);
      		echo $cmd."\n";
          echo shell_exec($cmd);
    		} else {
      		$cmd = '/usr/bin/git add '.escapeshellarg($relativefilename);
      		echo $cmd."\n";
          echo shell_exec($cmd);
    		}
        $cmd = '/usr/bin/git -c '.escapeshellarg('user.name='.$change->user).' commit -m '.escapeshellarg($change->comment);
    		echo $cmd."\n";
        echo shell_exec($cmd);
      }
	  }
  }
  
  foreach($wikiConfig['gitremotes'] as $remote) {
    $cmd = '/usr/bin/git push '.$remote.' master';
    echo $cmd."\n";
    echo shell_exec($cmd);
  }
} // foreach wiki

set_last_synced_date();
