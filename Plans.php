<?php
App::uses('Common','Model');

class Plans {
  var $messages = array();
  var $plan = array();
  var $plans = array();
  var $plan_types = array();
  var $s = 'plan_sort,plan_number';
  var $d = 'ASC';

  # user, hash
  function add_plan($args){
    $hash = $args['hash'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $hash['plan_added_by'] = $user_name;
    if(!$hash['plan_number']){
      $this->messages[] = "You did not enter in a plan number!";
    } else {
      $id = Database::insert(array('table' => 'plans', 'hash' => $hash));
      if($id){
        $this->messages[] = "You have successfully added a plan!";
        return $id;
      }
    }
  }

  # id
  function get_next_plan($args){
    $plan_type = $args['plan_type'].'-'.date("Y").'-';
    $sql = "
      SELECT p.plan_number
      FROM plans p
      WHERE plan_number LIKE '$plan_type%'
      ORDER BY plan_number DESC
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $last_number = preg_replace("/".$args['plan_type']."-".date("Y")."-0*/","",$r['plan_number']);
      $new_last_number = sprintf("%03d",$last_number+1);
      $plan_number = $args['plan_type'].'-'.date("Y").'-'.$new_last_number;
    }
    if(!$last_number){
      $plan_number = $args['plan_type'].'-'.date("Y").'-001';
    }
    return $plan_number;
  }

  # id
  function get_plan($args){
    $id = $args['id'];
    if(preg_match('/^\d+$/',$id)){
      $where = "WHERE plan_id = '$id' ";
    } else {
      $where = "WHERE plan_number = '$id' ";
    }
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT p.*
      FROM plans p
      $where AND
        plan_display = '1'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['plan_url'] = Common::get_url(array('bow' => $r['plan_description'],
                                             'id' => 'PL'.$r['plan_id']));
      $r['plan_viewer_url'] = Common::get_url(array('bow' => $r['plan_description'],
                                             'id' => 'PLV'.$r['plan_id']));
      #/images/Plan_Lib/2007/GR/GR-2007-146/GR-2007-146_001.tif
      #$r['files'] = array('file1.tif');
      #$path = $_SERVER['DOCUMENT_ROOT'].'maps-and-records/webroot/images/Plan_Lib/2013/H/H-2013-001/';
      preg_match("/^(\w+)-/i",$r['plan_number'],$matches);
      $tp = ($matches[1] ? $matches[1] : '');
      preg_match("/\w+-(\d+)-/i",$r['plan_number'],$matches);
      $yr = ($matches[1] ? $matches[1] : '0000');
      $paths = array('/maps-and-records/webroot/images/Plan_Lib/'.$tp.'/'.$r['plan_number'].'/',
                     '/maps-and-records/webroot/images/Plan_Lib/0000/'.$tp.'/'.$r['plan_number'].'/',
                     '/maps-and-records/webroot/images/Plan_Lib/0100/'.$tp.'/'.$r['plan_number'].'/',
                     '/maps-and-records/webroot/images/Plan_Lib/0200/'.$tp.'/'.$r['plan_number'].'/',
                     '/maps-and-records/webroot/images/Plan_Lib/0300/'.$tp.'/'.$r['plan_number'].'/',
                     '/maps-and-records/webroot/images/Plan_Lib/0400/'.$tp.'/'.$r['plan_number'].'/',
                     '/maps-and-records/webroot/images/Plan_Lib/0500/'.$tp.'/'.$r['plan_number'].'/',
                     '/maps-and-records/webroot/images/Plan_Lib/0600/'.$tp.'/'.$r['plan_number'].'/',
                     '/maps-and-records/webroot/images/Plan_Lib/0700/'.$tp.'/'.$r['plan_number'].'/',
                     '/maps-and-records/webroot/images/Plan_Lib/0800/'.$tp.'/'.$r['plan_number'].'/',
                     '/maps-and-records/webroot/images/Plan_Lib/'.$yr.'/'.$tp.'/'.$r['plan_number'].'/',
                     '/maps-and-records/webroot/images/Plan_Lib/19'.$yr.'/'.$tp.'/'.$r['plan_number'].'/');
      foreach ($paths as $path){
      if($handle = opendir($_SERVER['DOCUMENT_ROOT'].$path)){
        while (false !== ($filename = readdir($handle))){
          if(preg_match("/\.tiff?/i",$filename)){
            $files[$filename] = array('filename' => $filename,
                             'url' => $path.$filename);
          }
        }
      }
      }
      sort($files);
      $r['files'] = $files;
      #$r['files'] = array($path);
      $this->plan = $r;
    }
    return $this->plan;
  }

  # hash
  function get_plans($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    $search_fields = "CONCAT_WS(' ',p.plan_number,p.plan_description)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "250");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "plan_scanned = '$hash[c]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_type = "plan_number LIKE '$hash[t]-%' AND ";
    $sql = "
      SELECT p.*
      FROM plans p
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_scanned
            $if_type
            plan_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['plan_url'] = Common::get_url(array('bow' => $r['plan_description'],
                                             'id' => 'PL'.$r['plan_id']));
      $plans[] = $r;
    }
    if($plans)
      $this->plans = $plans;
    $this->d = Common::direction_switch($this->d);
    return $this->plans;
  }

  # hash
  function get_plans_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',p.plan_number,p.plan_description)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "plan_scanned = '$hash[c]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_type = "plan_number LIKE '$hash[t]-%' AND ";
    if(array_key_exists('plan_customer_id', $hash))
      $if_customer_id = "plan_customer_id = '$hash[plan_customer_id]' AND ";
    $sql = "
      SELECT count(p.plan_id)
      FROM plans p
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_scanned
            $if_type
            $if_customer_id
            plan_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function get_plan_types($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',pt.type_name)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "plan_scanned = '$hash[c]' AND ";
    if(array_key_exists('plan_customer_id', $hash))
      $if_customer_id = "plan_customer_id = '$hash[plan_customer_id]' AND ";
    $sql = "
      SELECT pt.*
      FROM plan_types pt
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            type_display = '1'
      ORDER BY type_abbreviation ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->plan_types = $items;
    return $this->plan_types;
  }

  # id, hash
  function update_plan($args){
    $id = $args['id'];
    $hash = $args['hash'];
    #$hash['plan_due_date'] = date('Y-m-d',strtotime($hash['plan_due_date']));
    $item = $this->get_plan(array('id' => $id));
    $where = "plan_id = '$id'";
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
    $sql = "UPDATE plans SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
