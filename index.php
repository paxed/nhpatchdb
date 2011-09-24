<?php

/*
  NetHack Patch Database
 */

/*
error_reporting(E_ALL);
ini_set('display_errors','On');
*/


if (isset($_SERVER['HTTP_REFERER']) && preg_match('/, http:.*, http:.*, http:/', $_SERVER['HTTP_REFERER'])) {
  /* some stupid spammer */
  header("HTTP/1.1 301 Moved Permanently");
  header('Location: http://127.0.0.1/');
  exit;
}


include_once 'config.php';
include_once 'html.php';
include_once 'db.php';

$actionstrs = array(''=>'main',
		    'login'=>'login',
		    'search'=>'search',
		    'contact'=>'contact',
		    'add'=>'add',
		    'browse'=>'browse',
		    'rndshow'=>'random',
		    'random'=>'random',
		    'help'=>'help',
		    'faq'=>'help',
		    'show'=>'show',
		    'queued'=>'queued',
		    'update'=>'update',
		    'commentdel'=>'commentdel',
		    'commentedit'=>'commentedit',
		    'download'=>'download',
		    'patchapprove'=>'patchapprove',
		    'patchdel'=>'patchdel',
		    'patchdeapprove'=>'patchdeapprove',
		    'patchedit'=>'patchedit',
		    'viewdiff'=>'viewdiff',
		    'patchupdate'=>'patchupdate'
);

$id = (isset($_GET['id']) ? $_GET['id'] : NULL);
$act = (isset($_GET['act']) ? $_GET['act'] : NULL);

if (isset($act) && isset($id) && preg_match('/^[0-9]+$/', $id)) {
  /* Redirect old-style url */
  header("HTTP/1.1 301 Moved Permanently");
  header('Location: '.location_url('?'.$act.'='.$id));
  exit;
}

if (!isset($act)) {
  foreach ($actionstrs as $actstr => $value)
    if (isset($_GET[$actstr])) {
      $act=$actstr;
      if (!isset($id) && preg_match('/^[0-9]+$/', $_GET[$actstr]))
	$id = $_GET[$actstr];
      break;
    }
}

if (isset($id)) {
  if (!preg_match('/^[0-9]+$/', $id)) {
    unset($id);
    $act = 'main';
  } else {
    if (!isset($act)) $act = 'show';
  }
} else {
  $qact = $_SERVER['QUERY_STRING'];
  if (strpos($qact, '&')) $qact = substr($qact, 0, strpos($qact, '&'));
  if (preg_match('/^[0-9]+$/', $qact)) {
    $id = $qact;
    $act = 'show';
  } else
    $act = strtolower($qact);
}

if (!isset($actionstrs[$act])) $act = '';


call_user_func('mk_page_'.$actionstrs[$act], $id);

exit;

/***********************************************************************/

/* main page */
function mk_page_main($id)
{
  main_screen();
}

function mk_page_add($id)
{
  $title = 'Add a patch';
  page_header(array('title'=>$title));
  echo '<h3 class="title">'.$title.'</h3>';
  if ($_SERVER['REQUEST_METHOD'] == 'POST') patch_add_post($_POST);
  patch_add_form($_POST);
  html_footer();
}

function mk_page_queued($id)
{
  auth_user_error();
  $title = 'Queued patches';
  page_header(array('title'=>$title));
  echo '<h3 class="title">'.$title.'</h3>';
  patch_browse(TRUE);
  html_footer();
}

function mk_page_browse($id)
{
  $title = 'Browse patches';
  page_header(array('title'=>$title));
  echo '<h3 class="title">'.$title.'</h3>';
  patch_browse();
  html_footer();
}

function mk_page_help($id)
{
  global $help_filename;
  $title = 'FAQ, Help, Links and other stuff';
  page_header(array('title'=>$title));
  echo '<h3 class="title">'.$title.'</h3>';
  $bytesread = @readfile($help_filename);
  if ($bytesread === FALSE)
    echoid('No help text?');
  html_footer();
}

function mk_page_search($id)
{
  $title = 'Search patches';

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    mk_cookie('searchbar', (isset($_POST['searchbar']) ? $_POST['searchbar'] : NULL));
    mk_cookie('searchsort', (isset($_POST['sort']) ? $_POST['sort'] : NULL));
    mk_cookie('searchrev', (isset($_POST['revsort']) ? $_POST['revsort'] : NULL));
  }

  page_header(array('title'=>$title, 'js_focus'=>1));
  echo '<h3 class="title">'.$title.'</h3>';
  if ($_SERVER['REQUEST_METHOD'] == 'POST') $data = $_POST;
  else if (isset($_GET['searchbar'])) $data = $_GET;
  else $data = NULL;
  patch_search_post($data);
  echo '<p>';
  patch_search_form($data);
  html_footer();
}

function mk_page_random($id)
{
  $sql = 'SELECT * FROM patches WHERE queue=FALSE ORDER BY RANDOM() LIMIT 1';
  $connection = db_connect();
  $myresult = db_query($connection, $sql);
  $numrows = db_numrows($myresult);
  if ($numrows > 0) {
    $x = rand(0, $numrows-1);
    $data = db_get_rowdata($myresult, $x);
    header('Location: '.location_url('?'.$data['id']));
    exit;
  }
}

function mk_page_show($id)
{
  $title = 'Show patch data';

  $quote = NULL;

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['preview'])) {
      if ($_POST['remembername']) {
	if ($_POST['username']) {
	  mk_cookie('remembername', $_POST['username']);
	}
      } else {
	mk_cookie('remembername');
      }
    }
  }

  page_header(array('title'=>$title,'js_limittext'=>1));
  echo '<h3 class="title">'.$title.'</h3>';

  if (isset($_GET['quote']) && preg_match('/^[0-9]+$/', $_GET['quote'])) {
    $quotedcomment = get_comment_patchid($_GET['quote']);
    if ($quotedcomment['patch'] == $id) {
      $quote['text'] = "\n[quote:".$quotedcomment['username']."]\n".$quotedcomment['text']."\n[/quote]\n";
      $quote['quoted'] = 1;
    }
  }

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    comment_add_post($_POST);
    patch_show($id, $_POST);
  } else
    patch_show($id, $quote);
  html_footer();
}

function mk_page_contact($id)
{
  $title = 'Contact';
  page_header(array('title'=>$title));
  echo '<h3 class="title">'.$title.'</h3>';
  if ($_SERVER['REQUEST_METHOD'] == 'POST') contact_post($_POST);
  contact_form();
  html_footer();
}

function mk_page_viewdiff($id)
{
  if (isset($id) && preg_match('/^[0-9]+$/',$id)) patch_download($id, NULL);
}

function mk_page_download($id)
{
  if (isset($id) && preg_match('/^[0-9]+$/',$id)) patch_download($id, 1);
}

function mk_page_login($id)
{
  main_screen(1);
}

function mk_page_patchapprove($id)
{
  auth_user_error();
  admin_patchapprove($id, 0);
  header('Location: '.location_url('?'.$id));
  exit;
}

function mk_page_patchdeapprove($id)
{
  auth_user_error();
  admin_patchapprove($id, 1);
  header('Location: '.location_url('?'.$id));
  exit;
}

function mk_page_patchdel($id)
{
   auth_user_error();
   admin_patchdel($id);
   header('Location: '.location_url('?queued'));
   exit;
}

function mk_page_update($id) /* user submits an update to another patch */
{
  $title = 'Submit an update';
  page_header(array('title'=>$title));
  echo '<h3 class="title">'.$title.'</h3>';
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    patch_update_insert($_POST, $id);
  } else
    patch_update_edit($id);
  html_footer();
}

function mk_page_patchupdate($id)
{
   auth_user_error();
   page_header();
   admin_patch_update($id);
   html_footer();
}

function mk_page_patchedit($id)
{
   auth_user_error();
   $title = 'Edit patch data';
   page_header(array('title'=>$title));
   echo '<h3 class="title">'.$title.'</h3>';
   echoid('Warning: Changes will be done directly to the patch data, not go to the queue.');
   if ($_SERVER['REQUEST_METHOD'] == 'POST') {
     admin_patch_replace($_POST, $id);
     patch_add_form($_POST, 1);
   } else
     admin_patchedit($id);
   html_footer();
}

function mk_page_commentedit($id)
{
   auth_user_error();
   if ($_SERVER['REQUEST_METHOD'] == 'POST') {
     admin_comment_replace($_POST);
     header('Location: '.location_url('?'.$_POST['patch']));
     //patch_add_form($_POST, 1);
   } else {
     page_header(array('js_limittext'=>1));
     echoid('Warning: Changes will go live directly.');
     admin_commentedit($id);
     html_footer();
   }
}


function mk_page_commentdel($id)
{
  auth_user_error();
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patchid = admin_commentdel($id);
    if ($patchid) {
      header('Location: '.location_url('?'.$patchid));
      exit;
    }
  } else {
    page_header(array('js_limittext'=>1));
    echoid('Warning: This will delete the comment permanently.');
    admin_querycommentdel($id);
    html_footer();
  }
}

/*********************************************************/

function convert_texttags_html($str, $remove=0)
{
  $lvl = 0;
  do {
    $origstr = $str;
    $repla = array('/\[url\]([^"[]+)\[\/url\]/','/\[quote:([^]]+)\](.+)\[\/quote\]/');

    if ($lvl&1) $cls = 'oddqc';
    else $cls = 'evenqc';

    if ($remove) $reparr = array('$1','');
    else $reparr = array('<A href="$1">$1</A>', '<DIV class="quotedcomment '.$cls.'"><SPAN class="quotednick">$1 wrote:</SPAN><BR>$2</DIV>');

    $str = preg_replace($repla, $reparr, $str);
    $lvl++;
  } while ($str != $origstr);
  return $str;
}

function mk_text_viewable($str, $remove=0)
{
  $str = str_htmlize_quotes($str);
  $str = str_replace("\n", "<br>", $str);
  $str = convert_texttags_html($str, $remove);
  return $str;
}

function main_screen($login=0)
{
  if ($login)
    page_header(array('need_id'=>1));
  else
    page_header();
  newest_patches();
  newest_comments();
  html_footer();
}

function get_patchdata_errstr($data, $xnded = 0)
{
  global $patch_descs_maxlen;
  $errstr = '';
  if (strlen($data['pname']) < 1)  $errstr .= 'Name required.<br>';
  if (strlen($data['ver']) < 1)    $errstr .= 'Version required.<br>';
  if (strlen($data['author']) < 1) $errstr .= 'Author required.<br>';
  if (strlen($data['file']) < 1)   $errstr .= 'File required.<br>';
  if ($patch_descs_maxlen > 0)
    if (strlen($data['descs']) < 1)  $errstr .= 'Short description required.<br>';
  if ($xnded)
    if (strlen($data['dlurl']) < 1)  $errstr .= 'Patch download URL required.<br>';
  return $errstr;
}

function admin_patch_replace($data, $doid)
{
  $errstr = '';

  patch_data_trim($data);

  $data['changed'] = date("Y-m-d H:i:s");

  $errstr = get_patchdata_errstr($data);

  patch_data_clip($data);

  $errstr .= nhquestion_validate($data);

  if (($data['localdl'] == 'on') || ($data['localdl'] == 't'))
    $data['localdl'] = 1;
  else
    $data['localdl'] = 0;

  if (!$errstr) {
    //if (!($data['preview'] == "on")) {
      $etsi = array('id'=>$doid);
      unset($data['id']);

      $connection = db_connect();
      $res = db_update($connection, 'patches', $data, $etsi);
      if ($res) {
	echoid('Patch data changed.','notice');
      } else echoid('Something went wrong when inserting the data.');
      db_close($connection);
      /*} else {
      echo "<table class=patchpreview>";
      patch_show_tablerow($data, 0, 0);
      echo "</table>\n";
      }*/
  } else echoid($errstr);
}

function admin_comment_replace($data)
{
  global $comment_name_maxlen, $comment_text_maxlen;

  comment_data_trim($data);

  $data['username'] = mb_substr($data['username'], 0, $comment_name_maxlen);
  $data['text']     = mb_substr($data['text'],     0, $comment_text_maxlen);

  $etsi = array('id'=>$data['id']);

  $connection = db_connect();
  $res = db_update($connection, 'comments', $data, $etsi);
  db_close($connection);
  return $res;
}


function admin_patch_update($doid)
{
  $errstr = '';

  if (auth_user() && isset($doid) && preg_match('/^[0-9]+$/',$doid)) {
    $data = patch_get_data($doid);

    patch_data_trim($data);

    $data['changed'] = date("Y-m-d H:i:s");

    $errstr .= get_patchdata_errstr($data);

    patch_data_clip($data);

    /*$errstr .= nhquestion_validate($data);*/

    if (($data['localdl'] == 'on') || ($data['localdl'] == 't'))
      $data['localdl'] = 1;
    else
      $data['localdl'] = 0;

    if (!$errstr) {
      //if (!($data['preview'] == "on")) {
      $etsi = array('id'=>$data['patchref']);
      $patchref = $data['patchref'];
      unset($data['patchref']);
      unset($data['id']);
      unset($data['added']);
      unset($data['queue']);

      $connection = db_connect();
      $res = db_update($connection, 'patches', $data, $etsi);
      if ($res) {
	admin_patchdel($doid);
	patch_calc_rating($patchref);
	echoid('Patch data changed.','notice');
      } else echoid('Something went wrong when inserting the data.');
      /*db_close($connection);*/
      /*} else {
       echo "<table class=patchpreview>";
       patch_show_tablerow($data, 0, 0);
       echo "</table>\n";
       }*/
    } else echoid($errstr);
  }
}

function admin_patchedit($id)
{
  if (auth_user() && isset($id) && preg_match('/^[0-9]+$/',$id)) {
    $sql = 'SELECT * FROM patches WHERE id='.$id;

    $connection = db_connect();
    $myresult = db_query($connection, $sql);
    $numrows = db_numrows($myresult);
    if ($numrows <= 0) echoid('Nothing to show?!');
    else {
      $data = db_get_rowdata($myresult, 0);
      patch_add_form($data, 1);
    }
    db_close($connection);
  }
}

function admin_commentedit($id)
{
  if (auth_user() && isset($id) && preg_match('/^[0-9]+$/',$id)) {
    $sql = 'SELECT * FROM comments WHERE id='.$id;

    $connection = db_connect();
    $myresult = db_query($connection, $sql);
    $numrows = db_numrows($myresult);
    if ($numrows <= 0) echoid('Nothing to show?!');
    else {
      $data = db_get_rowdata($myresult, 0);
      comment_add_form($id, $data, 1);
    }
    db_close($connection);
  }
}

function admin_querycommentdel($id)
{
  if (auth_user() && isset($id) && preg_match('/^[0-9]+$/',$id)) {
    $sql = 'SELECT * FROM comments WHERE id='.$id;

    $connection = db_connect();
    $myresult = db_query($connection, $sql);
    $numrows = db_numrows($myresult);
    if ($numrows <= 0) echoid('Nothing to show?!');
    else {
      $data = db_get_rowdata($myresult, 0);
      unset($data['id']);
      echo '<table class="commentshow">';
      comment_show_tablerow($data);
      echo tablerowd('<form method="POST" action="'.phpself_querystr().'"><input type="submit" value="Delete"></form>');
      echo '</table>';
    }
    db_close($connection);
  }
}

function patch_update_edit($id)
{
  if (isset($id) && preg_match('/^[0-9]+$/',$id)) {
    $sql = 'SELECT * FROM patches WHERE id='.$id;

    if (!auth_user())
      $sql .= ' AND queue=FALSE';

    $connection = db_connect();
    $myresult = db_query($connection, $sql);
    $numrows = db_numrows($myresult);
    if ($numrows > 0) {
      $data = db_get_rowdata($myresult, 0);
      $data['patchref'] = $id;
      patch_add_form($data, 2);
    }
    db_close($connection);
  }
}

function patch_update_insert($data, $id)
{
  if (isset($id) && preg_match('/^[0-9]+$/',$id)) {
    $data['patchref'] = $id;
    patch_add_post($data);
  }
}

function patch_get_data($id)
{
  $data = NULL;
  if (isset($id) && preg_match('/^[0-9]+$/',$id)) {
    $sql = 'SELECT * FROM patches WHERE id='.$id;

    $connection = db_connect();
    $myresult = db_query($connection, $sql);
    $numrows = db_numrows($myresult);
    if ($numrows > 0)
      $data = db_get_rowdata($myresult, 0);
    /*db_close($connection);*/
  }
  return $data;
}


function patch_download($id, $dload)
{
  if (isset($id) && preg_match('/^[0-9]+$/',$id)) {
    $sql = 'SELECT file,fsize,fdata FROM patches WHERE id='.$id;

    if (!auth_user())
      $sql .= ' AND queue=FALSE AND localdl=TRUE';

    $connection = db_connect();
    $myresult = db_query($connection, $sql);
    $numrows = db_numrows($myresult);
    if ($numrows > 0) {
      $data = db_get_rowdata($myresult, 0);

      if ($data['fdata']) {
	header('Content-Type: text/plain');
	header('Content-Length: '.$data['fsize']);
	if (isset($dload))
	  header('Content-Disposition: attachment; filename="'.$data['file'].'"');
	echo $data['fdata'];
      }
    }
    db_close($connection);
  }
}


function admin_patchapprove($id, $state)
{
  if (auth_user() && isset($id) && preg_match('/^[0-9]+$/',$id)) {
    if ($state) $s = 'TRUE';
    else $s = 'FALSE';

    $sql = 'UPDATE patches SET queue='.$s.' WHERE id='.$id;

    $connection = db_connect();
    $myresult = db_query($connection, $sql);

    db_close($connection);
  }
}

function admin_patchdel($id)
{
  if (auth_user() && isset($id) && preg_match('/^[0-9]+$/',$id)) {

    $connection = db_connect();

    $sql = 'DELETE FROM comments WHERE patch='.$id.'; ';
    $sql .= 'DELETE FROM patches WHERE id='.$id;

    $myresult = db_query($connection, $sql);

    db_close($connection);
  }
}

function admin_commentdel($id)
{
  if (auth_user() && isset($id) && preg_match('/^[0-9]+$/',$id)) {

    $connection = db_connect();

    $sql = 'SELECT patch FROM comments WHERE id='.$id;
    $myresult = db_query($connection, $sql);
    $rows = db_numrows($myresult);
    if ($rows > 0)
      $patchid = db_result($myresult, 0, 0);

    $sql = 'DELETE FROM comments WHERE id='.$id;

    $myresult = db_query($connection, $sql);

    if ($patchid) patch_calc_rating($patchid);

    db_close($connection);
  }
  return $patchid;
}

function page_header($data = null)
{
  global $nhpatchdb_version;

  $num_accessible_patches = num_patches(0);
  $num_queued_patches = num_patches(1);

  html_header($data);

  echo '<h2 class="maintitle">NetHack Patch Database';
  echo '<span class="versnum"> '.$nhpatchdb_version.'</span>';
  echo '</h2>';

  echo '<div class="patchcount">';
  echo $num_accessible_patches.' patches';
  if (auth_user() && ($num_queued_patches > 0))
    echo ', '.$num_queued_patches.' queued';
  echo '</div>';

  $menuarr = array(menulinkurl(phpself_querystr(''), 'Main'),
		   menulinkurl(phpself_querystr('add'), 'Add'));

  if (auth_user() && ($num_queued_patches > 0))
    $menuarr[] = menulinkurl(phpself_querystr('queued'), 'Queued');

  if ($num_accessible_patches > 0) {
    $menuarr[] = menulinkurl(phpself_querystr('browse'), 'Browse');
    $menuarr[] = menulinkurl(phpself_querystr('search'), 'Search');
    $menuarr[] = menulinkurl(phpself_querystr('random'), 'Random');
  }

  $menuarr[] = menulinkurl(phpself_querystr('contact'), 'Contact');
  $menuarr[] = menulinkurl(phpself_querystr('faq'), 'FAQ');

  echo '<div class="menu">'.make_menu_string($menuarr);
  echo '</div>';

  echo '<hr>';
}

function patch_add_post(&$data)
{
  global $patchfile_maxlen;
  $errstr = '';

  patch_data_trim($data);

  $data['added'] = $data['changed'] = date("Y-m-d H:i:s");

  if ((strlen($data['dlurl']) > 1) && !preg_match('/^(http|ftp):\/\//', $data['dlurl'])) {
    $data['dlurl'] = 'http://'.$data['dlurl'];
  }

  unset($data['file']);
  unset($data['fsize']);
  unset($data['fdata']);
  // handle uploaded patch file
  $filedata = $_FILES['file'];
  if (isset($filedata) && (isset($filedata['tmp_name'])) &&
      ($filedata['error'] == 0) && is_uploaded_file($filedata['tmp_name'])) {
    if (preg_match('/\.(dif|diff|patch|txt)$/', $filedata['name'])) {
      $data['file'] = $filedata['name'];
      $data['fsize'] = $filedata['size'];
      $data['fdata'] = file_get_contents($filedata['tmp_name']);
      if (strlen($data['dlurl']) < 1) {
	$data['localdl'] = 'on'; // no download URL, allow local dl
      }
    } else $errstr .= 'Patch file is not a plain text diff file.<br>';
  } else {
    $data['file'] = basename($data['dlurl']);
    if (preg_match('/\.(dif|diff|patch|txt)$/', $data['file'])) {
      $data['fdata'] = substr(@file_get_contents($data['dlurl']), 0, -1);
      $data['fsize'] = strlen($data['fdata']);
    } else $errstr .= 'Patch file is not a plain text diff file.<br>';
  }

  if (($patchfile_maxlen > 0) && ($data['fsize'] > $patchfile_maxlen))
    $errstr .= 'Patch file too big, max allowed size is '.$patchfile_maxlen.' bytes.<br>';

  //$data['xinfo'] = NULL; // No admin info for new submissions
  $data['queue'] = TRUE; // It's in the queue, waiting approval

  if (($data['localdl'] == 'on') || ($data['localdl'] == 't'))
    $data['localdl'] = 1;
  else
    $data['localdl'] = 0;

  if (preg_match('/^$/', trim($data['ver']))) $data['ver'] = '0';

  $errstr .= get_patchdata_errstr($data, (($data['localdl']) ? 0 : 1));

  patch_data_clip($data, true);

  $data['rating'] = 0; // 0% rating by default

  $errstr .= nhquestion_validate($data);

  if (!$errstr) {
    // insert the data to the db
    //if (!($data['preview'] == "on")) {
      unset($data['preview']);
      unset($data['nhquestionanswer']);
      unset($data['nhquestionid']);

      $connection = db_connect();
      $res = db_insert($connection, 'patches', $data);
      db_close($connection);
      if ($res) {
	echoid('Patch added to the queue.<br>'.
	       'It will show up in the database once the admins have reviewed it.', 'notice');
	patch_data_clear($data);
      } else echoid('Something went wrong when inserting the data.');
      /*} else {
      echo "<table class=patchpreview>";
      patch_show_tablerow($data, 0, 0);
      echo "</table>\n";
      }*/

  } else echoid($errstr);
}

// trim off extra whitespace from user-submitted patch data
function patch_data_trim(&$data)
{
  $data['pname']  = trim($data['pname']);
  $data['ver']    = trim($data['ver']);
  $data['author'] = trim($data['author']);
  $data['descs']  = trim($data['descs']);
  $data['descl']  = trim($data['descl']);
  $data['url']    = trim($data['url']);
  $data['dlurl']  = trim($data['dlurl']);
}

// clear the patch data from array, except for id-number.
function patch_data_clear(&$data)
{
  unset($data['pname']);
  unset($data['ver']);
  unset($data['author']);
  unset($data['descs']);
  unset($data['descl']);
  unset($data['url']);
  unset($data['dlurl']);
  unset($data['file']);
  unset($data['fsize']);
  unset($data['nhfor']);
  unset($data['xinfo']);
  unset($data['queue']);
  unset($data['added']);
  unset($data['changed']);
}

// Clip patch data into correct ranges
function patch_data_clip(&$data, $dourls=false)
{
  global $patch_descs_maxlen, $patch_descl_maxlen;
  $data['pname']  = mb_substr($data['pname'],  0, 80);
  $data['ver']    = mb_substr($data['ver'],    0, 10);
  $data['author'] = mb_substr($data['author'], 0, 80);
  $data['file']   = mb_substr($data['file'],   0, 255);
  if ($patch_descs_maxlen > 0)
    $data['descs']  = mb_substr($data['descs'],  0, $patch_descs_maxlen);
  if ($patch_descl_maxlen > 0)
    $data['descl']  = mb_substr($data['descl'],  0, $patch_descl_maxlen);
  if ($dourls) {
    $data['url']    = mb_substr($data['url'],    0, 255);
    $data['dlurl']  = mb_substr($data['dlurl'],  0, 255);
  }
}

function nhquestion_validate(&$data)
{
  global $nethack_questions;

  $qid = $data['nhquestionid'];
  $qanswer = $data['nhquestionanswer'];

  unset($data['nhquestionid']);
  unset($data['nhquestionanswer']);

  $errstr = NULL;
  if (strlen($qanswer) == 1) {
    if (!preg_match('/^[0-9]+$/',$qid) || ($qid > count($nethack_questions)-1))
      $errstr .= 'Wrong question id.<br>';
    if ($qanswer != $nethack_questions[$qid]['char'])
      $errstr .= 'Wrong answer to the question.<br>';
  } else $errstr .= 'Wrong answer to the question.<br>';
  return $errstr;
}

function nhquestion_inputbar()
{
  global $nethack_questions;
  $qn = rand(0,count($nethack_questions)-1);
  $ret = '<p class="nhquestion">';
  $ret .= 'You will need to answer the following question correctly: ';
  $ret .= 'What symbol represents '.$nethack_questions[$qn]['text'].'? ';
  $ret .= '<input type="text" name="nhquestionanswer" size="1" maxlength="1">';
  $ret .= '<input name="nhquestionid" type="hidden" value="'.$qn.'">';
  $ret .= '</p>';
  return $ret;
}

function is_patch_queued($id)
{
  $ret = NULL;
  if (isset($id) && preg_match('/^[0-9]+$/',$id)) {
    $sql = 'SELECT queue FROM patches WHERE id='.$id;

    $connection = db_connect();
    $myresult = db_query($connection, $sql);
    $numrows = db_numrows($myresult);
    if ($numrows > 0)
      $ret = db_result($myresult, 0, 0);
    db_close($connection);
  }
  return $ret;
}

function comment_add_post($data)
{
  global $comment_max_score, $comment_text_maxlen, $comment_name_maxlen;

  if (is_patch_queued($data['patch']) == 'f') {

    comment_data_trim($data);

    if (!($data['preview'] == 'on'))
      $errstr .= nhquestion_validate($data);
    if (!$errstr) {
      $data['added'] = date("Y-m-d H:i:s");

      if (strlen($data['username']) < 1)  $errstr .= 'Username required.<br>';
      if (strlen($data['text']) < 1)  $errstr .= 'Comment required.<br>';
      if (strlen($data['text']) > $comment_text_maxlen)
	$errstr .= 'Comment too long by '.strlen($data['text'])-$comment_text_maxlen.' chars.<br>';
      if (!preg_match('/^[0-9]+$/',$data['patch'])) $errstr .= 'Wrong patch ID number.<br>';
      if ($data['score'] == -1) unset($data['score']);
      else if (preg_match('/^[0-9]+$/',$data['score']) &&
	       (($data['score'] < 0) || ($data['score'] > $comment_max_score))) $errstr .= 'Wrong score number.<br>';

      if (!$errstr) {
	$data['username'] = mb_substr($data['username'], 0, $comment_name_maxlen);
	$data['text']     = mb_substr($data['text'],     0, $comment_text_maxlen);
      }
    }

    $remembername = $data['remembername'];
    unset($data['remembername']);

    if (!$errstr) {
      if (!($data['preview'] == 'on')) {
	$connection = db_connect();
	$res = db_insert($connection, 'comments', $data);
	if ($res) {
	  patch_calc_rating($data['patch']);
	  comment_data_clear($data);
	} else echoid('Something went wrong when adding a comment.');
	db_close($connection);
      }
    } else echoid($errstr);

    if ($remembername) $data['remembername'] = $remembername;
  }
}

function patch_calc_rating($patchid)
{
  global $comment_max_score;
  if (isset($patchid) && preg_match('/^[0-9]+$/',$patchid)) {
    $rating = patch_rating($patchid);
    $yhteys = db_connect();
    $tulos = db_query($yhteys, 'UPDATE patches SET rating='.$rating*(100/$comment_max_score).' WHERE id='.$patchid);
    db_free_result($tulos);
  }
}

function comment_data_trim(&$data)
{
  $data['patch'] = trim($data['patch']);
  $data['username'] = trim($data['username']);
  $data['score'] = trim($data['score']);
  $data['text'] = trim($data['text']);
}

function comment_data_clear(&$data)
{
  unset($data['patch']);
  unset($data['username']);
  unset($data['score']);
  unset($data['text']);
}

function comment_add_form($id, $data, $editing = NULL)
{
  global $comment_max_score, $comment_text_maxlen, $comment_name_maxlen;

  if (($data['score'] == "-1") || ($data['score'] == -1)) unset($data['score']);

  if (!$editing && ($data['preview'] == 'on') && (strlen(trim($data['text'])) > 0)) {
    echo '<h3 class="title">Comment Preview</h3>';
    echo '<table class="commentshow">';
    comment_show_tablerow($data, $x, 0);
    echo '</table>';
  } else if (!$data['quoted'] && !$editing) comment_data_clear($data);

  if ($editing) {
    echo '<h3 class="title" id="addcomment">Edit a comment</h3>';
    echo '<form name="commentform" method="POST" action="'.phpself_querystr(array('commentedit'=>$id)).'">';
    echo '<input name="id" type="hidden" value="'.$data['id'].'">';
    echo '<input name="patch" type="hidden" value="'.$data['patch'].'">';
    echo '<input name="added" type="hidden" value="'.$data['added'].'">';
  } else {
    echo '<h3 class="title" id="addcomment">Add a comment</h3>';
    echo '<form name="commentform" method="POST" action="'.phpself_querystr($id).'">';
    echo '<input name="patch" type="hidden" value="'.$id.'">';
  }
  if (isset($_COOKIE['remembername']) && !isset($data['username']) && !isset($editing)) {
    $data['username'] = $_COOKIE['remembername'];
    $data['remembername'] = 'on';
  }
  echo '<table class="commentform">';

  $trd = get_input_postdata('text','username',$data, array('size'=>$comment_name_maxlen,'maxlength'=>$comment_name_maxlen));
  if (!$editing)
    $trd .= ' (<label>'.get_input_postdata('checkbox','remembername',$data).' Remember name</label>)';

  echo tablerowd(array('Name', $trd, 'required'));

  if (!isset($data['score']) || is_null($data['score']))
    $nsel = 0;
  $sel = array(''=>-1);
  for ($x = 0; $x <= $comment_max_score; $x++) {
    $sel = $sel + array($x=>$x);
    if (isset($data['score']) && ($data['score'] == $x)) $nsel = $x+1;
  }

  echo tablerowd(array('Rating', mk_selectbox('score',$sel,$nsel).' (0 is worst, '.$comment_max_score.' is best)', '&nbsp;'));

  $keydownfunc = "limittext(document.commentform.text,'commentlimit',".$comment_text_maxlen.")";

  echo tablerowd(array('Comment',get_input_postdata('textarea', 'text', $data, array('rows'=>5,'cols'=>70,'style'=>'width:90%;','onKeyDown'=>$keydownfunc,'onKeyUp'=>$keydownfunc, 'onFocus'=>$keydownfunc)),'required'));

  echo tablerowd(array('','You can use up to <span id="commentlimit">'.$comment_text_maxlen.'</span> characters.',''));
  echo tablerowd(array('','Only supported tag is [url]clickable_link[/url].',''));

  $trd = array(array('colspan'=>3));

  if (!$editing) {
    echo tablerowd('<label><input type="checkbox" checked name="preview">Preview</label>', $trd);
    echo tablerowd(nhquestion_inputbar(), $trd);
    $btn = 'Add comment';
  } else {
    $btn = 'Save changes';
  }
  echo tablerowd('<input type="submit" value="'.$btn.'"><input type="reset" value="Reset">', $trd);
  echo '</table>';
  echo '</form>';
}

function contact_form()
{
  global $admin_public_email;

  $cs = array(array('colspan'=>'2'));

  echo '<form method="POST" action="'.phpself_querystr('contact').'">';
  echo '<table class="contactform">';

  echo tablerowd('You can either fill out the following form, or email <i>'.$admin_public_email.'</i> directly.', $cs);
  echo tablerowd(array('Your name and EMail','<input type="text" name="from" size="50" maxlength="120" style="width:90%;">'));
  echo tablerowd(array('Subject','<input type="text" name="subject" size="50" maxlength="120" style="width:90%;">'));
  echo tablerowd(array('Comment','<textarea name="text" rows="7" cols="50" style="width:90%;"></textarea>'));
  echo tablerowd(nhquestion_inputbar(), $cs);
  echo tablerowd('<input type="submit" value="Send"><input type="reset" value="Reset">', $cs);
  echo '</table>';
  echo '</form>';
}

function contact_post($data)
{
  global $admin_hidden_email;

  $errstr = '';

  $errstr .= nhquestion_validate($data);

  $data['from'] = trim($data['from']);
  $data['subject'] = trim($data['subject']);
  $data['text'] = trim($data['text']);

  if (strlen($data['from']) < 1) $errstr .= 'Name required.<br>';
  if (strlen($data['subject']) < 1) $errstr .= 'Subject required.<br>';
  if (strlen($data['text']) < 1) $errstr .= 'Comment required.<br>';

  if (strlen($data['from']) > 120) $errstr .= 'Name too long.<br>';
  if (strlen($data['subject']) > 120) $errstr .= 'Subject too long.<br>';
  if (strlen($data['text']) > 8192) $errstr .= 'Comment too long.<br>';

  if (!$errstr) {
    $headers = 'From: '.$data['from']."\r\n";
    $subject = '[NHPATCHDB] '.$data['subject'];
    if (mail($admin_hidden_email, $subject, $data['text'], $headers))
      echoid('Mail sent', 'notice');
    else
      echoid('An error was encountered while trying to send mail');
  } else echoid($errstr);
}

function patch_add_form($data, $edit = null)
{
  global $timestamp_format,$active_nethack_ver, $patch_descs_maxlen, $patch_descl_maxlen;

  echo '<form method="POST" enctype="multipart/form-data" action="'.phpself_querystr().'">';
  echo '<table class="patchform">';

  if ($edit)
    echo '<input type="hidden" name="added" value="'.date("Y-m-d H:i:s",strtotime($data['added'])).'">';
  if (isset($data['patchref']))
    echo '<input type="hidden" name="patchref" value="'.$data['patchref'].'">';

  if (auth_user() && isset($data['id']))
    echo tablerowd(array('ID Number',$data['id']));
  echo tablerowd(array('Patch name',get_input_postdata('text', 'pname', $data, array('size'=>80, 'maxlength'=>80,'style'=>'width:90%;')),'required'));
  echo tablerowd(array('Version',get_input_postdata('text', 'ver', $data, array('size'=>10, 'maxlength'=>10,'style'=>'width:90%;'))));
  echo tablerowd(array('Author',get_input_postdata('text', 'author', $data, array('size'=>80, 'maxlength'=>80,'style'=>'width:90%;')),'required'));
  echo tablerowd(array('Author\'s Homepage or EMail', get_input_postdata('text', 'url', $data, array('size'=>80, 'maxlength'=>255,'style'=>'width:90%;'))));
  if ($edit == 1) {
    $trd = get_input_postdata('text', 'file', $data);
    if (auth_user())
      $trd .= make_menu_string(array(menulinkurl(phpself_querystr(array('viewdiff'=>$data['id'])), 'View')),1);
    echo tablerowd(array('Patch file', $trd));
  }
  if (auth_user() && $edit)
    echo tablerowd(array('<label for="checkbox_localdl">Allow local download</label>', get_input_postdata('checkbox', 'localdl', $data)));
  if ((!$edit) || ($edit == 2))
    echo tablerowd(array('Patch file','<input name="file" type="file" value="'.(isset($data['file']) ? $data['file'] : '').'" size="50">'));
  echo tablerowd(array('Patch Download URL',get_input_postdata('text', 'dlurl', $data, array('size'=>80, 'maxlength'=>255,'style'=>'width:90%;'))));

  echo tablerowd(array('For', db_make_dropdown_table('variant', 'nhfor', (isset($data['nhfor']) ? $data['nhfor'] : $active_nethack_ver))));

  if ($patch_descs_maxlen > 0)
    echo tablerowd(array('Short Description',get_input_postdata('text', 'descs', $data, array('size'=>80,'maxlength'=>$patch_descs_maxlen,'style'=>'width:90%;')),'required'));
  else
    echo tablerowd(array('Short Description',get_input_postdata('text', 'descs', $data, array('size'=>80,'maxlength'=>$patch_descs_maxlen,'style'=>'width:90%;'))));
  echo tablerowd(array('Long Description',get_input_postdata('textarea', 'descl', $data, array('rows'=>7, 'cols'=>70,'style'=>'width:90%;')), 'Max. '.$patch_descl_maxlen.' chars'));
  if (auth_user())
    echo tablerowd(array('Admin Info', get_input_postdata('textarea', 'xinfo', $data, array('rows'=>7, 'cols'=>70,'style'=>'width:90%;'))));

  $trd = array(array('colspan'=>3));
  echo tablerowd(nhquestion_inputbar(), $trd);
  echo tablerowd('<input type="submit" value="Submit"><input type="reset" value="Reset">', $trd);

  echo '</table>';
  echo '</form>';
}

function newest_patches()
{
  $sql = 'SELECT * FROM patches WHERE queue=FALSE ORDER BY added DESC,pname LIMIT 15';

  $connection = db_connect();
  $myresult = db_query($connection, $sql);
  $numrows = db_numrows($myresult);
  $showtabletype = 2;

  if ($numrows > 0) {
      echo '<table class="patchlatest">';
      $data = array('tablename'=>'Recently added patches') + db_get_rowdata($myresult, 0);
      patch_show_tableheader($data, $showtabletype);
      for ($x = 0; $x < $numrows; $x++) {
	$data = db_get_rowdata($myresult, $x);
	patch_show_tablerow($data, $x, $showtabletype);
      }
      echo '</table>';
  }
  db_close($connection);
}

function newest_comments()
{
  $sql = 'SELECT comments.* FROM comments,patches WHERE (comments.patch=patches.id) AND (patches.queue=FALSE) ORDER BY comments.added DESC LIMIT 15';

  $connection = db_connect();
  $myresult = db_query($connection, $sql);
  $numrows = db_numrows($myresult);
  $showtabletype = 2;

  if ($numrows > 0) {
      echo '<table class="commentlatest">';
      $data = array('tablename'=>'Newest comments') + db_get_rowdata($myresult, 0);
      comment_show_tableheader($data, $showtabletype);
      for ($x = 0; $x < $numrows; $x++) {
	$data = db_get_rowdata($myresult, $x);
	comment_show_tablerow($data, $x, $showtabletype);
      }
      echo '</table>';
  }
  db_close($connection);
}

function patch_orderby_str($data)
{
  $ret = '';
  $rev = 0;
  $addname = 1;

  if (!isset($data['sort'])) $data['sort'] = NULL;

  switch ($data['sort']) {
  case 'id':
    $ret = ' ORDER BY id';
    break;
  default:
  case 'name':
    $ret = ' ORDER BY pname';
    $addname = 0;
    break;
  case 'author':
    $ret = ' ORDER BY author';
    break;
  case 'ver':
    $ret = ' ORDER BY nhfor';
    break;
  case 'rating':
    $ret = ' ORDER BY rating';
    $rev = 1;
    break;
  case 'added':
    $ret = ' ORDER BY added';
    $rev = 1;
    break;
  case 'changed':
    $ret = ' ORDER BY changed';
    $rev = 1;
    break;
  }

  if ($rev) {
    if (!$data['revsort']) $ret .= ' DESC';
    else unset($data['revsort']);
  }

  if (isset($data['revsort'])) $ret .= ' DESC';

  if ($addname) $ret .= ', pname';

  return $ret;
}

function patch_browse($show_queued = NULL)
{
  global $browse_page_height;
  $sqlq = 'SELECT * FROM patches';
  if ($show_queued && auth_user()) {
    $sqlq .= ' WHERE queue=TRUE';
    if (!isset($_GET['sort'])) $_GET['sort'] = 'id';
  } else {
    $sqlq .= ' WHERE queue=FALSE';
  }

  $sqlq .= patch_orderby_str($_GET);

  $npage = 1;
  $pagehei = (isset($_GET['pagehei']) ? $_GET['pagehei'] : $browse_page_height);
  $startpage = (isset($_GET['page']) ? $_GET['page'] : 0);
  $showtabletype = 1;

  if (!preg_match('/^[0-9]+$/', $startpage)) $startpage = 0;
  if (!preg_match('/^[0-9]+$/', $pagehei)) $startpage = $browse_page_height;

  $connection = db_connect();
  $myresult = db_query($connection, $sqlq);
  $numrows = db_numrows($myresult);

  if ($numrows <= 0) echoid('Nothing to show.');
  else {

    if ($startpage >= ($numrows/$pagehei)) $startpage = intval($numrows/$pagehei);

    $pgcontrols = show_pagecontrols($startpage, $numrows, $pagehei);
    echo $pgcontrols . '<table class="patchbrowse">';
    patch_show_tableheader(db_get_rowdata($myresult, 0), $showtabletype, 1);
    for ($x = ($startpage * $pagehei); $x < $numrows; $x++) {
      $data = db_get_rowdata($myresult, $x);
      patch_show_tablerow($data, $x, $showtabletype);
      $npage++;
      if ($npage > $pagehei) break;
    }
    echo '</table>';
    echo $pgcontrols;
  }
  db_close($connection);
}

function patch_rating($id)
{
  $ret = '0';
  if (isset($id) && preg_match('/^[0-9]+$/',$id)) {
    $yhteys = db_connect();
    $tulos = db_query($yhteys, 'SELECT AVG(score) FROM comments WHERE patch='.$id);
    $rows = db_numrows($tulos);
    if ($rows > 0)
      $ret = db_result($tulos, 0, 0);
    db_free_result($tulos);
  }
  return mb_substr($ret,0,3);
}

function patch_show($id, $commentpostdata = NULL)
{
  if (isset($id) && preg_match('/^[0-9]+$/',$id)) {

    $sql = 'SELECT * FROM patches WHERE id='.$id;

    if (!auth_user())
      $sql .= ' AND queue=FALSE';

    $connection = db_connect();
    $myresult = db_query($connection, $sql);
    $numrows = db_numrows($myresult);
    if ($numrows <= 0) echoid('Nothing to show?!');
    else {
      for ($x = 0; $x < $numrows; $x++) {
	$data = db_get_rowdata($myresult, $x);
	if (isset($data['patchref']) && auth_user()) $showtabletype = 3;
	else $showtabletype = 0;
	echo '<table class="patchshow">';
	patch_show_tableheader(db_get_rowdata($myresult, 0), $showtabletype);
	patch_show_tablerow($data, 0, $showtabletype);
	echo '</table>';
      }
    }
    db_close($connection);
    if ($numrows > 0) {
      comment_show($id);
      if (!($data['queue'] == 't')) {
	comment_add_form($id, $commentpostdata);
      } else echoid('You cannot comment on queued patches.', 'notice');
    }
  } else echoid('ID number required.');
}

function get_comment_patchid($commentid)
{
  if (isset($commentid) && preg_match('/^[0-9]+$/',$commentid)) {
    $sql = 'SELECT * FROM comments WHERE id='.$commentid;
    $connection = db_connect();
    $myresult = db_query($connection, $sql);
    $numrows = db_numrows($myresult);
    if ($numrows > 0)
      $ret = db_get_rowdata($myresult, 0);
    db_free_result($myresult);
    /*db_close($connection);*/
  }
  return $ret;
}

function num_patchcomments($id, $ratings=0)
{
  $ret = 0;
  if (isset($id) && preg_match('/^[0-9]+$/',$id)) {
    $sql = 'SELECT COUNT(*) FROM comments WHERE patch='.$id;
    if ($ratings)
      $sql .= ' AND (score IS NOT NULL)';
    $connection = db_connect();
    $myresult = db_query($connection, $sql);
    $ret = db_result($myresult, 0, 0);
    db_free_result($myresult);
    /*db_close($connection);*/
  }
  return $ret;
}

function num_patches($queued)
{
  $sql = 'SELECT COUNT(*) FROM patches';
  if ($queued) $sql .= ' WHERE queue=TRUE';
  else $sql .= ' WHERE queue=FALSE';

  $connection = db_connect();
  $myresult = db_query($connection, $sql);
  $ret = db_result($myresult, 0, 0);
  db_free_result($myresult);
  db_close($connection);
  return $ret;
}

// Show all comments that patch $id has
function comment_show($id)
{
  global $comment_page_height;
  if (isset($id) && preg_match('/^[0-9]+$/',$id)) {

    $numrows = num_patchcomments($id);
    $pagehei = $comment_page_height;

    $startpage = (isset($_GET['page']) ? $_GET['page'] : 0);
    $revorder = (isset($_GET['rev']) ? $_GET['rev'] : NULL);
    if (!preg_match('/^[0-9]+$/', $startpage)) $startpage = 0;
    if ($startpage >= ($numrows/$pagehei)) $startpage = intval($numrows/$pagehei);

    $npage = 1;
    $showtabletype = 0;

    $sql = 'SELECT * FROM comments WHERE patch='.$id.' ORDER BY added DESC LIMIT '.$pagehei.' OFFSET '.($startpage*$pagehei);
    $connection = db_connect();
    $myresult = db_query($connection, $sql);
    $curnumrows = db_numrows($myresult);
    if ($numrows > 0)
      echo '<h3 class="title">'.$numrows.' '.($numrows > 1 ? 'Comments' : 'Comment').'</h3>';
    if ($numrows <= 0 || ($curnumrows <= 0)) {
      //echoid("No comments.", "notice");
    } else {
      $pgcontrols = show_pagecontrols($startpage, $numrows, $pagehei);
      echo $pgcontrols . '<table class="commentshow">';
      comment_show_tableheader(db_get_rowdata($myresult, 0), $showtabletype);
      for ($x = 0; $x < $curnumrows; $x++) {
	$data = db_get_rowdata($myresult, ($revorder) ? ($curnumrows-$x-1) : $x);
	comment_show_tablerow($data, $x, $showtabletype);
      }
      echo '</table>';
      echo $pgcontrols;
    }
    db_close($connection);
  }
}

function patch_search_post($sdata)
{
  global $max_search_results;
  $s = trim($sdata['searchbar'].(isset($sdata['author']) ? $sdata['author'] : '').
	    (isset($sdata['name']) ? $sdata['name'] : ''));
  if (strlen($s) <= 0) return;

  if ($s && (strlen($s) > 2)) {
    $maxshow = $max_search_results;
    if (isset($sdata['limit']) && preg_match('/^[0-9]+$/',$sdata['limit']) &&
	($sdata['limit'] < $maxshow) && ($sdata['limit'] > 0))
      $maxshow = $sdata['limit'];

    $slim = '';

    $sql  = 'SELECT * FROM patches WHERE ';
    if ($sdata['searchbar']) {
      $s = strtoupper(preg_replace('/ +/', '%', trim(safe_str($sdata['searchbar']))));
      $slim .= '( ';
      $slim .= "upper(pname) LIKE '%".$s."%'";
      $slim .= " OR upper(author) LIKE '%".$s."%'";
      $slim .= " OR upper(descs) LIKE '%".$s."%'";
      $slim .= " OR upper(descl) LIKE '%".$s."%'";
      $slim .= ' )';
    }

    if (isset($sdata['author'])) {
      if (strlen($slim) > 0) $slim .= ' AND ';
      $s = safe_str($sdata['author']);
      $slim .= "upper(author) LIKE upper('%".$s."%')";
    }

    if (isset($sdata['name'])) {
      if (strlen($slim) > 0) $slim .= ' AND ';
      $s = safe_str($sdata['name']);
      $slim .= "upper(pname) LIKE upper('%".$s."%')";
    }

    $sql .= $slim;

    $sql .= ' AND queue=FALSE';

    $sql .= patch_orderby_str($sdata);

    $sql .= ' LIMIT '.($maxshow+1);

    $connection = db_connect();
    $myresult = db_query($connection, $sql);
    $numrows = db_numrows($myresult);
    $showtabletype = 1;

    if ($numrows <= 0) {
      echoid('No results.');
    } else {
      echo '<table class="patchsearch">';
      patch_show_tableheader(db_get_rowdata($myresult, 0), $showtabletype);
      for ($x = 0; $x < min($numrows,$maxshow); $x++) {
	$data = db_get_rowdata($myresult, $x);
	patch_show_tablerow($data, $x, $showtabletype);
      }
      echo '</table>';
      if ($numrows >= $maxshow)
	echoid('Showing the first '.$maxshow.' matches only.', 'notice');
    }
    db_close($connection);
  } else echoid('Search string must be at least 3 chars long.');
}

function patch_search_form($data)
{
  if (isset($_COOKIE['searchbar']) && !isset($data['searchbar'])) $data['searchbar'] = $_COOKIE['searchbar'];
  if (isset($_COOKIE['searchsort']) && !isset($data['sort'])) $data['sort'] = $_COOKIE['searchsort'];
  if (isset($_COOKIE['searchrev']) && !isset($data['revsort'])) $data['revsort'] = $_COOKIE['searchrev'];

  echo '<form name="searchform" method="POST" action="'.preg_replace('/&/','&amp;',phpself_querystr()).'">';
  echo '<span class="searchform">';
  echo get_input_postdata('text', 'searchbar', $data, array('size'=>30, 'maxlength'=>30));

  /*
  if ($data['supersearch']) {
    echo "<br>";
    echo "AND Author is ";
    echo get_input_postdata("text", "author", $data, array('size'=>"30", 'maxlength'=>"30"));
    echo " AND Patch Name is ";
    echo get_input_postdata("text", "name", $data, array('size'=>"30", 'maxlength'=>"30"));
    echo " AND Limit hits to ";
    echo get_input_postdata("text", "limit", $data, array('size'=>"3", 'maxlength'=>"3"));
  }
  */

  echo '<input type="submit" value="Search">';

  echo ' Sort by ';
  echo mk_selectbox('sort',
		    array('Name'=>'name',
			  'Author'=>'author',
			  'NetHack version'=>'ver',
			  'Rating'=>'rating',
			  'Add date'=>'added',
			  'Change date'=>'changed'),
		    $data['sort']);

  echo '<label>'.get_input_postdata('checkbox','revsort', $data).'Reversed</label>';
  echo '</span>';
  echo '</form>';
}

/*********************************************************/

function comment_show_tableheader($data, $tabletype = 0)
{
  if (isset($data['tablename']))
    echo '<tr><th>'.$data['tablename'].'</th></tr>';
}

function comment_show_tablerow($data, $num = 0, $tabletype = 0)
{
  global $timestamp_format;
  global $strip_html_comments;

  if ((($num+1) & 1) == 1)
    $bgc = 'oddcomment';
  else
    $bgc = 'evencomment';

  $data['username'] = strip_tags($data['username']);

  switch ($tabletype) {
  default:
  case 0:
  case 1:
    if ($strip_html_comments) $data['text'] = strip_tags($data['text']);

    $trd = array('class'=>$bgc);

    echo tablerowd(array((($data['score'] >= 0) ? $data['score'] : '&nbsp;'), '<a name="'.$data['id'].'">'.mk_text_viewable($data['username']).'</a>', date($timestamp_format,strtotime($data['added']))),
		   array(array('class'=>'commentscore','rowspan'=>2), array('class'=>'commentuser'), array('class'=>'commentdate')),
		   $trd);

    $docomment = array();
    if (auth_user() && isset($data['id'])) {
      $docomment[] = menulinkurl(phpself_querystr(array('commentdel'=>$data['id'])), 'Delete');
      $docomment[] = menulinkurl(phpself_querystr(array('commentedit'=>$data['id'])), 'Edit');
    }
    if (isset($data['id']))
      $docomment[] = menulinkurl(phpself_querystr(array($data['patch']=>null, 'quote'=>$data['id'])).'#addcomment', 'Quote');

    echo tablerowd(mk_text_viewable($data['text']).'<span class="commentact">'.make_menu_string($docomment,1).'</span>',
		   array(array('class'=>'commenttext','colspan'=>3)), $trd);

    break;
  case 2: /* for short synopsis, used on main page */
    $patchdata = patch_get_data($data['patch']);

    $data['text'] = convert_texttags_html(str_replace("\n", ' ', str_htmlize_quotes($data['text'])), 1);

    /* 113 is long enough to fit on 2 lines in lynx on a 80 char wide screen */
    if (strlen($data['username'])+strlen($data['text']) > 113)
      $data['text'] = substr($data['text'],0,110-strlen($data['username'])).'...';
    $data['text'] = str_replace("\n", " ", $data['text']);

    $pname = str_htmlize_quotes($patchdata['pname']);

    $trd = array('class'=>$bgc);
    echo tablerowd(menulinkurl(phpself_querystr($data['patch']).'#'.$data['id'],$pname).': '.$data['text'].' --<i>'.$data['username'].'</i>',
		   array(array('class'=>'xlink', 'onClick'=>'parent.location=\''.phpself_querystr($data['patch'].'#'.$data['id']).'\'')), $trd);
    break;
  }
}

function sortable_tableheaderlink($name, $showname, $sortable=1)
{
  if ($sortable) {
    parse_str($_SERVER['QUERY_STRING'], $admstr);
    if (isset($admstr['sort']) && ($admstr['sort'] == $name) && !isset($admstr['revsort'])) $admstr['revsort'] = 1;
    else unset($admstr['revsort']);
    $admstr['sort'] = $name;
    echo '<th>'.make_link(preg_replace('/&/','&amp;',phpself_querystr($admstr)), $showname).'</th>';
  } else echo '<th>'.$showname.'</th>';
}

function patch_show_tableheader($data, $tabletype = 0, $sortlinks = NULL)
{
  switch ($tabletype) {
  default:
  case 0:
    break;
  case 1:
    echo '<tr>';
    echo '<th>#</th>';
    sortable_tableheaderlink('name', 'Name', $sortlinks);
    sortable_tableheaderlink('author', 'Author', $sortlinks);
    sortable_tableheaderlink('ver', 'For', $sortlinks);
    sortable_tableheaderlink('rating', 'Rating', $sortlinks);
    echo '<th>Short description</th>';
    echo '</tr>';
    break;
  case 2:
    if ($data['tablename'])
      echo '<tr><th>'.$data['tablename'].'</th></tr>';
    break;
  }
}

function get_pnamever($data)
{
  $pnamever = str_htmlize_quotes($data['pname']);
  if (!preg_match('/^0$/', $data['ver']))
    $pnamever .= ' '.$data['ver'];
  return $pnamever;
}

function patch_show_tablerow($data, $num = 0, $tabletype = 0)
{
  global $timestamp_format,
    $strip_html_patchdesc;

  if ((($num+1) & 1) == 1)
    $bgc = 'odd';
  else
    $bgc = 'even';

  $data['descs'] = strip_tags($data['descs']);
  $data['author'] = strip_tags($data['author']);
  if ($strip_html_patchdesc)
    $data['descl'] = strip_tags($data['descl']);

  $trd = array('class'=>$bgc);

  $pnamever = get_pnamever($data);

  switch ($tabletype) {
  default:
  case 0: /* for full-blown, all data shown */
    echo tablerowd(array('Name', $pnamever), null, $trd);

    if (isset($data['rating']))
      echo tablerowd(array('Rating', rating_table($data['rating'], num_patchcomments($data['id'], 1))), null, $trd);

    if ($data['url']) {
      if ((strpos($data['url'], 'http://') === FALSE) &&
          (strpos($data['url'], "@"))) $data['url'] = 'mailto:'.munge_email($data['url']);
      $ld = make_link($data['url'], str_htmlize_quotes($data['author']));
    } else {
      $ld = str_htmlize_quotes($data['author']);
    }
    echo tablerowd(array('Author', $ld), null, $trd);

    echo tablerowd(array('For', db_get_variant_name($data['nhfor'])), null, $trd);

    echo tablerowd(array('Description', str_htmlize_quotes($data['descs'])), null, $trd);
    if (strlen($data['descl']) >= 1)
      echo tablerowd(array('&nbsp;', mk_text_viewable($data['descl'])), null, $trd);

    if ($data['dlurl']) $ld = make_link($data['dlurl']);
    else $ld = '';

    if (($data['localdl'] == 't') && $data['file'] && $data['fdata']) {
      if ($ld) $ld .= ' or ';
      $ld .= make_link(phpself_querystr(array('download'=>$data['id'])), 'Get it from us');
    }
    $ld .= get_approx_size($data['fsize']);
    echo tablerowd(array('Download', $ld), null, $trd);

    echo tablerowd(array('Added', date($timestamp_format,strtotime($data['added']))), null, $trd);

    if ($data['changed'] != $data['added'])
      echo tablerowd(array('Changed', date($timestamp_format,strtotime($data['changed']))), null, $trd);

    if (auth_user()) {
      if ($data['xinfo'])
	echo tablerowd(array('Admin Info', $data['xinfo']), null, $trd);

      $ld = (($data['queue'] == 't') ? 'Queued' : 'Accepted');
      if ($data['id']) {
	$menuarr = array();
	if ($data['queue'] == 't') {
	  $menuarr[] = menulinkurl(phpself_querystr(array('patchapprove'=>$data['id'])), 'Approve');
	  $menuarr[] = menulinkurl(phpself_querystr(array('patchdel'=>$data['id'])), 'Delete');
	} else {
	  $menuarr[] = menulinkurl(phpself_querystr(array('patchdeapprove'=>$data['id'])), 'Deapprove');
	}
	$menuarr[] = menulinkurl(phpself_querystr(array('patchedit'=>$data['id'])), 'Edit');
	$menuarr[] = menulinkurl(phpself_querystr(array('viewdiff'=>$data['id'])), 'View Diff');
	$menuarr[] = menulinkurl(phpself_querystr(array('download'=>$data['id'])), 'Download Diff');
	$ld .= ' '.make_menu_string($menuarr, 1);
      }
      echo tablerowd(array('Status', $ld), null, $trd);
    }
    if (!($data['queue'] == 't')) {
      echo tablerowd('<span class="submitupdate">'.menulinkurl(phpself_querystr(array('update'=>$data['id'])), 'Submit an update to this patch').'</span>', array(array('colspan'=>2)), $trd);
    }
    break;
  case 1: /* for browsing the patches */

    echo tablerowd(array(($num+1), make_link(phpself_querystr($data['id']), $pnamever), str_htmlize_quotes($data['author']), db_get_variant_name($data['nhfor']), rating_table($data['rating'], num_patchcomments($data['id'], 1)), str_htmlize_quotes($data['descs'])),
		   array(array(), array('class'=>'xlink', 'onClick'=>'parent.location=\''.phpself_querystr($data['id']).'\'')), $trd);

    break;
  case 2: /* for very short synopsis, used on main page */

    $ld = menulinkurl(phpself_querystr($data['id']),str_htmlize_quotes($pnamever));
    $data['descs'] = strip_tags($data['descs']);
    /* 119 is long enough to fit on 2 lines in lynx on a 80 char wide screen */
    if (strlen($pnamever) + strlen($data['descs']) > 119)
      $data['descs'] = substr($data['descs'],0,116-strlen($pnamever)) . '...';
    $data['descs'] = str_htmlize_quotes(str_replace("\n", " ", $data['descs']));
    $ld .= ' (<span class="newpatchnhver">'.db_get_variant_name($data['nhfor']).'</span>): '.$data['descs'];

    echo tablerowd(array($ld), array(array('class'=>'xlink','onClick'=>'parent.location=\''.phpself_querystr($data['id']).'\'')), $trd);

    break;
  case 3: /* for when reporting differences between patches */

    if ($data['patchref']) $other = patch_get_data($data['patchref']);

    $org = array(array('class'=>'rowname'), array('class'=>'origpatch'));

    if (auth_user()) {
      echo tablerowd(array('ID Number', $data['id']), null, $trd);
      if ($other['id']) {
	echo tablerowd(array('Update to', make_link(phpself_querystr($other['id']), $other['id'])), $org, $trd);
      }
    }

    $pname_old = $pnamever;
    $pname_new = get_pnamever($other);

    echo tablerowd(array('Name', $pname_old), null, $trd);
    if ($pname_new != $pname_old)
      echo tablerowd(array('&nbsp;', $pname_new), $org, $trd);

    if (isset($data['rating'])) {
      echo tablerowd(array('Rating', rating_table($data['rating'], num_patchcomments($data['id'], 1))), null, $trd);
    }

    echo tablerowd(array('Author', str_htmlize_quotes($data['author']).' &lt;'.$data['url'].'&gt;'), null, $trd);
    if (($data['author'] != $other['author']) || ($data['url'] != $other['url']))
      echo tablerowd(array('&nbsp', str_htmlize_quotes($other['author']).' &lt;'.$other['url'].'&gt;'), $org, $trd);

    echo tablerowd(array('For', db_get_variant_name($data['nhfor'])), null, $trd);
    if ($data['nhfor'] != $other['nhfor'])
      echo tablerowd(array('&nbsp;', db_get_variant_name($other['nhfor'])), $org, $trd);

    echo tablerowd(array('Description', str_htmlize_quotes($data['descs'])), null, $trd);
    if ($data['descs'] != $other['descs'])
      echo tablerowd(array('&nbsp;', str_htmlize_quotes($other['descs'])), null, $trd);

    if (strlen($data['descl']) >= 1)
      echo tablerowd(array('&nbsp;', mk_text_viewable($data['descl'])), null, $trd);

    if ($strip_html_patchdesc)
      $other['descl'] = strip_tags($other['descl']);

    if ((strlen($other['descl']) >= 1) && ($data['descl'] != $other['descl']))
      echo tablerowd(array('&nbsp;', mk_text_viewable($other['descl'])), $org, $trd);

    if (auth_user()) {
      echo tablerowd(array('Local Download', (($data['localdl'] == 't') ? 'YES' : 'Not allowed')), null, $trd);
      if ($data['localdl'] != $other['localdl']) {
	  echo tablerowd(array('&nbsp;', (($other['localdl'] == 't') ? 'YES' : 'Not allowed')), $org, $trd);
      }
    }

    echo tablerowd(array('Download URL', make_link($data['dlurl'])), null, $trd);
    if ($data['dlurl'] != $other['dlurl']) {
      echo tablerowd(array('&nbsp;', make_link($other['dlurl'])), $org, $trd);
    }

    if (auth_user()) {
      if ($data['fdata'] == $other['fdata']) {
	$ld = 'Are the same';
	$xld = '&nbsp;';
      } else {
	$ld = make_link(phpself_querystr(array('viewdiff'=>$data['id'])), 'View').get_approx_size($data['fsize']);
	$xld = make_link(phpself_querystr(array('viewdiff'=>$other['id'])), 'View').get_approx_size($other['fsize']);
      }
      echo tablerowd(array('Diffs', $ld), null, $trd);
      echo tablerowd(array('&nbsp;', $xld), $org, $trd);
    }

    echo tablerowd(array('File name', $data['file']), null, $trd);
    if ($data['file'] != $other['file']) {
      echo tablerowd(array('&nbsp;', $other['file']), $org, $trd);
    }

    echo tablerowd(array('Added', date($timestamp_format,strtotime($data['added']))), null, $trd);
    if ($data['added'] != $other['added'])
      echo tablerowd(array('&nbsp;', date($timestamp_format,strtotime($other['added']))), $org, $trd);

    if ($data['changed'] != $data['added'])
      echo tablerowd(array('Changed', date($timestamp_format,strtotime($data['changed']))), null, $trd);

    if (auth_user()) {
      if ($data['xinfo'])
	echo tablerowd(array('Admin Info', $data['xinfo']), null, $trd);
      $ld =  (($data['queue'] == 't') ? 'Queued' : 'Accepted');
      if ($data['id']) {
	$menuarr = array();
	if ($data['queue'] == 't') {
	  $menuarr[] = menulinkurl(phpself_querystr(array('patchupdate'=>$data['id'])), 'Update patch '.$other['id']);
	  $menuarr[] = menulinkurl(phpself_querystr(array('patchdel'=>$data['id'])), 'Delete this update');
	} else {
	  $menuarr[] = menulinkurl(phpself_querystr(array('patchdeapprove'=>$data['id'])), 'Deapprove');
	}
	$menuarr[] = menulinkurl(phpself_querystr(array('patchedit'=>$data['id'])), 'Edit');
	$ld .= ' '.make_menu_string($menuarr,1);
      }
      echo tablerowd(array('Status', $ld), null, $trd);
      }
    break;
  }
}
