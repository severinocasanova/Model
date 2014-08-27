<?php

class ProjectContacts {
  var $messages = array();
  var $project_contact = array();
  var $project_contact_addresses = array();
  var $project_contact_email_addresses = array();
  var $project_contact_phone_numbers = array();
  var $project_contact_projects = array();
  var $project_contact_screen_names = array();
  var $project_contacts = array();

  # user, hash
  function add_project_contact($args){
    $hash = $args['hash'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $hash['project_contact_user_name'] = $user_name;
    if(!$hash['project_contact_contact_id']){
      $this->messages[] = "You did not select a contact!";
    } else {
      $id = Database::insert(array('table' => 'project_contacts', 'hash' => $hash));
      return $id;
    }
  }

  # id, hash
  function add_project_contact_address($args){
    $hash = $args['hash'];
    $hash['address_project_contact_id'] = $args['id'];
    $hash['address_created'] = date("Y-m-d H:i:s");
    $hash['address_primary'] = ($hash['address_primary'] == 'on' ? 1 : 0);
    unset($hash['address_add']);
    if(!$hash['address_address']){
      $this->messages[] = "You did not enter in an address!";
    } else {
      $id = Database::insert(array('table' => 'addresses', 'hash' => $hash));
      if($id){
        $this->messages[] = "You have successfully added a new address!";
        return $id;
      }
    }
  }

  # id, hash
  function add_project_contact_email_address($args){
    $hash = $args['hash'];
    $hash['email_primary'] = ($hash['email_primary'] == 'on' ? 1 : 0);
    $hash['email_project_contact_id'] = $args['id'];
    $hash['email_created'] = date("Y-m-d H:i:s");
    unset($hash[email_add]);
    if(!$hash['email_address']){
      $this->messages[] = "You did not enter in an email address!";
    } else {
      $id = Database::insert(array('table' => 'email_addresses', 'hash' => $hash));
      if($id){
        $this->messages[] = "You have successfully added an email address!";
      }
    }
  }

  # id, hash
  function add_project_contact_phone_number($args){
    $hash = $args['hash'];
    $hash['phone_primary'] = ($hash['phone_primary'] == 'on' ? 1 : 0);
    $hash['phone_project_contact_id'] = $args['id'];
    $hash['phone_created'] = date("Y-m-d H:i:s");
    unset($hash[phone_add]);
    if(!$hash['phone_three']){
      $this->messages[] = "You did not enter in a full phone number!";
    } else {
      $id = Database::insert(array('table' => 'phone_numbers', 'hash' => $hash));
      if($id){
        $this->messages[] = "You have successfully added a phone number!";
      }
    }
  }

  # id, hash
  function add_project_contact_screen_name($args){
    $hash = $args['hash'];
    $hash[screen_project_contact_id] = $args['id'];
    $hash[screen_created] = date("Y-m-d H:i:s");
    unset($hash[screen_add]);
    if(!$hash['screen_name']){
      $this->messages[] = "You did not enter in a screen name!";
    } else {
      $id = Database::insert(array('table' => 'screen_names', 'hash' => $hash));
      if($id){
        $this->messages[] = "You have successfully added a screen name!";
      }
    }
  }

  # id, hash
  function delete_project_contact_address($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $project_contact = $this->get_project_contact(array('id' => $id));
    $sql = "
      UPDATE addresses
      SET address_display = '0'
      WHERE address_id = '$hash[address_id]'";
    mysql_query($sql) or die('Error: ' . mysql_error());
    $this->messages[] = "You have successfully deleted a project_contact address!";
  }

  # hash
  function delete_project_contact_email_address($args){
    $hash = $args['hash'];
    $sql = "
      UPDATE email_addresses
      SET email_display = '0'
      WHERE email_id = '$hash[email_id]'";
    mysql_query($sql) or die('Error: ' . mysql_error());
    $this->messages[] = "You have successfully deleted an email address!";
  }

  # id, hash
  function delete_project_contact_phone_number($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $project_contact = $this->get_project_contact(array('id' => $id));
    $sql = "
      UPDATE phone_numbers
      SET phone_display = '0'
      WHERE phone_id = '$hash[phone_id]'";
    mysql_query($sql) or die('Error: ' . mysql_error());
    $this->messages[] = "You have successfully deleted a project_contact phone number!";
    foreach($project_contact[phone_numbers] as $k => $v){
      if($v[phone_id] == $hash[phone_id]){
        $project_contact[phone_numbers][$k][phone_display] = '0';
      }
    }
    return $project_contact;
  }

  # id, hash
  function delete_project_contact_screen_name($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $project_contact = $this->get_project_contact(array('id' => $id));
    $sql = "
      UPDATE screen_names
      SET screen_display = '0'
      WHERE screen_id = '$hash[screen_id]'";
    mysql_query($sql) or die('Error: ' . mysql_error());
    $this->messages[] = "You have successfully deleted a screen name!";
    foreach($project_contact[screen_names] as $k => $v){
      if($v[screen_id] == $hash[screen_id]){
        $project_contact[screen_names][$k][screen_display] = '0';
      }
    }
    return $project_contact;
  }

  # id
  function get_project_contact($args){
    $this->project_contact = array();
    $id = $args['id'];
    $sql = "
      SELECT pc.*
      FROM project_contacts pc
      WHERE project_contact_id = '$id'
      LIMIT 1";
    $results = mysql_query($sql);
    $r = mysql_fetch_assoc($results);
    if($r){
      $this->project_contact = $r;
    }
    return $this->project_contact;
  }

  # id
  function get_project_contact_addresses($args){
    $this->project_contact_addresses = array();
    $id = $args['id'];
    $sql = "
      SELECT a.*
      FROM addresses a
      WHERE address_project_contact_id = '$id' AND
            address_display = 1";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $items[] = $r;
    }
    if($items)
      $this->project_contact_addresses = $items;
    return $this->project_contact_addresses;
  }

  # id
  function get_project_contact_email_addresses($args){
    $this->project_contact_email_addresses = array();
    $id = $args['id'];
    $sql = "      
      SELECT e.*
      FROM email_addresses e
      WHERE email_project_contact_id = '$id' AND
            email_display = 1";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $items[] = $r;
    }
    if($items)
      $this->project_contact_email_addresses = $items;
    return $this->project_contact_email_addresses;
  }

  # id
  function get_project_contact_phone_numbers($args){
    $id = $args['id'];
    $sql = "
      SELECT pn.*
      FROM phone_numbers pn
      WHERE phone_project_contact_id = '$id' AND
            phone_display = 1";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $items[] = $r;
    } 
    if($items)
      $this->project_contact_phone_numbers = $items;
    return $this->project_contact_phone_numbers;
  }

  # id
  function get_project_contact_projects($args){
    $id = $args['id'];
    $sql = "
      SELECT pc.*,p.*
      FROM project_contacts pc
      LEFT JOIN projects p ON (pc.project_contact_project_id = p.project_id && p.project_display = '1')
      WHERE project_contact_contact_id = '$id' AND
            project_contact_display = 1";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $r['project_url'] = Common::get_url(array('bow' => $r['project_name'],
                                                'id' => 'PRJ'.$r['project_id']));
      $items[] = $r;
    }
    if($items)
      $this->project_contact_projects = $items;
    return $this->project_contact_projects;
  }

  # id
  function get_project_contact_screen_names($args){
    $id = $args['id'];
    $sql = "
      SELECT *
      FROM screen_names
      WHERE screen_project_contact_id = '$id'";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $items[] = $r;
    }
    if($items)
      $this->project_contact_screen_names = $items;
    return $this->project_contact_screen_names;
  }

  # hash
  function get_project_contacts($args){
    $hash = $args['hash'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $search_fields = "CONCAT_WS(' ',c.contact_first,c.contact_last,c.contact_organization,c.contact_notes,e.email_address,pn.phone_areacode,pn.phone_three,pn.phone_four)";
    #$search_fields = "CONCAT_WS(' ',c.project_contact_first,c.project_contact_last,c.project_contact_organization)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if($hash['t'])
      $if_type = "contact_type = '$hash[t]' AND ";
    if(!$hash['offset'])
      $hash['offset'] = 0;
    if(array_key_exists('project_contact_project_id', $hash))
      $if_project_contact_project_id = "project_contact_project_id = '$hash[project_contact_project_id]' AND ";
    $sql = "
      SELECT pc.*, 
             DATE_FORMAT(pc.project_contact_created, '%m/%e/%Y %l:%i%p')
               AS project_contact_created_formatted,
             c.*, CONCAT(contact_last,contact_first) AS contact_name,
             e.*,
             pn.*
      FROM contacts c
      LEFT JOIN project_contacts pc ON (c.contact_id = pc.project_contact_contact_id AND pc.project_contact_display = '1')
      LEFT JOIN email_addresses e ON (c.contact_id = e.email_contact_id AND e.email_display = '1')
      LEFT JOIN phone_numbers pn ON (c.contact_id = pn.phone_contact_id AND pn.phone_primary = '1' AND pn.phone_display = '1')
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_type 
            $if_project_contact_project_id
            contact_display = '1'
      ORDER BY contact_name ASC
      LIMIT $hash[offset],100";
      #LEFT OUTER JOIN addresses a ON (c.project_contact_id = a.address_project_contact_id)
      #LEFT OUTER JOIN screen_names sn ON (c.project_contact_id = sn.screen_project_contact_id)
    $result = mysql_query($sql);
    while ($r = mysql_fetch_assoc($result)){
      $r['contact_url'] = Common::get_url(array('bow' => $r['contact_first'].'-'.$r['contact_last'],
                                              'id' => 'CT'.$r['contact_id']));
      $items[] = $r;
    }
    if($items)
      $this->project_contacts = $items;
    return $this->project_contacts;
  }

  function get_project_contacts_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',c.project_contact_first,c.project_contact_last,c.project_contact_organization,a.address_address,a.address_city)";
    #$search_fields = "CONCAT_WS(' ',c.project_contact_first,c.project_contact_last,c.project_contact_organization)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if($hash['t'])
      $if_type = "project_contact_type = '$hash[t]' AND ";
    $sql = "
      SELECT count(c.project_contact_id)
      FROM project_contacts c
      LEFT JOIN addresses a ON (c.project_contact_id = a.address_project_contact_id AND address_display = '1')
      LEFT JOIN phone_numbers pn ON (c.project_contact_id = pn.phone_project_contact_id AND phone_display = '1')
      WHERE $search_fields LIKE '%$hash[q]%' AND
            $if_type
            project_contact_display = '1'";
    $result = mysql_query($sql);
    $matched = mysql_fetch_row($result);
    return $matched[0];
  }

  # id, hash
  function update_project_contact($args){
    $id = $args['id'];
    $hash = $args['hash'];
    if(isset($hash['birthday_month'])){
      #$this->update_project_contact_birthday($args);
      $hash['project_contact_birth_date'] = sprintf("%04d-%02d-%02d", $hash['birthday_year'],$hash['birthday_month'],$hash['birthday_day']);
    }
    unset($hash['birthday_year']);
    unset($hash['birthday_month']);
    unset($hash['birthday_day']);
    $item = $this->get_project_contact(array('id' => $id));
    $where = "project_contact_id = '$id'";
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
    $sql = "UPDATE project_contacts SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    list($item['birthday_year'],$item['birthday_month'],$item['birthday_day']) = preg_split('/-/', $item['project_contact_birth_date']);
    return $item;
  }

  # id, hash
  function update_project_contact_address($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['address_primary'] = ($hash['address_primary'] == 'on' ? 1 : 0);
    $project_contact = $this->get_project_contact(array('id' => $id));
    foreach($project_contact['addresses'] as $i){
      if($i['address_id'] == $hash['address_id']){
        $item = $i;
      }
    }
    $where = "address_id = '$hash[address_id]'";
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
    $sql = "UPDATE addresses SET $update WHERE $where";
    #print $sql;
    foreach($project_contact['addresses'] as $k => $v){
      if($v['address_id'] == $hash[address_id]){
        $project_contact['addresses'][$k] = $item;
      }
    }
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $project_contact;
  }

  # id, hash
  function update_project_contact_birthday($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $item = $this->get_project_contact(array('id' => $id));
    $hash['birthday_date'] = sprintf("%04d-%02d-%02d", $hash['birthday_year'],$hash['birthday_month'],$hash['birthday_day']);
    unset($hash['birthday_year']);
    unset($hash['birthday_month']);
    unset($hash['birthday_day']);
    $update = NULL;
    $where = "birthday_first = '$hash[project_contact_first]' AND birthday_last = '$hash[project_contact_last]' ";
    foreach($hash as $k => $v){
      if($hash[$k] != $item[$k] && isset($item[$k]) && preg_match("/birthday_/",$k)){
        $new_value = mysql_real_escape_string($hash[$k]);
        $update .= "$k = '$new_value', ";
        $item[$k] = $hash[$k];
        $this->messages[] = "You have successfully updated the $k!";
      }
    }
    $where = rtrim($where, ' AND ');
    $update = rtrim($update, ', ');
    $sql = "UPDATE birthdays SET $update WHERE $where";
    #print $sql;
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    #return $project_contact;
  }

  # id, hash
  function update_project_contact_email_address($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['email_primary'] = ($hash['email_primary'] == 'on' ? 1 : 0);
    $start_date = ($args['start_date'] ? $args['start_date'] : date("Y-m-01"));
    unset($hash['email_update']);
    $project_contact = $this->get_project_contact(array('id' => $id));
    foreach($project_contact['email_addresses'] as $i){
      if($i['email_id'] == $hash['email_id']){
        $item = $i;
      }
    }
    $where = "email_id = '$hash[email_id]'";
    $update = NULL;
    foreach($hash as $k => $v){
      if($v != $item[$k] && isset($v)){
        $new_value = mysql_real_escape_string($v);
        $update .= "$k = '$new_value', ";
        $item[$k] = $v;
        $this->messages[] = "You have successfully updated the $k!";
      }
    }
    $where = rtrim($where, ' AND ');
    $update = rtrim($update, ', ');
    $sql = "UPDATE email_addresses SET $update WHERE $where";
    #print $sql;
    foreach($project_contact['email_addresses'] as $k => $v){
      if($v['email_id'] == $hash['email_id']){
        $project_contact['email_addresses'][$k] = $item;
      }
    }
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $project_contact;
  }


  # id, hash
  function update_project_contact_phone_number($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['phone_primary'] = ($hash['phone_primary'] == 'on' ? 1 : 0);
    unset($hash['phone_update']);
    $project_contact = $this->get_project_contact(array('id' => $id));
    foreach($project_contact['phone_numbers'] as $i){
      if($i['phone_id'] == $hash['phone_id']){
        $item = $i;
      }
    }
    $where = "phone_id = '$hash[phone_id]'";
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
    $sql = "UPDATE phone_numbers SET $update WHERE $where";
    foreach($project_contact['phone_numbers'] as $k => $v){
      if($v['phone_id'] == $hash['phone_id']){
        $project_contact['phone_numbers'][$k] = $item;
      }
    }
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $project_contact;
  }

}

?>
