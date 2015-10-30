<?php
header('content-type: text/plain');
return;
include '../evo/boot.php';

echo "THIS TOOL SHOULD BE RUN ON A CLEAN INSTALLATION\nWARNING, THIS WILL CLEAR YOUR CMS USERS AND FORUMS TABLES\n\n";

$mybb = new PDO('mysql:dbname=test;host=localhost', 'test', 'test');
$mybb_prefix = 'mybb_';


$mybb->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

echo "I will now TRUNCATE forum tables\n";

Db::Truncate('users');
Db::Truncate('mailbox');
Db::Truncate('friends');
Db::Truncate('forums');
Db::Truncate('forums_cat');
Db::Truncate('forums_topics');
Db::Truncate('forums_posts');
Db::Exec('DELETE FROM {groups} WHERE id > 4');


$query = "SELECT uid as id, usergroup as group_id, username, email, password, salt, 
				 hideemail as hide_email, 1 as discuss, regdate as registered, lastactive as activity, 
				 regip as registration_ip, website, signature as about
		  FROM {$mybb_prefix}users";
		  
$inserts = [];

foreach ($mybb->query($query) as $row) {
	$inserts[] = $row;
}
Db::Insert('users', $inserts);

echo 'Imported ' . Db::$affected_rows . " users.\n";






$query = "SELECT gid as id, (CASE WHEN usertitle = '' THEN title ELSE usertitle END) as name FROM {$mybb_prefix}usergroups WHERE type = 2";
$inserts = [];

foreach ($mybb->query($query) as $row) {
	$inserts[] = $row;
}
Db::Insert('groups', $inserts);

echo 'Imported ' . Db::$affected_rows . " custom user groups.\n";






$query = "SELECT fid as id, CAST(parentlist AS UNSIGNED) as cat, disporder as priority, name,
				 description, 'arrow-circle-right' as icon
		  FROM {$mybb_prefix}forums
		  WHERE type='f'";
		  
$inserts = [];

foreach ($mybb->query($query) as $row) {
	$inserts[] = $row;
}
Db::Insert('forums', $inserts);

echo 'Imported ' . Db::$affected_rows . " forums.\n";





$query = "SELECT fid as id, name, disporder as priority FROM {$mybb_prefix}forums WHERE type='c'";
$inserts = [];

foreach ($mybb->query($query) as $row) {
	$inserts[] = $row;
}
Db::Insert('forums_cat', $inserts);

echo 'Imported ' . Db::$affected_rows . " forum categories.\n";







$query = "SELECT tid as id, fid as forum_id, uid as poster_id, username as poster, subject, 
				 firstpost as first_post_id, dateline as first_post, lastpost as last_post, 
				 lastposter as last_poster, views as num_views, sticky, closed 
		  FROM {$mybb_prefix}threads";
		  
$inserts = [];

foreach ($mybb->query($query) as $row) {
	$inserts[] = $row;
}
Db::Insert('forums_topics', $inserts);

echo 'Imported ' . Db::$affected_rows . " threads.\n";







$query = "SELECT pid as id, tid as topic_id, uid as poster_id, username as poster, 
				 ipaddress as poster_ip, message, dateline as posted, edittime as edited, 
				 NULL as user_agent
		  FROM {$mybb_prefix}posts";
		  
$inserts = [];

foreach ($mybb->query($query) as $row) {
	$inserts[] = $row;
}
Db::Insert('forums_posts', $inserts);

echo 'Imported ' . Db::$affected_rows . " posts.\n";






Db::Exec('REPLACE INTO {permissions} (name, group_id, related_id, value) 
		  SELECT "forum.read", g.id, f.id, 1 FROM {groups} as g JOIN {forums} as f');
		  
Db::Exec('REPLACE INTO {permissions} (name, group_id, related_id, value) 
		  SELECT "forum.write", g.id, f.id, 1 FROM {groups} as g JOIN {forums} as f WHERE g.id > 1');

echo "Registered default public permissions.\n";

// Group Mapping

Db::Exec('UPDATE {users} SET group_id = 1 WHERE group_id = 4'); // MyBB 4 = Evo 1 (Administrator)
Db::Exec('UPDATE {users} SET group_id = 2 WHERE group_id = 3'); // MyBB 3 = Evo 2 (Moderator)
Db::Exec('UPDATE {users} SET group_id = 3 WHERE group_id = 2'); // MyBB 2 = Evo 3 (Member)
Db::Exec('UPDATE {users} SET group_id = 2 WHERE (select id from {groups} where id = group_id ) IS NULL');


//Rebuilding some stuff

Db::Exec('UPDATE {users} as u set num_posts = (select count(*) from {forums_posts} where poster_id = u.id)');

Db::Exec('UPDATE {forums_topics} as t 
		  SET num_posts = (select count(*) from {forums_posts} where topic_id = t.id),
			  last_post_id = (select max(id) from {forums_posts} where topic_id = t.id),
			  first_post_id = (select max(id) from {forums_posts} where topic_id = t.id)
		');
		
Db::Exec('UPDATE {forums} as f 
		  SET num_topics = (select count(*) from {forums_topics} where forum_id = f.id),
			  num_posts = (select SUM(num_posts) from {forums_topics} where forum_id = f.id),
			  last_topic_id = (select max(id) from {forums_topics} where forum_id = f.id)
		');
		