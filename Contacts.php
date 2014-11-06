<?php

class Contacts {
  var $messages = array();
  var $contact = array();
  var $contact_addresses = array();
  var $contact_email_addresses = array();
  var $contact_phone_numbers = array();
  var $contact_screen_names = array();
  var $contacts = array();
  var $d = 'ASC';
  var $s = 'contact_name';

  # user, hash
  function add_contact($args){
    $hash = $args['hash'];
    $hash['contact_created'] = date("Y-m-d H:i:s");
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $hash['contact_user_name'] = $user_name;
    if(isset($hash['birthday_month'])){
      $hash['contact_birth_date'] = sprintf("%04d-%02d-%02d", $hash['birthday_year'],$hash['birthday_month'],$hash['birthday_day']);
    }
    unset($hash['birthday_month']);
    unset($hash['birthday_day']);
    unset($hash['birthday_year']);
    if(!$hash['contact_first']){
      $this->messages[] = "You did not enter in a first name!";
    } else {
      $id = Database::insert(array('table' => 'contacts', 'hash' => $hash));
      return $id;
    }
  }

  # id, hash
  function add_contact_address($args){
    $hash = $args['hash'];
    $hash['address_contact_id'] = $args['id'];
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
  function add_contact_email_address($args){
    $hash = $args['hash'];
    $hash['email_primary'] = ($hash['email_primary'] == 'on' ? 1 : 0);
    $hash['email_contact_id'] = $args['id'];
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
  function add_contact_phone_number($args){
    $hash = $args['hash'];
    $hash['phone_primary'] = ($hash['phone_primary'] == 'on' ? 1 : 0);
    $hash['phone_contact_id'] = $args['id'];
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
  function add_contact_screen_name($args){
    $hash = $args['hash'];
    $hash[screen_contact_id] = $args['id'];
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
  function delete_contact_address($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $contact = $this->get_contact(array('id' => $id));
    $sql = "
      UPDATE addresses
      SET address_display = '0'
      WHERE address_id = '$hash[address_id]'";
    mysql_query($sql) or die('Error: ' . mysql_error());
    $this->messages[] = "You have successfully deleted a contact address!";
  }

  # hash
  function delete_contact_email_address($args){
    $hash = $args['hash'];
    $sql = "
      UPDATE email_addresses
      SET email_display = '0'
      WHERE email_id = '$hash[email_id]'";
    mysql_query($sql) or die('Error: ' . mysql_error());
    $this->messages[] = "You have successfully deleted an email address!";
  }

  # id, hash
  function delete_contact_phone_number($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $contact = $this->get_contact(array('id' => $id));
    $sql = "
      UPDATE phone_numbers
      SET phone_display = '0'
      WHERE phone_id = '$hash[phone_id]'";
    mysql_query($sql) or die('Error: ' . mysql_error());
    $this->messages[] = "You have successfully deleted a contact phone number!";
    foreach($contact[phone_numbers] as $k => $v){
      if($v[phone_id] == $hash[phone_id]){
        $contact[phone_numbers][$k][phone_display] = '0';
      }
    }
    return $contact;
  }

  # id, hash
  function delete_contact_screen_name($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $contact = $this->get_contact(array('id' => $id));
    $sql = "
      UPDATE screen_names
      SET screen_display = '0'
      WHERE screen_id = '$hash[screen_id]'";
    mysql_query($sql) or die('Error: ' . mysql_error());
    $this->messages[] = "You have successfully deleted a screen name!";
    foreach($contact[screen_names] as $k => $v){
      if($v[screen_id] == $hash[screen_id]){
        $contact[screen_names][$k][screen_display] = '0';
      }
    }
    return $contact;
  }

  # id
  function get_contact($args){
    $this->contact = array();
    $id = $args['id'];
    $sql = "
      SELECT c.*,
             DATE_FORMAT(c.contact_birth_date, '%b %e, %Y')
               AS contact_birth_date_formatted
      FROM contacts c
      WHERE contact_id = '$id'
      LIMIT 1";
    $results = mysql_query($sql);
    $r = mysql_fetch_assoc($results);
    if($r){
      $r['contact_url'] = Common::get_url(array('bow' => $r['contact_first'].'-'.$r['contact_last'],
                                                'id' => 'CT'.$r['contact_id']));
      $r['addresses'] = $this->get_contact_addresses(array('id' => $id));
      $r['phone_numbers'] = $this->get_contact_phone_numbers(array('id' => $id));
      $r['email_addresses'] = $this->get_contact_email_addresses(array('id' => $id));
      $r['screen_names'] = $this->get_contact_screen_names(array('id' => $id));
      list($r['birthday_year'],$r['birthday_month'],$r['birthday_day']) = preg_split('/-/', $r['contact_birth_date']);
      $this->contact = $r;
    }
    return $this->contact;
  }

  # id
  function get_contact_addresses($args){
    $this->contact_addresses = array();
    $id = $args['id'];
    $sql = "
      SELECT a.*
      FROM addresses a
      WHERE address_contact_id = '$id' AND
            address_display = 1";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $items[] = $r;
    }
    if($items)
      $this->contact_addresses = $items;
    return $this->contact_addresses;
  }

  # id
  function get_contact_email_addresses($args){
    $this->contact_email_addresses = array();
    $id = $args['id'];
    $sql = "      
      SELECT e.*
      FROM email_addresses e
      WHERE email_contact_id = '$id' AND
            email_display = 1";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $items[] = $r;
    }
    if($items)
      $this->contact_email_addresses = $items;
    return $this->contact_email_addresses;
  }

  # id
  function get_contact_phone_numbers($args){
    $id = $args['id'];
    $sql = "
      SELECT pn.*
      FROM phone_numbers pn
      WHERE phone_contact_id = '$id' AND
            phone_display = 1";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $items[] = $r;
    } 
    if($items)
      $this->contact_phone_numbers = $items;
    return $this->contact_phone_numbers;
  }

  # id
  function get_contact_screen_names($args){
    $id = $args['id'];
    $sql = "
      SELECT *
      FROM screen_names
      WHERE screen_contact_id = '$id'";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $items[] = $r;
    }
    if($items)
      $this->contact_screen_names = $items;
    return $this->contact_screen_names;
  }

  # hash
  function get_contacts($args){
    $hash = $args['hash'];
    $this->d = (isset($hash['d']) ? $hash['d']:$this->d);
    $this->s = (isset($hash['s']) ? $hash['s']:$this->s);
    $args['user']['user_name'] = (isset($args['user']['user_name']) ? $args['user']['user_name']:'');
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $search_fields = "CONCAT_WS(' ',c.contact_first,c.contact_last,c.contact_organization,c.contact_notes,a.address_address,a.address_city,pn.phone_areacode,pn.phone_three,pn.phone_four)";
    #$search_fields = "CONCAT_WS(' ',c.contact_first,c.contact_last,c.contact_organization)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_type = (isset($hash['t']) ? "contact_type = '$hash[t]' AND ":'');
    $hash['offset'] = (isset($hash['offset']) ? $hash['offset']:'0');
    $sql = "
      SELECT c.*, CONCAT(contact_last,contact_first) AS contact_name,
             a.*,
             e.*,
             pn.*
      FROM contacts c
      LEFT JOIN addresses a ON (c.contact_id = a.address_contact_id AND address_display = '1')
      LEFT JOIN email_addresses e ON (c.contact_id = e.email_contact_id AND email_display = '1')
      LEFT JOIN phone_numbers pn ON (c.contact_id = pn.phone_contact_id AND pn.phone_primary = '1' AND pn.phone_display = '1')
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_type 
            contact_display = '1'
      ORDER BY $this->s $this->d
      LIMIT $hash[offset],100";
            #contact_user_name = '$user_name' AND
      #LEFT OUTER JOIN addresses a ON (c.contact_id = a.address_contact_id)
      #LEFT OUTER JOIN screen_names sn ON (c.contact_id = sn.screen_contact_id)
    $result = mysql_query($sql);
    while ($r = mysql_fetch_assoc($result)){
      $r['contact_url'] = Common::get_url(array('bow' => $r['contact_first'].'-'.$r['contact_last'],
                                              'id' => 'CT'.$r['contact_id']));
      $contacts[] = $r;
    }
    if($contacts)
      $this->contacts = $contacts;
    $this->d = Common::direction_switch($this->d);
    return $this->contacts;
  }

  function get_contacts_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',c.contact_first,c.contact_last,c.contact_organization,a.address_address,a.address_city)";
    #$search_fields = "CONCAT_WS(' ',c.contact_first,c.contact_last,c.contact_organization)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if($hash['t'])
      $if_type = "contact_type = '$hash[t]' AND ";
    $sql = "
      SELECT count(c.contact_id)
      FROM contacts c
      LEFT JOIN addresses a ON (c.contact_id = a.address_contact_id AND address_display = '1')
      LEFT JOIN phone_numbers pn ON (c.contact_id = pn.phone_contact_id AND phone_display = '1')
      WHERE $search_fields LIKE '%$hash[q]%' AND
            $if_type
            contact_display = '1'";
    $result = mysql_query($sql);
    $matched = mysql_fetch_row($result);
    return $matched[0];
  }

  # id, hash
  function update_contact($args){
    $id = $args['id'];
    $hash = $args['hash'];
    if(isset($hash['birthday_month'])){
      #$this->update_contact_birthday($args);
      $hash['contact_birth_date'] = sprintf("%04d-%02d-%02d", $hash['birthday_year'],$hash['birthday_month'],$hash['birthday_day']);
    }
    unset($hash['birthday_year']);
    unset($hash['birthday_month']);
    unset($hash['birthday_day']);
    $item = $this->get_contact(array('id' => $id));
    $where = "contact_id = '$id'";
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
    $sql = "UPDATE contacts SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    list($item['birthday_year'],$item['birthday_month'],$item['birthday_day']) = preg_split('/-/', $item['contact_birth_date']);
    return $item;
  }

  # id, hash
  function update_contact_address($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['address_primary'] = ($hash['address_primary'] == 'on' ? 1 : 0);
    $contact = $this->get_contact(array('id' => $id));
    foreach($contact['addresses'] as $i){
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
    foreach($contact['addresses'] as $k => $v){
      if($v['address_id'] == $hash[address_id]){
        $contact['addresses'][$k] = $item;
      }
    }
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $contact;
  }

  # id, hash
  function update_contact_birthday($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $item = $this->get_contact(array('id' => $id));
    $hash['birthday_date'] = sprintf("%04d-%02d-%02d", $hash['birthday_year'],$hash['birthday_month'],$hash['birthday_day']);
    unset($hash['birthday_year']);
    unset($hash['birthday_month']);
    unset($hash['birthday_day']);
    $update = NULL;
    $where = "birthday_first = '$hash[contact_first]' AND birthday_last = '$hash[contact_last]' ";
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
    #return $contact;
  }

  # id, hash
  function update_contact_email_address($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['email_primary'] = ($hash['email_primary'] == 'on' ? 1 : 0);
    $start_date = ($args['start_date'] ? $args['start_date'] : date("Y-m-01"));
    unset($hash['email_update']);
    $contact = $this->get_contact(array('id' => $id));
    foreach($contact['email_addresses'] as $i){
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
    foreach($contact['email_addresses'] as $k => $v){
      if($v['email_id'] == $hash['email_id']){
        $contact['email_addresses'][$k] = $item;
      }
    }
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $contact;
  }


  # id, hash
  function update_contact_phone_number($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['phone_primary'] = ($hash['phone_primary'] == 'on' ? 1 : 0);
    unset($hash['phone_update']);
    $contact = $this->get_contact(array('id' => $id));
    foreach($contact['phone_numbers'] as $i){
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
    foreach($contact['phone_numbers'] as $k => $v){
      if($v['phone_id'] == $hash['phone_id']){
        $contact['phone_numbers'][$k] = $item;
      }
    }
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $contact;
  }

}

?>
