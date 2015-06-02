<?php

class Departmentcontacts {
  var $messages = array();
  var $department_contact = array();
  var $department_contacts = array();
  var $department_contact_categories = array();
  var $department_contact_departments = array();
  var $department_contact_types = array();
  var $s = 'department_contact_name';
  var $d = 'ASC';

  # user, hash
  function add_department_contact($args){
    $hash = $args['hash'];
    #$hash['ProjectPicture'] = preg_replace("/\s/",'_',$hash['ProjectPicture']);
    #$destination = $args['location'].$hash['ProjectPicture'];
    if(!$hash['department_contact_name']){
      $this->messages[] = "You did not enter in a Contact Name!";
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
        $id = Database::insert(array('table' => 'department_contacts', 'hash' => $hash));
        if($id){
          $this->messages[] = "You have successfully added a constituent department_contact!";
          return $id;
        }
      }
    }
  }

  # id
  function get_department_contact($args){
    $id = $args['id'];
    if(preg_match("/^\d+$/",$id)){
      $where = "WHERE department_contact_id = '$id' AND ";
    }else if($args['email']){
      $where = "WHERE department_contact_email = '$args[email]' AND ";
    }else if($args['name']){
      $where = "WHERE department_contact_name = '$args[name]' AND ";
    }else{
      $where = "WHERE department_contact_id = '9999999999999' AND ";
    }
    $sql = "
      SELECT dc.*,
             DATE_FORMAT(dc.department_contact_created, '%c/%e/%Y')
              AS department_contact_created_formatted2
      FROM department_contacts dc
           $where
           department_contact_display = '1'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $this->department_contact = $r;
    }
    return $this->department_contact;
  }

  # hash
  function get_department_contacts($args){
    $hash = $args['hash'];
    $this->d = (isset($hash['d']) ? $hash['d']:$this->d);
    $this->s = (isset($hash['s']) ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',d.department_contact_name)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "d.department_contact_department LIKE '$hash[c]' AND ":'');
    $if_type = (isset($hash['t']) && $hash['t'] != '' ? "d.department_contact_initiator = '$hash[t]' AND ":'');
    $sql = "
      SELECT d.*
      FROM department_contacts d
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_type
            department_contact_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['department_contact_url'] = Common::get_url(array('bow' => $r['department_contact_issue'],
                                                  'id' => 'CNRN'.$r['department_contact_id']));
      $department_contacts[] = $r;
    }
    if($department_contacts)
      $this->department_contacts = $department_contacts;
    $this->d = Common::direction_switch($this->d);
    return $this->department_contacts;
  }

  # hash
  function get_department_contacts_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',c.department_contact_constituent_name,c.department_contact_issue,c.department_contact_description)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $if_scanned = (isset($hash['c']) && $hash['c'] != '' ? "Status = '$hash[c]' AND ":'');
    $if_type = (isset($hash['t']) && $hash['t'] != '' ? "Type LIKE '$hash[t]' AND ":'');
    $sql = "
      SELECT count(c.department_contact_id)
      FROM department_contacts c
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_scanned
            $if_type
            department_contact_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function list_department_contact_departments($args){
    $this->department_contact_departments = array(
      'IT',
      'Police',
      'Water');
    return $this->department_contact_departments;
  }

  # hash
  function list_department_contact_categories($args){
    $sql = "
      SELECT DISTINCT Status
      FROM PWCIP
      WHERE
            department_contact_display = '1'
      ORDER BY Status ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->department_contact_categories = $items;
    return $this->department_contact_categories;
  }

  # hash
  function list_department_contact_types($args){
    $sql = "
      SELECT DISTINCT Type
      FROM PWCIP
      WHERE
            department_contact_display = '1'
      ORDER BY Type ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->department_contact_types = $items;
    return $this->department_contact_types;
  }

  # id, hash
  function update_department_contact($args){
    $id = $args['id'];
    $hash = $args['hash'];
    #$hash['department_contact_due_date'] = date('Y-m-d',strtotime($hash['department_contact_due_date']));
    $item = $this->get_department_contact(array('id' => $id));
    $where = "department_contact_id = '$id'";
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
    $sql = "UPDATE department_contacts SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
