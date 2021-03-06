<?php
App::uses('CakeEmail', 'Network/Email');
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
                   'AD' => '/ad/',
                   'ADV' => '/ad-viewer/',
                   'ANX' => '/annexations/',
                   'APP' => '/apps/',
                   'B' => '/blog/',
                   'BL' => '/bills/',
                   'C' => '/calendar/',
                   'CL' => '/clients/',
                   'CNRN' => '/concerns/',
                   'COMP' => '/complaints/',
                   'CT' => '/contacts/',
                   'CU' => '/customers/',
                   'D' => '/deals/',
                   'DOC' => '/documents/',
                   'E' => '/events/',
                   'EM' => '/employees/',
                   'EML' => '/emails/',
                   'FB' => '/fb/',
                   'FBV' => '/fb-viewer/',
                   'H' => '/healthcare/',
                   'HR' => '/residents/',
                   'IN' => '/instances/',
                   'M' => '/movies/',
                   'MS' => '/ms/',
                   'MSV' => '/ms-viewer/',
                   'NBH' => '/neighborhoods/',
                   'OS' => '/os/',
                   'P' => '/pictures/',
                   'PF' => '/portfolio/',
                   'PA' => '/points/accounts/',
                   'PL' => '/plans/',
                   'PLV' => '/plans-viewer/',
                   'PRJ' => '/projects/',
                   'PSS' => '/pss/',
                   'PSSV' => '/pss-viewer/',
                   'PST' => '/posts/',
                   'PT' => '/patients/',
                   'R' => '/ringtones/',
                   'RG' => '/roommates/',
                   'RQ' => '/requests/',
                   'RS' => '/rs/',
                   'RSV' => '/rs-viewer/',
                   'SID' => '/submissions/',
                   'SMP' => '/smp/',
                   'SMPV' => '/smp-viewer/',
                   'TK' => '/trak/',
                   'TS' => '/ts/',
                   'TSV' => '/ts-viewer/',
                   'U' => '/users/',
                   'V' => '/vehicles/',
                   'W' => '/wishes/',
                   'WC' => '/wedding/costs/');
    $type = preg_replace('/\d+/','', $args['id']);
    $types[$type] = (isset($types[$type]) ? $types[$type] : "");
    $bow_characters = 150-strlen('https://thiswebsitewebsite.com')-strlen($types[$type])-strlen($args['id'])-1;
    $bow = strtolower($args['bow']);
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
    $url = $types[$type].$bow.'/'.$args['id'];
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

  function send_email($from, $to, $subject, $body) {
    $Email = new CakeEmail();
    $Email->from($from);
    $Email->to($to);
    $Email->subject($subject);
    $Email->send($body);
  }

  function list_state_abreviations($args){
    $states = array('AL',
                    'AK',
                    'AZ',
                    'AR',
                    'CA',
                    'CO',
                    'CT',
                    'DE',
                    'FL',
                    'GA',
                    'HI',
                    'ID',
                    'IL',
                    'IN',
                    'IA',
                    'KS',
                    'KY',
                    'LA',
                    'ME',
                    'MD',
                    'MA',
                    'MI',
                    'MN',
                    'MS',
                    'MO',
                    'MT',
                    'NE',
                    'NV',
                    'NH',
                    'NJ',
                    'NM',
                    'NY',
                    'NC',
                    'ND',
                    'OH',
                    'OK',
                    'OR',
                    'PA',
                    'RI',
                    'SC',
                    'SD',
                    'TN',
                    'TX',
                    'UT',
                    'VT',
                    'VA',
                    'WA',
                    'WV',
                    'WI',
                    'WY');
    return $states;
  }
}

?>
