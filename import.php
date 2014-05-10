<?php
chdir(dirname(__FILE__));

include('config.php');
include('include.php');

$db = new WikiSyncDB();
$i = 0;

function download_page($title, $filename, $domain, $mw) {
	global $db;

    $response = $mw->request('query', array('prop'=>'info', 'titles'=>$title));
    $pages = $response->query->pages;
    $info = get_object_vars($pages);
    $info = array_pop($info);

    $wikitext = $mw->getPage($info->lastrevid);
    
    if(!file_exists(dirname($filename)))
      mkdir(dirname($filename));

    file_put_contents($filename, $wikitext);
    touch($filename, strtotime($info->touched), strtotime($info->touched));
    $db->set($domain, $title . ' WikiUpdated', strtotime($info->touched));
    
    return $filename;
}

foreach($config as $domain=>$c) {
  $mw = logInToMediaWiki($domain);
  
  if(!file_exists($c['local']))
	mkdir($c['local']);
  if(!file_exists($c['local'].'images/'))
    mkdir($c['local'].'images/');

/*
  $files = $mw->request('query', array('list'=>'allimages', 'ailimit'=>5000));
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
      $db->set($domain, 'images/' . $file->name . ' Timestamp', strtotime($file->timestamp));
      $i++;
      if($i % 10 == 0)
        $db->write();
  }
*/

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
		    $filename = $c['local'] . pageTitleToFilename($page->title) . '.txt';
		    echo $filename . "\n";
    
			download_page($page->title, $filename, $domain, $mw);
			$files_seen[] = strtolower($filename);
		    
		    $i++;
		    if($i % 10 == 0)
		      $db->write();
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
		    $filename = $c['local'] . pageTitleToFilename($page->title) . '.txt';
		    
		    // If there is not already a file with the same case-insensitive name, download the redirect
		    // This means redirects won't be downloaded if they are the same name as a page
		    if(!in_array(strtolower($filename), $files_seen)) {
			    echo $filename . "\n";
				download_page($page->title, $filename, $domain, $mw);
				$files_seen[] = strtolower($filename);

			    $i++;
			    if($i % 10 == 0)
			      $db->write();
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

$db->write();

echo "\n";
