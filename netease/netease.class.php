<?php

require_once ("include/browser/browseremulator.class.php");
require_once ("include/browser/info_extractor.php");
require_once (dirname(__FILE__)."/netease_config.php");

class netease extends netease_config {
  var $songID = "";
  var $ext = ".json";
  var $page;

  var $main_lyric = "";

  var $info_excer;

  /** check for valid url
  * @method urlstate ()
  * @param none
  * @return int state (0-not valid, 1-valid)
  */
  function urlstate () {
    if (strlen($this->songID) <= 0) return 0;
    else return 1;
  }

  /** 检查Lyric缓存是否完整
  * @method cachestate ()
  * @param $target array
  * @return int state (0-not complete, 1-cache complete, 2-cache not enabled, 3-not valid imdb url)
  */
  function cachestate () {
    if (strlen($this->songID) <= 0) {
      //echo "Invalid songID: ".$this->songID."<BR>".strlen($this->songID);
      $this->page = "cannot open page";
      return 3;
    }
    if ($this->usecache) {
      if(!file_exists($this->cachedir."/".$this->songID.$this->ext)) return 0;
      @$fp = fopen ($this->cachedir."/".$this->songID.$this->ext, "r");
      if (!$fp) return 0;
      return 1;
    }
    else return 2;
  }

  /** 打开song页面
  * @method openpage
  */
  function openpage () {
    if (strlen($this->songID) <= 0) {
      echo "Invalid songID: ".$this->songID."<BR>".strlen($this->songID);
      $this->page = "cannot open page";
      return;
    }

    if ($this->usecache) {
      @$fp = fopen ($this->cachedir."/".$this->songID.$this->ext, "r");
      if ($fp) {
        $temp="";
        while (!feof ($fp)) {
          $temp .= fread ($fp, 1024);
        }
        if ($temp) {
          $this->page = $temp;
          return;
        }
      }
    } // end cache

    // $req = new NetEase_Request("");
    // $req->setURL("http://".$this->website."/media?id=".$this->songID);
    // $response = $req->send();

    $url="http://".$this->website."/media?id=".$this->songID;//url为当前歌词完整连接
    $curl = curl_init();//初始化一个CURL会话，cURL库可以简单和有效地去抓网页
    curl_setopt($curl, CURLOPT_URL,$url);//为CURL会话设置参数，CURLOPT_URL为$url
    curl_setopt($curl, CURLOPT_HEADER, 0);//CURLOPT_HEADER$url
    curl_setopt($curl, CURLOPT_NOBODY, 0);//CURLOPT_NOBODY$url
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//CURLOPT_RETURNTRANSFER$url
    curl_setopt($curl, CURLOPT_TIMEOUT,10);//CURLOPT_TIMEOUT$url
    $data = curl_exec($curl);//执行CURL会话
    curl_close($curl);//关闭CURL会话

    $this->page = $data;

    /*if ($responseBody) {
      // $this->page = utf8_encode($responseBody);
      // $this->page = iconv ("gb2312","UTF-8",$responseBody);
    }*/
    if($this->page) {//如果成功获取到网页
      if ($this->storecache) {
        $fp = fopen($this->cachedir."/".$this->songID.$this->ext, "w");//以写入方式打开文件
        fputs ($fp, $this->page);//写入文件
        fclose ($fp);//关闭打开的文件
      }
      return;
    }
    $this->page = "cannot open page";
    //echo "page not found";
  }

  /** Retrieve the song ID
  * @method songid
  * @return string id
  */
  function songid () {
    return $this->songID;
  }

  /** 为一个新的IMDB ID设置类
  * @method setid
  * @param string id
  */
  function setid ($id) {
    $this->songID = $id;

    $this->page = "";
    $this->ext = ".json";

    $this->main_lyric = "";

    $this->info_excer = new info_extractor();
  }

  /** Initialize class
  * @constructor netease
  * @param string id
  */
  function netease ($id) {
    $this->netease_config();
    $this->setid($id);
    //if ($this->storecache && ($this->cache_expire > 0)) $this->purge();
  }

  /** Check cache and purge outdated files
  *  This method looks for files older than the cache_expire set in the
  *  netease_config and removes them
  * @method purge
  */
  function purge($explicit = false) {
    if (is_dir($this->cachedir)) {
      $thisdir = dir($this->cachedir);
      $now = time();
      while( $file=$thisdir->read() ) {
        if ($file!="." && $file!="..") {
          $fname = $this->cachedir ."/". $file;
          if (is_dir($fname)) continue;
          $mod = filemtime($fname);
          if ($mod && (($now - $mod > $this->cache_expire) || $explicit == true)) unlink($fname);
        }
      }
    }
  }

  /** Check cache and purge outdated single song file
  *  This method looks for files older than the cache_expire set in the
  *  netease_config and removes them
  * @method purge
  */
  function purge_single($explicit = false) {
    if (is_dir($this->cachedir)) {
      $thisdir = dir($this->cachedir);
      $fname = $this->cachedir ."/". $this->songid() . $this->ext;
      //return $fname;
      if(file_exists($fname)) {
        $now = time();
        $mod = filemtime($fname);
        if ($mod && (($now - $mod > $this->cache_expire) || $explicit == true)) unlink($fname);
      }
    }
  }

  /** get the time that cache is stored
  * @method getcachetime
  */
  function getcachetime() {
    $mod =0;
    if (is_dir($this->cachedir)) {
      $thisdir = dir($this->cachedir);
      $fname = $this->cachedir ."/". $this->songid() . $this->ext;
      if(file_exists($fname)) {
        if($mod > filemtime($fname) || $mod==0)
        $mod = filemtime($fname);
      }
    }
    return $mod;
  }

  /** Set up the URL to the movie title page
  * @method main_url
  * @return string url
  */
  function main_url(){
    return "http://".$this->website."/media?id=".$this->songid()."/";
  }

  /** Url to the local temp file of the lyric
  * @method temp_url
  * @return string temp lyric url
  */
  function temp_url() {
    return $this->tempdir."/".$this->songid().".lrc";
  }

  /** Get song lyric
  * @method lyric
  * @return string lyric
  */
  function lyric () {
    if ($this->main_lyric == "") {
      if ($this->page == "") $this->openpage();
      // read json
      $data = json_decode($this->page, true);
      $this->main_lyric = $data["lyric"];
    }
    return $this->main_lyric;
  }

  /** Save lyric to disk
  * @method savelyric
  * @return boolean success
  */
  function savelyric() {
    $lyric_content = $this->lyric();
    if (!$lyric_content) return FALSE;

    $path = $this->temp_url();
    $fp = fopen($path,"w");
    if (!$fp) return FALSE;
    // format lyric_content
    $lyric_content = str_replace("\\n\\n","\r\n",$lyric_content);
    $lyric_content = str_replace("\\r\\n","\r\n",$lyric_content);
    $lyric_content = str_replace("\\n","\r\n",$lyric_content);

    fputs($fp,$lyric_content);
    fclose($fp);

    return TRUE;
  }

}

?>
