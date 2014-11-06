<?php

class Projecthistory {
  var $messages = array();
  var $project = array();
  var $project_history_entries = array();
  var $projects = array();
  var $project_types = array();
  var $s = 'project_name';
  var $d = 'ASC';

  # hash, user
  function add_project_history($args){
    $hash = $args['hash'];
    $hash['project_history_user_name'] = $args['user']['user_name'];
    $hash['project_history_date'] = date('Y-m-d',strtotime($hash['project_history_date']));
    if(!$hash['project_history_title']){
      $this->messages[] = "You did not enter in any project history!";
    } else {
      $id = Database::insert(array('table' => 'project_history', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added project history!";
      return $id;
    }
  }

  # id
  function get_project_history($args){
    $id = $args['id'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT ph.*,
             DATE_FORMAT(ph.project_history_created, '%m/%e/%Y %l:%i%p')
               AS project_history_created_formatted,
             DATE_FORMAT(ph.project_history_date, '%c/%e/%Y')
               AS project_history_date_formatted2
      FROM project_history ph
      WHERE project_history_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['project_history_url'] = Common::get_url(array('bow' => $r['project_history_title'],
                                                        'id' => 'PH'.$r['project_history_id']));
      $this->project_history = $r;
    }
    return $this->project_history;
  }

  function get_project_history_entries($args){
    $hash = $args['hash'];
    $this->d = (isset($hash['d']) ? $hash['d']:$this->d);
    $this->s = (isset($hash['s']) ? $hash['s']:$this->s);
    $limit = '';
    $hash['l'] = (isset($hash['l']) ? $hash['l']:'');
    if($hash['l']){$this->limit = $hash['l'];$limit = 'LIMIT '.$this->limit;}
    $search_fields = "CONCAT_WS(' ',ph.project_history_title,ph.project_history_content)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $hash['p'] = (isset($hash['project_history_project_id']) ? $hash['project_history_project_id']:'');
    $if_project_history_project_id = ($hash['p'] ? "project_history_project_id = '".$hash['p']."' AND ":'');
    $hash['u'] = (isset($hash['u']) ? $hash['u']:'');
    $if_username = ($hash['u'] ? "project_history_user_name = '".$hash['u']."' AND ":'');
    $sql = "
      SELECT ph.*,p.*,
             DATE_FORMAT(ph.project_history_created, '%m/%e/%Y %l:%i%p')
              AS project_history_created_formatted,
             DATE_FORMAT(ph.project_history_date, '%c/%e/%Y')
               AS project_history_date_formatted2
      FROM project_history ph
      LEFT JOIN projects p ON (ph.project_history_project_id = p.project_id)
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_project_history_project_id
            $if_username
            project_history_display = '1'
      ORDER BY project_history_date DESC
      $limit";
    $results = mysql_query($sql);
    while($r = mysql_fetch_assoc($results)){
      $r['project_url'] = Common::get_url(array('bow' => $r['project_name'],
                                                'id' => 'PRJ'.$r['project_id']));
      $items[] = $r;
    }
    if($items)
      $this->project_history_entries = $items;
    return $this->project_history_entries;
  }

  # id, hash
  function update_project_history($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $hash['project_history_date'] = date('Y-m-d',strtotime($hash['project_history_date']));
    $item = $this->get_project_history(array('id' => $id));
    $where = "project_history_id = '$id'";
    $update = NULL;
    if($user_name == $item['project_history_user_name']){
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
      $sql = "UPDATE project_history SET $update WHERE $where";
    }else{
      $this->messages[] = "You do not have permission to edit this entry!";
    }
    if($update){
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
      return $item;
    }
  }

}

?>
