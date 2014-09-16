<?php

class PSS {
  var $books = array();
  var $book_pages = array();
  var $messages = array();
  var $ranges = array();
  var $sections = array();
  var $pss = array();
  var $psss = array();
  var $townships = array();
  var $s = 'PSImageID';
  var $d = 'ASC';

  # user, hash
  function pssd_pss($args){
    $hash = $args['hash'];
    $hash['pss_due_date'] = date('Y-m-d',strtotime($hash['pss_due_date']));
    if(!$hash['pss_customer_id']){
      $this->messages[] = "You did not enter in a customer!";
    } else {
      $id = database::insert(array('table' => 'psss', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully pssded an pss!";
      return $id;
    }
  }

  # hash
  function get_ranges($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',pss.Rng)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT Rng
      FROM PSSTable pss
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY Rng
      ORDER BY Rng ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $itepss[] = $r;
    }
    if($itepss)
      $this->ranges = $itepss;
    return $this->ranges;
  }

  # hash
  function get_sections($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',pss.Sec)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT Sec
      FROM PSSTable pss
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY Sec
      ORDER BY Sec ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $itepss[] = $r;
    }
    if($itepss)
      $this->sections = $itepss;
    return $this->sections;
  }

  # id
  function get_pss($args){
    $id = $args['id'];
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT pss.*,
             pt.Description as PlanDescription,
             pt.RecID as PlanRecID,
             DATE_FORMAT(pss.PlanDate, '%c/%e/%Y')
               AS pss_plan_date_formatted
      FROM PSSTable pss
      LEFT JOIN PlansTable pt ON (pss.RefPlanNumbers = pt.PlanNum)
      WHERE pss.RecID = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['pss_url'] = Common::get_url(array('bow' => $r['Description'],
                                            'id' => 'PSS'.$r['RecID']));
      $r['plan_url'] = Common::get_url(array('bow' => $r['PlanDescription'],
                                             'id' => 'PL'.$r['PlanRecID']));
      #/images/Plan_Lib/2007/GR/GR-2007-146/GR-2007-146_001.tif
      #$r['files'] = array('file1.tif');
      #$path = $_SERVER['DOCUMENT_ROOT'].'maps-and-records/webroot/images/Plan_Lib/2013/H/H-2013-001/';
      preg_match("/^(\w+)-/i",$r['PlanNum'],$matches);
      $tp = ($matches[1] ? $matches[1] : '');
      preg_match("/\w+-(\d+)-/i",$r['PlanNum'],$matches);
      $yr = ($matches[1] ? $matches[1] : '0000');
      $path = '/maps-and-records/webroot/images/Plan_Lib/'.$yr.'/'.$tp.'/'.$r['PlanNum'].'/';
      if($handle = opendir($_SERVER['DOCUMENT_ROOT'].$path)){
        while (false !== ($filename = readdir($handle))){
          if(preg_match("/\.tiff?/i",$filename)){
            $files[] = array('filename' => $filename,
                             'url' => $path.$filename);
          }
        }
      }
      sort($files);
      $r['files'] = $files;
      #$r['files'] = array($path);
      $this->pss = $r;
    }
    return $this->pss;
  }

  # hash
  function get_psss($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    $search_fields = "CONCAT_WS(' ',pss.PSImageID,pss.Description,pss.TEPlanIDNum)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "PSSScanned = '$hash[c]' AND ";
    $sql = "
      SELECT pss.*, 
             pt.Description as PlanDescription,
             pt.RecID as PlanRecID,
             DATE_FORMAT(pss.PlanDate, '%c/%e/%Y')
               AS pss_plan_date_formatted
      FROM PSSTable pss
      LEFT JOIN PlansTable pt ON (pss.RefPlanNumbers = pt.PlanNum)
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_scanned
            pss.RecID IS NOT NULL
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['pss_url'] = Common::get_url(array('bow' => $r['Description'],
                                           'id' => 'PSS'.$r['RecID']));
      $r['plan_url'] = Common::get_url(array('bow' => $r['PlanDescription'],
                                             'id' => 'PL'.$r['PlanRecID']));
      $psss[] = $r;
    }
    if($psss)
      $this->psss = $psss;
    $this->d = Common::direction_switch($this->d);
    return $this->psss;
  }

  # hash
  function get_psss_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',pss.PSImageID,pss.Description,pss.TEPlanIDNum)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "PSSScanned = '$hash[c]' AND ";
    $sql = "
      SELECT count(pss.RecID)
      FROM PSSTable pss
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_scanned
            RecID IS NOT NULL";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # id, hash
  function update_pss($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['pss_due_date'] = date('Y-m-d',strtotime($hash['pss_due_date']));
    $item = $this->get_pss(array('id' => $id));
    $where = "pss_id = '$id'";
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
    $sql = "UPDATE psss SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
