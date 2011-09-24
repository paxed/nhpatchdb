<?php

/* Configurable variables */

$nhpatchdb_version	= 'v0.25';

$admin_username		= 'CHANGEME';
$admin_passwd		= 'CHANGEME';

$admin_public_email	= 'CHANGEME'; // remember to munge it for spam
$admin_hidden_email	= 'CHANGEME'; // email sent via the contact form

$db_connect_str		= 'dbname=nhpatchdb host=localhost user=CHANGEME password=CHANGEME';

$timestamp_format	= 'F d, Y H:i';

$help_filename		= 'nhpatchdb_help.txt';

$active_nethack_ver	= 1; // What nethack version is active in dropdown lists?

$comment_max_score	= 5; // Scores go from 0 up to this
$comment_text_maxlen    = 1024; // Max. length of user comment
$comment_name_maxlen    = 20; // Max. length of user name in a comment

$patchfile_maxlen	= 0; // Max. length of diff file accepted, in bytes.  0=no limit
$patch_descs_maxlen	= 255; // Max. length of patch data short description. 0=no limit
$patch_descl_maxlen	= 65500; // Max. length of patch data long description. 0=no limit

$strip_html_comments	= 0; // Strip HTML from user comments? [0/1]
$strip_html_patchdesc	= 0; // Strip HTML from patch descriptions? [0/1]

$max_search_results     = 20; // How many patches searching returns at max
$browse_page_height     = 20; // How many patches in a page when browsing

$comment_page_height    = 30; // How many comments per page

// Questions asked from users to prevent spammers.
$nethack_questions = array(
			   array('char'=>'+','text'=>'a spellbook'),
			   array('char'=>'*','text'=>'a gem or a rock'),
			   array('char'=>'?','text'=>'a scroll'),
			   array('char'=>'!','text'=>'a potion'),
			   array('char'=>'%','text'=>'something edible'),
			   array('char'=>'=','text'=>'a ring'),
			   array('char'=>'|','text'=>'a grave'),
			   array('char'=>'#','text'=>'a kitchen sink'),
			   array('char'=>'^','text'=>'a trap'),
			   array('char'=>')','text'=>'a weapon'),
			   array('char'=>'[','text'=>'a piece of armor'),
			   array('char'=>'/','text'=>'a wand'),
			   array('char'=>'_','text'=>'an altar'),
			   array('char'=>'{','text'=>'a fountain'),
			   array('char'=>'o','text'=>'a goblin'),
			   array('char'=>'h','text'=>'a dwarf'),
			   array('char'=>'e','text'=>'a floating eye'),
			   array('char'=>':','text'=>'a lizard'),
);
