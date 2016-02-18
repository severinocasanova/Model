<?php

class ActivityAlerts {
  var $messages = array();
  var $activity_alert = array();
  var $activity_alerts = array();
  var $activity_alert_categories = array();
  var $activity_alert_departments = array();
  var $activity_alert_department_divisions = array();
  var $activity_alert_issues = array();
  var $activity_alert_types = array();
  var $s = 'activity_alert_id';
  var $d = 'DESC';

  # user, hash
  function add_activity_alert($args){
    $hash = $args['hash'];
    #$hash['ProjectPicture'] = preg_replace("/\s/",'_',$hash['ProjectPicture']);
    #$destination = $args['location'].$hash['ProjectPicture'];
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $hash['activity_alert_user_name'] = $user_name;
    if(!$hash['activity_alert_title']){
      $this->messages[] = "You did not enter in an Activity Title!";
    }elseif(!$hash['activity_alert_content']){
      $this->messages[] = "You did not enter in any content!";
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
        $id = Database::insert(array('table' => 'activity_alerts', 'hash' => $hash));
        if($id){
          $this->messages[] = "You have successfully added an activity_alert!";
          return $id;
        }
      }
    }
  }

  # id
  function get_activity_alert($args){
    $id = $args['id'];
    if(preg_match("/^\d+$/",$id)){
      $where = "WHERE activity_alert_id = '$id' ";
    }else{
      $where = "WHERE activity_alert_id = '9999999999999' ";
    }
    $sql = "
      SELECT an.*,
             DATE_FORMAT(an.activity_alert_created, '%c/%e/%Y %l:%i%p')
               AS activity_alert_created_formatted
      FROM activity_alerts an
      $where
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      #$r['activity_alert_url'] = Common::get_url(array('bow' => $r['activity_alert_title'],
      #                                          'id' => 'TK'.$r['activity_alert_id']));
      $this->activity_alert = $r;
    }
    return $this->activity_alert;
  }

  # hash
  function get_activity_alerts($args){
    $items = array();
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = ((isset($hash['d']) && $hash['d']) ? $hash['d'] : $this->d);
    $this->s = ((isset($hash['s']) && $hash['s']) ? $hash['s'] : $this->s);
    $search_fields = "CONCAT_WS(' ',an.activity_alert_title)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "20");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset]" : "LIMIT 0");
    $offset = ($ipp ? "$offset, $ipp" : "");
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "activity_alert_instance_id = '$hash[c]' AND ":'');
    $sql = "
      SELECT an.*,
             DATE_FORMAT(an.activity_alert_created, '%c/%e/%Y %l:%i%p')
               AS activity_alert_created_formatted
      FROM activity_alerts an
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            activity_alert_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['activity_alert_url'] = Common::get_url(array('bow' => $r['activity_alert_title'],
                                                 'id' => 'A'.$r['activity_alert_id']));
      $items[] = $r;
    }
    if($items)
      $this->activity_alerts = $items;
    $this->d = Common::direction_switch($this->d);
    return $this->activity_alerts;
  }

  # hash
  function get_activity_alerts_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',t.activity_alert_title)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "activity_alert_department = '$hash[c]' AND ":'');
    $sql = "
      SELECT count(t.activity_alert_id)
      FROM activity_alerts t
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            activity_alert_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function list_activity_alert_departments($args){
    $this->activity_alert_departments = array(
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
    return $this->activity_alert_departments;
  }

  # hash
  function list_activity_alert_department_divisions($args){
    $this->activity_alert_department_divisions = array(
      'Billing',
      'Marketing',
      'Personnel',
      'Maintenance');
    return $this->activity_alert_department_divisions;
  }

  # hash
  #function list_activity_alert_categories($args){
  #  $sql = "
  #    SELECT DISTINCT activity_alert_status
  #    FROM activity_alerts
  #    WHERE
  #          activity_alert_display = '1'
  #    ORDER BY activity_alert_status ASC";
  #  $results = mysql_query($sql);
  #  while($r = mysql_fetch_row($results)){
  #    $items[] = $r[0];
  #  }
  #  if($items)
  #    $this->activity_alert_categories = $items;
  #  return $this->activity_alert_categories;
  #}

  function list_activity_alert_issues($args){
    $this->activity_alert_issues = array(
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
    return $this->activity_alert_issues;
  }

  # id, hash
  function update_activity_alert($args){
    $id = $args['id'];
    $hash = $args['hash'];
    #$hash['activity_alert_due_date'] = date('Y-m-d',strtotime($hash['activity_alert_due_date']));
    $item = $this->get_activity_alert(array('id' => $id));
    $where = "activity_alert_id = '$id'";
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
    $sql = "UPDATE activity_alerts SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
