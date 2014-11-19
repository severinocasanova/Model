<?php

class Submissions {
  var $messages = array();
  var $submission = array();
  var $submissions = array();
  var $submission_categories = array();
  var $submission_types = array();
  var $s = 'submitted';
  var $d = 'DESC';

  # user, hash
  function add_submission($args){
    $hash = $args['hash'];
    $hash['AddIP'] = $_SERVER['REMOTE_ADDR'];
    $hash['ProjectPicture'] = preg_replace("/\s/",'_',$hash['ProjectPicture']);
    $destination = $args['location'].$hash['ProjectPicture'];
    if(!$hash['CIPName']){
      $this->messages[] = "You did not enter in a Project Name!";
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
        $id = Database::insert(array('table' => 'PWCIP', 'hash' => $hash));
        if($id){
          $this->messages[] = "You have successfully added a project!";
          return $id;
        }
      }
    }
  }

  # id
  function get_submission($args){
    $id = $args['id'];
    if(preg_match("/^\d+$/",$id)){
      $where = "WHERE s.sid = '$id' ";
    }else{
      $where = "WHERE CIPID = '$id' ";
    }
    $sql = "
      SELECT s.*, sd.*, GROUP_CONCAT(sd.data SEPARATOR '##,##') AS data_all,
             FROM_UNIXTIME(s.submitted, '%M %e, %Y %l:%i %p')
               AS submitted_formatted
      FROM webform_submissions s
      LEFT JOIN webform_submitted_data sd ON (s.sid = sd.sid)
      $where
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['submission_url'] = Common::get_url(array('bow' => 'detail',
                                                   'id' => 'SID'.$r['sid']));
      $this->submission = $r;
    }
    return $this->submission;
  }

  # hash
  function get_submissions($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    $search_fields = "CONCAT_WS(' ',s.sid,sd.data)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
#    if(isset($hash['c']) && $hash['c'] != "")
#      $if_scanned = "Status = '$hash[c]' AND ";
#    if(isset($hash['t']) && $hash['t'] != "")
#      $if_type = "Type LIKE '$hash[t]' AND ";
    $sql = "
      SELECT s.*, sd.*, GROUP_CONCAT(sd.data SEPARATOR '##,##') AS data_all,
             FROM_UNIXTIME(s.submitted, '%M %e, %Y %l:%i %p')
               AS submitted_formatted
      FROM webform_submissions s
      LEFT JOIN webform_submitted_data sd ON (s.sid = sd.sid)
      WHERE ($search_fields LIKE '%$hash[q]%' OR (select GROUP_CONCAT(data) from webform_submitted_data where sid = s.sid) LIKE '%$hash[q]%') AND
            s.nid = '1335' AND
            s.sid != '0'
      GROUP BY s.sid
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['submission_url'] = Common::get_url(array('bow' => 'detail',
                                                   'id' => 'SID'.$r['sid']));
      $submissions[] = $r;
    }
    if($submissions)
      $this->submissions = $submissions;
    $this->d = Common::direction_switch($this->d);
    return $this->submissions;
  }

  # hash
  function get_submissions_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',s.sid,sd.data)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    #if(isset($hash['c']) && $hash['c'] != "")
    #  $if_scanned = "Status = '$hash[c]' AND ";
    #if(isset($hash['t']) && $hash['t'] != "")
    #  $if_type = "Type LIKE '$hash[t]' AND ";
    $sql = "
      SELECT count(distinct s.sid)
      FROM webform_submissions s
      LEFT JOIN webform_submitted_data sd ON (s.sid = sd.sid)
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            s.nid = '1335' AND
            s.sid != '0'";
      #GROUP BY s.sid";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function list_submission_fields($args){
    $this->submission_fields =  array(
      'Email Address',
      'Last Name',
      'Payroll/Badge #',
      'Attorney',
      'Phone Number',
      'Address',
      'TPD #',
      'Docket #',
      'Defendant',
      'Requests',
      'Comments');
    return $this->submission_fields;
  }

  # hash
  function list_submission_categories($args){
    $sql = "
      SELECT DISTINCT Status
      FROM PWCIP
      WHERE
            submission_display = '1'
      ORDER BY Status ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->submission_categories = $items;
    return $this->submission_categories;
  }

  # hash
  function list_submission_types($args){
    $sql = "
      SELECT DISTINCT Type
      FROM PWCIP
      WHERE
            submission_display = '1'
      ORDER BY Type ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->submission_types = $items;
    return $this->submission_types;
  }

  # id, hash
  function update_submission($args){
    $id = $args['id'];
    $hash = $args['hash'];
    #$hash['submission_due_date'] = date('Y-m-d',strtotime($hash['submission_due_date']));
    $item = $this->get_submission(array('id' => $id));
    $where = "submission_id = '$id'";
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
    $sql = "UPDATE PWCIP SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
