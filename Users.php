<?php

class Users {
  var $messages = array();
  var $user = array();
  var $users = array();
  var $user_types = array();
  var $s = 'username';
  var $d = 'ASC';

  # user, hash
  function add_user($args){
    $hash = $args['hash'];
    if(!empty($hash['user_due_date'])){
      $hash['user_due_date'] = date('Y-m-d',strtotime($hash['user_due_date']));
    }else{
      unset($hash['user_due_date']);
    }
    if(!$hash['user_name']){
      $this->messages[] = "You did not enter in a user name!";
    } else {
      $id = Database::insert(array('table' => 'users', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added a user!";
      return $id;
    }
  }

  # id
  function get_user($args){
    $id = $args['id'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT p.*,
             DATE_FORMAT(p.user_created, '%m/%e/%Y %l:%i%p')
               AS user_created_formatted,
             DATE_FORMAT(p.user_due_date, '%c/%e/%Y')
               AS user_due_date_formatted2
      FROM users p
      WHERE user_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['user_url'] = Common::get_url(array('bow' => $r['user_name'],
                                                'id' => 'PRJ'.$r['user_id']));
      $this->user = $r;
    }
    return $this->user;
  }

  # hash
  function get_users($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = (isset($hash['d']) ? $hash['d']:$this->d);
    $this->s = (isset($hash['s']) ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',u.username)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $if_category = ((isset($hash['c']) && $hash['c'] != "") ? "user_category = '$hash[c]' AND ":'');
    $if_owner = ((isset($hash['o']) && $hash['o'] != "") ? "user_owner = '$hash[o]' AND ":'');
    if(!empty($hash['t'])){
      if($hash['t'] == 'Open'){
        $if_status = "user_status != 'Closed' AND ";
      } else {
        $if_status = "user_status = '$hash[t]' AND ";
      }
    }
    if(array_key_exists('user_customer_id', $hash))
      $if_customer_id = "user_customer_id = '$hash[user_customer_id]' AND ";
    $sql = "
      SELECT u.*,
             DATE_FORMAT(u.created, '%m/%e/%Y %l:%i%p')
               AS user_created_formatted
      FROM users u
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            status = '1'
      ORDER BY $this->s $this->d";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      #$r['user_url'] = Common::get_url(array('bow' => $r['user_name'],
      #                                       'id' => 'U'.$r['user_id']));
      $r['user_url'] = Common::get_url(array('bow' => $r['username'],
                                             'id' => 'U'.$r['id']));
      $items[] = $r;
    }
    if($items)
      $this->users = $items;
    $this->d = Common::direction_switch($this->d);
    return $this->users;
  }

  # hash
  function get_users_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',p.PlanNum,p.Description)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['c']) && $hash['c'] != "")
      $if_category = "user_category = '$hash[c]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_type = "PlanNum LIKE '$hash[t]-%' AND ";
    if(array_key_exists('user_customer_id', $hash))
      $if_customer_id = "user_customer_id = '$hash[user_customer_id]' AND ";
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
  function get_user_types($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',pt.type_name)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "Scanned = '$hash[c]' AND ";
    if(array_key_exists('user_customer_id', $hash))
      $if_customer_id = "user_customer_id = '$hash[user_customer_id]' AND ";
    $sql = "
      SELECT pt.*
      FROM user_types pt
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            type_display = '1'
      ORDER BY type_abbreviation ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->user_types = $items;
    return $this->user_types;
  }

  function list_user_categories($args){
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT DISTINCT user_category
      FROM users
      WHERE 
            user_display = 1
      ORDER BY user_category ASC";
            #transaction_date > '$last_year_date' AND
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $categories[] = $r[0];
    }
    return $categories;
  }

  function list_user_owners($args){
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT DISTINCT user_owner
      FROM users
      WHERE
            user_display = 1
      ORDER BY user_owner ASC";
            #transaction_date > '$last_year_date' AND
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    return $items;
  }

  # id, hash
  function update_user($args){
    $id = $args['id'];
    $hash = $args['hash'];
    if(!empty($hash['user_due_date'])){
      $hash['user_due_date'] = date('Y-m-d',strtotime($hash['user_due_date']));
    }else{
      $hash['user_due_date'] = NULL;
    }
    $item = $this->get_user(array('id' => $id));
    $where = "user_id = '$id'";
    $update = NULL;
    foreach($hash as $k => $v){
      if($v != $item[$k] && isset($item[$k])){
        $new_value = mysql_real_escape_string($v);
        $update .= (is_null($v) ? "$k = NULL," : "$k = '$new_value', ");
        $item[$k] = $v;
        $this->messages[] = "You have successfully updated the $k!";
      }
    }
    $where = rtrim($where, ' AND ');
    $update = rtrim($update, ', ');
    $sql = "UPDATE users SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
