<?php

function logInToMediaWiki($domain) {
  global $config;

  if(array_key_exists('client', $config[$domain]))
    return $config[$domain]['client'];

  $c = $config[$domain];  
  $config[$domain]['client'] = new MWClient($c['api'], $c['username'], $c['password']);
  return $config[$domain]['client'];
}

function pageTitleToFilename($title) {
  return str_replace('+',' ',urlencode(str_replace(array('_','/'), array(' ','--'), $title)));
}

function filenameToPageTitle($name) {
  return urldecode(str_replace(array(' ','--'), array(' ','/'), $name));
}



class MWClient {
  private $_cookie = FALSE;
  private $_api;

  public function __construct($api, $username, $password) {
    $this->_api = $api;
    $login = $this->request('login', array(
      'lgname' => $username,
      'lgpassword' => $password
    ));

    if($login && property_exists($login->login, 'token')) {
      $token = $login->login->token;
      $login = $this->request('login', array(
        'lgname' => $username,
        'lgpassword' => $password,
        'lgtoken' => $token
      ));

      if($login && property_exists($login->login, 'lgusername')) {
        echo "[$api] Logged in as: " . $login->login->lgusername . "\n";
        return TRUE;
      } else {
        throw new Exception('MediaWiki Login failure after second attempt: $api');
      }
    } else {
      throw new Exception('MediaWiki Login failure: $api');
    }
  }
  
  public function request($action, $params=FALSE) {
    $ch = curl_init();
    
    if(is_array($params)) {
      curl_setopt($ch, CURLOPT_URL, $this->_api);
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array_merge(array(
        'action' => $action,
        'format' => 'json'
      ), $params)));
    } else {
      $params = array(
        'action' => $action,
        'format' => 'json'
      );
      curl_setopt($ch, CURLOPT_URL, $this->_api . '?' . http_build_query($params));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    
    if($this->_cookie) {
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie: ' . $this->_cookie));
    }
    
    $json = curl_exec($ch);

    list($headers, $json) = explode("\r\n\r\n", $json);
    if(preg_match('/Set-Cookie: (cyborg_wiki_session=[^;]+)/i', $headers, $match)) {
      $this->_cookie = $match[1];
    }
    return json_decode($json);
  }
  
  public function editPage($pageTitle, $text) {
    $response = $this->request('query', array('titles'=>$pageTitle, 'prop'=>'info', 'intoken'=>'edit'));
    print_r($response);
    echo "\n";
    
    if($response && property_exists($response->query, 'pages')) {
      
      $pages = $response->query->pages;
      $page = get_object_vars($pages);
      $page = array_pop($page);
      $token = $page->edittoken;
        
      /* 
       * MediaWiki provides some ways to prevent editing conflicts
       
          basetimestamp  - Timestamp of the base revision (gotten through prop=revisions&rvprop=timestamp).
                           Used to detect edit conflicts; leave unset to ignore conflicts.
          starttimestamp - Timestamp when you obtained the edit token.
                           Used to detect edit conflicts; leave unset to ignore conflicts
       */
      $response = $this->request('edit', array('title'=>$pageTitle, 'text'=>$text, 'summary'=>'--changed-in-dropbox--', 'token'=>$token));
      print_r($response);
      echo "\n";
      
      return TRUE;
    }
    
    return FALSE;
  }
}

class WikiSyncDB {
  private $_filename = 'db.json';
  private $_data;

  public function __construct() {
    if(!file_exists($this->_filename)) {
      $this->_data = new StdClass;
      file_put_contents($this->_filename, '{}');
    } else {
      $this->_data = json_decode(file_get_contents($this->_filename));
    }
  }
  
  public function get($domain, $key) {
    $this->_setup($domain);
    if(property_exists($this->_data->$domain, $key))
      return $this->_data->$domain->$key;
    else
      return FALSE;
  }
  
  public function set($domain, $key, $val) {
    $this->_setup($domain);
    $this->_data->$domain->$key = $val;
  }
  
  private function _setup($domain) {
    if(!property_exists($this->_data, $domain))
      $this->_data->$domain = new StdClass;
  }
  
  public function write() {
    file_put_contents($this->_filename, json_encode($this->_data)); 
  }
  
  public static function hash($string) {
    return sha1($string);
  }
  
  public static function hashFile($filename) {
    return sha1_file($filename);
  }  
}

