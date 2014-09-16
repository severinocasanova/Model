<?php

class TS {
  var $messages = array();
  var $streets_ew = array();
  var $streets_ns = array();
  var $ts = array();
  var $tss = array();
  var $townships = array();
  var $s = 'TSNum';
  var $d = 'ASC';

  # user, hash
  function add_ts($args){
    $hash = $args['hash'];
    $hash['ts_due_date'] = date('Y-m-d',strtotime($hash['ts_due_date']));
    if(!$hash['ts_customer_id']){
      $this->messages[] = "You did not enter in a customer!";
    } else {
      $id = database::insert(array('table' => 'tss', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added an ts!";
      return $id;
    }
  }

  # hash
  function get_streets_ew($args){
    $hash = $args['hash'];
    $sql = "
      SELECT DISTINCT EWStr
      FROM TSTable ts
      ORDER BY EWStr ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->streets_ew = $items;
    return $this->streets_ew;
  }

  # hash
  function get_streets_ns($args){
    $hash = $args['hash'];
    $sql = "
      SELECT DISTINCT NSStr
      FROM TSTable ts
      ORDER BY NSStr ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->streets_ns = $items;
    return $this->streets_ns;
  }

  # id
  function get_ts($args){
    $id = $args['id'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT ts.*
      FROM TSTable ts
      WHERE RecID = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['ts_url'] = Common::get_url(array('bow' => $r['EWStr'].'-'.$r['NSStr'],
                                           'id' => 'TS'.$r['RecID']));
      #/images/Plan_Lib/2007/GR/GR-2007-146/GR-2007-146_001.tif
      #$r['files'] = array('file1.tif');
      #$path = $_SERVER['DOCUMENT_ROOT'].'maps-and-records/webroot/images/Plan_Lib/2013/H/H-2013-001/';
      preg_match("/^(\w+)-/i",$r['TSNum'],$matches);
      $tp = ($matches[1] ? $matches[1] : '');
      preg_match("/\w+-(\d+)-/i",$r['TSNum'],$matches);
      $yr = ($matches[1] ? $matches[1] : '0000');
      $path = '/maps-and-records/webroot/images/Plan_Lib/TSImage/'.$r['TSNum'].'/';
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
      $this->ts = $r;
    }
    return $this->ts;
  }

  # hash
  function get_tss($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    $search_fields = "CONCAT_WS(' ',ts.TSNum,ts.PlanNum,ts.EWStr,ts.NSStr,ts.Comments)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['ew']) && $hash['ew'] != "")
      $if_ew = "EWStr = '$hash[ew]' AND ";
    if(isset($hash['ns']) && $hash['ns'] != "")
      $if_ns = "NSStr = '$hash[ns]' AND ";
    $sql = "
      SELECT ts.*
      FROM TSTable ts
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_ew
            $if_ns
            RecID IS NOT NULL
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['ts_url'] = Common::get_url(array('bow' => $r['EWStr'].'-'.$r['NSStr'],
                                           'id' => 'TS'.$r['RecID']));
      $tss[] = $r;
    }
    if($tss)
      $this->tss = $tss;
    $this->d = Common::direction_switch($this->d);
    return $this->tss;
  }

  # hash
  function get_tss_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',ts.TSNum,ts.PlanNum,ts.EWStr,ts.NSStr,ts.Comments)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['ew']) && $hash['ew'] != "")
      $if_ew = "EWStr = '$hash[ew]' AND ";
    if(isset($hash['ns']) && $hash['ns'] != "")
      $if_ns = "NSStr = '$hash[ns]' AND ";
    $sql = "
      SELECT count(ts.RecID)
      FROM TSTable ts
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_ew
            $if_ns
            RecID IS NOT NULL";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # id, hash
  function update_ts($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['ts_due_date'] = date('Y-m-d',strtotime($hash['ts_due_date']));
    $item = $this->get_ts(array('id' => $id));
    $where = "ts_id = '$id'";
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
    $sql = "UPDATE tss SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
