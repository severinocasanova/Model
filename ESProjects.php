<?php

class ESProjects {
  var $messages = array();
  var $esproject = array();
  var $esprojects = array();
  var $esproject_categories = array();
  var $esproject_types = array();
  var $s = 'CIPName';
  var $d = 'ASC';

  # user, hash
  function add_esproject($args){
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
  function get_next_esproject($args){
    $esproject_type = $args['esproject_type'].'-'.date("Y").'-';
    $sql = "
      SELECT p.esproject_number
      FROM esprojects p
      WHERE esproject_number LIKE '$esproject_type%'
      ORDER BY esproject_number DESC
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $last_number = preg_replace("/".$args['esproject_type']."-".date("Y")."-0*/","",$r['esproject_number']);
      $new_last_number = sprintf("%03d",$last_number+1);
      $esproject_number = $args['esproject_type'].'-'.date("Y").'-'.$new_last_number;
    }
    if(!$last_number){
      $esproject_number = $args['esproject_type'].'-'.date("Y").'-001';
    }
    return $esproject_number;
  }

  # id
  function get_esproject($args){
    $id = $args['id'];
    if(preg_match("/^\d+$/",$id)){
      $where = "WHERE esproject_id = '$id' ";
    }else{
      $where = "WHERE CIPID = '$id' ";
    }
    $sql = "
      SELECT p.*,
             DATE_FORMAT(p.UpdateDate, '%W %b %e, %Y')
              AS UpdateDate_formatted
      FROM PWCIP p
      $where
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['esproject_url'] = Common::get_url(array('bow' => $r['CIPName'],
                                                  'id' => 'PRJ'.$r['esproject_id']));
      #$r['Activities'] = "";
      #
      # why did they do this!!!
      #if($r['ProjDesignations'] == 0){$r['Activities'] = 'None, ';}
      #if(substr(strrev(decbin($r['ProjDesignations'])),0,1) == 1){$r['Activities'] .= 'Inspections, ';}
      #if(substr(strrev(decbin($r['ProjDesignations'])),1,1) == 1){$r['Activities'] .= 'Landfill Gas Monitoring, ';}
      #if(substr(strrev(decbin($r['ProjDesignations'])),2,1) == 1){$r['Activities'] .= 'Groundwater Monitoring, ';}
      #if(substr(strrev(decbin($r['ProjDesignations'])),3,1) == 1){$r['Activities'] .= 'Landfill Gas Control System, ';}
      #if(substr(strrev(decbin($r['ProjDesignations'])),4,1) == 1){$r['Activities'] .= 'Landfill Gas Remediation (soil vapor extraction), ';}
      #if(substr(strrev(decbin($r['ProjDesignations'])),5,1) == 1){$r['Activities'] .= 'Groundwater Remediation, ';}
      #if(substr(strrev(decbin($r['ProjDesignations'])),6,1) == 1){$r['Activities'] .= 'Soil Remediation, ';}
      #$r['Activities'] = rtrim($r['Activities'], ', ');
      $r['Activities'] = str_split(strrev(sprintf("%0".count($this->list_esproject_activities(array()))."d",decbin($r['ProjDesignations']))));
      foreach($this->list_esproject_activities(array()) as $k => $v){
        if($r['Activities'][$k] == 1){
          $array[] = $v;
        }
      }
      $r['ActivitiesString'] = implode(", ",$array);
#<cfset strDesignation = "">
#<cfscript>
#if (ProjDesignations EQ 0) { strDesignation = "None, "; }
#if (BitMaskRead(ProjDesignations,0,1) EQ 1){ strDesignation = "Inspections, "; }
#if (BitMaskRead(ProjDesignations,1,1) EQ 1){ strDesignation = strDesignation & "Landfill Gas Monitoring, "; }
#if (BitMaskRead(ProjDesignations,2,1) EQ 1){ strDesignation = strDesignation & "Groundwater Monitoring, "; }
#if (BitMaskRead(ProjDesignations,3,1) EQ 1){ strDesignation = strDesignation & "Landfill Gas Control System, "; }
#if (BitMaskRead(ProjDesignations,4,1) EQ 1){ strDesignation = strDesignation & "Landfill Gas Remediation (soil vapor extraction), "; }
#if (BitMaskRead(ProjDesignations,5,1) EQ 1){ strDesignation = strDesignation & "Groundwater Remediation, "; }
#if (BitMaskRead(ProjDesignations,6,1) EQ 1){ strDesignation = strDesignation & "Soil Remediation, "; }
#if(Len(strDesignation) GT 2) strDesignation = Left(strDesignation, Len(strDesignation)-2);
#</cfscript>
#strDesignation#
      $this->esproject = $r;
    }
    return $this->esproject;
  }

  # hash
  function get_esprojects($args){
    $hash = $args['hash'];
    $this->d = (isset($hash['d']) ? $hash['d']:$this->d);
    $this->s = (isset($hash['s']) ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',p.CIPName,p.Description,p.Location)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $if_scanned = (isset($hash['c']) && $hash['c'] != '' ? "Status = '$hash[c]' AND ":'');
    $if_type = (isset($hash['t']) && $hash['t'] != '' ? "Type LIKE '$hash[t]' AND ":'');
    $sql = "
      SELECT p.*,
             DATE_FORMAT(p.UpdateDate, '%c/%e/%Y')
               AS UpdateDate_formatted2
      FROM PWCIP p
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_scanned
            $if_type
            esproject_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['esproject_url'] = Common::get_url(array('bow' => $r['CIPName'],
                                                  'id' => 'PRJ'.$r['esproject_id']));
      $esprojects[] = $r;
    }
    if($esprojects)
      $this->esprojects = $esprojects;
    $this->d = Common::direction_switch($this->d);
    return $this->esprojects;
  }

  # hash
  function get_esprojects_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',p.CIPName,p.Description,p.Location)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
#    if(isset($hash['c']) && $hash['c'] != "")
#      $if_scanned = "Status = '$hash[c]' AND ";
#    if(isset($hash['t']) && $hash['t'] != "")
#      $if_type = "Type LIKE '$hash[t]' AND ";
    $if_scanned = (isset($hash['c']) && $hash['c'] != '' ? "Status = '$hash[c]' AND ":'');
    $if_type = (isset($hash['t']) && $hash['t'] != '' ? "Type LIKE '$hash[t]' AND ":'');
    $sql = "
      SELECT count(p.CIPID)
      FROM PWCIP p
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_scanned
            $if_type
            ProjectDeleted = '0'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function list_esproject_activities($args){
    $this->esproject_activities =  array(
      'Inspections',
      'Landfill Gas Monitoring',
      'Groundwater Monitoring',
      'Landfill Gas Control System',
      'Landfill Gas Remediation (soil vapor extraction)',
      'Groundwater Remediation',
      'Soil Remediation');
    return $this->esproject_activities;
  }

  # hash
  function list_esproject_categories($args){
    $sql = "
      SELECT DISTINCT Status
      FROM PWCIP
      WHERE
            esproject_display = '1'
      ORDER BY Status ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->esproject_categories = $items;
    return $this->esproject_categories;
  }

  # hash
  function list_esproject_types($args){
    $sql = "
      SELECT DISTINCT Type
      FROM PWCIP
      WHERE
            esproject_display = '1'
      ORDER BY Type ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_row($results)){
      $items[] = $r[0];
    }
    if($items)
      $this->esproject_types = $items;
    return $this->esproject_types;
  }

  # id, hash
  function update_esproject($args){
    $id = $args['id'];
    $hash = $args['hash'];
    #$hash['esproject_due_date'] = date('Y-m-d',strtotime($hash['esproject_due_date']));
    $item = $this->get_esproject(array('id' => $id));
    $where = "esproject_id = '$id'";
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
