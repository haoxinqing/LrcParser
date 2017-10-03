<?php
/********************************************************************
NetEase Config class
********************************************************************/

// the proxy to use for connections to lyric server.
// leave it empty for no proxy.
// this is only supported with PEAR.
define ('PROXY', "");
define ('PROXY_PORT', "");

class netease_config {
  var $website;
  var $timeout;
  var $lyricext;
  var $cachedir;
  var $usecache;
  var $storecache;
  var $cache_expire;
  var $tempdir;

  function netease_config()
  {
    $this->protocol_prefix = "http://"; // protocol prefix
    $this->website = "music.163.com/api/song/"; // lyric server to use
    $this->timeout = 60;  // timeout for retriving info, uint in second
    $this->lyricext = ".lrc"; // the extension of the lyric file
    $this->cachedir = "./netease/cache";  // cachedir should be writable by the webserver. This doesn't need to be under documentroot.
    $this->usecache = true; // whether to use a cached page to retrieve the information if available.
    $this->storecache = true; // whether to store the pages retrieved for later use.
    $this->cache_expire = 24*60*60; // automatically delete cached files older than X secs
    $this->tempdir = "./netease/cache";
  }
}

require_once ("HTTP/Request2.php");

class NetEase_Request extends HTTP_Request2
{
  function NetEase_Request($url){
    parent::__construct($url);
    if ( PROXY != ""){
      $this->setConfig(array('proxy_host' => PROXY, 'proxy_port' => PROXY_PORT));
    }
    $this->setConfig('follow_redirects', false);
    $this->setHeader("User-Agent", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
  }
}

 ?>
