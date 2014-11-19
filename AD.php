<?php

class AD {
  var $books = array();
  var $book_pages = array();
  var $messages = array();
  var $ranges = array();
  var $sections = array();
  var $ad = array();
  var $ads = array();
  var $townships = array();
  var $s = 'ad_number';
  var $d = 'ASC';

  # user, hash
  function add_ad($args){
    $hash = $args['hash'];
    if(!$hash['ad_number']){
      $this->messages[] = "You did not enter in an AD #!";
    } else {
      $id = Database::insert(array('table' => 'ads', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added an ad!";
      return $id;
    }
  }

  # id
  function get_ad($args){
    $id = $args['id'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT ad.*, pl.*
      FROM ads ad
      LEFT JOIN plans pl ON (ad.ad_plan_number = pl.plan_number)
      WHERE ad_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['ad_url'] = Common::get_url(array('bow' => $r['ad_description'],
                                            'id' => 'AD'.$r['ad_id']));
      $r['ad_viewer_url'] = Common::get_url(array('bow' => $r['ad_description'],
                                            'id' => 'ADV'.$r['ad_id']));
      $r['plan_url'] = Common::get_url(array('bow' => $r['plan_description'],
                                             'id' => 'PL'.$r['plan_id']));
      #/images/Plan_Lib/2007/GR/GR-2007-146/GR-2007-146_001.tif
      #$r['files'] = array('file1.tif');
      #$path = $_SERVER['DOCUMENT_ROOT'].'maps-and-records/webroot/images/Plan_Lib/2013/H/H-2013-001/';
      preg_match("/^(\w+)-/i",$r['ad_number'],$matches);
      $tp = ($matches[1] ? $matches[1] : '');
      preg_match("/\w+-(\d+)-/i",$r['ad_number'],$matches);
      $yr = ($matches[1] ? $matches[1] : '0000');
      $path = '/maps-and-records/webroot/images/Plan_Lib/AD/'.$r['ad_number'].'/';
      if($handle = opendir($_SERVER['DOCUMENT_ROOT'].$path)){
        while (false !== ($filename = readdir($handle))){
          if(preg_match("/\.tiff?/i",$filename)){
            $files[] = array('filename' => $filename,
                             'url' => $path.$filename);
          }
        }
      }
      sort($files);
      $r['files'] = $files;
      #$r['files'] = array($path);
      $this->ad = $r;
    }
    return $this->ad;
  }

  # hash
  function get_ads($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    $search_fields = "CONCAT_WS(' ',ad.ad_number,ad.ad_description,ad.ad_plan_number)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "ad_scanned = '$hash[c]' AND ";
    $sql = "
      SELECT ad.*, pl.plan_id, pl.plan_description, pl.plan_number
      FROM ads ad
      LEFT JOIN plans pl ON (pl.plan_number = ad.ad_plan_number)
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_scanned
            ad_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['ad_url'] = Common::get_url(array('bow' => $r['ad_description'],
                                           'id' => 'AD'.$r['ad_id']));
      $r['plan_url'] = Common::get_url(array('bow' => $r['plan_description'],
                                             'id' => 'PL'.$r['plan_id']));
      $ads[] = $r;
    }
    if($ads)
      $this->ads = $ads;
    $this->d = Common::direction_switch($this->d);
    return $this->ads;
  }

  # hash
  function get_ads_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',ad.ad_number,ad.ad_description,ad.ad_plan_number)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "ad_scanned = '$hash[c]' AND ";
    $sql = "
      SELECT count(ad.ad_id)
      FROM ads ad
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_scanned
            ad_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # id, hash
  function update_ad($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['ad_due_date'] = date('Y-m-d',strtotime($hash['ad_due_date']));
    $item = $this->get_ad(array('id' => $id));
    $where = "ad_id = '$id'";
    $update = NULL;
    foreach($hash as $k => $v){
      if($v != $item[$k] && isset($item[$k])){
        $new_value = mysql_real_escape_string($v);
        $update .= "$k = '$new_value', ";
        $item[$k] = $v;
        $this->messages[] = "You have successfully updated the $k!";
      }
    }
    $where = rtrim($where, ' AND ');
    $update = rtrim($update, ', ');
    $sql = "UPDATE ads SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
