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
  $mw = logInToMediaWiki($wikiDomain);
  
  $changedPages = array();
  $changes = $mw->getRecentChanges($since);
  if($changes && property_exists($changes, 'query') && property_exists($changes->query, 'recentchanges')) {
	  foreach($changes->query->recentchanges as $change) {
		  if(!array_key_exists($change->title, $changedPages)) {
			  $changedPages[$change->title] = $change->ns;
		  }
	  }
  }
  if(count($changedPages) > 0) {
    print_r(array_keys($changedPages));
  }

  $namespaces = array_flip($wikiConfig['namespaces']);
  
  foreach($changedPages as $title=>$ns) {
    // Only sync namespaces defined in the config file
    if(array_key_exists($ns, $namespaces)) {
	    $filename = $wikiConfig['local'] . pageTitleToFilename($title, $namespaces[$ns]) . '.txt';
	    echo $filename . "\n";
		$url = 'http://indiewebcamp.com/' . pageTitleToURL($title);
		$result = download_page($title, $filename, $url, $wikiDomain, $mw);
		if($result == -1) {
			echo "\tdeleted\n";
		}
	}
  }
  
} // foreach wiki

set_last_synced_date();
