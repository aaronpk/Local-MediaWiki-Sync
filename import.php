<?php
chdir(dirname(__FILE__));

include('config.php');
include('include.php');

$db = new WikiSyncDB();
$i = 0;

foreach($config as $domain=>$c) {
  $mw = logInToMediaWiki($domain);
  
  $pages = $mw->request('query', array('list'=>'allpages', 'aplimit'=>5000));
  foreach($pages->query->allpages as $page) {
    $response = $mw->request('query', array('prop'=>'info', 'titles'=>$page->title));
    $pages = $response->query->pages;
    $info = get_object_vars($pages);
    $info = array_pop($info);

    $filename = $dropboxPath . $domain . ' -- ' . pageTitleToFilename($page->title) . '.txt';
    echo $filename . "\n";
    
    $wikitext = $mw->getPage($info->lastrevid);
    file_put_contents($filename, $wikitext);
    touch($filename, strtotime($info->touched), strtotime($info->touched));
    $db->set($domain, $page->title . ' WikiUpdated', strtotime($info->touched));
    
    $i++;
    if($i % 10 == 0)
      $db->write();
  }  
}

$db->write();

echo "\n";
