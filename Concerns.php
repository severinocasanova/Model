<?php

class Concerns {
  var $messages = array();
  var $concern = array();
  var $concerns = array();
  var $concern_categories = array();
  var $concern_departments = array();
  var $concern_issues = array();
  var $concern_types = array();
  var $s = 'concern_id';
  var $d = 'DESC';

  # user, hash
  function add_concern($args){
    $hash = $args['hash'];
    #$hash['ProjectPicture'] = preg_replace("/\s/",'_',$hash['ProjectPicture']);
    #$destination = $args['location'].$hash['ProjectPicture'];
    if(!$hash['concern_constituent_name']){
      $this->messages[] = "You did not enter in a Constituent Name!";
    }elseif(!$hash['concern_department']){
      $this->messages[] = "You did not select a department!";
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
        $id = Database::insert(array('table' => 'concerns', 'hash' => $hash));
        if($id){
          $this->messages[] = "You have successfully added a constituent concern!";
          return $id;
        }
      }
    }
  }

  # id
  function get_concern($args){
    $id = $args['id'];
    if(preg_match("/^\d+$/",$id)){
      $where = "WHERE concern_id = '$id' ";
    }else{
      $where = "WHERE concern_id = '9999999999999' ";
    }
    $sql = "
      SELECT c.*,
             DATE_FORMAT(c.concern_start_date, '%m/%e/%Y %l:%i%p')
               AS concern_start_date_formatted,
             DATE_FORMAT(c.concern_start_date, '%c/%e/%Y')
              AS concern_start_date_formatted2
      FROM concerns c
      $where
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['concern_url'] = Common::get_url(array('bow' => $r['concern_constituent_name'],
                                                'id' => 'CNRN'.$r['concern_id']));
      $this->concern = $r;
    }
    return $this->concern;
  }

  # hash
  function get_concerns($args){
    $hash = $args['hash'];
    $this->d = ($hash['d'] ? $hash['d']:$this->d);
    $this->s = ($hash['s'] ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',c.concern_constituent_name,c.concern_issue,c.concern_department,c.concern_status)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "20");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset]" : "LIMIT 0");
    $offset = ($ipp ? "$offset, $ipp" : "");
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "concern_department = '$hash[c]' AND ":'');
    $if_type = (isset($hash['t']) && $hash['t'] != '' ? "Type LIKE '$hash[t]' AND ":'');
    $sql = "
      SELECT c.*,(select count(*) from documents where document_table_id = c.concern_id AND document_table = 'concerns' AND document_display = '1') as concern_document_count,
             DATE_FORMAT(c.concern_start_date, '%c/%e/%Y')
               AS concern_start_date_formatted2
      FROM concerns c
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_type
            concern_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['concern_url'] = Common::get_url(array('bow' => $r['concern_issue'],
                                                  'id' => 'CNRN'.$r['concern_id']));
      $concerns[] = $r;
    }
    if($concerns)
      $this->concerns = $concerns;
    $this->d = Common::direction_switch($this->d);
    return $this->concerns;
  }

  # hash
  function get_concerns_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',c.concern_constituent_name,c.concern_issue,c.concern_department,c.concern_status)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_category = (isset($hash['c']) && $hash['c'] != '' ? "concern_department = '$hash[c]' AND ":'');
    $if_type = (isset($hash['t']) && $hash['t'] != '' ? "Type LIKE '$hash[t]' AND ":'');
    $sql = "
      SELECT count(c.concern_id)
      FROM concerns c
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            $if_type
            concern_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function list_concern_departments($args){
    $this->concern_departments = array(
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
    return $this->concern_departments;
  }

  # hash
  function list_concern_categories($args){
    $sql = "
      SELECT DISTINCT Status
      FROM PWCIP
      WHERE
            concern_display = '1'
      ORDER BY Status ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->concern_categories = $items;
    return $this->concern_categories;
  }

  function list_concern_issues($args){
    $this->concern_issues = array(
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
    return $this->concern_issues;
  }

  # hash
  function list_concern_types($args){
    $sql = "
      SELECT DISTINCT Type
      FROM PWCIP
      WHERE
            concern_display = '1'
      ORDER BY Type ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->concern_types = $items;
    return $this->concern_types;
  }

  # id, hash
  function update_concern($args){
    $id = $args['id'];
    $hash = $args['hash'];
    #$hash['concern_due_date'] = date('Y-m-d',strtotime($hash['concern_due_date']));
    $item = $this->get_concern(array('id' => $id));
    $where = "concern_id = '$id'";
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
    $sql = "UPDATE concerns SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
