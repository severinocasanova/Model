<?php

class Documents {
  var $messages = array();
  var $project = array();
  var $document_entries = array();
  var $projects = array();
  var $project_types = array();
  var $s = 'project_name';
  var $d = 'ASC';

  # user, hash
  function add_document($args){
    $hash = $args['hash'];
    $hash['document_user_name'] = $args['user']['user_name'];
    $hash['document_filename'] = preg_replace("/\s/",'_',$hash['document_filename']);
    $destination = $args['location'].$hash['document_filename'];
    if(!$hash['document_name']){
      $this->messages[] = "You did not enter in a document name!";
    }elseif(!$hash['tmp_name']){
      $this->messages[] = "You did not select a document!";
    }elseif(!$args['location']){
      $this->messages[] = "The server admin has not set the location variable!";
    }else {
      if($hash['tmp_name']){
        if(move_uploaded_file($hash['tmp_name'], $destination)){
          $upload_success = 1;
        }else{
          $this->messages[] = "We could not upload the file at this time!";
        }
      }
      if($upload_success || !$hash['tmp_name']){
        unset($hash['tmp_name']);
        $id = Database::insert(array('table' => 'documents', 'hash' => $hash));
        if($id){
          $this->messages[] = "You have successfully added a document!";
          return $id;
        }
      }
    }
  }

  # id
  function get_document($args){
    $id = $args['id'];
    $sql = "
      SELECT d.*, 
             DATE_FORMAT(d.document_created, '%b %e, %Y')
               AS document_created_formatted
      FROM documents d
      WHERE document_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['document_url'] = Common::get_url(array('bow' => $r['document_name'],
                                                 'id' => 'DOC'.$r['document_id']));
      $this->document = $r;
    }
    return $this->document;
  }

  function get_documents($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    if($hash['l']){$this->l = $hash['l'];$limit = 'LIMIT '.$this->l;}
    $search_fields = "CONCAT_WS(' ',d.document_name,d.document_description)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    if($hash['document_project_id'])
      $hash['c'] = $hash['document_project_id'];
    if($hash['c'])
      $if_document_project_id = "document_project_id = '".$hash['c']."' AND ";
    if($hash['document_table']){
      $if_document_table = "document_table = '".$hash['document_table']."' AND ";
      $if_document_table .= "document_table_id = '".$hash['document_table_id']."' AND ";
    }
    $sql = "
      SELECT d.*,
             DATE_FORMAT(d.document_created, '%m/%e/%Y %l:%i%p')
              AS document_created_formatted
      FROM documents d
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_document_project_id
            $if_document_table
            document_display = '1'
      ORDER BY document_created DESC
      $limit";
    $results = mysql_query($sql);
    while($r = mysql_fetch_assoc($results)){
      $r['project_url'] = Common::get_url(array('bow' => $r['project_name'],
                                                 'id' => 'PRJ'.$r['project_id']));
      $r['document_url'] = Common::get_url(array('bow' => $r['document_name'],
                                                 'id' => 'DOC'.$r['document_id']));
      $items[] = $r;
    }
    if($items)
      $this->documents = $items;
    return $this->documents;
  }

  # id, hash
  function update_document($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $item = $this->get_document(array('id' => $id));
    $where = "document_id = '$id'";
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
    $sql = "UPDATE documents SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
