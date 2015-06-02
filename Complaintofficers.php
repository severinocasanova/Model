<?php

class Complaintofficers {
  var $messages = array();
  var $complaint_officer = array();
  var $complaint_officers = array();
  var $complaint_officer_categories = array();
  var $complaint_officer_disciplines = array();
  var $complaint_officer_issues = array();
  var $complaint_officer_types = array();
  var $s = 'complaint_officer_id';
  var $d = 'ASC';

  # user, hash
  function add_complaint_officer($args){
    $hash = $args['hash'];
    if(!$hash['complaint_officer_name']){
      $this->messages[] = "You did not enter in an officer name!";
    } else {
      $id = Database::insert(array('table' => 'complaint_officers', 'hash' => $hash));
      if($id){
        $this->messages[] = "You have successfully added a complaint officer!";
        return $id;
      }
    }
  }

  # id
  function get_complaint_officer($args){
    $id = $args['id'];
    $sql = "
      SELECT c.*
      FROM complaint_officers c
      WHERE complaint_officer_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['complaintofficer_url'] = Common::get_url(array('bow' => $r['complaintofficer_first_name'].'-'.$r['complaintofficer_last_name'],
                                             'id' => 'COMP'.$r['complaintofficer_allegation_id']));
      $this->complaint_officer = $r;
    }
    return $this->complaint_officer;
  }

  # hash
  function get_complaint_officers($args){
    $hash = $args['hash'];
    $this->d = ($hash['d'] ? $hash['d']:$this->d);
    $this->s = ($hash['s'] ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',co.complaint_officer_name,co.complaint_officer_allegation)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset]" : "LIMIT 0");
    $offset = ($ipp ? "$offset, $ipp" : "");
    if($hash['complaint_officer_complaint_id'])
      $hash['c'] = $hash['complaint_officer_complaint_id'];
    if($hash['c'])
      $if_complaint_officer_complaint_id = "complaint_officer_complaint_id = '".$hash['c']."' AND ";
    $sql = "
      SELECT co.*
      FROM complaint_officers co
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_complaint_officer_complaint_id
            $if_type
            complaint_officer_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $complaint_officers[] = $r;
    }
    if($complaint_officers)
      $this->complaint_officers = $complaint_officers;
    $this->d = Common::direction_switch($this->d);
    return $this->complaint_officers;
  }

  # hash
  function get_complaint_officers_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',c.complaintofficer_first_name,c.complaintofficer_last_name,c.complaintofficer_oia_number)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "complaintofficer_department = '$hash[c]' AND ":'');
    $if_type = (isset($hash['t']) && $hash['t'] != '' ? "Type LIKE '$hash[t]' AND ":'');
    $sql = "
      SELECT count(c.complaintofficer_allegation_id)
      FROM complaintofficers c
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_type
            complaintofficer_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function list_complaint_officer_disciplines($args){
    $this->complaint_officer_disciplines = array(
      'Corrective Action',
      'N/A',
      'Resign in Lieu of Term',
      'Suspension',
      'Termination',
      'Written Reprimand');
    return $this->complaint_officer_disciplines;
  }

  # hash
  function list_complaintofficer_categories($args){
    $sql = "
      SELECT DISTINCT Status
      FROM PWCIP
      WHERE
            complaintofficer_display = '1'
      ORDER BY Status ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->complaintofficer_categories = $items;
    return $this->complaintofficer_categories;
  }

  # hash
  function list_complaintofficer_types($args){
    $sql = "
      SELECT DISTINCT Type
      FROM PWCIP
      WHERE
            complaintofficer_display = '1'
      ORDER BY Type ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->complaintofficer_types = $items;
    return $this->complaintofficer_types;
  }

  # id, hash
  function update_complaint_officer($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $item = $this->get_complaint_officer(array('id' => $id));
    $where = "complaint_officer_id = '$id'";
    $update = NULL;
    foreach($hash as $k => $v){
      if($v != $item[$k] && array_key_exists($k, $item)){
        $new_value = mysql_real_escape_string($v);
        $update .= (is_null($v) ? "$k = NULL," : "$k = '$new_value', ");
        $item[$k] = $v;
        $this->messages[] = "You have successfully updated the $k!";
      }
    }
    $where = rtrim($where, ' AND ');
    $update = rtrim($update, ', ');
    $sql = "UPDATE complaint_officers SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
