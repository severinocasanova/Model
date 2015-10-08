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
    $hash['AddDate'] = date('Y-m-d',time());
    $hash['AddIP'] = $_SERVER['REMOTE_ADDR'];
    $hash['Picture'] = preg_replace("/\s/",'_',$hash['Picture']);
    $destination = $args['location'].$hash['Picture'];
    if(!$hash['Picture']){
      $this->messages[] = "You did not select a picture!";
    } else {
      if($hash['tmp_name']){
        if(move_uploaded_file($hash['tmp_name'], $destination)){
          $upload_success = 1;
        }else{
          $this->messages[] = "We could not upload the file at this time!";
        }
      }
      if($upload_success || !$hash['tmp_name']){
        unset($hash['tmp_name']);
        $id = Database::insert(array('table' => 'PWCIP_Pictures', 'hash' => $hash));
        if($id){
          $this->messages[] = "You have successfully added a project picture!";
          return $id;
        }
      }
    }
  }

  # id
  function get_esproject_picture($args){
    $id = $args['id'];
    $sql = "
      SELECT pp.*,DATE_FORMAT(pp.Date, '%c/%e/%Y') as Date_formatted2
      FROM PWCIP_Pictures pp
      WHERE esproject_picture_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $this->esproject_picture = $r;
    }
    return $this->esproject_picture;
  }

  function get_esproject_pictures($args){
    $hash = $args['hash'];
    $this->d = ((isset($hash['d']) && $hash['d'] != '') ? $hash['d']:$this->d);
    $this->s = ((isset($hash['s']) && $hash['s'] != '') ? $hash['s']:$this->s);
    #if($hash['l']){$this->limit = $hash['l'];$limit = 'LIMIT '.$this->limit;}
    $search_fields = "CONCAT_WS(' ',pp.Comments)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if($hash['esproject_id'])
      $hash['p'] = $hash['esproject_id'];
    if($hash['p'])
      $if_esproject_id = "pp.esproject_id = '".$hash['p']."' AND";
    $sql = "
      SELECT pp.*,
             DATE_FORMAT(pp.Date, '%m/%e/%Y')
              AS Date_formatted2
      FROM PWCIP_Pictures pp
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_esproject_id
            pp.esproject_picture_display = '1'
      ORDER BY pp.Date DESC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_assoc($results)){
#      $r['esproject_pictureurl'] = Common::get_url(array('bow' => $r['esproject_name'],
#                                                'id' => 'PRJ'.$r['esproject_id']));
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
    $hash['Date'] = date('Y-m-d H:i:s',strtotime($hash['Date']));
    $item = $this->get_esproject_picture(array('id' => $id));
    $where = "esproject_picture_id = '$id'";
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
    $sql = "UPDATE PWCIP_Pictures SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
