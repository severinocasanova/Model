<?php

class Database {
  function insert($args){
    $table = $args['table'];
    $hash = $args['hash'];
    $sql = sprintf('INSERT INTO %s (%s) VALUES ("%s")', $table, implode(', ', array_map('mysql_escape_string', array_keys($hash))), implode('", "',array_map('mysql_escape_string', $hash)));
    $result = mysql_query($sql);
    # or print('Error: '. mysql_error());
    if($result){
      return mysql_insert_id();
    } else {
      return;
    }
  }
}

?>
