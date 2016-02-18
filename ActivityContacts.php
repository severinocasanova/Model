<?php

class ActivityContacts {
  var $messages = array();
  var $activity_contact = array();
  var $activity_contact_addresses = array();
  var $activity_contact_email_addresses = array();
  var $activity_contact_phone_numbers = array();
  var $activity_contact_activities = array();
  var $activity_contacts = array();

  # user, hash
  function add_activity_contact($args){
    $hash = $args['hash'];
    $user_name = (isset($args['user_name']) && $args['user_name'] != '' ? $args['user_name'] : $args['user']['user_name']);
    $hash['activity_contact_user_name'] = $user_name;
    if(!$hash['activity_contact_contact_id']){
      $this->messages[] = "You did not select a contact!";
    } else {
      $id = Database::insert(array('table' => 'activity_contacts', 'hash' => $hash));
      return $id;
    }
  }

  # id, hash
  function add_activity_contact_address($args){
    $hash = $args['hash'];
    $hash['address_activity_contact_id'] = $args['id'];
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
  function add_activity_contact_email_address($args){
    $hash = $args['hash'];
    $hash['email_primary'] = ($hash['email_primary'] == 'on' ? 1 : 0);
    $hash['email_activity_contact_id'] = $args['id'];
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
  function add_activity_contact_phone_number($args){
    $hash = $args['hash'];
    $hash['phone_primary'] = ($hash['phone_primary'] == 'on' ? 1 : 0);
    $hash['phone_activity_contact_id'] = $args['id'];
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
  function delete_activity_contact_address($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $activity_contact = $this->get_activity_contact(array('id' => $id));
    $sql = "
      UPDATE addresses
      SET address_display = '0'
      WHERE address_id = '$hash[address_id]'";
    mysql_query($sql) or die('Error: ' . mysql_error());
    $this->messages[] = "You have successfully deleted a activity_contact address!";
  }

  # hash
  function delete_activity_contact_email_address($args){
    $hash = $args['hash'];
    $sql = "
      UPDATE email_addresses
      SET email_display = '0'
      WHERE email_id = '$hash[email_id]'";
    mysql_query($sql) or die('Error: ' . mysql_error());
    $this->messages[] = "You have successfully deleted an email address!";
  }

  # id, hash
  function delete_activity_contact_phone_number($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $activity_contact = $this->get_activity_contact(array('id' => $id));
    $sql = "
      UPDATE phone_numbers
      SET phone_display = '0'
      WHERE phone_id = '$hash[phone_id]'";
    mysql_query($sql) or die('Error: ' . mysql_error());
    $this->messages[] = "You have successfully deleted a activity_contact phone number!";
    foreach($activity_contact[phone_numbers] as $k => $v){
      if($v[phone_id] == $hash[phone_id]){
        $activity_contact[phone_numbers][$k][phone_display] = '0';
      }
    }
    return $activity_contact;
  }

  # id
  function get_activity_contact($args){
    $this->activity_contact = array();
    $id = $args['id'];
    $sql = "
      SELECT pc.*
      FROM activity_contacts pc
      WHERE activity_contact_id = '$id'
      LIMIT 1";
    $results = mysql_query($sql);
    $r = mysql_fetch_assoc($results);
    if($r){
      $this->activity_contact = $r;
    }
    return $this->activity_contact;
  }

  # id
  function get_activity_contact_addresses($args){
    $this->activity_contact_addresses = array();
    $id = $args['id'];
    $sql = "
      SELECT a.*
      FROM addresses a
      WHERE address_activity_contact_id = '$id' AND
            address_display = 1";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $items[] = $r;
    }
    if($items)
      $this->activity_contact_addresses = $items;
    return $this->activity_contact_addresses;
  }

  # id
  function get_activity_contact_email_addresses($args){
    $this->activity_contact_email_addresses = array();
    $id = $args['id'];
    $sql = "      
      SELECT e.*
      FROM email_addresses e
      WHERE email_activity_contact_id = '$id' AND
            email_display = 1";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $items[] = $r;
    }
    if($items)
      $this->activity_contact_email_addresses = $items;
    return $this->activity_contact_email_addresses;
  }

  # id
  function get_activity_contact_phone_numbers($args){
    $id = $args['id'];
    $sql = "
      SELECT pn.*
      FROM phone_numbers pn
      WHERE phone_activity_contact_id = '$id' AND
            phone_display = 1";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $items[] = $r;
    } 
    if($items)
      $this->activity_contact_phone_numbers = $items;
    return $this->activity_contact_phone_numbers;
  }

  # id
  function get_activity_contact_activities($args){
    $id = $args['id'];
    $sql = "
      SELECT ac.*,a.*
      FROM activity_contacts ac
      LEFT JOIN activities a ON (ac.activity_contact_activity_id = a.activity_id && a.activity_display = '1')
      WHERE activity_contact_contact_id = '$id' AND
            activity_contact_display = 1";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $r['activity_url'] = Common::get_url(array('bow' => $r['activity_title'],
                                                 'id' => 'A'.$r['activity_id']));
      $items[] = $r;
    }
    if(isset($items))
      $this->activity_contact_activities = $items;
    return $this->activity_contact_activities;
  }

  # hash
  function get_activity_contacts($args){
    $hash = $args['hash'];
    $args['user']['user_name'] = (isset($args['user']['user_name']) ? $args['user']['user_name'] : NULL);
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $search_fields = "CONCAT_WS(' ',c.contact_first,c.contact_last,c.contact_organization,c.contact_notes,e.email_address,pn.phone_areacode,pn.phone_three,pn.phone_four)";
    #$search_fields = "CONCAT_WS(' ',c.activity_contact_first,c.activity_contact_last,c.activity_contact_organization)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : NULL);
    $offset = (isset($args['offset']) ? "LIMIT $args[offset]" : "LIMIT 0");
    $offset = ($ipp ? "$offset, $ipp" : "");
    $if_type = ((isset($hash['t']) && $hash['t'] != "") ? "contact_type = '$hash[t]' AND ":'');
    if(array_key_exists('activity_contact_activity_id', $hash))
      $if_activity_contact_activity_id = "activity_contact_activity_id = '$hash[activity_contact_activity_id]' AND ";
    $sql = "
      SELECT ac.*, 
             DATE_FORMAT(ac.activity_contact_created, '%m/%e/%Y %l:%i%p')
               AS activity_contact_created_formatted,
             c.*, CONCAT(contact_last,contact_first) AS contact_name,
             e.*,
             pn.*
      FROM contacts c
      LEFT JOIN activity_contacts ac ON (c.contact_id = ac.activity_contact_contact_id AND ac.activity_contact_display = '1')
      LEFT JOIN email_addresses e ON (c.contact_id = e.email_contact_id AND e.email_display = '1')
      LEFT JOIN phone_numbers pn ON (c.contact_id = pn.phone_contact_id AND pn.phone_primary = '1' AND pn.phone_display = '1')
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_type 
            $if_activity_contact_activity_id
            contact_display = '1'
      ORDER BY contact_name ASC
      $offset";
      #LEFT OUTER JOIN addresses a ON (c.activity_contact_id = a.address_activity_contact_id)
    $result = mysql_query($sql);
    while ($r = mysql_fetch_assoc($result)){
      $r['contact_url'] = Common::get_url(array('bow' => $r['contact_first'].'-'.$r['contact_last'],
                                              'id' => 'CT'.$r['contact_id']));
      $items[] = $r;
    }
    if(isset($items))
      $this->activity_contacts = $items;
    return $this->activity_contacts;
  }

  function get_activity_contacts_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',ac.activity_contact_first,ac.activity_contact_last,ac.activity_contact_organization,a.address_address,a.address_city)";
    #$search_fields = "CONCAT_WS(' ',c.activity_contact_first,c.activity_contact_last,c.activity_contact_organization)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if($hash['t'])
      $if_type = "activity_contact_type = '$hash[t]' AND ";
    $sql = "
      SELECT count(ac.activity_contact_id)
      FROM activity_contacts ac
      LEFT JOIN addresses a ON (ac.activity_contact_id = a.address_activity_contact_id AND address_display = '1')
      LEFT JOIN phone_numbers pn ON (ac.activity_contact_id = pn.phone_activity_contact_id AND phone_display = '1')
      WHERE $search_fields LIKE '%$hash[q]%' AND
            $if_type
            activity_contact_display = '1'";
    $result = mysql_query($sql);
    $matched = mysql_fetch_row($result);
    return $matched[0];
  }

  # id, hash
  function update_activity_contact($args){
    $id = $args['id'];
    $hash = $args['hash'];
    if(isset($hash['birthday_month'])){
      #$this->update_activity_contact_birthday($args);
      $hash['activity_contact_birth_date'] = sprintf("%04d-%02d-%02d", $hash['birthday_year'],$hash['birthday_month'],$hash['birthday_day']);
    }
    unset($hash['birthday_year']);
    unset($hash['birthday_month']);
    unset($hash['birthday_day']);
    $item = $this->get_activity_contact(array('id' => $id));
    $where = "activity_contact_id = '$id'";
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
    $sql = "UPDATE activity_contacts SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    if(isset($item['contact_birth_date'])){
      list($item['birthday_year'],$item['birthday_month'],$item['birthday_day']) = preg_split('/-/', $item['contact_birth_date']);
    }
    return $item;
  }

  # id, hash
  function update_activity_contact_address($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['address_primary'] = ($hash['address_primary'] == 'on' ? 1 : 0);
    $activity_contact = $this->get_activity_contact(array('id' => $id));
    foreach($activity_contact['addresses'] as $i){
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
    foreach($activity_contact['addresses'] as $k => $v){
      if($v['address_id'] == $hash[address_id]){
        $activity_contact['addresses'][$k] = $item;
      }
    }
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $activity_contact;
  }

  # id, hash
  function update_activity_contact_birthday($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $item = $this->get_activity_contact(array('id' => $id));
    $hash['birthday_date'] = sprintf("%04d-%02d-%02d", $hash['birthday_year'],$hash['birthday_month'],$hash['birthday_day']);
    unset($hash['birthday_year']);
    unset($hash['birthday_month']);
    unset($hash['birthday_day']);
    $update = NULL;
    $where = "birthday_first = '$hash[activity_contact_first]' AND birthday_last = '$hash[activity_contact_last]' ";
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
    #return $activity_contact;
  }

  # id, hash
  function update_activity_contact_email_address($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['email_primary'] = ($hash['email_primary'] == 'on' ? 1 : 0);
    $start_date = ($args['start_date'] ? $args['start_date'] : date("Y-m-01"));
    unset($hash['email_update']);
    $activity_contact = $this->get_activity_contact(array('id' => $id));
    foreach($activity_contact['email_addresses'] as $i){
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
    foreach($activity_contact['email_addresses'] as $k => $v){
      if($v['email_id'] == $hash['email_id']){
        $activity_contact['email_addresses'][$k] = $item;
      }
    }
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $activity_contact;
  }


  # id, hash
  function update_activity_contact_phone_number($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['phone_primary'] = ($hash['phone_primary'] == 'on' ? 1 : 0);
    unset($hash['phone_update']);
    $activity_contact = $this->get_activity_contact(array('id' => $id));
    foreach($activity_contact['phone_numbers'] as $i){
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
    foreach($activity_contact['phone_numbers'] as $k => $v){
      if($v['phone_id'] == $hash['phone_id']){
        $activity_contact['phone_numbers'][$k] = $item;
      }
    }
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $activity_contact;
  }

}

?>
