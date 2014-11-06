<?php

class Projects {
  var $messages = array();
  var $project = array();
  var $projects = array();
  var $project_types = array();
  var $s = 'project_priority';
  var $d = 'ASC';

  # user, hash
  function add_project($args){
    $hash = $args['hash'];
    if(!empty($hash['project_due_date'])){
      $hash['project_due_date'] = date('Y-m-d',strtotime($hash['project_due_date']));
    }else{
      unset($hash['project_due_date']);
    }
    if(!$hash['project_name']){
      $this->messages[] = "You did not enter in a project name!";
    } else {
      $id = Database::insert(array('table' => 'projects', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added a project!";
      return $id;
    }
  }

  # id
  function get_project($args){
    $id = $args['id'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT p.*,
             DATE_FORMAT(p.project_created, '%m/%e/%Y %l:%i%p')
               AS project_created_formatted,
             DATE_FORMAT(p.project_due_date, '%c/%e/%Y')
               AS project_due_date_formatted2
      FROM projects p
      WHERE project_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['project_url'] = Common::get_url(array('bow' => $r['project_name'],
                                                'id' => 'PRJ'.$r['project_id']));
      $this->project = $r;
    }
    return $this->project;
  }

  # hash
  function get_projects($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = (isset($hash['d']) ? $hash['d']:$this->d);
    $this->s = (isset($hash['s']) ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',p.project_name)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $if_category = ((isset($hash['c']) && $hash['c'] != "") ? "project_category = '$hash[c]' AND ":'');
    $if_owner = ((isset($hash['o']) && $hash['o'] != "") ? "project_owner = '$hash[o]' AND ":'');
    $if_status = '';
    if(!empty($hash['t'])){
      if($hash['t'] == 'Open'){
        $if_status = "project_status != 'Closed' AND ";
      } else {
        $if_status = "project_status = '$hash[t]' AND ";
      }
    }
    if(array_key_exists('project_customer_id', $hash))
      $if_customer_id = "project_customer_id = '$hash[project_customer_id]' AND ";
    $sql = "
      SELECT p.*,
             DATE_FORMAT(p.project_created, '%m/%e/%Y %l:%i%p')
               AS project_created_formatted,
             DATE_FORMAT(p.project_due_date, '%c/%e/%Y')
               AS project_due_date_formatted2
      FROM projects p
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_owner
            $if_status
            project_display = '1'
      ORDER BY $this->s $this->d";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['project_url'] = Common::get_url(array('bow' => $r['project_name'],
                                                'id' => 'PRJ'.$r['project_id']));
      $r['days_before'] = ceil((strtotime($r['project_due_date']) - time()) / 86400);
      $projects[] = $r;
    }
    if($projects)
      $this->projects = $projects;
    $this->d = Common::direction_switch($this->d);
    return $this->projects;
  }

  # hash
  function get_projects_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',p.PlanNum,p.Description)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['c']) && $hash['c'] != "")
      $if_category = "project_category = '$hash[c]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_type = "PlanNum LIKE '$hash[t]-%' AND ";
    if(array_key_exists('project_customer_id', $hash))
      $if_customer_id = "project_customer_id = '$hash[project_customer_id]' AND ";
    $sql = "
      SELECT count(p.RecID)
      FROM PlansTable p
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_type
            $if_customer_id
            RecID IS NOT NULL";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function get_project_types($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',pt.type_name)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "Scanned = '$hash[c]' AND ";
    if(array_key_exists('project_customer_id', $hash))
      $if_customer_id = "project_customer_id = '$hash[project_customer_id]' AND ";
    $sql = "
      SELECT pt.*
      FROM project_types pt
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            type_display = '1'
      ORDER BY type_abbreviation ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->project_types = $items;
    return $this->project_types;
  }

  function list_project_categories($args){
    $args['user']['user_name'] = (isset($args['user']['user_name']) ? $args['user']['user_name']:'');
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT DISTINCT project_category
      FROM projects
      WHERE 
            project_display = 1
      ORDER BY project_category ASC";
            #transaction_date > '$last_year_date' AND
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $categories[] = $r[0];
    }
    return $categories;
  }

  function list_project_owners($args){
    $args['user']['user_name'] = (isset($args['user']['user_name']) ? $args['user']['user_name']:'');
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT DISTINCT project_owner
      FROM projects
      WHERE
            project_display = 1
      ORDER BY project_owner ASC";
            #transaction_date > '$last_year_date' AND
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    return $items;
  }

  # id, hash
  function update_project($args){
    $id = $args['id'];
    $hash = $args['hash'];
    if(!empty($hash['project_due_date'])){
      $hash['project_due_date'] = date('Y-m-d',strtotime($hash['project_due_date']));
      print "DATE:".$hash['project_due_date'];
    }else{
      $hash['project_due_date'] = NULL;
    }
    if($hash['project_task_status'] == 'Closed'){
      $hash['project_task_closed'] = date("Y-m-d H:i:s");
    }else{
      $hash['project_task_closed'] = NULL;
    }
    $item = $this->get_project(array('id' => $id));
    $where = "project_id = '$id'";
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
    $sql = "UPDATE projects SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
