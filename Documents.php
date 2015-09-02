<?php

class Documents {
  var $messages = array();
  var $document = array();
  var $project = array();
  var $document_entries = array();
  var $documents = array();
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
    }elseif(!$hash['document_filename']){
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
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = ((isset($hash['d']) && $hash['d'] != '') ? $hash['d']:$this->d);
    $this->s = ((isset($hash['s']) && $hash['s'] != '') ? $hash['s']:$this->s);

    if(isset($hash['l'])){$this->l = $hash['l'];$limit = 'LIMIT '.$this->l;}
    $limit = (isset($hash['l']) ? 'LIMIT '.$hash['l'] : '');
    $search_fields = "CONCAT_WS(' ',d.document_name,d.document_description)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);

    $ipp = (isset($args['ipp']) ? $args['ipp'] : NULL);
    $offset = (isset($args['offset']) ? "LIMIT $args[offset]" : "LIMIT 0");
    $offset = ($ipp ? "$offset, $ipp" : "");
    if(isset($hash['document_project_id']))
      $hash['c'] = $hash['document_project_id'];
    $if_document_project_id = ((isset($hash['c']) && $hash['c'] != "") ? "document_project_id = '$hash[c]' AND ":'');
    $if_document_table = '';
    if(isset($hash['document_table'])){
      $if_document_table = "document_table = '".$hash['document_table']."' AND ";
      if(isset($hash['document_table_id'])){
        $if_document_table .= "document_table_id = '".$hash['document_table_id']."' AND ";
      }
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
      $r['document_url'] = Common::get_url(array('bow' => $r['document_name'],
                                                 'id' => 'DOC'.$r['document_id']));
      $items[] = $r;
    }
    if(isset($items))
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
