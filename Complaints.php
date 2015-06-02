<?php

class Complaints {
  var $messages = array();
  var $complaint = array();
  var $complaints = array();
  var $complaint_categories = array();
  var $complaint_departments = array();
  var $complaint_issues = array();
  var $complaint_types = array();
  var $s = 'complaint_id';
  var $d = 'DESC';

  # user, hash
  function add_complaint($args){
    $hash = $args['hash'];
    if(!empty($hash['complaint_incident_date'])){
      $hash['complaint_incident_date'] = date('Y-m-d 00:00:00',strtotime($hash['complaint_incident_date']));
    }else{
      $hash['complaint_incident_date'] = NULL;
    }
    if(!empty($hash['complaint_received_date'])){
      $hash['complaint_received_date'] = date('Y-m-d 00:00:00',strtotime($hash['complaint_received_date']));
    }else{
      $hash['complaint_received_date'] = NULL;
    }
    if(!empty($hash['complaint_oia_close_date'])){
      $hash['complaint_oia_close_date'] = date('Y-m-d 00:00:00',strtotime($hash['complaint_oia_close_date']));
    }else{
      $hash['complaint_oia_close_date'] = NULL;
    }
    if(!empty($hash['complaint_audited_date'])){
      $hash['complaint_audited_date'] = date('Y-m-d 00:00:00',strtotime($hash['complaint_audited_date']));
    }else{
      $hash['complaint_audited_date'] = NULL;
    }
    if(!empty($hash['complaint_closed_date'])){
      $hash['complaint_closed_date'] = date('Y-m-d 00:00:00',strtotime($hash['complaint_closed_date']));
    }else{
      $hash['complaint_closed_date'] = NULL;
    }
    if(!$hash['complaint_first_name']){
      $this->messages[] = "You did not enter in a First Name!";
    } else {
      if($hash['tmp_name']){
        if(move_uploaded_file($hash['tmp_name'], $destination)){
          $upload_success = 1;
        }else{
          $this->messages[] = "We could not upload the file at this time!";
        }
      }
      if($upload_success || !$hash['tmp_name']){
        unset($hash['tmp_name']);
        $id = Database::insert(array('table' => 'complaints', 'hash' => $hash));
        if($id){
          $this->messages[] = "You have successfully added a complaint!";
          return $id;
        }
      }
    }
  }

  # id
  function get_complaint($args){
    $id = $args['id'];
    if(preg_match("/^\d+$/",$id)){
      $where = "WHERE complaint_id = '$id' ";
    }else{
      $where = "WHERE complaint_id = '9999999999999' ";
    }
             #DATE_FORMAT(c.complaint_start_date, '%m/%e/%Y %l:%i%p')
             #  AS complaint_start_date_formatted,
             #DATE_FORMAT(c.complaint_start_date, '%c/%e/%Y')
             # AS complaint_start_date_formatted2
    $sql = "
      SELECT c.*,
             DATE_FORMAT(c.complaint_incident_date, '%c/%e/%Y')
               AS complaint_incident_date_formatted2,
             DATE_FORMAT(c.complaint_received_date, '%c/%e/%Y')
               AS complaint_received_date_formatted2,
             DATE_FORMAT(c.complaint_oia_close_date, '%c/%e/%Y')
               AS complaint_oia_close_date_formatted2,
             DATE_FORMAT(c.complaint_audited_date, '%c/%e/%Y')
               AS complaint_audited_date_formatted2,
             DATE_FORMAT(c.complaint_closed_date, '%c/%e/%Y')
               AS complaint_closed_date_formatted2
      FROM complaints c
      $where
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['complaint_url'] = Common::get_url(array('bow' => $r['complaint_first_name'].'-'.$r['complaint_last_name'],
                                             'id' => 'COMP'.$r['complaint_id']));
      $this->complaint = $r;
    }
    return $this->complaint;
  }

  # hash
  function get_complaints($args){
    $hash = $args['hash'];
    $this->d = ($hash['d'] ? $hash['d']:$this->d);
    $this->s = ($hash['s'] ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',c.complaint_id,c.complaint_first_name,c.complaint_last_name,c.complaint_oia_number)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset]" : "LIMIT 0");
    $offset = ($ipp ? "$offset, $ipp" : "");
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "complaint_department = '$hash[c]' AND ":'');
    $if_type = (isset($hash['t']) && $hash['t'] != '' ? "Type LIKE '$hash[t]' AND ":'');
             #DATE_FORMAT(c.Date Received, '%c/%e/%Y')
             #  AS complaint_date_received_formatted2
    $sql = "
      SELECT c.*,
             DATE_FORMAT(c.complaint_received_date, '%c/%e/%Y')
               AS complaint_received_date_formatted2
      FROM complaints c
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_type
            complaint_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['complaint_url'] = Common::get_url(array('bow' => $r['complaint_first_name'].'-'.$r['complaint_last_name'],
                                             'id' => 'COMP'.$r['complaint_id']));
      $complaints[] = $r;
    }
    if($complaints)
      $this->complaints = $complaints;
    $this->d = Common::direction_switch($this->d);
    return $this->complaints;
  }

  # hash
  function get_complaints_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',c.complaint_id,c.complaint_first_name,c.complaint_last_name,c.complaint_oia_number)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "complaint_department = '$hash[c]' AND ":'');
    $if_type = (isset($hash['t']) && $hash['t'] != '' ? "Type LIKE '$hash[t]' AND ":'');
    $sql = "
      SELECT count(c.complaint_id)
      FROM complaints c
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_type
            complaint_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  ## hash
  #function list_complaint_categories($args){
  #  $sql = "
  #    SELECT DISTINCT Status
  #    FROM PWCIP
  #    WHERE
  #          complaint_display = '1'
  #    ORDER BY Status ASC";
  #  $results = mysql_query($sql);
  #  while($r = mysql_fetch_row($results)){
  #    $items[] = $r[0];
  #  }
  #  if($items)
  #    $this->complaint_categories = $items;
  #  return $this->complaint_categories;
  #}

  # 
  function list_complaint_contacted_via($args){
    $this->complaint_contacted_via = array(
      'Phone',
      'Mail',
      'Fax',
      'Walk-In',
      'Internet',
      'Referral',
    );
    return $this->complaint_contacted_via;
  }

  function list_complaint_issues($args){
    $this->complaint_issues = array(
      'City Provided Services',
      'Customer Service',
      'Discontinuation of Service',
      'Employee Misconduct',
      'Fiscal',
      'Infrastructure',
      'Non-City',
      'Other',
      'Policy',
    );
    return $this->complaint_issues;
  }

  # hash
  function list_complaint_types($args){
    $this->complaint_types = array(
      'Audit',
      'Complaint: Inv',
      'Inquiry',
    );
    return $this->complaint_types;
  }

  # id, hash
  function update_complaint($args){
    $id = $args['id'];
    $hash = $args['hash'];
    if(!empty($hash['complaint_incident_date'])){
      $hash['complaint_incident_date'] = date('Y-m-d 00:00:00',strtotime($hash['complaint_incident_date']));
    }else{
      $hash['complaint_incident_date'] = NULL;
    }
    if(!empty($hash['complaint_received_date'])){
      $hash['complaint_received_date'] = date('Y-m-d 00:00:00',strtotime($hash['complaint_received_date']));
    }else{
      $hash['complaint_received_date'] = NULL;
    }
    if(!empty($hash['complaint_oia_close_date'])){
      $hash['complaint_oia_close_date'] = date('Y-m-d 00:00:00',strtotime($hash['complaint_oia_close_date']));
    }else{
      $hash['complaint_oia_close_date'] = NULL;
    }
    if(!empty($hash['complaint_audited_date'])){
      $hash['complaint_audited_date'] = date('Y-m-d 00:00:00',strtotime($hash['complaint_audited_date']));
    }else{
      $hash['complaint_audited_date'] = NULL;
    }
    if(!empty($hash['complaint_closed_date'])){
      $hash['complaint_closed_date'] = date('Y-m-d 00:00:00',strtotime($hash['complaint_closed_date']));
    }else{
      $hash['complaint_closed_date'] = NULL;
    }
    #$hash['complaint_due_date'] = date('Y-m-d',strtotime($hash['complaint_due_date']));
    $item = $this->get_complaint(array('id' => $id));
    $where = "complaint_id = '$id'";
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
    $sql = "UPDATE complaints SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
