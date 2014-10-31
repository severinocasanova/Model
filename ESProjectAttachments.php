<?php

class ESProjectattachments {
  var $messages = array();
  var $esproject = array();
  var $esproject_attachments = array();
  var $esprojects = array();
  var $esproject_types = array();
  var $s = 'Date';
  var $d = 'DESC';

  # hash, user
  function add_esproject_attachment($args){
    $hash = $args['hash'];
    $hash['AddDate'] = date('Y-m-d H:i:s',time());
    $hash['AddIP'] = $_SERVER['REMOTE_ADDR'];
    $hash['Attachment'] = preg_replace("/\s/",'_',$hash['Attachment']);
    $hash['Date'] = date('Y-m-d',strtotime($hash['Date']));
    $destination = $args['location'].$hash['Attachment'];
    if(!$hash['Attachment']){
      $this->messages[] = "You did not select a attachment!";
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
        $id = Database::insert(array('table' => 'PWCIP_Attachments', 'hash' => $hash));
        if($id){
          $this->messages[] = "You have successfully added a project attachment!";
          return $id;
        }
      }
    }
  }

  # id
  function get_esproject_attachment($args){
    $id = $args['id'];
    $sql = "
      SELECT pa.*,DATE_FORMAT(pa.Date, '%c/%e/%Y') as Date_formatted2
      FROM PWCIP_Attachments pa
      WHERE esproject_attachment_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $this->esproject_attachment = $r;
    }
    return $this->esproject_attachment;
  }

  function get_esproject_attachments($args){
    $hash = $args['hash'];
    if($hash['s']){$this->sort = $hash['s'];}
    if($hash['d']){$this->direction = $hash['d'];}
    if($hash['l']){$this->limit = $hash['l'];$limit = 'LIMIT '.$this->limit;}
    $search_fields = "CONCAT_WS(' ',pa.Comments)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    if($hash['esproject_id'])
      $hash['p'] = $hash['esproject_id'];
    if($hash['p'])
      $if_esproject_id = "pa.esproject_id = '".$hash['p']."' AND";
    $sql = "
      SELECT pa.*,
             DATE_FORMAT(pa.Date, '%m/%e/%Y')
              AS Date_formatted2
      FROM PWCIP_Attachments pa
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_esproject_id
            pa.esproject_attachment_display
      ORDER BY pa.Date DESC
      $limit";
    $results = mysql_query($sql);
    while($r = mysql_fetch_assoc($results)){
      #$r['esproject_url'] = Common::get_url(array('bow' => $r['esproject_name'],
      #                                          'id' => 'PRJ'.$r['esproject_id']));
      $items[] = $r;
    }
    if($items)
      $this->esproject_attachments = $items;
    return $this->esproject_attachments;
  }

  # id, hash
  function update_esproject_attachment($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['Date'] = date('Y-m-d H:i:s',strtotime($hash['Date']));
    $item = $this->get_esproject_attachment(array('id' => $id));
    $where = "esproject_attachment_id = '$id'";
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
    $sql = "UPDATE PWCIP_Attachments SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
