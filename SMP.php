<?php

class SMP {
  var $books = array();
  var $book_pages = array();
  var $messages = array();
  var $ranges = array();
  var $sections = array();
  var $smp = array();
  var $smps = array();
  var $townships = array();
  var $s = 'smp_number';
  var $d = 'ASC';

  # user, hash
  function add_smp($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $hash['PlanAddedBy'] = $args['user']['user_name'];
    $hash['WhenAddedDate'] = date("Y-m-d H:i:s");
    if(!$hash['smp_number']){
      $this->messages[] = "You did not enter in an MP #!";
    } else {
      $id = Database::insert(array('table' => 'smps', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added an smp!";
      return $id;
    }
  }

  # hash
  function get_books($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $search_fields = "CONCAT_WS(' ',smp.smp_book)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT smp_book
      FROM smps smp
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY smp_book
      ORDER BY smp_book ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->books = $items;
    return $this->books;
  }

  # hash
  function get_book_pages($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $search_fields = "CONCAT_WS(' ',smp.smp_page)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT smp_page
      FROM smps smp
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY smp_page
      ORDER BY smp_page ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->book_pages = $items;
    return $this->book_pages;
  }

  # hash
  function get_ranges($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $search_fields = "CONCAT_WS(' ',smp.smp_range)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT smp_range
      FROM smps smp
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY smp_range
      ORDER BY smp_range ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->ranges = $items;
    return $this->ranges;
  }

  # hash
  function get_sections($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $search_fields = "CONCAT_WS(' ',smp.smp_section)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT smp_section
      FROM smps smp
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY smp_section
      ORDER BY smp_section ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->sections = $items;
    return $this->sections;
  }

  # id
  function get_smp($args){
    $id = $args['id'];
    if(preg_match('/^\d+$/',$id)){
      $where = "WHERE smp_id = '$id' ";
    } else {
      $where = "WHERE smp_number = '$id' ";
    }
    $sql = "
      SELECT smp.*
      FROM smps smp
      $where AND
        smp_display = '1'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['smp_url'] = Common::get_url(array('bow' => $r['smp_description'],
                                            'id' => 'SMP'.$r['smp_id']));
      $r['smp_viewer_url'] = Common::get_url(array('bow' => $r['smp_description'],
                                                   'id' => 'SMPV'.$r['smp_id']));
      #/images/Plan_Lib/2007/GR/GR-2007-146/GR-2007-146_001.tif
      #$r['files'] = array('file1.tif');
      #$path = $_SERVER['DOCUMENT_ROOT'].'maps-and-records/webroot/images/Plan_Lib/2013/H/H-2013-001/';
      #webroot/images/Plan_Lib/MP/02/MP-02-041/MP-02-041_001.tif
      preg_match("/^(\w+)-/i",$r['smp_number'],$matches);
      $tp = ($matches[1] ? $matches[1] : '');
      preg_match("/\w+-(\d+)-/i",$r['smp_number'],$matches);
      $yr = ($matches[1] ? $matches[1] : '0000');
      $path = '/apps/maps-and-records/webroot/images/Plan_Lib/'.$tp.'/'.$yr.'/'.$r['smp_number'].'/';
      #print "path:".$path;
      if($handle = opendir($_SERVER['DOCUMENT_ROOT'].$path)){
        while (false !== ($filename = readdir($handle))){
          if(preg_match("/(\.jpg|\.gif|\.tiff?|\.png)/i",$filename)){
            $files[] = array('filename' => $filename,
                             'url' => $path.$filename);
          }
        }
      }
      sort($files);
      $r['files'] = $files;
      #$r['files'] = array($path);
      $this->smp = $r;
    }
    return $this->smp;
  }

  # hash
  function get_smps($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = (isset($hash['d']) ? $hash['d']:$this->d);
    $this->s = (isset($hash['s']) ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',s.smp_number,s.smp_description,s.smp_plan_number)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $if_book = '';
    if(isset($hash['b']) && $hash['b'] != "")
      $if_book = "smp_book = '$hash[b]' AND ";
    $if_book_page = '';
    if(isset($hash['bp']) && $hash['bp'] != "")
      $if_book_page = "smp_page = '$hash[bp]' AND ";
    $if_range = '';
    if(isset($hash['r']) && $hash['r'] != "")
      $if_range = "smp_range = '$hash[r]' AND ";
    $if_section = '';
    if(isset($hash['sec']) && $hash['sec'] != "")
      $if_section = "smp_section = '$hash[sec]' AND ";
    $if_township = '';
    if(isset($hash['t']) && $hash['t'] != "")
      $if_township = "smp_township = '$hash[t]' AND ";
    $sql = "
      SELECT s.*
      FROM smps s
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_book
            $if_book_page
            $if_range
            $if_section
            $if_township
            smp_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['smp_url'] = Common::get_url(array('bow' => $r['smp_description'],
                                            'id' => 'SMP'.$r['smp_id']));
      $smps[] = $r;
    }
    if($smps)
      $this->smps = $smps;
    $this->d = Common::direction_switch($this->d);
    return $this->smps;
  }

  # hash
  function get_smps_count($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $search_fields = "CONCAT_WS(' ',s.smp_number,s.smp_description,s.smp_plan_number)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_book = '';
    if(isset($hash['b']) && $hash['b'] != "")
      $if_book = "smp_book = '$hash[b]' AND ";
    $if_book_page = '';
    if(isset($hash['bp']) && $hash['bp'] != "")
      $if_book_page = "smp_page = '$hash[bp]' AND ";
    $if_range = '';
    if(isset($hash['r']) && $hash['r'] != "")
      $if_range = "smp_range = '$hash[r]' AND ";
    $if_section = '';
    if(isset($hash['sec']) && $hash['sec'] != "")
      $if_section = "smp_section = '$hash[sec]' AND ";
    $if_township = '';
    if(isset($hash['t']) && $hash['t'] != "")
      $if_township = "smp_township = '$hash[t]' AND ";
    $sql = "
      SELECT count(s.smp_id)
      FROM smps s
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_book
            $if_book_page
            $if_range
            $if_section
            $if_township
            smp_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function get_townships($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $search_fields = "CONCAT_WS(' ',smp.smp_township)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $sql = "
      SELECT DISTINCT smp_township
      FROM smps smp
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY smp_township
      ORDER BY smp_township ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->townships = $items;
    return $this->townships;
  }

  # id, hash
  function update_smp($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['smp_due_date'] = date('Y-m-d',strtotime($hash['smp_due_date']));
    $item = $this->get_smp(array('id' => $id));
    $where = "smp_id = '$id'";
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
    $sql = "UPDATE smps SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
