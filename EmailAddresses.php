<?php

class EmailAddresses {
  var $messages = array();
  var $email_address = array();
  var $email_addresses = array();
  var $email_address_categories = array();
  var $email_address_departments = array();
  var $email_address_department_divisions = array();
  var $email_address_issues = array();
  var $email_address_types = array();
  var $s = 'email_address_id';
  var $d = 'DESC';

  # user, hash
  function add_email_address($args){
    $hash = $args['hash'];
    #$hash['ProjectPicture'] = preg_replace("/\s/",'_',$hash['ProjectPicture']);
    #$destination = $args['location'].$hash['ProjectPicture'];
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $hash['email_address_user_name'] = $user_name;
    if(!$hash['email_address_title']){
      $this->messages[] = "You did not enter in an Activity Title!";
    }elseif(!$hash['email_address_content']){
      $this->messages[] = "You did not enter in any content!";
    } else {
      $upload_success = 0;
      $hash['tmp_name'] = (isset($hash['tmp_name']) ? $hash['tmp_name'] : '');
      if($hash['tmp_name']){
        if(move_uploaded_file($hash['tmp_name'], $destination)){
          $upload_success = 1;
        }else{
          $this->messages[] = "We could not upload the file at this time!";
        }
      }
      if($upload_success || !$hash['tmp_name']){
        unset($hash['tmp_name']);
        $id = Database::insert(array('table' => 'email_addresses', 'hash' => $hash));
        if($id){
          $this->messages[] = "You have successfully added an email_address!";
          return $id;
        }
      }
    }
  }

  # id
  function get_email_address($args){
    $id = $args['id'];
    $this->email_address = array();
    $sql = "
      SELECT e.*,c.*,
             DATE_FORMAT(e.email_created, '%b %e, %Y %l:%i%p')
               AS email_created_formatted
      FROM email_addresses e
      LEFT JOIN contacts c ON (c.contact_id = e.email_contact_id)
      WHERE email_id = '$id' AND
            email_display = '1'
      LIMIT 1";
    $results = mysql_query($sql);
    $r = mysql_fetch_assoc($results);
    if($r){
      $this->email_address = $r;
    }
    return $this->email_address;
  }

  # hash
  function get_email_addresses($args){
    $items = array();
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = ((isset($hash['d']) && $hash['d']) ? $hash['d'] : $this->d);
    $this->s = ((isset($hash['s']) && $hash['s']) ? $hash['s'] : $this->s);
    $search_fields = "CONCAT_WS(' ',an.email_address_title)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "20");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset]" : "LIMIT 0");
    $offset = ($ipp ? "$offset, $ipp" : "");
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "email_address_instance_id = '$hash[c]' AND ":'');
    $sql = "
      SELECT an.*,
             DATE_FORMAT(an.email_address_created, '%c/%e/%Y %l:%i%p')
               AS email_address_created_formatted
      FROM email_addresses an
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            email_address_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['email_address_url'] = Common::get_url(array('bow' => $r['email_address_title'],
                                                 'id' => 'A'.$r['email_address_id']));
      $items[] = $r;
    }
    if($items)
      $this->email_addresses = $items;
    $this->d = Common::direction_switch($this->d);
    return $this->email_addresses;
  }

  # hash
  function get_email_addresses_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',t.email_address_title)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "email_address_department = '$hash[c]' AND ":'');
    $sql = "
      SELECT count(t.email_address_id)
      FROM email_addresses t
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            email_address_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function list_email_address_departments($args){
    $this->email_address_departments = array(
      'Budget & Internal Audit Programs',
      'City Attorney',
      'City Clerk',
      'City Court',
      "City Manager's Office",
      'Code Enforcement',
      'Environmental Services',
      'Finance',
      'General Services',
      'Golf',
      'Housing & Community Development',
      'Human Resources',
      'Information Technology',
      'Office of Equal Opportunity Programs',
      'Office of Intergrated Planning',
      'Park Tucson',
      'Parks & Recreation',
      'Planning & Development Services',
      'Police Audit',
      'Procurement',
      'Real Estate',
      'Streets',
      'SunTrans',
      'Transportation',
      'Tucson Convention Center',
      'Tucson Fire',
      'Tucson Police',
      'Tucson Water',
      'Zoning');
    return $this->email_address_departments;
  }

  # hash
  function list_email_address_department_divisions($args){
    $this->email_address_department_divisions = array(
      'Billing',
      'Marketing',
      'Personnel',
      'Maintenance');
    return $this->email_address_department_divisions;
  }

  # hash
  #function list_email_address_categories($args){
  #  $sql = "
  #    SELECT DISTINCT email_address_status
  #    FROM email_addresses
  #    WHERE
  #          email_address_display = '1'
  #    ORDER BY email_address_status ASC";
  #  $results = mysql_query($sql);
  #  while($r = mysql_fetch_row($results)){
  #    $items[] = $r[0];
  #  }
  #  if($items)
  #    $this->email_address_categories = $items;
  #  return $this->email_address_categories;
  #}

  function list_email_address_issues($args){
    $this->email_address_issues = array(
      'City Provided Services',
      'Customer Service',
      'Discontinuation of Service',
      'Employee Misconduct',
      'Fiscal',
      'Infrastructure',
      'Non-City',
      'Other',
      'Policy',
    );
    return $this->email_address_issues;
  }

  # id, hash
  function update_email_address($args){
    $id = $args['id'];
    $hash = $args['hash'];
    #$hash['email_address_due_date'] = date('Y-m-d',strtotime($hash['email_address_due_date']));
    $item = $this->get_email_address(array('id' => $id));
    $where = "email_address_id = '$id'";
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
    $sql = "UPDATE email_addresses SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
