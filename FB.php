<?php

class FB {
  var $books = array();
  var $book_pages = array();
  var $messages = array();
  var $ranges = array();
  var $sections = array();
  var $surveyors = array();
  var $fb = array();
  var $fbs = array();
  var $townships = array();
  var $s = 'fb_book';
  var $d = 'ASC';

  # user, hash
  function add_fb($args){
    $hash = $args['hash'];
    $hash['fb_FBDATE'] = date('Y-m-d',strtotime($hash['fb_FBDATE']));
    if(!$hash['fb_book']){
      $this->messages[] = "You did not enter in a Book #!";
    } else {
      $id = Database::insert(array('table' => 'fbs', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added an FB!";
      return $id;
    }
  }

  # hash
  function get_books($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',fb.fb_book)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT fb_book
      FROM fbs fb
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY fb_book
      ORDER BY fb_book ASC";
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
    $search_fields = "CONCAT_WS(' ',fb.fb_page)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT fb_page
      FROM fbs fb
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY fb_page
      ORDER BY fb_page ASC";
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
    $search_fields = "CONCAT_WS(' ',fb.fb_range)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT fb_range
      FROM fbs fb
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY fb_range
      ORDER BY fb_range ASC";
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
    $search_fields = "CONCAT_WS(' ',fb.fb_section)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT fb_section
      FROM fbs fb
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY fb_section
      ORDER BY fb_section ASC";
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
    $search_fields = "CONCAT_WS(' ',fb.fb_surveyor)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT fb_surveyor
      FROM fbs fb
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY fb_surveyor
      ORDER BY fb_surveyor ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->surveyors = $items;
    return $this->surveyors;
  }

  # id
  function get_fb($args){
    $id = $args['id'];
    $sql = "
      SELECT fb.*,DATE_FORMAT(fb.fb_FBDATE, '%c/%e/%Y') as fb_FBDATE_formatted2
      FROM fbs fb
      WHERE fb_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['fb_url'] = Common::get_url(array('bow' => $r['fb_description'],
                                           'id' => 'FB'.$r['fb_id']));
      $r['fb_viewer_url'] = Common::get_url(array('bow' => $r['fb_description'],
                                           'id' => 'FBV'.$r['fb_id']));
      $path = '/maps-and-records/webroot/images/Survey/FieldBook/'.$r['fb_book'].'/';
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
      $this->fb = $r;
    }
    return $this->fb;
  }

  # hash
  function get_fbs($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    $search_fields = "CONCAT_WS(' ',f.fb_book,f.fb_description)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['b']) && $hash['b'] != "")
      $if_book = "fb_book = '$hash[b]' AND ";
    if(isset($hash['bp']) && $hash['bp'] != "")
      $if_book_page = "fb_page = '$hash[bp]' AND ";
    if(isset($hash['r']) && $hash['r'] != "")
      $if_range = "fb_range = '$hash[r]' AND ";
    if(isset($hash['sec']) && $hash['sec'] != "")
      $if_section = "fb_section = '$hash[sec]' AND ";
    if(isset($hash['fs']) && $hash['fs'] != "")
      $if_surveyor = "fb_surveyor = '$hash[fs]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_township = "fb_township = '$hash[t]' AND ";
    $sql = "
      SELECT f.*
      FROM fbs f
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_book
            $if_book_page
            $if_range
            $if_section
            $if_surveyor
            $if_township
            fb_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['fb_url'] = Common::get_url(array('bow' => $r['fb_description'],
                                           'id' => 'FB'.$r['fb_id']));
      $fbs[] = $r;
    }
    if($fbs)
      $this->fbs = $fbs;
    $this->d = Common::direction_switch($this->d);
    return $this->fbs;
  }

  # hash
  function get_fbs_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',f.fb_book,f.fb_description)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['b']) && $hash['b'] != "")
      $if_book = "fb_book = '$hash[b]' AND ";
    if(isset($hash['bp']) && $hash['bp'] != "")
      $if_book_page = "fb_page = '$hash[bp]' AND ";
    if(isset($hash['r']) && $hash['r'] != "")
      $if_range = "fb_range = '$hash[r]' AND ";
    if(isset($hash['sec']) && $hash['sec'] != "")
      $if_section = "fb_section = '$hash[sec]' AND ";
    if(isset($hash['t']) && $hash['t'] != "")
      $if_township = "fb_township = '$hash[t]' AND ";
    $sql = "
      SELECT count(f.fb_id)
      FROM fbs f
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_book
            $if_book_page
            $if_range
            $if_section
            $if_township
            fb_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # hash
  function get_townships($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',fb.fb_township)";
    $q = $hash['q'];
    $hash['q'] = Common::clean_search_query($q,$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    $sql = "
      SELECT DISTINCT fb_township
      FROM fbs fb
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY fb_township
      ORDER BY fb_township ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->townships = $items;
    return $this->townships;
  }

  # id, hash
  function update_fb($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['fb_due_date'] = date('Y-m-d',strtotime($hash['fb_due_date']));
    $item = $this->get_fb(array('id' => $id));
    $where = "fb_id = '$id'";
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
    $sql = "UPDATE fbs SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
