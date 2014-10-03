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
    $hash = $args['hash'];
    $hash['smp_due_date'] = date('Y-m-d',strtotime($hash['smp_due_date']));
    if(!$hash['smp_customer_id']){
      $this->messages[] = "You did not enter in a customer!";
    } else {
      $id = database::insert(array('table' => 'smps', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added an smp!";
      return $id;
    }
  }

  # hash
  function get_books($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',smp.smp_book)";
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
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',smp.smp_page)";
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
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',smp.smp_range)";
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
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',smp.smp_section)";
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
    $user_name = ($args['user_name'] ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT smp.*
      FROM smps smp
      WHERE smp_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['smp_url'] = Common::get_url(array('bow' => $r['smp_description'],
                                            'id' => 'SMP'.$r['smp_id']));
      #/images/Plan_Lib/2007/GR/GR-2007-146/GR-2007-146_001.tif
      #$r['files'] = array('file1.tif');
      #$path = $_SERVER['DOCUMENT_ROOT'].'maps-and-records/webroot/images/Plan_Lib/2013/H/H-2013-001/';
      preg_match("/^(\w+)-/i",$r['smp_number'],$matches);
      $tp = ($matches[1] ? $matches[1] : '');
      preg_match("/\w+-(\d+)-/i",$r['smp_number'],$matches);
      $yr = ($matches[1] ? $matches[1] : '0000');
      $path = '/maps-and-records/webroot/images/Plan_Lib/'.$tp.'/'.$yr.'/'.$r['smp_number'].'/';
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
      $this->smp = $r;
    }
    return $this->smp;
  }

  # hash
  function get_smps($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    $search_fields = "CONCAT_WS(' ',s.smp_number,s.smp_description,s.smp_plan_number)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['b']) && $hash['b'] != "")
      $if_book = "smp_book = '$hash[b]' AND ";
    if(isset($hash['bp']) && $hash['bp'] != "")
      $if_book_page = "smp_page = '$hash[bp]' AND ";
    if(isset($hash['r']) && $hash['r'] != "")
      $if_range = "smp_range = '$hash[r]' AND ";
    if(isset($hash['sec']) && $hash['sec'] != "")
      $if_section = "smp_section = '$hash[sec]' AND ";
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
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',s.smp_number,s.smp_description,s.smp_plan_number)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['b']) && $hash['b'] != "")
      $if_book = "smp_book = '$hash[b]' AND ";
    if(isset($hash['bp']) && $hash['bp'] != "")
      $if_book_page = "smp_page = '$hash[bp]' AND ";
    if(isset($hash['r']) && $hash['r'] != "")
      $if_range = "smp_range = '$hash[r]' AND ";
    if(isset($hash['sec']) && $hash['sec'] != "")
      $if_section = "smp_section = '$hash[sec]' AND ";
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
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',smp.smp_township)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
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