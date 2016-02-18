<?php 
class Calendar {
  var $messages = array();
  var $calendar = array();
  var $calendar_event = array();
  var $calendar_events = array();
  var $events = array();
  var $days_in_month = array('31', '28', '31', '30', '31', '30', '31', '31', '30', '31', '30', '31');
  var $month_names = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

  # user, hash
  function add_calendar_event($args){
    $hash = $args['hash'];
    $hash['calendar_user_name'] = $args['user']['user_name'];
    if(isset($hash['calendar_start_date'])){
      $hash['calendar_start_datetime'] = date('Y-m-d H:i:s',strtotime($hash['calendar_start_date'].' '.$hash['calendar_start_time']));
      unset($hash['calendar_start_date']);
      unset($hash['calendar_start_time']);
    }
    if(isset($hash['calendar_end_date'])){
      $hash['calendar_end_datetime'] = date('Y-m-d H:i:s',strtotime($hash['calendar_end_date'].' '.$hash['calendar_end_time']));
      unset($hash['calendar_end_date']);
      unset($hash['calendar_end_time']);
    }
    if(!$hash['calendar_title']){
      $this->messages[] = "You did not enter in an event title!";
    } else {
      $id = Database::insert(array('table' => 'calendar', 'hash' => $hash));
      $this->messages[] = "You have successfully added a calendar event!";
      return $id;
    }
  }

  # id, user
  function get_calendar_event($args){
    $id = $args['id'];
    $args['user']['user_name'] = (isset($args['user']['user_name']) ? $args['user']['user_name'] : '');
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $if_user_name = '';
    if($user_name)
      $if_user_name = "calendar_user_name = '$user_name' AND ";
    $sql = "
      SELECT c.*,
             DATE_FORMAT(c.calendar_start_datetime, '%b %e, %Y %l:%i%p %W')
               AS calendar_start_datetime_formatted,
             DATE_FORMAT(c.calendar_start_datetime, '%c/%e/%Y')
               AS calendar_start_date_formatted2,
             DATE_FORMAT(c.calendar_start_datetime, '%l:%i%p')
               AS calendar_start_time_formatted2,
             DATE_FORMAT(c.calendar_end_datetime, '%c/%e/%Y')
               AS calendar_end_date_formatted2,
             DATE_FORMAT(c.calendar_end_datetime, '%l:%i%p')
               AS calendar_end_time_formatted2
      FROM calendar c
      WHERE calendar_id = '$id' AND
            $if_user_name
            calendar_display = '1'
      LIMIT 1";
    $results = mysql_query($sql);
    $r = mysql_fetch_assoc($results);
    if($r){
      $r['calendar_url'] = Common::get_url(array('bow' => $r['calendar_title'],
                                                 'id' => 'C'.$r['calendar_id']));
      $this->calendar_event = $r;
    }
    return $this->calendar_event;
  }

  # id
  function get_employee($args){
    $id = $args['id'];
    $contact_id = $args['contact_id'];
    $sql = "
      SELECT e.*,c.*,
             DATE_FORMAT(c.contact_birth_date, '%b %e, %Y')
               AS contact_birth_date_formatted
      FROM employees e
      LEFT JOIN contacts c ON (e.employee_contact_id = c.contact_id)
      WHERE (employee_id = '$id' OR employee_contact_id = '$contact_id')
      LIMIT 1";
    $results = mysql_query($sql);
    $r = mysql_fetch_assoc($results);
    if($r){
      $r['employee_url'] = Common::get_url(array('bow' => $r['contact_first'].'-'.$r['contact_last'],
                                                 'id' => 'EM'.$r['employee_id']));
      #$r['appointments'] = $this->get_employee_appointments(array('id' => $r['employee_id']));
      $this->employee = $r;
    }
    return $this->employee;
  }

  function get_events($args){
    $this->events = array();
    $args['user']['user_name'] = (isset($args['user']['user_name']) ? $args['user']['user_name'] : '');
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $if_user_name = '';
    if($user_name)
      $if_user_name = "calendar_user_name = '$user_name' AND ";
    $date = $args['date'];
    $hash = $args['hash'];
    $if_zip = '';
    if(isset($hash['address_zip'])){
      $if_zip = "address_zip = '$hash[address_zip]' AND ";
    }
    $sql = "
      SELECT c.*, a.*,
             DATE_FORMAT(c.calendar_start_datetime, '%b %e %l:%i%p %W')
               AS calendar_start_datetime_formatted,
             DATE_FORMAT(c.calendar_start_datetime, '%l:%i%p')
               AS calendar_time
      FROM calendar c
      LEFT JOIN addresses a ON (c.calendar_address_id = a.address_id)
      WHERE calendar_start_datetime >= '$date' AND
            calendar_end_datetime <= '$date 23:59:59' AND
            $if_zip
            $if_user_name
            calendar_display = 1
      ORDER BY calendar_start_datetime ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_assoc($results)){
      $r['calendar_url'] = Common::get_url(array('bow' => $r['calendar_title'],
                                                 'id' => 'C'.$r['calendar_id']));
      $r['calendar_time'] = preg_replace("/AM/",'a',$r['calendar_time']);
      $r['calendar_time'] = preg_replace("/PM/",'p',$r['calendar_time']);
      if($r['calendar_employee_id']){
        $r['employee'] = $this->get_employee(array('id' => $r['calendar_employee_id']));
      }
      $items[] = $r;
    }
    if(isset($items)){
      $this->events = $items;
    }
    return $this->events;
  }

  # user,
  function get_future_calendar($args){
    $user = $args['user'];
    $sql = "
      SELECT c.*,
             DATE_FORMAT(c.calendar_datetime, '%b %e %l:%i%p %W')
               AS calendar_datetime_formatted
      FROM calendar c
      WHERE calendar_datetime > now() AND
            calendar_completed = '0' AND
            (calendar_privacy <= '$user[user_permission]' OR
             calendar_user_name = '$user[user_name]')
      ORDER BY calendar_datetime ASC";
    $results = mysql_query($sql);
    while($r = mysql_fetch_assoc($results)){
      $r['calendar_url'] = Common::get_url($r[calendar_title],'C'.$r[calendar_id]);
      $r['calendar_datetime_formatted'] = preg_replace("/12:00AM/",'',$r[calendar_datetime_formatted]);
      $future_calendar[] = $r;
    }
    return $future_calendar;
  }

  # user,
  function get_past_calendar(){
    $user = $args['user'];
    $sql = "
      SELECT c.*,
             DATE_FORMAT(c.calendar_datetime, '%b %e %l:%i%p %W')
               AS calendar_datetime_formatted
      FROM calendar c
      WHERE calendar_datetime < now() AND
            (calendar_privacy <= '$user[user_permission]' OR
             calendar_user_name = '$user[user_name]')
      ORDER BY calendar_datetime DESC
      LIMIT 15";
    $results = mysql_query($sql);
    while($r = mysql_fetch_assoc($results)){
      $r['calendar_url'] = Common::get_url($r[calendar_title],'C'.$r[calendar_id]);
      $past_calendar[] = $r;
    }
    return $past_calendar;
  }

  # user,
  function get_month_calendar_events($args){
    $user = $args['user'];
    $sql = "
      SELECT c.*,
             DATE_FORMAT(c.calendar_start_datetime, '%b %e %l:%i%p %W')
               AS calendar_start_datetime_formatted,
             DATE_FORMAT(c.calendar_start_datetime, '%l:%i%p')
               AS calendar_time
      FROM calendar c
      WHERE calendar_start_datetime >= '$args[y]-$args[m]-01' AND
            calendar_start_datetime <= '$args[y]-$args[m]-31 23:59:59' AND
            (calendar_privacy <= '$user[user_permission]' OR
             calendar_user_name = '$user[user_name]')
      ORDER BY calendar_start_datetime ASC";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $r['calendar_url'] = Common::get_url(array('bow' => $r['calendar_title'],
                                                 'id' => 'C'.$r['calendar_id']));
      $r['calendar_datetime_formatted'] = preg_replace("/12:00AM/",'',$r[calendar_datetime_formatted]);
      $r['calendar_day'] = preg_replace("/20\d{2}-\d{2}-0?/",'',$r[calendar_datetime]);
      $r['calendar_day'] = preg_replace("/\s.+/",'',$r['calendar_day']);
      $r['calendar_day'] = preg_replace("/\s.+/",'',$r['calendar_day']);
      $r['calendar_time'] = preg_replace("/AM/",'a',$r['calendar_time']);
      $r['calendar_time'] = preg_replace("/PM/",'p',$r['calendar_time']);
      $calendar[$r['calendar_day']][] = $r;
    }
    if($calendar)
      $this->calendar = $calendar;
    return $this->calendar;
  }

  # y, m, user
  function get_month_calendar_events_new($args){
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $sql = "
      SELECT c.*,
             DATE_FORMAT(c.calendar_start_datetime, '%b %e %l:%i%p %W')
               AS calendar_start_datetime_formatted,
             DATE_FORMAT(c.calendar_start_datetime, '%l:%i%p')
               AS calendar_time
      FROM calendar c
      WHERE calendar_start_datetime >= '$args[y]-$args[m]-01' AND
            calendar_start_datetime <= '$args[y]-$args[m]-31 23:59:59' AND
            calendar_display = 1
      ORDER BY calendar_start_datetime ASC";
    $result = mysql_query($sql);
    while($r = mysql_fetch_assoc($result)){
      $r['calendar_url'] = Common::get_url(array('bow' => $r['calendar_title'],
                                                 'id' => 'C'.$r['calendar_id']));
      $r['calendar_datetime_formatted'] = preg_replace("/12:00AM/",'',$r['calendar_start_datetime_formatted']);
      $r['calendar_start_date'] = preg_replace("/\s+.*/",'',$r['calendar_start_datetime']);
      $r['calendar_day'] = preg_replace("/20\d{2}-\d{2}-0?/",'',$r['calendar_start_datetime']);
      $r['calendar_day'] = preg_replace("/\s.+/",'',$r['calendar_day']);
      $r['calendar_day'] = preg_replace("/\s.+/",'',$r['calendar_day']);
      $r['calendar_time'] = preg_replace("/AM/",'a',$r['calendar_time']);
      $r['calendar_time'] = preg_replace("/PM/",'p',$r['calendar_time']);
      $items[$r['calendar_start_date']][] = $r;
    }
    if(isset($items))
      $this->calendar_events = $items;
    return $this->calendar_events;
  }

  # y, m, user, calendar_events
  function get_month_calendar_html($args){
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $hash = $args['hash'];
    $h = "";

    $days = $this->get_days_in_month(array('m' => $args['m'], 'y' => $args['y']));
    $date = getdate(mktime(12, 0, 0, $args['m'], 1, $args['y']));

    $first = $date["wday"];
    $prev = $this->get_adjusted_date($args['m'] - 1, $args['y']);
    $next = $this->get_adjusted_date($args['m'] + 1, $args['y']);

    $month_name = $this->month_names[$args['m'] - 1];
    $header = "$month_name $args[y]";

    $h .= "<table class=\"calendar\">\n";
    $h .= "<tr>";
    $h .= "<td class=\"calendar-prev\"><a href=\"/calendar/month/$prev[1]/$prev[0]\">&lt;&lt; Prev</a></td>";
    $h .= "<td class=\"calendar-header\" colspan=5>$header</td>";
    $h .= "<td class=\"calendar-next\"><a href=\"/calendar/month/$next[1]/$next[0]\">Next &gt;&gt;</a></td>";
    $h .= "</tr>";
    $h .= "<tr>";
    $h .= "<td class=\"calendar-day-heading\">Sun</td>";
    $h .= "<td class=\"calendar-day-heading\">Mon</td>";
    $h .= "<td class=\"calendar-day-heading\">Tue</td>";
    $h .= "<td class=\"calendar-day-heading\">Wed</td>";
    $h .= "<td class=\"calendar-day-heading\">Thu</td>";
    $h .= "<td class=\"calendar-day-heading\">Fri</td>";
    $h .= "<td class=\"calendar-day-heading\">Sat</td>";
    $h .= "</tr>";

    # we need to work out what date to start at so that the first appears in the correct column
    $d = 1 - $first;
    while($d > 1){
      $d -= 7;
    }

    # make sure we know when today is, so that we can use a different CSS style
    $today = getdate();
    while($d <= $days){
      $h .= "<tr>";
      for ($i = 0; $i < 7; $i++){
        $h .= "<td>";
        if($d > 0 && $d <= $days){
          $class = ($args['y'] == $today["year"] && $args['m'] == $today["mon"] && $d == $today["mday"]) ? "day_number_today" : "day_number";
          $month = sprintf("%02s", $args['m']);
          $day = sprintf("%02s", $d);
          $event_date = "$args[y]-$month-$day";
          $h .= "<div class=\"day $class\"><div class=$class><a href=\"/calendar/day/$args[y]/$args[m]/$day\">$d</a></div>";
          $events = $this->get_events(array('date' => $event_date, 'hash' => $hash, 'user_name' => $user_name));
          #print_r($events);
          foreach($events as $e){

            $style = "color:black;";
            $caption = "Time: $e[calendar_start_datetime_formatted]&#013;";
            $caption .= "Title: $e[calendar_title]&#013;";
            if($e['address_address']){
              $caption .= "Where: $e[address_address], $e[address_city], $e[address_region], $e[address_zip]&#013;";
            }
            #$caption .= "Employee: ".$e['employee']['contact_first']."&#013;";
            #if($e['employee']['employee_color']){
            #  $style = "color:".$e['employee']['employee_color'];
            #}
            $h .= "<div class=event><a href=\"$e[calendar_url]\" alt=\"$caption\" title=\"$caption\" style=\"$style\">$e[calendar_time] $e[calendar_title]</a> </div>";
          }
          $h .= "</div>";
        } else {
          $h .= "&nbsp;";
        }
        $h .= "</td>\n";
        $d++;
      }
      $h .= "</tr>\n";
    }
    $h .= "</table>\n";
    return $h;
  }

  # y, m, user, events
  function get_month_calendar_html_new($args){
    $user_name = (isset($args['user_name']) ? $args['user_name'] : $args['user']['user_name']);
    $hash = $args['hash'];
    $events = $args['events'];
    $h = "";

    $days = $this->get_days_in_month(array('m' => $args['m'], 'y' => $args['y']));
    $date = getdate(mktime(12, 0, 0, $args['m'], 1, $args['y']));

    $first = $date["wday"];
    $prev = $this->get_adjusted_date($args['m'] - 1, $args['y']);
    $next = $this->get_adjusted_date($args['m'] + 1, $args['y']);

    $month_name = $this->month_names[$args['m'] - 1];
    $header = "$month_name $args[y]";

    $h .= "<table class=\"calendar\">\n";
    $h .= "<tr>";
    $h .= "<td class=\"calendar-prev\"><a href=\"/calendar/month/$prev[1]/$prev[0]\">&lt;&lt; Prev</a></td>";
    $h .= "<td class=\"calendar-header\" colspan=5>$header</td>";
    $h .= "<td class=\"calendar-next\"><a href=\"/calendar/month/$next[1]/$next[0]\">Next &gt;&gt;</a></td>";
    $h .= "</tr>";
    $h .= "<tr>";
    $h .= "<td class=\"calendar-day-heading\">Sun</td>";
    $h .= "<td class=\"calendar-day-heading\">Mon</td>";
    $h .= "<td class=\"calendar-day-heading\">Tue</td>";
    $h .= "<td class=\"calendar-day-heading\">Wed</td>";
    $h .= "<td class=\"calendar-day-heading\">Thu</td>";
    $h .= "<td class=\"calendar-day-heading\">Fri</td>";
    $h .= "<td class=\"calendar-day-heading\">Sat</td>";
    $h .= "</tr>";

    # we need to work out what date to start at so that the first appears in the correct column
    $d = 1 - $first;
    while($d > 1){
      $d -= 7;
    }

    # make sure we know when today is, so that we can use a different CSS style
    $today = getdate();
    while($d <= $days){
      $h .= "<tr>";
      for ($i = 0; $i < 7; $i++){
        $h .= "<td>";
        if($d > 0 && $d <= $days){
          $class = ($args['y'] == $today["year"] && $args['m'] == $today["mon"] && $d == $today["mday"]) ? "day_number_today" : "day_number";
          $month = sprintf("%02s", $args['m']);
          $day = sprintf("%02s", $d);
          $event_date = "$args[y]-$month-$day";
          $h .= "<div class=\"day $class\"><div class=$class><a href=\"/calendar/day/$args[y]/$args[m]/$day\">$d</a></div>";
          #$events = $this->get_events(array('date' => $event_date, 'hash' => $hash, 'user_name' => $user_name));
          #print_r($events);
          if(isset($events[$event_date])){
            foreach($events[$event_date] as $e){
              $style = '';
              if($e['calendar_color'])
                $style = "style=\"background-color:$e[calendar_color];\"";
              $event_content = '';
              if(!$e['calendar_all_day'])
                $event_content .= $e['calendar_time'].' ';
              $event_content .= $e['calendar_title'];
              $h .= "<div class=\"event\" $style><a href=\"$e[calendar_url]\">$event_content</a> </div>";
            }
          }
          $h .= "</div>";
        } else {
          $h .= "&nbsp;";
        }
        $h .= "</td>\n";
        $d++;
      }
      $h .= "</tr>\n";
    }
    $h .= "</table>\n";
    return $h;
  }

  function get_days_in_month($args){
    $month = $args['m'];
    $year = $args['y'];
    if ($month < 1 || $month > 12){
      return 0;
    }
    $d = $this->days_in_month[$month - 1];

    if($month == 2){
      // Check for leap year
      // Forget the 4000 rule, I doubt I'll be around then...

      if($year%4 == 0){
        if($year%100 == 0){
          if($year%400 == 0){
            $d = 29;
          }
        } else {
          $d = 29;
        }
      }
    }
    return $d;
  }

  function get_adjusted_date($month, $year){
    $a = array();
    $a[0] = $month;
    $a[1] = $year;

    while ($a[0] > 12) {
      $a[0] -= 12;
      $a[1]++;
    }
    while ($a[0] <= 0) {
      $a[0] += 12;
      $a[1]--;
    }
    return $a;
  }

  function get_times(){
    foreach(range(0,23) as $h){
      $hour = ($h < 10 ? "0$h:00" : "$h:00");
      $pretty = date("g:ia",strtotime($hour));
      $times[$hour] = $pretty;
      $thirty = ($h < 10 ? "0$h:30" : "$h:30");
      $pretty = date("g:ia",strtotime($thirty));
      $times[$thirty] = $pretty;
    }
    return $times;
  }

  function update_calendar_event($args){
    $id = $args['id'];
    $hash = $args['hash'];
    if(isset($hash['calendar_start_date'])){
      $hash['calendar_start_datetime'] = date('Y-m-d H:i:s',strtotime($hash['calendar_start_date'].' '.$hash['calendar_start_time']));
      unset($hash['calendar_start_date']);
      unset($hash['calendar_start_time']);
    }
    if(isset($hash['calendar_end_date'])){
      $hash['calendar_end_datetime'] = date('Y-m-d H:i:s',strtotime($hash['calendar_end_date'].' '.$hash['calendar_end_time']));
      unset($hash['calendar_end_date']);
      unset($hash['calendar_end_time']);
    }
    $item = $this->get_calendar_event($args);
    $where = "calendar_id = '$id' ";
    $update = NULL;
    foreach($hash as $k => $v){
      if($v != $item[$k] && array_key_exists($k, $item)){
        $new_value = mysql_real_escape_string($v);
        $update .= (is_null($v) ? "$k = NULL," : "$k = '$new_value', ");
        $item[$k] = $v;
        $this->messages[] = "You have successfully updated the $k!";
      }
    }
    $where = rtrim($where, ' AND ');
    $update = rtrim($update, ', ');
    $sql = "UPDATE calendar SET $update WHERE $where";
    #print $sql;
    if(isset($hash['calendar_start_date'])){
      $item['calendar_start_date_formatted2'] = $args['hash']['calendar_start_date'];
      $item['calendar_start_time_formatted2'] = $args['hash']['calendar_start_time'];
      $item['calendar_end_date_formatted2'] = $args['hash']['calendar_end_date'];
      $item['calendar_end_time_formatted2'] = $args['hash']['calendar_end_time'];
    }
    if($update)
      $results = mysql_query($sql) or trigger_error("SQL", E_USER_ERROR);
    return $item;
  }

}

?>
