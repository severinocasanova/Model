<?php

class Trainings {
  var $messages = array();
  var $training = array();
  var $trainings = array();
  var $training_types = array();
  var $s = 'training_datetime';
  var $d = 'ASC';

  # user, hash
  function add_training($args){
    $hash = $args['hash'];
    $hash['training_datetime'] = date('Y-m-d H:i:s',strtotime($hash['training_date'].' '.$hash['training_time']));
    unset($hash['training_date']);
    unset($hash['training_time']);
    if(!$hash['training_datetime']){
      $this->messages[] = "You did not enter in a training date time!";
    } else {
      $id = Database::insert(array('table' => 'trainings', 'hash' => $hash));
      if($id){
        $this->messages[] = "You have successfully added a training!";
        return $id;
      }
    }
  }

  # id
  function get_training($args){
    $id = $args['id'];
    $sql = "
      SELECT t.*,
             DATE_FORMAT(t.training_datetime, '%m/%e/%Y')
               AS training_date_formatted,
             DATE_FORMAT(t.training_datetime, '%H:%i:%s')
               AS training_time_formatted
      FROM trainings t
      WHERE training_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $this->training = $r;
    }
    return $this->training;
  }

  # hash
  function get_trainings($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = (isset($hash['d']) ? $hash['d']:$this->d);
    $this->s = (isset($hash['s']) ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',t.training_trainee)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    #if(isset($hash['c']) && $hash['c'] != "")
    #  $if_category = "training_category = '$hash[c]' AND ";
    $if_category = ((isset($hash['c']) && $hash['c'] != "") ? "training_category = '$hash[c]' AND ":'');
    $sql = "
      SELECT t.*,
             DATE_FORMAT(t.training_created, '%m/%e/%Y %l:%i%p')
               AS training_created_formatted,
             DATE_FORMAT(t.training_datetime, '%m/%e/%Y %l:%i%p')
               AS training_datetime_formatted
      FROM trainings t
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            training_display = '1'
      ORDER BY $this->s $this->d";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['training_url'] = Common::get_url(array('bow' => $r['training_trainee'],
                                                'id' => 'TR'.$r['training_id']));
      #$r['days_before'] = ceil((strtotime($r['training_due_date']) - time()) / 86400);
      $trainings[] = $r;
    }
    if($trainings)
      $this->trainings = $trainings;
    $this->d = Common::direction_switch($this->d);
    return $this->trainings;
  }

  # hash
  function get_trainings_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',p.PlanNum,p.Description)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_category = ((isset($hash['c']) && $hash['c'] != "") ? "training_category = '$hash[c]' AND ":'');
    $sql = "
      SELECT count(p.RecID)
      FROM PlansTable p
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            RecID IS NOT NULL";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function get_training_types($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',pt.type_name)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "Scanned = '$hash[c]' AND ";
    if(array_key_exists('training_customer_id', $hash))
      $if_customer_id = "training_customer_id = '$hash[training_customer_id]' AND ";
    $sql = "
      SELECT pt.*
      FROM training_types pt
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            type_display = '1'
      ORDER BY type_abbreviation ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->training_types = $items;
    return $this->training_types;
  }

  function list_trainings_categories($args){
    $args['user']['user_name'] = (isset($args['user']['user_name']) ? $args['user']['user_name'] : NULL);
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT DISTINCT training_category
      FROM trainings
      WHERE 
            training_display = 1
      ORDER BY training_category ASC";
            #transaction_date > '$last_year_date' AND
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $categories[] = $r[0];
    }
    return $categories;
  }

  function list_training_owners($args){
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT DISTINCT training_owner
      FROM trainings
      WHERE
            training_display = 1
      ORDER BY training_owner ASC";
            #transaction_date > '$last_year_date' AND
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    return $items;
  }

  # id, hash
  function update_training($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['training_due_date'] = date('Y-m-d',strtotime($hash['training_due_date']));
    $item = $this->get_training(array('id' => $id));
    $where = "training_id = '$id'";
    $update = NULL;
    foreach($hash as $k => $v){
      if($v != $item[$k] && isset($item[$k])){
        $new_value = mysql_real_escape_string($v);
        $update .= "$k = '$new_value', ";
        $item[$k] = $v;
        $this->messages[] = "You have successfully updated the $k!";
      }
    }
    $where = rtrim($where, ' AND ');
    $update = rtrim($update, ', ');
    $sql = "UPDATE trainings SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
