<?php

class Annexations {
  var $books = array();
  var $book_pages = array();
  var $messages = array();
  var $ranges = array();
  var $sections = array();
  var $annexation = array();
  var $annexations = array();
  var $townships = array();
  var $s = 'annexation_map_index';
  var $d = 'ASC';

  # user, hash
  function add_annexation($args){
    $hash = $args['hash'];
    $hash['annexation_due_date'] = date('Y-m-d',strtotime($hash['annexation_due_date']));
    if(!$hash['annexation_customer_id']){
      $this->messages[] = "You did not enter in a customer!";
    } else {
      $id = database::insert(array('table' => 'annexations', 'hash' => $hash));
      if($id)
        $this->messages[] = "You have successfully added an annexation!";
      return $id;
    }
  }

  # hash
  function get_books($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',annexation.annexation_book)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT annexation_book
      FROM annexations annexation
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY annexation_book
      ORDER BY annexation_book ASC";
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
    $search_fields = "CONCAT_WS(' ',annexation.annexation_page)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT annexation_page
      FROM annexations annexation
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY annexation_page
      ORDER BY annexation_page ASC";
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
    $search_fields = "CONCAT_WS(' ',annexation.annexation_range)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT annexation_range
      FROM annexations annexation
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY annexation_range
      ORDER BY annexation_range ASC";
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
    $search_fields = "CONCAT_WS(' ',annexation.annexation_section)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $sql = "
      SELECT DISTINCT annexation_section
      FROM annexations annexation
      WHERE ($search_fields LIKE '%$hash[q]%')
      GROUP BY annexation_section
      ORDER BY annexation_section ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $items[] = $r;
    }
    if($items)
      $this->sections = $items;
    return $this->sections;
  }

  # id
  function get_annexation($args){
    $id = $args['id'];
    $sql = "
      SELECT a.*, smp.*, pl.*,
             DATE_FORMAT(a.annexation_effective_date, '%c/%e/%Y')
               AS annexation_effective_date_formatted2
      FROM annexations a
      LEFT JOIN smps smp ON (smp.smp_number = a.annexation_mp_number)
      LEFT JOIN plans pl ON (pl.plan_number = a.annexation_plan_number)
      WHERE annexation_id = '$id'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    if($r){
      $r['annexation_url'] = Common::get_url(array('bow' => $r['annexation_description'],
                                                   'id' => 'ANX'.$r['annexation_id']));
      if($r['plan_id']){
        $r['plan_url'] = Common::get_url(array('bow' => $r['plan_description'],
                                               'id' => 'PL'.$r['plan_id']));
      }
      if($r['smp_id']){
        $r['smp_url'] = Common::get_url(array('bow' => $r['smp_description'],
                                              'id' => 'SMP'.$r['smp_id']));
      }
      $this->annexation = $r;
    }
    return $this->annexation;
  }

  # hash
  function get_annexations($args){
    $hash = $args['hash'];
    if($hash['s']){$this->s = $hash['s'];}
    if($hash['d']){$this->d = $hash['d'];}
    $search_fields = "CONCAT_WS(' ',a.annexation_map_index,a.annexation_description,a.annexation_plan_number)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    $ipp = (isset($args['ipp']) ? $args['ipp'] : "100");
    $offset = (isset($args['offset']) ? "LIMIT $args[offset],$ipp" : "LIMIT 0,$ipp");
    if(isset($hash['b']) && $hash['b'] != "")
      $if_book = "annexation_book = '$hash[b]' AND ";
    if(isset($hash['bp']) && $hash['bp'] != "")
      $if_book_page = "annexation_page = '$hash[bp]' AND ";
    $sql = "
      SELECT a.*,
             DATE_FORMAT(a.annexation_effective_date, '%c/%e/%Y')
               AS annexation_effective_date_formatted2
      FROM annexations a
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_book
            $if_book_page
            annexation_display = '1'
      ORDER BY $this->s $this->d
      $offset";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $r['annexation_url'] = Common::get_url(array('bow' => $r['annexation_description'],
                                                   'id' => 'ANX'.$r['annexation_id']));
      $annexations[] = $r;
    }
    if($annexations)
      $this->annexations = $annexations;
    $this->d = Common::direction_switch($this->d);
    return $this->annexations;
  }

  # hash
  function get_annexations_total_sq_miles($args){
    $annexation_total_sq_miles = 0;
    $sql = "
      SELECT a.annexation_id,a.annexation_sq_miles
      FROM annexations a
      WHERE 
            annexation_display = '1'
      ORDER BY annexation_effective_date ASC";
    $results = mysql_query($sql);
    while ($r = mysql_fetch_assoc($results)){
      $annexation_total_sq_miles += $r['annexation_sq_miles'];
      $r['annexation_total_sq_miles'] = $annexation_total_sq_miles;
      $items[$r[annexation_id]] = $r;
    }
    if($items)
      $this->annexations_total_sq_miles = $items;
    return $this->annexations_total_sq_miles;
  }

  # hash
  function get_annexations_count($args){
    $hash = $args['hash'];
    $search_fields = "CONCAT_WS(' ',a.annexation_map_index,a.annexation_description,a.annexation_plan_number)";
    $hash['q'] = Common::clean_search_query($hash['q'],$search_fields);
    if(isset($hash['b']) && $hash['b'] != "")
      $if_book = "annexation_book = '$hash[b]' AND ";
    if(isset($hash['bp']) && $hash['bp'] != "")
      $if_book_page = "annexation_page = '$hash[bp]' AND ";
    $sql = "
      SELECT count(a.annexation_id)
      FROM annexations a
      WHERE ($search_fields LIKE '%$hash[q]%') AND
            $if_book
            $if_book_page
            annexation_display = '1'";
    $results = mysql_query($sql);
    $matched = mysql_fetch_row($results);
    return $matched[0];
  }

  # id, hash
  function update_annexation($args){
    $id = $args['id'];
    $hash = $args['hash'];
    $hash['annexation_due_date'] = date('Y-m-d',strtotime($hash['annexation_due_date']));
    $item = $this->get_annexation(array('id' => $id));
    $where = "annexation_id = '$id'";
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
    $sql = "UPDATE annexations SET $update WHERE $where";
    if($update)
      mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
