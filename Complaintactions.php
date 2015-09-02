<?php

class Complaintactions {
  var $messages = array();
  var $complaint_action = array();
  var $complaint_actions = array();
  var $complaint_action_categories = array();
  var $complaint_action_disciplines = array();
  var $complaint_action_issues = array();
  var $complaint_action_types = array();
  var $s = 'complaint_action_id';
  var $d = 'ASC';

  # user, hash
  function add_complaint_action($args){
    $hash = $args['hash'];
    $hash['complaint_action_date'] = date('Y-m-d',strtotime($hash['complaint_action_date']));
    if(!$hash['complaint_action_taken']){
      $this->messages[] = "You did not enter in an action!";
    } else {
      $id = Database::insert(array('table' => 'complaint_actions', 'hash' => $hash));
      if($id){
        $this->messages[] = "You have successfully added a complaint action!";
        return $id;
      }
    }
  }

  # id
  function get_complaint_action($args){
    $id = $args['id'];
    $sql = "
      SELECT ca.*,
             DATE_FORMAT(ca.complaint_action_date, '%M %d, %Y')
               AS complaint_action_date_formatted,
             DATE_FORMAT(ca.complaint_action_date, '%c/%e/%Y')
               AS complaint_action_date_formatted2
      FROM complaint_actions ca
      WHERE complaint_action_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $this->complaint_action = $r;
    }
    return $this->complaint_action;
  }

  # hash
  function get_complaint_actions($args){
    $hash = $args['hash'];
    $this->d = ((isset($hash['d']) && $hash['d'] != '') ? $hash['d']:$this->d);
    $this->s = ((isset($hash['s']) && $hash['s'] != '') ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',ca.complaint_action_taken)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset]" : "LIMIT 0");
    $offset = ($ipp ? "$offset, $ipp" : "");
    if($hash['complaint_action_complaint_id'])
      $hash['c'] = $hash['complaint_action_complaint_id'];
    #if($hash['c'])
    #  $if_complaint_action_complaint_id = "complaint_action_complaint_id = '".$hash['c']."' AND ";
    $if_category = ((isset($hash['c']) && $hash['c'] != "") ? "complaint_action_complaint_id = '$hash[c]' AND ":'');
    #         DATE_FORMAT(ca.complaint_action_date, '%m/%e/%Y %l:%i%p')
    #           AS complaint_action_date_formatted,
    $sql = "
      SELECT ca.*,
             DATE_FORMAT(ca.complaint_action_date, '%M %d, %Y')
               AS complaint_action_date_formatted,
             DATE_FORMAT(ca.complaint_action_date, '%c/%e/%Y')
               AS complaint_action_date_formatted2
      FROM complaint_actions ca
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            complaint_action_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $complaint_actions[] = $r;
    }
    if($complaint_actions)
      $this->complaint_actions = $complaint_actions;
    $this->d = Common::direction_switch($this->d);
    return $this->complaint_actions;
  }

  # hash
  function get_complaint_actions_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',c.complaintaction_first_name,c.complaintaction_last_name,c.complaintaction_oia_number)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "complaintaction_department = '$hash[c]' AND ":'');
    $if_type = (isset($hash['t']) && $hash['t'] != '' ? "Type LIKE '$hash[t]' AND ":'');
    $sql = "
      SELECT count(c.complaintaction_allegation_id)
      FROM complaintactions c
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_type
            complaintaction_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function list_complaint_action_disciplines($args){
    $this->complaint_action_disciplines = array(
      'Corrective Action',
      'N/A',
      'Resign in Lieu of Term',
      'Suspension',
      'Termination',
      'Written Reprimand');
    return $this->complaint_action_disciplines;
  }

  # hash
  function list_complaintaction_categories($args){
    $sql = "
      SELECT DISTINCT Status
      FROM PWCIP
      WHERE
            complaintaction_display = '1'
      ORDER BY Status ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->complaintaction_categories = $items;
    return $this->complaintaction_categories;
  }

  # hash
  function list_complaintaction_types($args){
    $sql = "
      SELECT DISTINCT Type
      FROM PWCIP
      WHERE
            complaintaction_display = '1'
      ORDER BY Type ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->complaintaction_types = $items;
    return $this->complaintaction_types;
  }

  # id, hash
  function update_complaint_action($args){
    $id = $args['id'];
    $hash = $args['hash'];
    if(!empty($hash['complaint_action_date'])){
      $hash['complaint_action_date'] = date('Y-m-d 00:00:00',strtotime($hash['complaint_action_date']));
    }else{
      $hash['complaint_action_date'] = NULL;
    }
    $item = $this->get_complaint_action(array('id' => $id));
    $where = "complaint_action_id = '$id'";
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
    $sql = "UPDATE complaint_actions SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
