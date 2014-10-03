<?php

class ESProjects {
  var $messages = array();
  var $esproject = array();
  var $esprojects = array();
  var $esproject_categories = array();
  var $esproject_types = array();
  var $s = 'CIPName';
  var $d = 'ASC';

  # user, hash
  function add_esproject($args){
    $hash = $args['hash'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $hash['esproject_added_by'] = $user_name;
    if(!$hash['esproject_number']){
      $this->messages[] = "You did not enter in a esproject number!";
    } else {
      $id = Database::insert(array('table' => 'esprojects', 'hash' => $hash));
      if($id){
        $this->messages[] = "You have successfully added a esproject!";
        return $id;
      }
    }
  }

  # id
  function get_next_esproject($args){
    $esproject_type = $args['esproject_type'].'-'.date("Y").'-';
    $sql = "
      SELECT p.esproject_number
      FROM esprojects p
      WHERE esproject_number LIKE '$esproject_type%'
      ORDER BY esproject_number DESC
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $last_number = preg_replace("/".$args['esproject_type']."-".date("Y")."-0*/","",$r['esproject_number']);
      $new_last_number = sprintf("%03d",$last_number+1);
      $esproject_number = $args['esproject_type'].'-'.date("Y").'-'.$new_last_number;
    }
    if(!$last_number){
      $esproject_number = $args['esproject_type'].'-'.date("Y").'-001';
    }
    return $esproject_number;
  }

  # id
  function get_esproject($args){
    $id = $args['id'];
    $sql = "
      SELECT p.*
      FROM esprojects p
      WHERE esproject_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['esproject_url'] = Common::get_url(array('bow' => $r['esproject_name'],
                                                  'id' => 'PRJ'.$r['esproject_id']));
      $this->esproject = $r;
    }
    return $this->esproject;
  }

  # hash
  function get_esprojects($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    $search_fields = "CONCAT_WS(' ',p.CIPName,p.Description,p.Location)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "Status = '$hash[c]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_type = "Type LIKE '$hash[t]' AND ";
    $sql = "
      SELECT p.*,
             DATE_FORMAT(p.UpdateDate, '%c/%e/%Y')
               AS UpdateDate_formatted2
      FROM PWCIP p
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_scanned
            $if_type
            ProjectDeleted = '0'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['esproject_url'] = Common::get_url(array('bow' => $r['CIPName'],
                                             'id' => 'PRJ'.$r['CIPID']));
      $esprojects[] = $r;
    }
    if($esprojects)
      $this->esprojects = $esprojects;
    $this->d = Common::direction_switch($this->d);
    return $this->esprojects;
  }

  # hash
  function get_esprojects_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',p.CIPName,p.Description,p.Location)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "Status = '$hash[c]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_type = "Type LIKE '$hash[t]' AND ";
    $sql = "
      SELECT count(p.CIPID)
      FROM PWCIP p
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_scanned
            $if_type
            ProjectDeleted = '0'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function list_esproject_categories($args){
    $sql = "
      SELECT DISTINCT Status
      FROM PWCIP
      WHERE
            ProjectDeleted = '0'
      ORDER BY Status ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->esproject_categories = $items;
    return $this->esproject_categories;
  }

  # hash
  function list_esproject_types($args){
    $sql = "
      SELECT DISTINCT Type
      FROM PWCIP
      WHERE
            ProjectDeleted = '0'
      ORDER BY Type ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->esproject_types = $items;
    return $this->esproject_types;
  }

  # id, hash
  function update_esproject($args){
    $id = $args['id'];
    $hash = $args['hash'];
    #$hash['esproject_due_date'] = date('Y-m-d',strtotime($hash['esproject_due_date']));
    $item = $this->get_esproject(array('id' => $id));
    $where = "esproject_id = '$id'";
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
    $sql = "UPDATE esprojects SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
