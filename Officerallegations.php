<?php

class Officerallegations {
  var $messages = array();
  var $allegations = array();
  var $dispositions = array();
  var $officer_allegation = array();
  var $officer_allegations = array();
  var $officer_allegation_categories = array();
  var $officer_allegation_disciplines = array();
  var $officer_allegation_issues = array();
  var $officer_allegation_types = array();
  var $s = 'officer_allegation_id';
  var $d = 'DESC';

  # user, hash
  function add_officer_allegation($args){
    $hash = $args['hash'];
    if(!$hash['officer_allegation_allegation_code']){
      $this->messages[] = "You did not enter in an allegation code!";
    } else {
      $id = Database::insert(array('table' => 'officer_allegations', 'hash' => $hash));
      if($id){
        $this->messages[] = "You have successfully added an officer allegation!";
        return $id;
      }
    }
  }

  # hash
  function get_allegations($args){
    $this->allegations = array();
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = ((isset($hash['d']) && $hash['d'] != '') ? $hash['d']:'ASC');
    $this->s = ((isset($hash['s']) && $hash['s'] != '') ? $hash['s']:'allegation_code');
    $search_fields = "CONCAT_WS(' ',a.allegation_code)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset]" : "LIMIT 0");
    $offset = ($ipp ? "$offset, $ipp" : "");
    if(isset($hash['officer_allegation_complaint_id']))
      $hash['c'] = $hash['officer_allegation_complaint_id'];
    #if($hash['c'])
    #  $if_officer_allegation_complaint_officer_id = "officer_allegation_complaint_officer_id = '".$hash['c']."' AND ";
    $if_category = ((isset($hash['c']) && $hash['c'] != "") ? "officer_allegation_complaint_officer_id = '$hash[c]' AND ":'');
    $sql = "
      SELECT a.*
      FROM allegations a
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            allegation_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $allegations[] = $r;
    }
    if($allegations)
      $this->allegations = $allegations;
    $this->d = Common::direction_switch($this->d);
    return $this->allegations;
  }

  # hash
  function get_dispositions($args){
    $this->dispositions = array();
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = ((isset($hash['d']) && $hash['d'] != '') ? $hash['d']:'ASC');
    $this->s = ((isset($hash['s']) && $hash['s'] != '') ? $hash['s']:'disposition');
    $search_fields = "CONCAT_WS(' ',d.disposition)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset]" : "LIMIT 0");
    $offset = ($ipp ? "$offset, $ipp" : "");
    if(isset($hash['officer_disposition_complaint_id']))
      $hash['c'] = $hash['officer_disposition_complaint_id'];
    #if($hash['c'])
    #  $if_officer_disposition_complaint_officer_id = "officer_disposition_complaint_officer_id = '".$hash['c']."' AND ";
    $if_category = ((isset($hash['c']) && $hash['c'] != "") ? "officer_disposition_complaint_officer_id = '$hash[c]' AND ":'');
    $sql = "
      SELECT d.*
      FROM dispositions d
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_category
            disposition_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $dispositions[] = $r;
    }
    if($dispositions)
      $this->dispositions = $dispositions;
    $this->d = Common::direction_switch($this->d);
    return $this->dispositions;
  }

  # id
  function get_officer_allegation($args){
    $id = $args['id'];
    $sql = "
      SELECT c.*
      FROM officer_allegations c
      WHERE officer_allegation_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['officerallegation_url'] = Common::get_url(array('bow' => $r['officer_allegation_disposition'],
                                             'id' => 'COMP'.$r['officer_allegation_id']));
      $this->officer_allegation = $r;
    }
    return $this->officer_allegation;
  }

  # hash
  function get_officer_allegations($args){
    $this->officer_allegations = array();
    $hash = $args['hash'];
    $this->d = ((isset($hash['d']) && $hash['d'] != '') ? $hash['d']:$this->d);
    $this->s = ((isset($hash['s']) && $hash['s'] != '') ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',oa.officer_allegation_disposition,co.complaint_officer_name,co.complaint_officer_payroll)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $limit = (isset($args['offset']) ? 'LIMIT '.$args['offset'].',' : 'LIMIT 0,');
    $limit = (isset($args['ipp']) ? $limit.$args['ipp'] : '');
    #$if_category = ((isset($hash['c']) && $hash['c'] != "") ? "project_category = '$hash[c]' AND ":'');
    
    if(isset($hash['officer_allegation_complaint_id']))
      $hash['c'] = $hash['officer_allegation_complaint_id'];
    $if_officer_allegation_complaint_officer_id = ((isset($hash['c']) && $hash['c'] != "") ? "officer_allegation_complaint_officer_id = '".$hash['c']."' AND ":'');
    $sql = "
      SELECT oa.*,a.*,co.*
      FROM officer_allegations oa
      LEFT JOIN allegations a ON (oa.officer_allegation_allegation_code = a.allegation_code)
      LEFT JOIN complaint_officers co ON (oa.officer_allegation_complaint_officer_id = co.complaint_officer_id)
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_officer_allegation_complaint_officer_id
            officer_allegation_display = '1'
      ORDER BY $this->s $this->d
      $limit";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $officer_allegations[] = $r;
    }
    if($officer_allegations)
      $this->officer_allegations = $officer_allegations;
    $this->d = Common::direction_switch($this->d);
    return $this->officer_allegations;
  }

  # hash
  function get_officer_allegations_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',oa.officer_allegation_disposition,co.complaint_officer_name,co.complaint_officer_payroll)";
    $this->d = ((isset($hash['d']) && $hash['d'] != '') ? $hash['d']:$this->d);
    $this->s = ((isset($hash['s']) && $hash['s'] != '') ? $hash['s']:$this->s);
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['officer_allegation_complaint_id']))
      $hash['c'] = $hash['officer_allegation_complaint_id'];
    $if_officer_allegation_complaint_officer_id = ((isset($hash['c']) && $hash['c'] != "") ? "officer_allegation_complaint_officer_id = '".$hash['c']."' AND ":'');
    $sql = "
      SELECT count(oa.officer_allegation_id)
      FROM officer_allegations oa
      LEFT JOIN allegations a ON (oa.officer_allegation_allegation_code = a.allegation_code)
      LEFT JOIN complaint_officers co ON (oa.officer_allegation_complaint_officer_id = co.complaint_officer_id)
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_officer_allegation_complaint_officer_id
            officer_allegation_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  #function list_officer_allegation_disciplines($args){
  #  $this->officer_allegation_disciplines = array(
  #    'Corrective Action',
  #    'N/A',
  #    'Resign in Lieu of Term',
  #    'Suspension',
  #    'Termination',
  #    'Written Reprimand');
  #  return $this->officer_allegation_disciplines;
  #}

  # hash
  function list_officerallegation_categories($args){
    $sql = "
      SELECT DISTINCT Status
      FROM PWCIP
      WHERE
            officerallegation_display = '1'
      ORDER BY Status ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->officerallegation_categories = $items;
    return $this->officerallegation_categories;
  }

  # hash
  function list_officerallegation_types($args){
    $sql = "
      SELECT DISTINCT Type
      FROM PWCIP
      WHERE
            officerallegation_display = '1'
      ORDER BY Type ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->officerallegation_types = $items;
    return $this->officerallegation_types;
  }

  # id, hash
  function update_officer_allegation($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $item = $this->get_officer_allegation(array('id' => $id));
    $where = "officer_allegation_id = '$id'";
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
    $sql = "UPDATE officer_allegations SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
