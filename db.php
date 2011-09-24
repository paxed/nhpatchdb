<?php

include_once 'config.php';

function db_connect()
{
  global $db_connect_str;
  $connection = pg_connect($db_connect_str);

  if (!$connection) {
    echo 'Database connection failed.';
    exit;
  }
  return $connection;
}

function db_query($connection, $sql)
{
  return pg_exec($connection, $sql);
}

function db_close($connection)
{
  if (isset($connection)) pg_close($connection);
}

function db_result($result, $row, $field)
{
  return pg_result($result, $row, $field);
}

function db_free_result($result)
{
  pg_free_result($result);
}

function db_num_fields($result)
{
  return pg_num_fields($result);
}

function db_field_name($result, $field)
{
  return pg_field_name($result, $field);
}

function db_numrows($result)
{
  return pg_numrows($result);
}

function db_escape_string($str)
{
  return pg_escape_string($str);
}

function db_update($connection, $table, $data, $where)
{
  return pg_update($connection, $table, $data, $where);
}

function db_insert($connection, $table, $data)
{
  return pg_insert($connection, $table, $data);
}

function db_get_rowdata($results, $row)
{
  $data = null;
  for($gt = 0; $gt < db_num_fields($results); $gt++) {
    $field = db_field_name($results, $gt);
    $data[$field] = db_result($results, $row, $gt);
  }
  return $data;
}

function db_make_dropdown_table($table, $outputnimi, $valittu = 0)
{
  $yhteys = db_connect();
  $tulos = db_query($yhteys, 'SELECT * FROM '.$table.' ORDER BY name DESC');
  $ret = '<select name="'.$outputnimi.'">';
  for ($x = 0; $x < db_numrows($tulos); $x++) {
    $idn = db_result($tulos, $x, 0);
    $ret .= '<option value='.$idn;
    if ($idn == $valittu) $ret .= ' selected';
    $ret .= '>'.db_result($tulos, $x, 1).'</option>';
  }
  $ret .= '</select>';
  return $ret;
}

function db_get_variant_name($id)
{
  $ret = NULL;
  if (isset($id) && is_numeric($id)) {
    $yhteys = db_connect();
    $tulos = db_query($yhteys, "SELECT * FROM variant WHERE id=".$id);
    $rows = db_numrows($tulos);
    if ($rows > 0)
      $ret = db_result($tulos, 0, 1);
    db_free_result($tulos);
  }
  return $ret;
}

?>