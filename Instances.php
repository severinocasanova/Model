<?php

class Instances {
  var $messages = array();
  var $instance = array();
  var $instances = array();
  var $instance_categories = array();
  var $instance_departments = array();
  var $instance_department_divisions = array();
  var $instance_issues = array();
  var $instance_types = array();
  var $s = 'instance_name';
  var $d = 'ASC';

  # user, hash
  function add_instance($args){
    $hash = $args['hash'];
    #$hash['ProjectPicture'] = preg_replace("/\s/",'_',$hash['ProjectPicture']);
    #$destination = $args['location'].$hash['ProjectPicture'];
    if(!$hash['instance_name']){
      $this->messages[] = "You did not enter in an Instance name!";
    } else {
      $upload_success = 0;
      $hash['tmp_name'] = (isset($hash['tmp_name']) ? $hash['tmp_name'] : '');
      if($hash['tmp_name']){
        if(move_uploaded_file($hash['tmp_name'], $destination)){
          $upload_success = 1;
        }else{
          $this->messages[] = "We could not upload the file at this time!";
        }
      }
      if($upload_success || !$hash['tmp_name']){
        unset($hash['tmp_name']);
        $id = Database::insert(array('table' => 'instances', 'hash' => $hash));
        if($id){
          $this->messages[] = "You have successfully added a instance!";
          return $id;
        }
      }
    }
  }

  # id
  function get_instance($args){
    $id = $args['id'];
    if(preg_match("/^\d+$/",$id)){
      $where = "WHERE instance_id = '$id' ";
    }else{
      $where = "WHERE instance_id = '9999999999999' ";
    }
    $sql = "
      SELECT i.*,
             DATE_FORMAT(i.instance_created, '%m/%e/%Y %l:%i%p')
               AS instance_created_formatted,
             DATE_FORMAT(i.instance_modified, '%m/%e/%Y %l:%i%p')
               AS instance_modified_formatted,
             DATE_FORMAT(i.instance_refresh_datetime, '%c/%e/%Y')
               AS instance_refresh_datetime_formatted2
      FROM instances i
      $where
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['instance_url'] = Common::get_url(array('bow' => $r['instance_name'],
                                                'id' => 'IN'.$r['instance_id']));
      $this->instance = $r;
    }
    return $this->instance;
  }

  # hash
  function get_instances($args){
    $items = array();
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = ((isset($hash['d']) && $hash['d']) ? $hash['d'] : $this->d);
    $this->s = ((isset($hash['s']) && $hash['s']) ? $hash['s'] : $this->s);
    $search_fields = "CONCAT_WS(' ',i.instance_name,i.instance_version)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "20");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset]" : "LIMIT 0");
    $offset = ($ipp ? "$offset, $ipp" : "");
    if(!empty($hash['t'])){
      if($hash['t'] == 'Open'){
        $if_status = "instance_status != 'Closed' AND ";
      } else {
        $if_status = "instance_status = '$hash[t]' AND ";
      }
    }
    #$if_type = (isset($hash['t']) && $hash['t'] != '' ? "instance_status LIKE '$hash[t]' AND ":'');
    $sql = "
      SELECT i.*,
             DATE_FORMAT(i.instance_refresh_datetime, '%c/%e/%Y')
               AS instance_refresh_datetime_formatted2
      FROM instances i
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            instance_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['instance_url'] = Common::get_url(array('bow' => $r['instance_name'],
                                                  'id' => 'IN'.$r['instance_id']));
      $items[] = $r;
    }
    if($items)
      $this->instances = $items;
    $this->d = Common::direction_switch($this->d);
    return $this->instances;
  }

  # hash
  function get_instances_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',i.instance_name,i.instance_version)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(!empty($hash['t'])){
      if($hash['t'] == 'Open'){
        $if_status = "instance_status != 'Closed' AND ";
      } else {
        $if_status = "instance_status = '$hash[t]' AND ";
      }
    }
    #$if_type = (isset($hash['t']) && $hash['t'] != '' ? "instance_status LIKE '$hash[t]' AND ":'');
    $sql = "
      SELECT count(i.instance_id)
      FROM instances i
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            instance_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function list_instance_departments($args){
    $this->instance_departments = array(
      'Budget & Internal Audit Programs',
      'City Attorney',
      'City Clerk',
      'City Court',
      "City Manager's Office",
      'Code Enforcement',
      'Environmental Services',
      'Finance',
      'General Services',
      'Golf',
      'Housing & Community Development',
      'Human Resources',
      'Information Technology',
      'Office of Equal Opportunity Programs',
      'Office of Intergrated Planning',
      'Park Tucson',
      'Parks & Recreation',
      'Planning & Development Services',
      'Police Audit',
      'Procurement',
      'Real Estate',
      'Streets',
      'SunTrans',
      'Transportation',
      'Tucson Convention Center',
      'Tucson Fire',
      'Tucson Police',
      'Tucson Water',
      'Zoning');
    return $this->instance_departments;
  }

  # hash
  function list_instance_department_divisions($args){
    $this->instance_department_divisions = array(
      'Billing',
      'Marketing',
      'Personnel',
      'Maintenance');
    return $this->instance_department_divisions;
  }

  # hash
  #function list_instance_categories($args){
  #  $sql = "
  #    SELECT DISTINCT instance_status
  #    FROM instances
  #    WHERE
  #          instance_display = '1'
  #    ORDER BY instance_status ASC";
  #  $results = mysql_query($sql);
  #  while($r = mysql_fetch_row($results)){
  #    $items[] = $r[0];
  #  }
  #  if($items)
  #    $this->instance_categories = $items;
  #  return $this->instance_categories;
  #}

  function list_instance_issues($args){
    $this->instance_issues = array(
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
    return $this->instance_issues;
  }

  # hash
  function list_instance_types($args){
    $sql = "
      SELECT DISTINCT instance_status
      FROM instances
      WHERE
            instance_display = '1'
      ORDER BY instance_status ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->instance_types = $items;
    return $this->instance_types;
  }

  # id, hash
  function update_instance($args){
    $id = $args['id'];
    $hash = $args['hash'];
    #$hash['instance_due_date'] = date('Y-m-d',strtotime($hash['instance_due_date']));
    $hash['instance_refresh_datetime'] = date('Y-m-d H:i:s',strtotime($hash['instance_refresh_datetime']));
    $item = $this->get_instance(array('id' => $id));
    $where = "instance_id = '$id'";
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
    $sql = "UPDATE instances SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
