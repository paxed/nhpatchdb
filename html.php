<?php

function html_header($data = null)
{
  // Ask password if we need it.
  if (isset($data['need_id']) && !isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="nethack_patch_database"');
  }

  header('Content-type: text/html; charset=iso-8859-1');

 # echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"\n";
 # echo " \"http://www.w3.org/TR/html4/strict.dtd\">";

  echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"';
  echo ' "http://www.w3.org/TR/html4/loose.dtd">';

  echo '<html><head><title>'.((isset($data['title'])) ? 'NHPatchDB : '.$data['title'] : 'NetHack Patch Database').'</title>';

  echo '<link rel="icon" href="'.location_url('bilious.png').'">';

  echo '<link rel="stylesheet" type="text/css" media="screen" href="'.(isset($data['css']) ? $data['css'] : 'nhpatchdb.css').'">';

  if (isset($data['js_focus']) || isset($data['js_limittext'])) {
    echo '<script type="text/javascript">';
    echo "<!-- \n";
    if (isset($data['js_focus']))
      echo 'function sf(){document.searchform.searchbar.focus();}';
    if (isset($data['js_limittext'])) {
      echo 'function limittext(field,id,limit){';
      echo 'if(field.value.length>limit)';
      echo 'field.value=field.value.substring(0,limit);';
      echo 'else{';
      echo 'var f=document.getElementById(id);';
      echo 'f.innerHTML=limit-field.value.length.toString();';
      echo '}}';
    }
    echo "\n -->";
    echo '</script>';
  }

  if (isset($data['metadesc'])) {
      print '<meta name="description" content="'.$data['metadesc'].'" />';
  }

  echo '</head><body'.((isset($data['js_focus'])) ? ' onLoad="sf()"' : '').'>';
}

function html_footer()
{
  echo '</body></html>';
}

function echoid($msg, $id='error')
{
  echo '<p class="'.$id.'">'.$msg.'</p>';
}

function mk_cookie($name, $data = null)
{
  if ($data) {
    setcookie($name, $data, time()+3600*24*365, '/', $_SERVER['SERVER_NAME']);
    $_COOKIE[$name] = $data;
  } else {
    setcookie($name, '', time()-3600, '/', $_SERVER['SERVER_NAME']);
    unset($_COOKIE[$name]);
  }
}

function menulinkurl($urli, $nimi)
{
  return make_link(htmlentities($urli), $nimi);
}

function get_rating_table($percent, $showpercent = NULL)
{
  $ret = '<span class="ratingbar">';
  $ret .= '<span class="rating" style="width:'.$percent.'%;"></span>';
  if ($showpercent) { $ret .= '<span class="ratingtext">'.$percent.'%</span>'; }
  $ret .= '</span>';
  return $ret;
}

function rating_table($percent, $ratings)
{
  if ($ratings <= 0)
    return '<span class="notrated">Not&nbsp;rated</span>';
  else
    return get_rating_table($percent, 1);
}

function safe_str($s,$newch='%')
{
  return preg_replace("/\;|:|\\'|\\\"|\,|\*|\\\/", $newch, $s);
}

function str_htmlize_quotes($str)
{
  $str = preg_replace("/(\\\"|\")/", "&quot;", $str);
  $str = str_replace("\\\\", "\\", $str);
  $str = str_replace("\\'", "'", $str);
  $str = str_replace("<", "&lt;", $str);
  $str = str_replace(">", "&gt;", $str);
  return $str;
}

function make_link($url, $name = null)
{
  if ($url) return '<a href="'.$url.'">'.(($name) ? $name : $url).'</a>';
  else return '';
}

function location_url($suffix='')
{
  return 'http://'.$_SERVER['SERVER_NAME'].preg_replace('/index.php$/','',$_SERVER['PHP_SELF']).$suffix;
}

function munge_email($str)
{
  switch (rand()%3) {
  case 0:$str = preg_replace('/@/', '@@@', $str); break;
  default:
  case 1:$str = preg_replace('/@/', ' at ', $str); break;
  case 2:$str = preg_replace('/@/', '(at)', $str); break;
  }

  switch (rand()%5) {
  case 0:$str = preg_replace('/\./', '...', $str); break;
  case 1:$str = preg_replace('/\./', ' . ', $str); break;
  default:
  case 2:$str = preg_replace('/\./', ' ', $str); break;
  case 3:$str = preg_replace('/\./', ' dot ', $str); break;
  case 4:$str = preg_replace('/\./', '(dot)', $str); break;
  }

  if (!(rand()%3)) $str = $str.'.NOSPAM';

  return $str;
}

function show_pagecontrols($startpage, $numrows, $pagehei)
{
  parse_str($_SERVER['QUERY_STRING'], $origquery);
  $ret = '';
  if (($startpage > 0) || (($startpage+1) < ($numrows / $pagehei))) {
    $ret .= '<table class="pagecontrol">';
    $ret .= '<tr>';

    $ret .= '<td class="pagefirst">';
    if ($startpage > 0) {
      $querystr = $origquery;
      unset($querystr['page']);
      $ret .= make_link(phpself_querystr($querystr), 'First');
    } else $ret .= 'First';
    $ret .= '</td>';

    $ret .= '<td class="pageprev">';
    if ($startpage > 0) {
      $querystr = $origquery;
      $querystr['page'] = $startpage-1;
      if ($querystr['page'] == 0) unset($querystr['page']);
      $ret .= make_link(preg_replace('/&/','&amp;',phpself_querystr($querystr)), 'Prev');
    } else $ret .= 'Prev';
    $ret .= '</td>';


    $ret .= '<td class="pagenum">';
    $ret .= "\n<script type=\"text/javascript\"><!-- \nvar l='<'; var g='>'; document.write(";
    $ret .= "l+'form name=pagejump method=GET onSubmit=\"return false;\"'+g";
    $ret .= "+l+'select name=pageselect onChange=\"parent.location=this.options[this.selectedIndex].value;\"'+g";
    $querystr = $origquery;
    for ($pageloop = 0; $pageloop < ($numrows/$pagehei); $pageloop++) {
      $querystr['page'] = $pageloop;
      if ($querystr['page'] == 0) unset($querystr['page']);
      $ret .= "+l+'option value=\'".preg_replace('/&/', '&amp;', phpself_querystr($querystr))."\'";
      if ($startpage == $pageloop) $ret .= ' selected';
      $ret .= "'+g+'Page ".($pageloop+1)."'+l+'/option'+g";
    }
    $ret .= "+l+'/select'+g";
    $ret .= "+l+'/form'+g";
    $ret .= ");\n --></script>\n";
    $ret .= '<noscript>';
    $ret .= 'Page '.($startpage+1).' (of '.(1+floor($numrows/$pagehei)).')';
    $ret .= '</noscript>';
    $ret .= '</td>';


    $ret .= '<td class="pagenext">';
    if (($startpage+1) < ($numrows / $pagehei)) {
      $querystr = $origquery;
      $querystr['page'] = $startpage + 1;
      $ret .= make_link(preg_replace('/&/','&amp;',phpself_querystr($querystr)), 'Next');
    } else $ret .= 'Next';
    $ret .= '</td>';

    $ret .= '<td class="pagelast">';
    if (($startpage+1) < ($numrows / $pagehei)) {
      $querystr = $origquery;
      $querystr['page'] = floor($numrows / $pagehei);
      $ret .= make_link(preg_replace('/&/','&amp;',phpself_querystr($querystr)), 'Last');
    } else $ret .= 'Last';
    $ret .= '</td>';

    $ret .= '</tr>';
    $ret .= '</table>';
  }
  return $ret;
}

function query_str($params, $sep='&', $quoted=0, $encode=1)
{
  $str = '';
  foreach ($params as $key => $value) {
    $str .= (strlen($str) < 1) ? '' : $sep;
    if (($value=='') || is_null($value)) {
      $str .= $key;
      continue;
    }
    $rawval = ($encode) ? rawurlencode($value) : $value;
    if ($quoted) $rawval = '"'.$rawval.'"';
    $str .= $key . '=' . $rawval;
  }
  return ($str);
}

function phpself_querystr($querystr = null)
{
  $ret = $_SERVER['PHP_SELF'];
  $ret = preg_replace('/\/index.php$/', '/', $ret);
  if (!isset($querystr)) parse_str($_SERVER['QUERY_STRING'], $querystr);
  if (is_array($querystr)) {
    if (count($querystr)) $ret .= '?' . query_str($querystr);
  } else {
    if ($querystr) {
      $ret .= '?' . $querystr;
    }
  }
  return $ret;
}

function get_input_postdata($type, $name, $data = NULL, $defdata = NULL)
{
  if (!isset($data)) {
    if (($_SERVER['REQUEST_METHOD'] == "POST") && ($_POST[$name]))
      $data = $_POST;
  } else {
    if (!is_array($data)) $data = array($name=>$data);
  }

  switch ($type) {
  default:
  case 'text':
    $data[$name] = str_htmlize_quotes(isset($data[$name]) ? $data[$name] : '');
    $defs = ($defdata) ? query_str($defdata, ' ', 1, 0) : '';
    $ret = '<input type="'.$type.'" name="'.$name.'" '.$defs.' value="'.$data[$name].'">';
    break;
  case 'checkbox':
    $ret = '<input type="'.$type.'" name="'.$name.'" id="checkbox_'.$name.'"';
    if (isset($data[$name]) && (($data[$name] == 't') || ($data[$name] == 'on'))) $ret .= ' checked';
    $ret .= '>';
    break;
  case 'textarea':
    $data[$name] = str_htmlize_quotes(isset($data[$name]) ? $data[$name] : '');
    $defs = ($defdata) ? query_str($defdata, ' ', 1, 0) : '';
    $ret = '<textarea name="'.$name.'" '.$defs.'>'.$data[$name].'</textarea>';
    break;
  case 'hidden':
    $data[$name] = str_htmlize_quotes($data[$name]);
    $ret = '<input type="'.$type.'" name="'.$name.'" value="'.$data[$name].'">';
    break;
  case 'submit':
  case 'reset':
    $data[$name] = str_htmlize_quotes($data[$name]);
    $ret = '<input type="'.$type.'" value="'.$data[$name].'">';
    break;
  }
  return $ret;
}

function get_approx_size($size)
{
  return ' ('.round($size/1024,1).' Kb)';
}

function make_menu_string($arr, $sepmode=0)
{
  $sepstrs = array(
		   array(' | ', '', '', '( ', ' )'),
		   array(' ', '[', ']', '', '')
);

  $ret = '';
  for ($i = 0; $i < count($arr); $i++) {
    if ($ret) $ret .= $sepstrs[$sepmode][0];
    $ret .= $sepstrs[$sepmode][1] . $arr[$i] . $sepstrs[$sepmode][2];
  }
  $ret = $sepstrs[$sepmode][3] . $ret . $sepstrs[$sepmode][4];
  if ($ret == '') $ret = '&nbsp;';
  return $ret;
}

function mk_selectbox($selname, $opts, $nselected = 0)
{
  $ret = '<select name="'.$selname.'">';
  $count = 0;
  foreach ($opts as $key=>$val) {
    $ret .= '<option ';
    if (($nselected == $count++) || (is_string($nselected) && ($val == $nselected))) $ret .= 'selected ';
    $ret .= 'value="'.$val.'">'.$key.'</option>';
  }
  $ret .= '</select>';
  return $ret;
}

function tablerowd($rowdata, $tddat=NULL, $trdat=NULL)
{
  $ret = '<tr';
  if (isset($trdat))
    foreach ($trdat as $key=>$val)
      $ret .= ' '.$key.'="'.$val.'"';
  $ret .= '>';

  if (!is_array($rowdata)) $rowdata = array($rowdata);
  if (!isset($tddat)) $tddat = array(array('class'=>'rowname'));
  for ($x = 0; $x < count($rowdata); $x++) {
    $ret .= '<td';
    if ($x < count($tddat))
      foreach ($tddat[$x] as $key=>$val)
	$ret .= ' '.$key.'="'.$val.'"';
    $ret .= '>'.$rowdata[$x].'</td>';
  }
  $ret .= '</tr>';
  return $ret;
}

function auth_user()
{
  global $admin_username, $admin_passwd;
  if (isset($_SERVER['PHP_AUTH_USER']) && ($_SERVER['PHP_AUTH_USER'] == $admin_username) &&
      isset($_SERVER['PHP_AUTH_PW']) && ($_SERVER['PHP_AUTH_PW'] == $admin_passwd))
    return 1;
  else return 0;
}

function auth_user_error()
{
  if (!auth_user()) exit;
}
