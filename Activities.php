<?php

class Activities {
  var $messages = array();
  var $activity = array();
  var $activities = array();
  var $activity_categories = array();
  var $activity_departments = array();
  var $activity_department_divisions = array();
  var $activity_issues = array();
  var $activity_types = array();
  var $s = 'activity_id';
  var $d = 'DESC';

  # user, hash
  function add_activity($args){
    $hash = $args['hash'];
    #$hash['ProjectPicture'] = preg_replace("/\s/",'_',$hash['ProjectPicture']);
    #$destination = $args['location'].$hash['ProjectPicture'];
    if(isset($hash['activity_scheduled_start_datetime'])){
      $hash['activity_scheduled_start_datetime'] = date('Y-m-d H:i:s',strtotime($hash['activity_scheduled_start_datetime']));
    }
    if(isset($hash['activity_scheduled_end_datetime'])){
      $hash['activity_scheduled_end_datetime'] = date('Y-m-d H:i:s',strtotime($hash['activity_scheduled_end_datetime']));
    }
    if(!$hash['activity_title']){
      $this->messages[] = "You did not enter in an Activity Title!";
    }elseif(!$hash['activity_department']){
      $this->messages[] = "You did not select a department!";
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
        $id = Database::insert(array('table' => 'activities', 'hash' => $hash));
        if($id){
          $this->messages[] = "You have successfully added an activity!";
          return $id;
        }
      }
    }
  }

  # id
  function get_activity($args){
    $id = $args['id'];
    if(preg_match("/^\d+$/",$id)){
      $where = "WHERE activity_id = '$id' ";
    }else{
      $where = "WHERE activity_id = '9999999999999' ";
    }
    $sql = "
      SELECT a.*,i.*,
             DATE_FORMAT(a.activity_created, '%c/%e/%Y %l:%i%p')
               AS activity_created_formatted,
             DATE_FORMAT(a.activity_scheduled_start_datetime, '%c/%e/%Y %l:%i%p')
               AS activity_scheduled_start_datetime_formatted,
             DATE_FORMAT(a.activity_scheduled_start_datetime, '%c/%e/%Y')
              AS activity_scheduled_start_datetime_formatted2,
             DATE_FORMAT(a.activity_start_datetime, '%c/%e/%Y')
              AS activity_start_datetime_formatted2,
             DATE_FORMAT(a.activity_scheduled_end_datetime, '%c/%e/%Y')
              AS activity_scheduled_end_datetime_formatted2,
             DATE_FORMAT(a.activity_end_datetime, '%c/%e/%Y')
              AS activity_end_datetime_formatted2
      FROM activities a
      LEFT JOIN instances i ON (a.activity_instance_id = i.instance_id)
      $where
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['activity_url'] = Common::get_url(array('bow' => $r['activity_title'],
                                                 'id' => 'A'.$r['activity_id']));
      $r['instance_url'] = Common::get_url(array('bow' => $r['instance_name'],
                                                 'id' => 'IN'.$r['instance_id']));
      $this->activity = $r;
    }
    return $this->activity;
  }

  # hash
  function get_activities($args){
    $items = array();
    $hash = $args['hash'];
    $this->d = ((isset($hash['d']) && $hash['d']) ? $hash['d'] : $this->d);
    $this->s = ((isset($hash['s']) && $hash['s']) ? $hash['s'] : $this->s);
    $search_fields = "CONCAT_WS(' ',t.activity_title)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "20");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset]" : "LIMIT 0");
    $offset = ($ipp ? "$offset, $ipp" : "");
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "activity_instance_id = '$hash[c]' AND ":'');
    $if_active_date = (isset($hash['active_date']) && $hash['active_date'] != '' ? "activity_scheduled_start_datetime <= '$hash[active_date]' AND activity_scheduled_end_datetime >= '$hash[active_date]' AND ":'');
    $if_status = '';
    if(!empty($hash['t'])){
      if($hash['t'] == 'Open'){
        $if_status = "activity_status != 'Closed' AND ";
      } else {
        $if_status = "activity_status = '$hash[t]' AND ";
      }
    }
    $sql = "
      SELECT t.*,(select count(*) from documents where document_table_id = t.activity_id AND document_table = 'activities' AND document_display = '1') as activity_document_count,
             DATE_FORMAT(t.activity_scheduled_start_datetime, '%c/%e/%Y')
               AS activity_scheduled_start_datetime_formatted2,
             DATE_FORMAT(t.activity_start_datetime, '%c/%e/%Y')
               AS activity_start_datetime_formatted2,
             DATE_FORMAT(t.activity_scheduled_end_datetime, '%c/%e/%Y')
               AS activity_scheduled_end_datetime_formatted2,
             DATE_FORMAT(t.activity_end_datetime, '%c/%e/%Y')
               AS activity_end_datetime_formatted2,
             i.*
      FROM activities t
      LEFT JOIN instances i ON (t.activity_instance_id = i.instance_id)
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_active_date
            $if_status
            activity_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['activity_url'] = Common::get_url(array('bow' => $r['activity_title'],
                                                 'id' => 'A'.$r['activity_id']));
      $items[] = $r;
    }
    if($items)
      $this->activities = $items;
    $this->d = Common::direction_switch($this->d);
    return $this->activities;
  }

  # hash
  function get_activities_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',t.activity_title)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "activity_department = '$hash[c]' AND ":'');
    $sql = "
      SELECT count(t.activity_id)
      FROM activities t
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            activity_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function list_activity_departments($args){
    $this->activity_departments = array(
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
    return $this->activity_departments;
  }

  # hash
  function list_activity_department_divisions($args){
    $this->activity_department_divisions = array(
      'Billing',
      'Marketing',
      'Personnel',
      'Maintenance');
    return $this->activity_department_divisions;
  }

  # hash
  #function list_activity_categories($args){
  #  $sql = "
  #    SELECT DISTINCT activity_status
  #    FROM activities
  #    WHERE
  #          activity_display = '1'
  #    ORDER BY activity_status ASC";
  #  $results = mysql_query($sql);
  #  while($r = mysql_fetch_row($results)){
  #    $items[] = $r[0];
  #  }
  #  if($items)
  #    $this->activity_categories = $items;
  #  return $this->activity_categories;
  #}

  function list_activity_issues($args){
    $this->activity_issues = array(
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
    return $this->activity_issues;
  }

  # id, hash
  function update_activity($args){
    $id = $args['id'];
    $hash = $args['hash'];
    if(isset($hash['activity_scheduled_start_datetime']))
      $hash['activity_scheduled_start_datetime'] = date('Y-m-d H:i:s',strtotime($hash['activity_scheduled_start_datetime']));
    if(isset($hash['activity_start_datetime']))
      $hash['activity_start_datetime'] = date('Y-m-d H:i:s',strtotime($hash['activity_start_datetime']));
    if(isset($hash['activity_scheduled_end_datetime']))
      $hash['activity_scheduled_end_datetime'] = date('Y-m-d H:i:s',strtotime($hash['activity_scheduled_end_datetime']));
    if(isset($hash['activity_end_datetime']))
      $hash['activity_end_datetime'] = ($hash['activity_end_datetime'] != '' ? $hash['activity_end_datetime']:null);
    if($hash['activity_end_datetime'])
      $hash['activity_end_datetime'] = date('Y-m-d H:i:s',strtotime($hash['activity_end_datetime']));
    $item = $this->get_activity(array('id' => $id));
    $where = "activity_id = '$id'";
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
    $sql = "UPDATE activities SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
