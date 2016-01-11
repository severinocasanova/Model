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
  var $s = 'pss_number';
  var $d = 'DESC';

  # user, hash
  function add_pss($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    if(!$hash['pss_direction']){
      $this->messages[] = "You did not enter in a direction!";
    } else {
      $id = Database::insert(array('table' => 'psss', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added a pss record!";
      return $id;
    }
  }

  # id
  function get_next_plan($args){
    $pss_plan_type = $args['pss_plan_type'].'-';
    $sql = "
      SELECT pss.pss_number
      FROM psss pss
      WHERE pss_number LIKE '$pss_plan_type%'
      ORDER BY pss_number DESC
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $last_number = preg_replace("/".$args['pss_plan_type']."-0*/","",$r['pss_number']);
      $new_last_number = sprintf("%04d",$last_number+1);
      $pss_number = $args['pss_plan_type'].'-'.$new_last_number;
    }
    if(!$last_number){
      $pss_number = 'ERROR:'.date(time());
    }
    return $pss_number;
  }

  # hash
  function get_ranges($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $search_fields = "CONCAT_WS(' ',pss.Rng)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT Rng
      FROM psss pss
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
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $search_fields = "CONCAT_WS(' ',pss.Sec)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT Sec
      FROM psss pss
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
    #$user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    if(preg_match('/^\d+$/',$id)){
      $where = "WHERE pss.pss_id = '$id' ";
    } else {
      $where = "WHERE pss.pss_number = '$id' ";
    }
    $sql = "
      SELECT pss.*, p.plan_id, p.plan_description,
             DATE_FORMAT(pss.pss_plan_date, '%c/%e/%Y')
               AS pss_plan_date_formatted2
      FROM psss pss
      LEFT JOIN plans p ON (pss.pss_RefPlanNumbers = p.plan_number)
      $where AND
        pss.pss_display = '1'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['pss_url'] = Common::get_url(array('bow' => $r['pss_description'],
                                            'id' => 'PSS'.$r['pss_id']));
      $r['pss_viewer_url'] = Common::get_url(array('bow' => $r['pss_description'],
                                            'id' => 'PSSV'.$r['pss_id']));
      $r['plan_url'] = Common::get_url(array('bow' => $r['plan_description'],
                                             'id' => 'PL'.$r['plan_id']));
      #/images/Plan_Lib/2007/GR/GR-2007-146/GR-2007-146_001.tif
      #$r['files'] = array('file1.tif');
      #$path = $_SERVER['DOCUMENT_ROOT'].'maps-and-records/webroot/images/Plan_Lib/2013/H/H-2013-001/';
      preg_match("/^(\w+)-/i",$r['pss_number'],$matches);
      $tp = ($matches[1] ? $matches[1] : '');
      preg_match("/\w+-(\d+)-/i",$r['pss_number'],$matches);
      $yr = (isset($matches[1]) ? $matches[1] : '0000');
      $path = '/apps/maps-and-records/webroot/images/Plan_Lib/PSImages/'.$r['pss_number'].'/';
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
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = (isset($hash['d']) ? $hash['d']:$this->d);
    $this->s = (isset($hash['s']) ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',pss.pss_number,pss.pss_description,pss.pss_TEPlanIDNum)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $if_scanned = '';
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "pss_scanned = '$hash[c]' AND ";
    $sql = "
      SELECT pss.*, 
             p.plan_description,
             p.plan_id,
             DATE_FORMAT(pss.pss_plan_date, '%c/%e/%Y')
               AS pss_plan_date_formatted
      FROM psss pss
      LEFT JOIN plans p ON (pss.pss_RefPlanNumbers = p.plan_number)
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_scanned
            pss_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['pss_url'] = Common::get_url(array('bow' => $r['pss_description'],
                                           'id' => 'PSS'.$r['pss_id']));
      if($r['plan_id']){
        $r['plan_url'] = Common::get_url(array('bow' => $r['plan_description'],
                                               'id' => 'PL'.$r['plan_id']));
      }
      $psss[] = $r;
    }
    if($psss)
      $this->psss = $psss;
    $this->d = Common::direction_switch($this->d);
    return $this->psss;
  }

  # hash
  function get_psss_count($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $search_fields = "CONCAT_WS(' ',pss.pss_number,pss.pss_description,pss.pss_TEPlanIDNum)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_scanned = '';
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "PSSScanned = '$hash[c]' AND ";
    $sql = "
      SELECT count(pss.pss_id)
      FROM psss pss
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_scanned
            pss_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # id, hash
  function update_pss($args){
    $id = $args['id'];
    $hash = (isset($args['hash']) ? $args['hash']:'');
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
