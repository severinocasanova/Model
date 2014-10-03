<?php

class ESProjecthistory {
  var $messages = array();
  var $esproject = array();
  var $esproject_history_entries = array();
  var $esprojects = array();
  var $esproject_types = array();
  var $s = 'esproject_name';
  var $d = 'ASC';

  # hash, user
  function add_esproject_history($args){
    $hash = $args['hash'];
    $hash['esproject_history_user_name'] = $args['user']['user_name'];
    $hash['esproject_history_date'] = date('Y-m-d',strtotime($hash['esproject_history_date']));
    if(!$hash['esproject_history_title']){
      $this->messages[] = "You did not enter in any esproject history!";
    } else {
      $id = Database::insert(array('table' => 'esproject_history', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added esproject history!";
      return $id;
    }
  }

  # id
  function get_esproject_history($args){
    $id = $args['id'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT ph.*,
             DATE_FORMAT(ph.esproject_history_created, '%m/%e/%Y %l:%i%p')
               AS esproject_history_created_formatted,
             DATE_FORMAT(ph.esproject_history_date, '%c/%e/%Y')
               AS esproject_history_date_formatted2
      FROM esproject_history ph
      WHERE esproject_history_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['esproject_history_url'] = Common::get_url(array('bow' => $r['esproject_history_title'],
                                                        'id' => 'PH'.$r['esproject_history_id']));
      $this->esproject_history = $r;
    }
    return $this->esproject_history;
  }

  function get_esproject_history_entries($args){
    $hash = $args['hash'];
    if($hash['s']){$this->sort = $hash['s'];}
    if($hash['d']){$this->direction = $hash['d'];}
    if($hash['l']){$this->limit = $hash['l'];$limit = 'LIMIT '.$this->limit;}
    $search_fields = "CONCAT_WS(' ',ph.esproject_history_title,ph.esproject_history_content)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    if($hash['esproject_history_esproject_id'])
      $hash['p'] = $hash['esproject_history_esproject_id'];
    if($hash['p'])
      $if_esproject_history_esproject_id = "esproject_history_esproject_id = '".$hash['p']."' AND";
    if($hash['u'])
      $if_username = "esproject_history_user_name = '".$hash['u']."' AND";
    $sql = "
      SELECT ph.*,p.*,
             DATE_FORMAT(ph.esproject_history_created, '%m/%e/%Y %l:%i%p')
              AS esproject_history_created_formatted,
             DATE_FORMAT(ph.esproject_history_date, '%c/%e/%Y')
               AS esproject_history_date_formatted2
      FROM esproject_history ph
      LEFT JOIN esprojects p ON (ph.esproject_history_esproject_id = p.esproject_id)
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_esproject_history_esproject_id
            $if_username
            esproject_history_display = '1'
      ORDER BY esproject_created DESC
      $limit";
    $results = mysql_query($sql);
    while($r = mysql_fetch_assoc($results)){
      $r['esproject_url'] = Common::get_url(array('bow' => $r['esproject_name'],
                                                'id' => 'PRJ'.$r['esproject_id']));
      $items[] = $r;
    }
    if($items)
      $this->esproject_history_entries = $items;
    return $this->esproject_history_entries;
  }

  # id, hash
  function update_esproject_history($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['esproject_history_date'] = date('Y-m-d',strtotime($hash['esproject_history_date']));
    $item = $this->get_esproject_history(array('id' => $id));
    $where = "esproject_history_id = '$id'";
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
    $sql = "UPDATE esproject_history SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
