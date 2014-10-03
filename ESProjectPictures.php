<?php

class ESProjectpictures {
  var $messages = array();
  var $esproject = array();
  var $esproject_pictures = array();
  var $esprojects = array();
  var $esproject_types = array();
  var $s = 'esproject_name';
  var $d = 'ASC';

  # hash, user
  function add_esproject_picture($args){
    $hash = $args['hash'];
    $hash['esproject_picture_user_name'] = $args['user']['user_name'];
    $hash['esproject_picture_date'] = date('Y-m-d',strtotime($hash['esproject_picture_date']));
    if(!$hash['esproject_picture_title']){
      $this->messages[] = "You did not enter in any esproject picture!";
    } else {
      $id = Database::insert(array('table' => 'esproject_picture', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added esproject picture!";
      return $id;
    }
  }

  # id
  function get_esproject_picture($args){
    $id = $args['id'];
    $sql = "
      SELECT ph.*,
             DATE_FORMAT(ph.esproject_picture_created, '%m/%e/%Y %l:%i%p')
               AS esproject_picture_created_formatted
      FROM esproject_pictures ph
      WHERE esproject_picture_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['esproject_picture_url'] = Common::get_url(array('bow' => $r['esproject_picture_title'],
                                                        'id' => 'PH'.$r['esproject_picture_id']));
      $this->esproject_picture = $r;
    }
    return $this->esproject_picture;
  }

  function get_esproject_pictures($args){
    $hash = $args['hash'];
    if($hash['s']){$this->sort = $hash['s'];}
    if($hash['d']){$this->direction = $hash['d'];}
    if($hash['l']){$this->limit = $hash['l'];$limit = 'LIMIT '.$this->limit;}
    $search_fields = "CONCAT_WS(' ',ph.esproject_picture_content)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    if($hash['esproject_picture_esproject_id'])
      $hash['p'] = $hash['esproject_picture_esproject_id'];
    if($hash['p'])
      $if_esproject_picture_esproject_id = "esproject_picture_esproject_id = '".$hash['p']."' AND";
    if($hash['u'])
      $if_username = "esproject_picture_user_name = '".$hash['u']."' AND";
    $sql = "
      SELECT ph.*,
             DATE_FORMAT(ph.esproject_picture_created, '%m/%e/%Y %l:%i%p')
              AS esproject_picture_created_formatted
      FROM esproject_pictures ph
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_esproject_picture_esproject_id
            $if_username
            esproject_picture_display = '1'
      ORDER BY esproject_picture_created DESC
      $limit";
    $results = mysql_query($sql);
    while($r = mysql_fetch_assoc($results)){
      $r['esproject_url'] = Common::get_url(array('bow' => $r['esproject_name'],
                                                'id' => 'PRJ'.$r['esproject_id']));
      $items[] = $r;
    }
    if($items)
      $this->esproject_pictures = $items;
    return $this->esproject_pictures;
  }

  # id, hash
  function update_esproject_picture($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $item = $this->get_esproject_picture(array('id' => $id));
    $where = "esproject_picture_id = '$id'";
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
    $sql = "UPDATE esproject_pictures SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
