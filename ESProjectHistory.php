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
    $hash['AddDate'] = date('Y-m-d',time());
    if(!$hash['Date']){
      $this->messages[] = "You did not enter in anything for the Date field!";
    } else {
      $id = Database::insert(array('table' => 'PWCIP_Status', 'hash' => $hash));
      if($id){
        $this->messages[] = "You have successfully added project history!";
        return $id;
      }
    }
  }

  # id
  function get_esproject_history($args){
    $id = $args['id'];
    $sql = "
      SELECT ph.*,
             DATE_FORMAT(ph.AddDate, '%m/%e/%Y')
               AS AddDate_formatted2
      FROM PWCIP_Status ph
      WHERE esproject_history_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      #$r['esproject_history_url'] = Common::get_url(array('bow' => $r['esproject_history_title'],
      #                                                 'id' => 'PH'.$r['esproject_history_id']));
      $this->esproject_history = $r;
    }
    return $this->esproject_history;
  }

  function get_esproject_history_entries($args){
    $hash = $args['hash'];
    $this->d = ((isset($hash['d']) && $hash['d'] != '') ? $hash['d']:$this->d);
    $this->s = ((isset($hash['s']) && $hash['s'] != '') ? $hash['s']:$this->s);
    #if($hash['l']){$this->limit = $hash['l'];$limit = 'LIMIT '.$this->limit;}
    $search_fields = "CONCAT_WS(' ',ph.Date,ph.Status)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if($hash['esproject_id'])
      $hash['p'] = $hash['esproject_id'];
    if($hash['p'])
      $if_esproject_id = "ph.esproject_id = '".$hash['p']."' AND";
    #if($hash['u'])
    #  $if_username = "esproject_history_user_name = '".$hash['u']."' AND";
    $sql = "
      SELECT ph.*,ph.Status as StatusStatus,
             DATE_FORMAT(ph.AddDate, '%c/%e/%Y')
               AS AddDate_formatted2
      FROM PWCIP_Status ph
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_esproject_id
            ph.esproject_history_display = '1'
      ORDER BY ph.Ordinal DESC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_assoc($results)){
      #$r['esproject_url'] = Common::get_url(array('bow' => $r['esproject_name'],
      #                                            'id' => 'PRJ'.$r['esproject_id']));
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
    $hash['AddDate'] = date('Y-m-d H:i:s',strtotime($hash['AddDate']));
    $item = $this->get_esproject_history(array('id' => $id));
    $where = "esproject_history_id = '$id'";
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
    $sql = "UPDATE PWCIP_Status SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
