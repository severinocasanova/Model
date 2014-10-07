<?php

class OS {
  var $books = array();
  var $book_pages = array();
  var $messages = array();
  var $ranges = array();
  var $sections = array();
  var $surveyors = array();
  var $os = array();
  var $oss = array();
  var $townships = array();
  var $s = 'os_id';
  var $d = 'DESC';

  # user, hash
  function add_os($args){
    $hash = $args['hash'];
    $hash['os_OSDATE'] = date('Y-m-d',strtotime($hash['os_OSDATE']));
    if(!$hash['os_township']){
      $this->messages[] = "You did not enter in a Township!";
    } else {
      $id = Database::insert(array('table' => 'oss', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added an OS!";
      return $id;
    }
  }

  # hash
  function get_books($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',os.os_book)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT os_book
      FROM oss os
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY os_book
      ORDER BY os_book ASC";
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
    $search_fields = "CONCAT_WS(' ',os.os_page)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT os_page
      FROM oss os
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY os_page
      ORDER BY os_page ASC";
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
    $search_fields = "CONCAT_WS(' ',os.os_range)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT os_range
      FROM oss os
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY os_range
      ORDER BY os_range ASC";
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
    $search_fields = "CONCAT_WS(' ',os.os_section)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT os_section
      FROM oss os
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY os_section
      ORDER BY os_section ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->sections = $items;
    return $this->sections;
  }

  # hash
  function get_surveyors($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',os.os_surveyor)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT os_surveyor
      FROM oss os
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY os_surveyor
      ORDER BY os_surveyor ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->surveyors = $items;
    return $this->surveyors;
  }

  # id
  function get_os($args){
    $id = $args['id'];
    $sql = "
      SELECT os.*,DATE_FORMAT(os.os_OSDATE, '%c/%e/%Y') as os_OSDATE_formatted2
      FROM oss os
      WHERE os_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['os_url'] = Common::get_url(array('bow' => $r['os_description'],
                                           'id' => 'OS'.$r['os_id']));
      $path = '/maps-and-records/webroot/images/Survey/FieldBook/'.$r['os_book'].'/';
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
      $this->os = $r;
    }
    return $this->os;
  }

  # hash
  function get_oss($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    $search_fields = "CONCAT_WS(' ',o.os_description)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['r']) && $hash['r'] != "")
      $if_range = "os_range = '$hash[r]' AND ";
    if(isset($hash['sec']) && $hash['sec'] != "")
      $if_section = "os_section = '$hash[sec]' AND ";
    if(isset($hash['fs']) && $hash['fs'] != "")
      $if_surveyor = "os_surveyor = '$hash[fs]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_township = "os_township = '$hash[t]' AND ";
    $sql = "
      SELECT o.*
      FROM oss o
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_range
            $if_section
            $if_surveyor
            $if_township
            os_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['os_url'] = Common::get_url(array('bow' => $r['os_description'],
                                           'id' => 'OS'.$r['os_id']));
      $oss[] = $r;
    }
    if($oss)
      $this->oss = $oss;
    $this->d = Common::direction_switch($this->d);
    return $this->oss;
  }

  # hash
  function get_oss_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',o.os_description)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['r']) && $hash['r'] != "")
      $if_range = "os_range = '$hash[r]' AND ";
    if(isset($hash['sec']) && $hash['sec'] != "")
      $if_section = "os_section = '$hash[sec]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_township = "os_township = '$hash[t]' AND ";
    $sql = "
      SELECT count(o.os_id)
      FROM oss o
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_range
            $if_section
            $if_township
            os_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function get_townships($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',os.os_township)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $sql = "
      SELECT DISTINCT os_township
      FROM oss os
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY os_township
      ORDER BY os_township ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->townships = $items;
    return $this->townships;
  }

  # id, hash
  function update_os($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['os_due_date'] = date('Y-m-d',strtotime($hash['os_due_date']));
    $item = $this->get_os(array('id' => $id));
    $where = "os_id = '$id'";
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
    $sql = "UPDATE oss SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
