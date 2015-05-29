<?php

class Neighborhoods {
  var $messages = array();
  var $neighborhood = array();
  var $neighborhoods = array();
  var $neighborhood_categories = array();
  var $neighborhood_departments = array();
  var $neighborhood_issues = array();
  var $neighborhood_types = array();
  var $s = 'ID';
  var $d = 'DESC';

  # user, hash
  function add_neighborhood($args){
    $hash = $args['hash'];
    if(!$hash['neighborhood_first_name']){
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
        $id = Database::insert(array('table' => 'neighborhoods', 'hash' => $hash));
        if($id){
          $this->messages[] = "You have successfully added a neighborhood!";
          return $id;
        }
      }
    }
  }

  # id
  function get_neighborhood($args){
    $id = $args['id'];
    if(preg_match("/^\d+$/",$id)){
      $where = "WHERE neighborhood_id = '$id' ";
    }else{
      $where = "WHERE neighborhood_id = '9999999999999' ";
    }
             #DATE_FORMAT(c.neighborhood_start_date, '%m/%e/%Y %l:%i%p')
             #  AS neighborhood_start_date_formatted,
             #DATE_FORMAT(c.neighborhood_start_date, '%c/%e/%Y')
             # AS neighborhood_start_date_formatted2
    $sql = "
      SELECT c.*,
             DATE_FORMAT(c.neighborhood_incident_date, '%c/%e/%Y')
               AS neighborhood_incident_date_formatted2,
             DATE_FORMAT(c.neighborhood_received_date, '%c/%e/%Y')
               AS neighborhood_received_date_formatted2,
             DATE_FORMAT(c.neighborhood_oia_close_date, '%c/%e/%Y')
               AS neighborhood_oia_close_date_formatted2,
             DATE_FORMAT(c.neighborhood_audited_date, '%c/%e/%Y')
               AS neighborhood_audited_date_formatted2,
             DATE_FORMAT(c.neighborhood_closed_date, '%c/%e/%Y')
               AS neighborhood_closed_date_formatted2
      FROM neighborhoods c
      $where
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['neighborhood_url'] = Common::get_url(array('bow' => $r['neighborhood_first_name'].'-'.$r['neighborhood_last_name'],
                                             'id' => 'COMP'.$r['neighborhood_id']));
      $this->neighborhood = $r;
    }
    return $this->neighborhood;
  }

  # hash
  function get_neighborhoods($args){
    $hash = $args['hash'];
    $this->d = (isset($hash['d']) ? $hash['d'] : $this->d);
    $this->s = (isset($hash['s']) ? $hash['s'] : $this->s);
    $search_fields = "CONCAT_WS(' ',n.NAME)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset]" : "LIMIT 0");
    $offset = ($ipp ? "$offset, $ipp" : "");
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "neighborhood_department = '$hash[c]' AND ":'');
    $if_type = (isset($hash['t']) && $hash['t'] != '' ? "Type LIKE '$hash[t]' AND ":'');
             #DATE_FORMAT(c.Date Received, '%c/%e/%Y')
             #  AS neighborhood_date_received_formatted2
    $sql = "
      SELECT n.*
      FROM neighborhoods n
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_type
            DISPLAY = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['neighborhood_url'] = Common::get_url(array('bow' => $r['NAME'],
                                                     'id' => 'NBH'.$r['ID']));
      $neighborhoods[] = $r;
    }
    if($neighborhoods)
      $this->neighborhoods = $neighborhoods;
    $this->d = Common::direction_switch($this->d);
    return $this->neighborhoods;
  }

  # hash
  function get_neighborhoods_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',c.neighborhood_id,c.neighborhood_first_name,c.neighborhood_last_name,c.neighborhood_oia_number)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "neighborhood_department = '$hash[c]' AND ":'');
    $if_type = (isset($hash['t']) && $hash['t'] != '' ? "Type LIKE '$hash[t]' AND ":'');
    $sql = "
      SELECT count(c.neighborhood_id)
      FROM neighborhoods c
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_type
            neighborhood_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  ## hash
  #function list_neighborhood_categories($args){
  #  $sql = "
  #    SELECT DISTINCT Status
  #    FROM PWCIP
  #    WHERE
  #          neighborhood_display = '1'
  #    ORDER BY Status ASC";
  #  $results = mysql_query($sql);
  #  while($r = mysql_fetch_row($results)){
  #    $items[] = $r[0];
  #  }
  #  if($items)
  #    $this->neighborhood_categories = $items;
  #  return $this->neighborhood_categories;
  #}

  function list_neighborhood_issues($args){
    $this->neighborhood_issues = array(
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
    return $this->neighborhood_issues;
  }

  # hash
  function list_neighborhood_types($args){
    $this->neighborhood_types = array(
      'Audit',
      'Neighborhood: Inv',
      'Inquiry',
    );
    return $this->neighborhood_types;
  }

  # id, hash
  function update_neighborhood($args){
    $id = $args['id'];
    $hash = $args['hash'];
    if(!empty($hash['neighborhood_incident_date'])){
      $hash['neighborhood_incident_date'] = date('Y-m-d 00:00:00',strtotime($hash['neighborhood_incident_date']));
    }else{
      $hash['neighborhood_incident_date'] = NULL;
    }
    if(!empty($hash['neighborhood_received_date'])){
      $hash['neighborhood_received_date'] = date('Y-m-d 00:00:00',strtotime($hash['neighborhood_received_date']));
    }else{
      $hash['neighborhood_received_date'] = NULL;
    }
    if(!empty($hash['neighborhood_oia_close_date'])){
      $hash['neighborhood_oia_close_date'] = date('Y-m-d 00:00:00',strtotime($hash['neighborhood_oia_close_date']));
    }else{
      $hash['neighborhood_oia_close_date'] = NULL;
    }
    if(!empty($hash['neighborhood_audited_date'])){
      $hash['neighborhood_audited_date'] = date('Y-m-d 00:00:00',strtotime($hash['neighborhood_audited_date']));
    }else{
      $hash['neighborhood_audited_date'] = NULL;
    }
    if(!empty($hash['neighborhood_closed_date'])){
      $hash['neighborhood_closed_date'] = date('Y-m-d 00:00:00',strtotime($hash['neighborhood_closed_date']));
    }else{
      $hash['neighborhood_closed_date'] = NULL;
    }
    #$hash['neighborhood_due_date'] = date('Y-m-d',strtotime($hash['neighborhood_due_date']));
    $item = $this->get_neighborhood(array('id' => $id));
    $where = "neighborhood_id = '$id'";
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
    $sql = "UPDATE neighborhoods SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
