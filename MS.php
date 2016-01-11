<?php

class MS {
  var $books = array();
  var $book_pages = array();
  var $messages = array();
  var $ranges = array();
  var $sections = array();
  var $ms = array();
  var $mss = array();
  var $townships = array();
  var $s = 'ms_number';
  var $d = 'ASC';

  # user, hash
  function add_ms($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    if(!$hash['ms_number']){
      $this->messages[] = "You did not enter in an MS #!";
    } else {
      $id = database::insert(array('table' => 'mss', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added an ms!";
      return $id;
    }
  }

  # hash
  function get_books($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $search_fields = "CONCAT_WS(' ',ms.ms_book)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT ms_book
      FROM mss ms
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY ms_book
      ORDER BY ms_book ASC";
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
    $search_fields = "CONCAT_WS(' ',ms.ms_page)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT ms_page
      FROM mss ms
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY ms_page
      ORDER BY ms_page ASC";
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
    $search_fields = "CONCAT_WS(' ',ms.ms_range)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT ms_range
      FROM mss ms
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY ms_range
      ORDER BY ms_range ASC";
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
    $search_fields = "CONCAT_WS(' ',ms.ms_section)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT ms_section
      FROM mss ms
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY ms_section
      ORDER BY ms_section ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->sections = $items;
    return $this->sections;
  }

  # id
  function get_ms($args){
    $id = $args['id'];
    $sql = "
      SELECT ms.*
      FROM mss ms
      WHERE ms_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['ms_url'] = Common::get_url(array('bow' => $r['ms_description'],
                                            'id' => 'MS'.$r['ms_id']));
      $r['ms_viewer_url'] = Common::get_url(array('bow' => $r['ms_description'],
                                            'id' => 'MSV'.$r['ms_id']));
      #/images/Plan_Lib/2007/GR/GR-2007-146/GR-2007-146_001.tif
      #$r['files'] = array('file1.tif');
      #$path = $_SERVER['DOCUMENT_ROOT'].'maps-and-records/webroot/images/Plan_Lib/2013/H/H-2013-001/';
      preg_match("/^(\w+)-/i",$r['ms_number'],$matches);
      $tp = ($matches[1] ? $matches[1] : '');
      preg_match("/\w+-(\d+)-/i",$r['ms_number'],$matches);
      $yr = ($matches[1] ? $matches[1] : '0000');
      $path = '/apps/maps-and-records/webroot/images/Plan_Lib/MS/'.$yr.'/'.$r['ms_number'].'/';
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
      $this->ms = $r;
    }
    return $this->ms;
  }

  # hash
  function get_mss($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $this->d = (isset($hash['d']) ? $hash['d']:$this->d);
    $this->s = (isset($hash['s']) ? $hash['s']:$this->s);
    $search_fields = "CONCAT_WS(' ',ms.ms_number,ms.ms_description,ms.ms_plan_number)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $if_book = '';
    if(isset($hash['b']) && $hash['b'] != "")
      $if_book = "ms_book = '$hash[b]' AND ";
    $if_book_page = '';
    if(isset($hash['bp']) && $hash['bp'] != "")
      $if_book_page = "ms_page = '$hash[bp]' AND ";
    $if_range = '';
    if(isset($hash['r']) && $hash['r'] != "")
      $if_range = "ms_range = '$hash[r]' AND ";
    $if_section = '';
    if(isset($hash['sec']) && $hash['sec'] != "")
      $if_section = "ms_section = '$hash[sec]' AND ";
    $if_township = '';
    if(isset($hash['t']) && $hash['t'] != "")
      $if_township = "ms_township = '$hash[t]' AND ";
    $sql = "
      SELECT ms.*
      FROM mss ms
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_book
            $if_book_page
            $if_range
            $if_section
            $if_township
            ms_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['ms_url'] = Common::get_url(array('bow' => $r['ms_description'],
                                           'id' => 'MS'.$r['ms_id']));
      $mss[] = $r;
    }
    if($mss)
      $this->mss = $mss;
    $this->d = Common::direction_switch($this->d);
    return $this->mss;
  }

  # hash
  function get_mss_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',ms.ms_number,ms.ms_description,ms.ms_plan_number)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $if_book = '';
    if(isset($hash['b']) && $hash['b'] != "")
      $if_book = "ms_book = '$hash[b]' AND ";
    $if_book_page = '';
    if(isset($hash['bp']) && $hash['bp'] != "")
      $if_book_page = "ms_page = '$hash[bp]' AND ";
    $if_range = '';
    if(isset($hash['r']) && $hash['r'] != "")
      $if_range = "ms_range = '$hash[r]' AND ";
    $if_section = '';
    if(isset($hash['sec']) && $hash['sec'] != "")
      $if_section = "ms_section = '$hash[sec]' AND ";
    $if_township = '';
    if(isset($hash['t']) && $hash['t'] != "")
      $if_township = "ms_township = '$hash[t]' AND ";
    $sql = "
      SELECT count(ms.ms_id)
      FROM mss ms
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_book
            $if_book_page
            $if_range
            $if_section
            $if_township
            ms_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function get_townships($args){
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $search_fields = "CONCAT_WS(' ',ms.ms_township)";
    $hash['q'] = (isset($hash['q']) ? $hash['q']:'');
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['c']) && $hash['c'] != "")
      $if_scanned = "Scanned = '$hash[c]' AND ";
    $sql = "
      SELECT DISTINCT ms_township
      FROM mss ms
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY ms_township
      ORDER BY ms_township ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->townships = $items;
    return $this->townships;
  }

  # id, hash
  function update_ms($args){
    $id = $args['id'];
    $hash = (isset($args['hash']) ? $args['hash']:'');
    $hash['ms_due_date'] = date('Y-m-d',strtotime($hash['ms_due_date']));
    $item = $this->get_ms(array('id' => $id));
    $where = "ms_id = '$id'";
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
    $sql = "UPDATE mss SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
