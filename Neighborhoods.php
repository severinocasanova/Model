<?php

class Neighborhoods {
  var $messages = array();
  var $neighborhood = array();
  var $neighborhoods = array();
  var $neighborhood_categories = array();
  var $neighborhood_departments = array();
  var $neighborhood_issues = array();
  var $neighborhood_types = array();
  var $s = 'NAME';
  var $d = 'ASC';

  # user, hash
  function add_neighborhood($args){
    $hash = $args['hash'];
    $destination = $args['location'].$hash['BY_LAWS'];
    if(!$hash['NAME']){
      $this->messages[] = "You did not enter in a Name!";
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
      $where = "WHERE ID = '$id' ";
    }else{
      $where = "WHERE ID = '9999999999999' ";
    }
             #DATE_FORMAT(c.neighborhood_start_date, '%m/%e/%Y %l:%i%p')
             #  AS neighborhood_start_date_formatted,
             #DATE_FORMAT(c.neighborhood_start_date, '%c/%e/%Y')
             # AS neighborhood_start_date_formatted2
    $sql = "
      SELECT n.*
      FROM neighborhoods n
      $where
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['neighborhood_url'] = Common::get_url(array('bow' => $r['NAME'],
                                                     'id' => 'NBH'.$r['ID']));
      $this->neighborhood = $r;
    }
    return $this->neighborhood;
  }

  # hash
  function get_neighborhoods($args){
    $items = array();
    $hash = $args['hash'];
    $this->d = (!empty($hash['d']) ? $hash['d'] : $this->d);
    $this->s = (!empty($hash['s']) ? $hash['s'] : $this->s);
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
            NEIGHBORHOOD_DISPLAY = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['neighborhood_url'] = Common::get_url(array('bow' => $r['NAME'],
                                                     'id' => 'NBH'.$r['ID']));
      $items[] = $r;
    }
    if($items)
      $this->neighborhoods = $items;
    $this->d = Common::direction_switch($this->d);
    return $this->neighborhoods;
  }

  # hash
  function get_neighborhoods_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',n.NAME)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    #$if_category = (isset($hash['c']) && $hash['c'] != '' ? "neighborhood_department = '$hash[c]' AND ":'');
    #$if_type = (isset($hash['t']) && $hash['t'] != '' ? "Type LIKE '$hash[t]' AND ":'');
    $sql = "
      SELECT count(n.ID)
      FROM neighborhoods n
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            NEIGHBORHOOD_DISPLAY = '1'";
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
    #$hash['neighborhood_due_date'] = date('Y-m-d',strtotime($hash['neighborhood_due_date']));
    $item = $this->get_neighborhood(array('id' => $id));
    $where = "ID = '$id'";
    $update = NULL;
    foreach($hash as $k => $v){
      if($v != $item[$k] && array_key_exists($k, $item)){
        if($k == 'BY_LAWS'){
          if(preg_match('/DELETE/i',$v)){
            $v = NULL;
          }elseif(move_uploaded_file($hash['tmp_name'], $args['location'].$hash['BY_LAWS'])){
            #$upload_success = 1;
          }
        }
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
