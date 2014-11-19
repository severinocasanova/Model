<?php

class RS {
  var $books = array();
  var $book_pages = array();
  var $messages = array();
  var $ranges = array();
  var $sections = array();
  var $rs = array();
  var $rss = array();
  var $townships = array();
  var $s = 'rs_number';
  var $d = 'ASC';

  # user, hash
  function add_rs($args){
    $hash = $args['hash'];
    if(!$hash['rs_number']){
      $this->messages[] = "You did not enter in an RS #!";
    } else {
      $id = Database::insert(array('table' => 'rss', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added an rs!";
      return $id;
    }
  }

  # hash
  function get_books($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',rs.rs_book)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT rs_book
      FROM rss rs
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY rs_book
      ORDER BY rs_book ASC";
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
    $search_fields = "CONCAT_WS(' ',rs.rs_page)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT rs_page
      FROM rss rs
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY rs_page
      ORDER BY rs_page ASC";
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
    $search_fields = "CONCAT_WS(' ',rs.rs_range)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT rs_range
      FROM rss rs
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY rs_range
      ORDER BY rs_range ASC";
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
    $search_fields = "CONCAT_WS(' ',rs.rs_section)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT rs_section
      FROM rss rs
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY rs_section
      ORDER BY rs_section ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->sections = $items;
    return $this->sections;
  }

  # id
  function get_rs($args){
    $id = $args['id'];
    if(preg_match('/^\d+$/',$id)){
      $where = "WHERE rs_id = '$id' ";
    } else {
      $where = "WHERE rs_number = '$id' ";
    }
    $sql = "
      SELECT rs.*
      FROM rss rs
      $where AND
        rs_display = '1'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['rs_url'] = Common::get_url(array('bow' => $r['rs_description'],
                                            'id' => 'RS'.$r['rs_id']));
      $r['rs_viewer_url'] = Common::get_url(array('bow' => $r['rs_description'],
                                            'id' => 'RSV'.$r['rs_id']));
      #/images/Plan_Lib/2007/GR/GR-2007-146/GR-2007-146_001.tif
      #$r['files'] = array('file1.tif');
      #$path = $_SERVER['DOCUMENT_ROOT'].'maps-and-records/webroot/images/Plan_Lib/2013/H/H-2013-001/';
      preg_match("/^(\w+)-/i",$r['rs_number'],$matches);
      $tp = ($matches[1] ? $matches[1] : '');
      preg_match("/\w+-(\d+)-/i",$r['rs_number'],$matches);
      $yr = ($matches[1] ? $matches[1] : '0000');
      $path = '/maps-and-records/webroot/images/Plan_Lib/RS/'.$yr.'/'.$r['rs_number'].'/';
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
      $this->rs = $r;
    }
    return $this->rs;
  }

  # hash
  function get_rss($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    $search_fields = "CONCAT_WS(' ',rs.rs_number,rs.rs_description,rs.rs_plan_number)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['b']) && $hash['b'] != "")
      $if_book = "rs_book = '$hash[b]' AND ";
    if(isset($hash['bp']) && $hash['bp'] != "")
      $if_book_page = "rs_page = '$hash[bp]' AND ";
    if(isset($hash['r']) && $hash['r'] != "")
      $if_range = "rs_range = '$hash[r]' AND ";
    if(isset($hash['sec']) && $hash['sec'] != "")
      $if_section = "rs_section = '$hash[sec]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_township = "rs_township = '$hash[t]' AND ";
    $sql = "
      SELECT rs.*
      FROM rss rs
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_book
            $if_book_page
            $if_range
            $if_section
            $if_township
            rs_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['rs_url'] = Common::get_url(array('bow' => $r['rs_description'],
                                           'id' => 'RS'.$r['rs_id']));
      $rss[] = $r;
    }
    if($rss)
      $this->rss = $rss;
    $this->d = Common::direction_switch($this->d);
    return $this->rss;
  }

  # hash
  function get_rss_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',rs.rs_number,rs.rs_description,rs.rs_plan_number)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['b']) && $hash['b'] != "")
      $if_book = "rs_book = '$hash[b]' AND ";
    if(isset($hash['bp']) && $hash['bp'] != "")
      $if_book_page = "rs_page = '$hash[bp]' AND ";
    if(isset($hash['r']) && $hash['r'] != "")
      $if_range = "rs_range = '$hash[r]' AND ";
    if(isset($hash['sec']) && $hash['sec'] != "")
      $if_section = "rs_section = '$hash[sec]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_township = "rs_township = '$hash[t]' AND ";
    $sql = "
      SELECT count(rs.rs_id)
      FROM rss rs
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_book
            $if_book_page
            $if_range
            $if_section
            $if_township
            rs_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function get_townships($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',rs.rs_township)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "Scanned = '$hash[c]' AND ";
    if(array_key_exists('plan_customer_id', $hash))
      $if_customer_id = "plan_customer_id = '$hash[plan_customer_id]' AND ";
    $sql = "
      SELECT DISTINCT rs_township
      FROM rss rs
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY rs_township
      ORDER BY rs_township ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->townships = $items;
    return $this->townships;
  }

  # id, hash
  function update_rs($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['rs_due_date'] = date('Y-m-d',strtotime($hash['rs_due_date']));
    $item = $this->get_rs(array('id' => $id));
    $where = "rs_id = '$id'";
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
    $sql = "UPDATE rss SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
