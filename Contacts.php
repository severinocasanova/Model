<?php

class Contacts {
  var $messages = array();
  var $contact = array();
  var $contacts = array();
  var $contact_types = array();
  var $s = 'contact_priority';
  var $d = 'ASC';

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

  # id
  function get_contact($args){
    $id = $args['id'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT p.*,
             DATE_FORMAT(p.contact_created, '%m/%e/%Y %l:%i%p')
               AS contact_created_formatted,
             DATE_FORMAT(p.contact_due_date, '%c/%e/%Y')
               AS contact_due_date_formatted2
      FROM contacts p
      WHERE contact_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['contact_url'] = Common::get_url(array('bow' => $r['contact_name'],
                                                'id' => 'PRJ'.$r['contact_id']));
      $this->contact = $r;
    }
    return $this->contact;
  }

  # hash
  function get_contacts($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    $search_fields = "CONCAT_WS(' ',p.contact_name)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_category = "contact_category = '$hash[c]' AND ";
    if(isset($hash['o']) && $hash['o'] != "")
      $if_owner = "contact_owner = '$hash[o]' AND ";
    if(!empty($hash['t'])){
      if($hash['t'] == 'Open'){
        $if_status = "contact_status != 'Closed' AND ";
      } else {
        $if_status = "contact_status = '$hash[t]' AND ";
      }
    }
    if(array_key_exists('contact_customer_id', $hash))
      $if_customer_id = "contact_customer_id = '$hash[contact_customer_id]' AND ";
    $sql = "
      SELECT p.*,
             DATE_FORMAT(p.contact_created, '%m/%e/%Y %l:%i%p')
               AS contact_created_formatted,
             DATE_FORMAT(p.contact_due_date, '%c/%e/%Y')
               AS contact_due_date_formatted2
      FROM contacts p
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_owner
            $if_status
            contact_display = '1'
      ORDER BY $this->s $this->d";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['contact_url'] = Common::get_url(array('bow' => $r['contact_name'],
                                                'id' => 'PRJ'.$r['contact_id']));
      $r['days_before'] = ceil((strtotime($r['contact_due_date']) - time()) / 86400);
      $contacts[] = $r;
    }
    if($contacts)
      $this->contacts = $contacts;
    $this->d = Common::direction_switch($this->d);
    return $this->contacts;
  }

  # hash
  function get_contacts_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',p.PlanNum,p.Description)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['c']) && $hash['c'] != "")
      $if_category = "contact_category = '$hash[c]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_type = "PlanNum LIKE '$hash[t]-%' AND ";
    if(array_key_exists('contact_customer_id', $hash))
      $if_customer_id = "contact_customer_id = '$hash[contact_customer_id]' AND ";
    $sql = "
      SELECT count(p.RecID)
      FROM PlansTable p
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_type
            $if_customer_id
            RecID IS NOT NULL";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function get_contact_types($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',pt.type_name)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "Scanned = '$hash[c]' AND ";
    if(array_key_exists('contact_customer_id', $hash))
      $if_customer_id = "contact_customer_id = '$hash[contact_customer_id]' AND ";
    $sql = "
      SELECT pt.*
      FROM contact_types pt
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            type_display = '1'
      ORDER BY type_abbreviation ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->contact_types = $items;
    return $this->contact_types;
  }

  function list_contact_categories($args){
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT DISTINCT contact_category
      FROM contacts
      WHERE 
            contact_display = 1
      ORDER BY contact_category ASC";
            #transaction_date > '$last_year_date' AND
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $categories[] = $r[0];
    }
    return $categories;
  }

  function list_contact_owners($args){
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT DISTINCT contact_owner
      FROM contacts
      WHERE
            contact_display = 1
      ORDER BY contact_owner ASC";
            #transaction_date > '$last_year_date' AND
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    return $items;
  }

  # id, hash
  function update_contact($args){
    $id = $args['id'];
    $hash = $args['hash'];
    if(!empty($hash['contact_due_date'])){
      $hash['contact_due_date'] = date('Y-m-d',strtotime($hash['contact_due_date']));
    }else{
      $hash['contact_due_date'] = NULL;
    }
    $item = $this->get_contact(array('id' => $id));
    $where = "contact_id = '$id'";
    $update = NULL;
    foreach($hash as $k => $v){
      if($v != $item[$k] && isset($item[$k])){
        $new_value = mysql_real_escape_string($v);
        $update .= (is_null($v) ? "$k = NULL," : "$k = '$new_value', ");
        $item[$k] = $v;
        $this->messages[] = "You have successfully updated the $k!";
      }
    }
    $where = rtrim($where, ' AND ');
    $update = rtrim($update, ', ');
    $sql = "UPDATE contacts SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
