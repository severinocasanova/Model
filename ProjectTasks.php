<?php

class ProjectTasks {
  var $messages = array();
  var $project = array();
  var $project_task_entries = array();
  var $projects = array();
  var $project_tasks = array();
  var $s = 'project_task_title';
  var $d = 'ASC';

  # user, hash
  function add_project_task($args){
    $hash = $args['hash'];
    if(!$hash['project_task_title']){
      $this->messages[] = "You did not enter in a task title!";
    }else {
      $id = Database::insert(array('table' => 'project_tasks', 'hash' => $hash));
      if($id){
        $this->messages[] = "You have successfully added a project task!";
        return $id;
      }
    }
  }

  # id
  function get_project_task($args){
    $id = $args['id'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT d.*, p.*,
             DATE_FORMAT(d.project_task_created, '%b %e, %Y')
               AS project_task_created_formatted
      FROM project_tasks d
      LEFT JOIN projects p ON (d.project_task_project_id = p.project_id)
      WHERE project_task_id = '$id' AND
            project_task_display = '1'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['project_task_url'] = Common::get_url(array('bow' => $r['project_task_name'],
                                                 'id' => 'DOC'.$r['project_task_id']));
      $this->project_task = $r;
    }
    return $this->project_task;
  }

  function get_project_tasks($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    if($hash['l']){$this->l = $hash['l'];$limit = 'LIMIT '.$this->l;}
    $search_fields = "CONCAT_WS(' ',pt.project_task_title)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    if($hash['project_task_project_id'])
      $hash['c'] = $hash['project_task_project_id'];
    if($hash['c'])
      $if_project_task_project_id = "project_task_project_id = '".$hash['c']."' AND";
    $sql = "
      SELECT pt.*,p.*,
             DATE_FORMAT(pt.project_task_created, '%m/%e/%Y %l:%i%p')
              AS project_task_created_formatted
      FROM project_tasks pt
      LEFT JOIN projects p ON (pt.project_task_project_id = p.project_id)
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_project_task_project_id
            project_task_display = '1'
      ORDER BY project_task_created DESC
      $limit";
    $results = mysql_query($sql);
    while($r = mysql_fetch_assoc($results)){
      $r['project_url'] = Common::get_url(array('bow' => $r['project_name'],
                                                'id' => 'PRJ'.$r['project_id']));
      $r['project_task_url'] = Common::get_url(array('bow' => $r['project_task_name'],
                                             'id' => 'TSK'.$r['project_task_id']));
      $items[] = $r;
    }
    if($items)
      $this->project_tasks = $items;
    return $this->project_tasks;
  }

  # id, hash
  function update_project_task($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $item = $this->get_project_task(array('id' => $id));
    $where = "project_task_id = '$id'";
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
    $sql = "UPDATE project_tasks SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
