<?php

class Common {
  var $messages = array();

  function clean_search_query($q,$search_fields){
    $q = str_replace("'", "", $q);
    $q = mysql_real_escape_string($q);
    $q = preg_replace('/"/',"&quot;", $q);
    $useless_words = array('a','an','of','the','in','with','on');
    foreach($useless_words as $word){
       $q = preg_replace('/\s'.$word.'\s/i',' ', $q);
    }
    if(!preg_match("/&quot;/",$q)){
      $q = preg_replace('/\s+/',"_s_", $q);
      $q = preg_replace('/_s_OR_s_/i',"%' OR $search_fields LIKE '%", $q);
      $q = preg_replace('/_s_AND_s_/i',"%' AND $search_fields LIKE '%", $q);
      $q = preg_replace('/_s_/',"%' AND $search_fields LIKE '%", $q);
    }
    return $q;
  }

  function direction_switch($direction){
    if($direction == 'DESC'){$direction = 'ASC';}
    else{$direction = 'DESC';}
    return $direction;
  }

  function get_cake_user($args){
    $user['user_id'] = $args['user']['id'];
    $user['user_name'] = $args['user']['username'];
    $user['user_email'] = $args['user']['email'];
    if(preg_match("/king/i",$args['user']['role'])){
      $user['user_permission'] = 4;
    }else{
      $user['user_permission'] = 0;
    }
    return $user;
  }

  function get_pages($page,$pages_count){
    if($pages_count < 1)
      $pages_count = 1;
    $page_range = range(1, $pages_count);
    foreach ($page_range as $p){
      # the 10 here is a 10 page spread, not items per page
      if(abs($page - $p) <= 10){
        $pages[] = $p;
      }
    }
    return($pages);
  }

  function get_redirect($args){
    $input = $args['input'];
    $sql = "
      SELECT *
      FROM redirects
      WHERE redirect_input = '$input'
      LIMIT 1";
    $result = mysql_query($sql);
    $r = mysql_fetch_assoc($result);
    return $r;
  }

  # id, bow
  function get_url($args){
    $types = array('AB' => '/address-book/',
                   'A' => '/activities/',
                   'AC' => '/accounts/',
                   'AD' => '/ads/',
                   'B' => '/blog/',
                   'BL' => '/bills/',
                   'C' => '/calendar/',
                   'CL' => '/clients/',
                   'CT' => '/contacts/',
                   'CU' => '/customers/',
                   'D' => '/deals/',
                   'DOC' => '/documents/',
                   'E' => '/events/',
                   'EM' => '/employees/',
                   'EML' => '/emails/',
                   'H' => '/healthcare/',
                   'HR' => '/residents/',
                   'M' => '/movies/',
                   'P' => '/pictures/',
                   'PF' => '/portfolio/',
                   'PA' => '/points/accounts/',
                   'PL' => '/maps-and-records/plans/',
                   'PRJ' => '/projects/',
                   'PST' => '/posts/',
                   'PT' => '/patients/',
                   'R' => '/ringtones/',
                   'RG' => '/roommates/',
                   'RQ' => '/requests/',
                   'SMP' => '/smp/',
                   'TK' => '/trak/',
                   'TS' => '/tests/',
                   'U' => '/users/',
                   'V' => '/vehicles/',
                   'W' => '/wishes/',
                   'WC' => '/wedding/costs/');
    $type = preg_replace('/\d+/','', $args[id]);
    $bow_characters = 150-strlen('http://severinocasanova.com')-strlen($types{$type})-strlen($args[id])-1;
    $bow = strtolower($args[bow]);
    $bow = preg_replace('/\s+/','-', $bow);
    $bow = preg_replace('/[^\w-\/]/','', $bow);
    $useless_words = array('a','an','of','the','in','with','fuck','shit',
                           'and','or','on');
    foreach($useless_words as $bad_word){
       $bow = preg_replace('/-'.$bad_word.'-/','-', $bow);
    }
    $bow = preg_replace('/-+/','-', $bow);
    if(preg_match('/(.{'.$bow_characters.'})/', $bow, $matches)){
      $bow = $matches[1];
      if(preg_match('/(.*)-/', $bow, $last_space)){
        $bow = $last_space[1];
      }
    }
    $url = $types[$type].$bow.'/'.$args[id];
    $url = preg_replace('/(-\/|\/-)/','/', $url);
    return $url;
  }

  function insert_comment($table, $user, $title, $comment){
    $title = addslashes($title);
    $comment = addslashes($comment);
    $sql = "INSERT INTO $table (comment_username,
                                comment_title,
                                comment_content)
                       VALUES ('$user[user_name]',
                               '$title',
                               '$comment')";
    mysql_query($sql) or die('Error: '. mysql_error());
  }

}

?>
