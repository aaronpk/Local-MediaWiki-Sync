<?php

$config = array();

$config['example.com'] = array(
  'username' => 'user',
  'password' => 'pass3333',
  'namespaces' => array(
    '' => 0,
    'User' => 2,
    'Template' => 10
  ),
  'baseurl' => 'http://example.com/',
  'root' => 'http://example.com/wiki/index.php',
  'api' => 'http://example.com/wiki/api.php',
  'local' => '/web/sites/example.com/backup/data/',
  'gitremotes' => ['origin'],
  'gitemail' => 'user@example.com',
);

