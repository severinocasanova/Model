<?php

class TS {
  var $messages = array();
  var $streets_ew = array();
  var $streets_ns = array();
  var $ts = array();
  var $tss = array();
  var $townships = array();
  var $s = 'ts_id';
  var $d = 'DESC';

  # user, hash
  function add_ts($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $hash['ts_due_date'] = date('Y-m-d',strtotime($hash['ts_due_date']));
    if(!$hash['ts_number']){
      $this->messages[] = "You did not enter in a TS #!";
    } else {
      $id = database::insert(array('table' => 'tss', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added an ts!";
      return $id;
    }
  }

  # hash
  function get_streets_ew($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $sql = "
      SELECT DISTINCT ts_EW_street
      FROM tss ts
      ORDER BY ts_EW_street ASC";
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
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $sql = "
      SELECT DISTINCT ts_NS_street
      FROM tss ts
      ORDER BY ts_NS_street ASC";
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
    if(preg_match('/^\d+$/',$id)){
      $where = "WHERE ts_id = '$id' ";
    } else {
      $where = "WHERE ts_number = '$id' ";
    }
    $sql = "
      SELECT ts.*,p.plan_id,p.plan_description,
             CONCAT(ts.ts_type,'-',LPAD(ts.ts_system_id,4,'0')) as ts_number_id
      FROM tss ts
      LEFT JOIN plans p ON (p.plan_number = ts.ts_plan_number)
      $where AND
        ts_display = '1'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['ts_url'] = Common::get_url(array('bow' => $r['ts_EW_street'].'-'.$r['ts_NS_street'],
                                           'id' => 'TS'.$r['ts_id']));
      $r['ts_viewer_url'] = Common::get_url(array('bow' => $r['ts_EW_street'].'-'.$r['ts_NS_street'],
                                           'id' => 'TSV'.$r['ts_id']));
      $r['plan_url'] = Common::get_url(array('bow' => $r['plan_description'],
                                             'id' => 'PL'.$r['plan_id']));
      #/images/Plan_Lib/2007/GR/GR-2007-146/GR-2007-146_001.tif
      #$r['files'] = array('file1.tif');
      #$path = $_SERVER['DOCUMENT_ROOT'].'maps-and-records/webroot/images/Plan_Lib/2013/H/H-2013-001/';
      preg_match("/^(\w+)-/i",$r['ts_image_id'],$matches);
      $tp = ($matches[1] ? $matches[1] : '');
      preg_match("/\w+-(\d+)-/i",$r['ts_image_id'],$matches);
      $yr = (isset($matches[1]) ? $matches[1] : '0000');
      $path = '/apps/maps-and-records/webroot/images/Plan_Lib/TSImage/'.$r['ts_image_id'].'/';
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
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = (isset($hash['d']) ? $hash['d']:$this->d);
    $this->s = (isset($hash['s']) ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',ts.ts_number,CONCAT(ts.ts_type,'-',LPAD(ts.ts_system_id,4,'0')),ts.ts_plan_number,ts.ts_EW_street,ts.ts_NS_street,ts.ts_comments)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $if_ew = '';
    if(isset($hash['ew']) && $hash['ew'] != "")
      $if_ew = "ts_EW_street = '$hash[ew]' AND ";
    $if_ns = '';
    if(isset($hash['ns']) && $hash['ns'] != "")
      $if_ns = "ts_NS_street = '$hash[ns]' AND ";
 #CONCAT(ts_type,'-',IF(LENGTH(ts_system_id)=4,ts_system_id),IF(LENGTH(ts_system_id)=3,'0',ts_system_id),IF(LENGTH(ts_system_id)=2,'00',ts_system_id),IF(LENGTH(ts_system_id)=1,'000',ts_system_id,'0000')) AS ts_number_id
 #CONCAT(ts_type,'-', CASE WHEN LENGTH(ts_system_id)=4 THEN ts_system_id 
 #                    ELSE WHEN LENGTH(ts_system_id)=3 THEN CONCAT('0',ts_system_id) END) AS ts_number_id
      #LEFT JOIN plans pl ON (CONCAT(ts.ts_type,'-',LPAD(ts.ts_system_id,4,'0')) = pl.plan_number)
    $sql = "
      SELECT ts.*,p.plan_id,p.plan_description,
             CONCAT(ts.ts_type,'-',LPAD(ts.ts_system_id,4,'0')) as ts_number_id
      FROM tss ts
      LEFT JOIN plans p ON (p.plan_number = ts.ts_plan_number)
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_ew
            $if_ns
            ts_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['ts_url'] = Common::get_url(array('bow' => $r['ts_EW_street'].'-'.$r['ts_NS_street'],
                                           'id' => 'TS'.$r['ts_id']));
      if($r['plan_id']){
        $r['plan_url'] = Common::get_url(array('bow' => $r['plan_description'],
                                               'id' => 'PL'.$r['plan_id']));
      }
      #$r['ts_number_id_plan_url'] = Common::get_url(array('bow' => $r['plan_description2'],
      #                                       'id' => 'PL'.$r['plan_id2']));
      $tss[] = $r;
    }
    if($tss)
      $this->tss = $tss;
    $this->d = Common::direction_switch($this->d);
    return $this->tss;
  }

  # hash
  function get_tss_count($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $search_fields = "CONCAT_WS(' ',ts.ts_number,ts.ts_plan_number,ts.ts_EW_street,ts.ts_NS_street,ts.ts_comments)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_ew = '';
    if(isset($hash['ew']) && $hash['ew'] != "")
      $if_ew = "ts_EW_street = '$hash[ew]' AND ";
    $if_ns = '';
    if(isset($hash['ns']) && $hash['ns'] != "")
      $if_ns = "ts_NS_street = '$hash[ns]' AND ";
    $sql = "
      SELECT count(ts.ts_id)
      FROM tss ts
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_ew
            $if_ns
            ts_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # id, hash
  function update_ts($args){
    $id = $args['id'];
    $hash = (isset($args['hash']) ? $args['hash']:'');
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
