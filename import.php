<?php
chdir(dirname(__FILE__));

include('config.php');
include('include.php');


// Warning: This code has not been tested in a while!


foreach($config as $domain=>$c) {
  $mw = logInToMediaWiki($domain);
  
  if(!file_exists($c['local']))
	mkdir($c['local']);
  if(!file_exists($c['local'].'images/'))
    mkdir($c['local'].'images/');

  $continue = true;
  $from = false;
  while($continue == true) {
    $opts = array('list'=>'allimages', 'ailimit'=>500);
    if($from) {
      $opts['aifrom'] = $from;
    }
	  $files = $mw->request('query', $opts);
	  foreach($files->query->allimages as $file) {
      echo "Downloading ".$file->name."\n";
      $filename = $c['local'].'images/'.$file->name;
      $fp = fopen($filename, 'w+');
      $ch = curl_init($file->url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_exec($ch);
      curl_close($ch);
      fclose($fp);
      touch($filename, strtotime($file->timestamp), strtotime($file->timestamp));
	  }

	  if(property_exists($files, 'query-continue')) {
		  $from = $files->{'query-continue'}->allimages->aifrom;
		  echo "\n";
	  } else {
		  $continue = false;
	  }
  }

  foreach($c['namespaces'] as $namespace => $nsid) {
	  echo $namespace . "\n";
	
	  $files_seen = array();
	
	  $continue = true;
	  $from = false;
	  while($continue == true) {
      $opts = array(
        'list' => 'allpages', 
        'aplimit' => 500, 
        'apfilterredir' => 'nonredirects',
        'apnamespace' => $nsid
      );
      if($from) {
	      $opts['apfrom'] = $from;
      }
	      
		  $pages = $mw->request('query', $opts);
		  foreach($pages->query->allpages as $page) {
		    $filename = $c['local'] . pageTitleToFilename($page->title, $namespace) . '.txt';
		    if(!file_exists($filename)) {
  		    echo $filename . "\n";
    			$url = $c['baseurl'] . pageTitleToURL($page->title);
    			download_page($page->title, $filename, $url, $domain, $mw);
    			$files_seen[] = strtolower($filename);
  			}
		  }
		  
		  if(property_exists($pages, 'query-continue')) {
			  $from = $pages->{'query-continue'}->allpages->apfrom;
			  echo "\n";
		  } else {
			  $continue = false;
		  }
	  }

	  // now find all the redirect pages
	  $continue = true;
	  $from = false;
	  while($continue == true) {
      $opts = array(
        'list' => 'allpages', 
        'aplimit' => 500, 
        'apfilterredir' => 'redirects',
        'apnamespace' => $nsid
      );
      if($from) {
	      $opts['apfrom'] = $from;
      }
	      
		  $pages = $mw->request('query', $opts);
		  foreach($pages->query->allpages as $page) {
		    $filename = $c['local'] . pageTitleToFilename($page->title, $namespace) . '.txt';
		    
		    // If there is not already a file with the same case-insensitive name, download the redirect
		    // This means redirects won't be downloaded if they are the same name as a page
		    if(!in_array(strtolower($filename), $files_seen)) {
			    echo $filename . "\n";
  				$url = $c['baseurl'] . pageTitleToURL($page->title);
  				download_page($page->title, $filename, $url, $domain, $mw);
  				$files_seen[] = strtolower($filename);
  			}
      }
	
		  if(property_exists($pages, 'query-continue')) {
			  $from = $pages->{'query-continue'}->allpages->apfrom;
			  echo "\n";
		  } else {
			  $continue = false;
		  }
	  }
  }
}

set_last_synced_date();

echo "Complete!\n";
