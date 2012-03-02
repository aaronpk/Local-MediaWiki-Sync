<?php
chdir(dirname(__FILE__));

include('config.php');
include('include.php');

$db = new WikiSyncDB();

/*
  From Dropbox:
  	Find files modified after the last time the script was run
  	Use the filename as the upload filename
  	Get an edit token from mediawiki (http://www.mediawiki.org/wiki/API:Upload)
  	Post to MediaWiki with the raw file data
*/

foreach($config as $wikiDomain=>$wikiConfig) {
  if(file_exists($uploadPath . $wikiDomain)) {
    $files = scandir($uploadPath . $wikiDomain);
    foreach($files as $filename) {
      if(substr($filename, 0, 1) == '.') continue;
      
      $absFilename = $uploadPath . $wikiDomain . '/' . $filename;
      
      # If the local file was modified after the last wiki update, upload the new page contents
      if(filemtime($absFilename) > $db->get($wikiDomain, $filename . ' Uploaded')) {
        $mw = logInToMediaWiki($wikiDomain);
        
        echo $filename . "\n";
        echo "\tFile updated: " . date('Y-m-d H:i:s', filemtime($absFilename)) . "\n";
        echo "\tWiki Version updated: " . date('Y-m-d H:i:s', $db->get($wikiDomain, $filename . ' Uploaded')) . "\n";
        echo "\tUploading new article content...";
        
        $response = $mw->uploadFile($filename, $absFilename);
        print_r($response);
        $db->set($wikiDomain, $filename . ' Uploaded', filemtime($absFilename));
        echo "done\n\n";
      }
  
    }
  }
}

$db->write();

