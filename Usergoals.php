<?php

class Usergoals {
  var $messages = array();
  var $user_goal = array();
  var $user_goals = array();
  var $user_goal_types = array();
  var $s = 'user_goal_created';
  var $d = 'ASC';

  # user, hash
  function add_user_goal($args){
    $hash = $args['hash'];
    if(!empty($hash['user_goal_due_date'])){
      $hash['user_goal_due_date'] = date('Y-m-d',strtotime($hash['user_goal_due_date']));
    }else{
      unset($hash['user_goal_due_date']);
    }
    if(!$hash['user_goal_name']){
      $this->messages[] = "You did not enter in a user_goal name!";
    } else {
      $id = Database::insert(array('table' => 'user_goals', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added a user_goal!";
      return $id;
    }
  }

  # id
  function get_user_goal($args){
    $id = $args['id'];
    $sql = "
      SELECT a.*,
             DATE_FORMAT(a.user_goal_created, '%m/%e/%Y %l:%i%p')
               AS user_goal_created_formatted
      FROM user_goals a
      WHERE user_goal_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['user_goal_url'] = Common::get_url(array('bow' => $r['user_goal_name'],
                                            'id' => 'APP'.$r['user_goal_id']));
      $this->user_goal = $r;
    }
    return $this->user_goal;
  }

  # hash
  function get_user_goals($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $this->d = (isset($hash['d']) ? $hash['d']:$this->d);
    $this->s = (isset($hash['s']) ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',ug.user_goal_user_name)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $if_category = ((isset($hash['c']) && $hash['c'] != "") ? "user_goal_server = '$hash[c]' AND ":'');
    $if_type = ((isset($hash['t']) && $hash['t'] != "") ? "user_goal_database = '$hash[t]' AND ":'');
    $if_owner = ((isset($hash['o']) && $hash['o'] != "") ? "user_goal_owner = '$hash[o]' AND ":'');
    $sql = "
      SELECT ug.*,
             DATE_FORMAT(ug.user_goal_created, '%m/%e/%Y %l:%i%p')
               AS user_goal_created_formatted
      FROM user_goals ug
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_type
            $if_owner
            user_goal_user_name = '$user_name' AND
            user_goal_display = '1'
      ORDER BY $this->s $this->d";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['user_goal_url'] = Common::get_url(array('bow' => $r['user_goal_name'],
                                            'id' => 'APP'.$r['user_goal_id']));
      $user_goals[] = $r;
    }
    if($user_goals)
      $this->user_goals = $user_goals;
    $this->d = Common::direction_switch($this->d);
    return $this->user_goals;
  }

  # hash
  function get_user_goals_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',p.PlanNum,p.Description)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['c']) && $hash['c'] != "")
      $if_category = "user_goal_category = '$hash[c]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_type = "PlanNum LIKE '$hash[t]-%' AND ";
    if(array_key_exists('user_goal_customer_id', $hash))
      $if_customer_id = "user_goal_customer_id = '$hash[user_goal_customer_id]' AND ";
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
  function get_user_goal_types($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',pt.type_name)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "Scanned = '$hash[c]' AND ";
    if(array_key_exists('user_goal_customer_id', $hash))
      $if_customer_id = "user_goal_customer_id = '$hash[user_goal_customer_id]' AND ";
    $sql = "
      SELECT pt.*
      FROM user_goal_types pt
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            type_display = '1'
      ORDER BY type_abbreviation ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->user_goal_types = $items;
    return $this->user_goal_types;
  }

  function list_user_goal_categories($args){
    $sql = "
      SELECT DISTINCT user_goal_server
      FROM user_goals
      WHERE 
            user_goal_display = 1
      ORDER BY user_goal_server ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $categories[] = $r[0];
    }
    return $categories;
  }

  function list_user_goal_owners($args){
    $args['user']['user_name'] = (isset($args['user']['user_name']) ? $args['user']['user_name']:'');
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT DISTINCT user_goal_owner
      FROM user_goals
      WHERE
            user_goal_display = 1
      ORDER BY user_goal_owner ASC";
            #transaction_date > '$last_year_date' AND
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    return $items;
  }

  function list_user_goal_types($args){
    $sql = "
      SELECT DISTINCT user_goal_database
      FROM user_goals
      WHERE
            user_goal_display = 1
      ORDER BY user_goal_database ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $types[] = $r[0];
    }
    return $types;
  }

  # id, hash
  function update_user_goal($args){
    $id = $args['id'];
    $hash = $args['hash'];
    if(!empty($hash['user_goal_due_date'])){
      $hash['user_goal_due_date'] = date('Y-m-d',strtotime($hash['user_goal_due_date']));
      print "DATE:".$hash['user_goal_due_date'];
    }else{
      $hash['user_goal_due_date'] = NULL;
    }
    if($hash['user_goal_task_status'] == 'Closed'){
      $hash['user_goal_task_closed'] = date("Y-m-d H:i:s");
    }else{
      $hash['user_goal_task_closed'] = NULL;
    }
    $item = $this->get_user_goal(array('id' => $id));
    $where = "user_goal_id = '$id'";
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
    $sql = "UPDATE user_goals SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
