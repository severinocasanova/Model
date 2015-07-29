<?php

class Apps {
  var $messages = array();
  var $app = array();
  var $apps = array();
  var $app_types = array();
  var $s = 'app_name';
  var $d = 'ASC';

  # user, hash
  function add_app($args){
    $hash = $args['hash'];
    if(!empty($hash['app_due_date'])){
      $hash['app_due_date'] = date('Y-m-d',strtotime($hash['app_due_date']));
    }else{
      unset($hash['app_due_date']);
    }
    if(!$hash['app_name']){
      $this->messages[] = "You did not enter in a app name!";
    } else {
      $id = Database::insert(array('table' => 'apps', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added a app!";
      return $id;
    }
  }

  # id
  function get_app($args){
    $id = $args['id'];
    $sql = "
      SELECT a.*,
             DATE_FORMAT(a.app_created, '%m/%e/%Y %l:%i%p')
               AS app_created_formatted
      FROM apps a
      WHERE app_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['app_url'] = Common::get_url(array('bow' => $r['app_name'],
                                            'id' => 'APP'.$r['app_id']));
      $this->app = $r;
    }
    return $this->app;
  }

  # hash
  function get_apps($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = (isset($hash['d']) ? $hash['d']:$this->d);
    $this->s = (isset($hash['s']) ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',a.app_name)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $if_category = ((isset($hash['c']) && $hash['c'] != "") ? "app_server = '$hash[c]' AND ":'');
    $if_type = ((isset($hash['t']) && $hash['t'] != "") ? "app_database = '$hash[t]' AND ":'');
    $if_owner = ((isset($hash['o']) && $hash['o'] != "") ? "app_owner = '$hash[o]' AND ":'');
    $sql = "
      SELECT a.*,
             DATE_FORMAT(a.app_created, '%m/%e/%Y %l:%i%p')
               AS app_created_formatted
      FROM apps a
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_type
            $if_owner
            app_display = '1'
      ORDER BY $this->s $this->d";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['app_url'] = Common::get_url(array('bow' => $r['app_name'],
                                            'id' => 'APP'.$r['app_id']));
      $apps[] = $r;
    }
    if($apps)
      $this->apps = $apps;
    $this->d = Common::direction_switch($this->d);
    return $this->apps;
  }

  # hash
  function get_apps_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',p.PlanNum,p.Description)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['c']) && $hash['c'] != "")
      $if_category = "app_category = '$hash[c]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_type = "PlanNum LIKE '$hash[t]-%' AND ";
    if(array_key_exists('app_customer_id', $hash))
      $if_customer_id = "app_customer_id = '$hash[app_customer_id]' AND ";
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
  function get_app_types($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',pt.type_name)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "Scanned = '$hash[c]' AND ";
    if(array_key_exists('app_customer_id', $hash))
      $if_customer_id = "app_customer_id = '$hash[app_customer_id]' AND ";
    $sql = "
      SELECT pt.*
      FROM app_types pt
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            type_display = '1'
      ORDER BY type_abbreviation ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->app_types = $items;
    return $this->app_types;
  }

  function list_app_categories($args){
    $sql = "
      SELECT DISTINCT app_server
      FROM apps
      WHERE 
            app_display = 1
      ORDER BY app_server ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $categories[] = $r[0];
    }
    return $categories;
  }

  function list_app_owners($args){
    $args['user']['user_name'] = (isset($args['user']['user_name']) ? $args['user']['user_name']:'');
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT DISTINCT app_owner
      FROM apps
      WHERE
            app_display = 1
      ORDER BY app_owner ASC";
            #transaction_date > '$last_year_date' AND
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    return $items;
  }

  function list_app_types($args){
    $sql = "
      SELECT DISTINCT app_database
      FROM apps
      WHERE
            app_display = 1
      ORDER BY app_database ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $types[] = $r[0];
    }
    return $types;
  }

  # id, hash
  function update_app($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['app_due_date'] = (isset($hash['app_due_date']) ? $hash['app_due_date'] : NULL);
    if(!empty($hash['app_due_date'])){
      $hash['app_due_date'] = date('Y-m-d',strtotime($hash['app_due_date']));
      #print "DATE:".$hash['app_due_date'];
    }else{
      $hash['app_due_date'] = NULL;
    }
    $hash['app_task_status'] = (isset($hash['app_task_status']) ? $hash['app_task_status'] : NULL);
    if($hash['app_task_status'] == 'Closed'){
      $hash['app_task_closed'] = date("Y-m-d H:i:s");
    }else{
      $hash['app_task_closed'] = NULL;
    }
    $item = $this->get_app(array('id' => $id));
    $item['app_due_date'] = (isset($item['app_due_date']) ? $item['app_due_date'] : NULL);
    $item['app_task_status'] = (isset($item['app_task_status']) ? $item['app_task_status'] : NULL);
    $item['app_task_closed'] = (isset($item['app_task_closed']) ? $item['app_task_closed'] : NULL);
    $where = "app_id = '$id'";
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
    $sql = "UPDATE apps SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
