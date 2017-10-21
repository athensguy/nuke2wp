 <?php


    $submit = $_POST['submit'];
   
    $hostname = "localhost";
    $username = "FIXME";
    $password = "FIXME";

    $sourcedb = "FIXME"; // your postnuke database
    $sourcetable = "nuke_stories"; // your postnuke stories table
    $sourcecat = "nuke_topics"; // your postnuke topics table
    
    $destdb = "FIXME"; // your wordpress database
    $desttable_prefix = "FIXME"; // you word press table prefix

    $db_connect = @mysql_connect($hostname, $username, $password) 
                    or die("Fatal Error: ".mysql_error());
    
    mysql_select_db($sourcedb, $db_connect);
    $srcresult = mysql_query("select * from $sourcetable", $db_connect) 
                    or die("Fatal Error: ".mysql_error());

    $srccatresult = mysql_query("select * from $sourcecat", $db_connect)
                    or die("Fatal Error: ".mysql_error());
    
    // sanitize function stolen from wordpress source
    // used to generate the dashed titles in the URLs
    function sanitize($title) {
        $title = strtolower($title);
        $title = preg_replace('/&.+?;/', '', $title); // kill entities
        $title = preg_replace('/[^a-z0-9 _-]/', '', $title);
        $title = preg_replace('/\s+/', ' ', $title);
        $title = str_replace(' ', '-', $title);
        $title = preg_replace('|-+|', '-', $title);
        $title = trim($title, '-');

        return $title;
    }
?>

<html>
<head>
    <title>Convert postnuke stories and comments to WordPress</title>
</head>
<body>
<h1>Convert postnuke stories and comments to WordPress</h1>
<hr>
<?php
	if($submit)
	{
		echo "<p>Converting Postnuke to WP2 databases!</p>";
	}
	else
	{
		echo "<p>This is a preview of what is going to happen,
			to actually convert you'll need to press the
			button at the very bottom of this page!</p>";
	}
?>

<hr>
<h2>Category Conversions</h2>

<?php
    // copy over categories
    $rownum = 0;
    while ($myrow = mysql_fetch_array($srccatresult))
    {
        $mytopictext = mysql_escape_string($myrow['pn_topictext']);
        $sql = "INSERT INTO `" . $desttable_prefix . "categories`
               (
                 `cat_ID`,
                 `cat_name`,
                 `category_nicename`,
                 `category_description`
               )
               VALUES
               (
                 '$myrow[pn_topicid]',
                 '$myrow[pn_topicname]',
                 '$mytopictext',
                 '$mytopictext'
               );";
        print $sql;
	echo "<br />";
	if ($submit && ( $myrow[pn_topicid] > 1 ) )
        {
            mysql_select_db($destdb, $db_connect);
            mysql_query($sql, $db_connect) or die("Fatal error: ".mysql_error());
        }
       

    }
               
?>
<hr>
<h2>Story Conversion</h2>
<table cellpadding="2" cellspacing="3" bgcolor="#DDDDDD">
    <thead>
        <td>pn_sid</td>
        <td>pn_title</td>
        <td>pn_time</td>
        <td>pn_hometext</td>
        <td>pn_bodytext</td>
        <td>pn_topic</td>
        <td>pn_counter</td>
        <td>comments</td>
    </thead>
<?php
    $rownum = 0; 
    while ($myrow = mysql_fetch_array($srcresult))
    {
        
        $myhometext = mysql_escape_string($myrow['pn_hometext']);
	if($myrow['pn_bodytext'])
	{
        	$mybodytext = mysql_escape_string($myrow['pn_bodytext']);
		$myhometext = $myhometext.'\n<!--more-->\n'.$mybodytext;
		#$myhometext = $mybodytext;
	}

        $mytitle = mysql_escape_string($myrow['pn_title']);
        $myname = mysql_escape_string(sanitize($mytitle));
        $sql = "INSERT INTO `" . $desttable_prefix . "posts` 
               ( 
                 `ID` ,
                 `post_author` ,
                 `post_date` ,
                 `post_date_gmt` ,
                 `post_content` ,
                 `post_title` ,
                 `post_name` ,
                 `post_category` ,
                 `post_excerpt` ,
                 `post_status` ,
                 `comment_status` ,
                 `ping_status` ,
                 `post_password` ,
                 `to_ping` ,
                 `pinged` ,
                 `post_modified` ,
                 `post_modified_gmt` ,
                 `post_content_filtered` ,
                 `post_parent` ) 
               VALUES 
               ( 
                 '$myrow[pn_sid]',
                 '1',
                 '$myrow[pn_time]',
                 '0000-00-00 00:00:00',
                 '$myhometext',
                 '$mytitle',
                 '$myname',
                 '$myrow[pn_topic]',
                 '',
                 'publish',
                 'open',
                 'open',
                 '',
                 '',
                 '',
                 '$myrow[pn_time]',
                 '0000-00-00 00:00:00',
                 '',
                 '0' );";
      
        // only really do insert if requested
        if ($submit)
        {
            mysql_select_db($destdb, $db_connect);
            mysql_query($sql, $db_connect);
       
            // now get the ID of the post we just added
            $sql = "select MAX(ID) from " . $desttable_prefix . "posts";
            $getID = mysql_query($sql, $db_connect);
            $currentID = mysql_fetch_array($getID);
            $currentID = $currentID['MAX(ID)'];
            printf("<h2>Just inserted ID %s</h2>\n", $currentID);
       
            // add post2cat map... why does he have this table?
            if ($myrow['pn_topic'] == 0)
                $topicnum = 1;
            else
                $topicnum = $myrow['pn_topic'];
            $sql = "insert into `" . $desttable_prefix . "post2cat` (`post_id`, `category_id`) VALUES ('$currentID', '$topicnum');";
            $result = mysql_query($sql, $db_connect) or die("Fatal error: ".mysql_error());
        }
        
        // print out source information
        if ($rownum++ % 2 == 0)
            printf("<tr bgcolor=\"#BBBBBB\">");
        else
            printf("<tr>\n");
        printf("    <td>%s</td>\n
                    <td>%s</td>\n
                    <td>%s</td>\n
                    <td>%s</td>\n
                    <td>%s</td>\n
                    <td>%s</td>\n
                    <td>%s</td>\n", 
                $myrow["pn_sid"], 
                $myrow["pn_title"], 
                $myrow["pn_time"], 
		$myrow["pn_hometext"], 
		$myrow["pn_bodytext"], 
                $myrow["pn_topic"], 
                $myrow["pn_counter"]);

        // retreive all associated comments
        $mysid = $myrow["pn_sid"];
        mysql_select_db($sourcedb, $db_connect);
        $comments = mysql_query("select * from nuke_comments where pn_sid = $mysid", $db_connect);
        print "<td>";
        print "<table cellpadding=\"2\" cellspacing=\"3\" bgcolor=\"#DDDDDD\">";
        print "<thead>";
        print "    <td>pn_tid</td>";
        print "    <td>pn_sid</td>";
        print "    <td>pn_date</td>";
        print "    <td>pn_name</td>";
        print "    <td>pn_email</td>";
        print "    <td>pn_url</td>";
        print "    <td>pn_host_name</td>";
        print "    <td>pn_subject</td>";
        print "    <td>pn_comment</td>";
        print "</thead>";
            
        $comrownum = 0;
        while ($comrow = mysql_fetch_array($comments))
        {
            
            $myname = mysql_escape_string($comrow['pn_name']);
            $myemail = mysql_escape_string($comrow['pn_email']);
            $myurl = mysql_escape_string($comrow['pn_url']);
            $myIP = mysql_escape_string($comrow['pn_host_name']);
            $mycomment = mysql_escape_string($comrow['pn_comment']);
            $sql = "INSERT INTO `" . $desttable_prefix . "comments` 
                     ( 
                        `comment_ID` , 
                        `comment_post_ID` , 
                        `comment_author` , 
                        `comment_author_email` , 
                        `comment_author_url` , 
                        `comment_author_IP` , 
                        `comment_date` , 
                        `comment_date_gmt` , 
                        `comment_content` , 
                        `comment_karma` , 
                        `comment_approved` , 
                        `user_id` )
                     VALUES 
                     (
                        '',
                        '$currentID',
                        '$myname',
                        '$myemail',
                        '$myurl',
                        '$myIP',
                        '$comrow[pn_date]',
                        '0000-00-00 00:00:00',
                        '$mycomment',
                        '0',
                        '1',
                        '0'
                     );";
            
            if ($submit)
            {
                mysql_select_db($destdb, $db_connect);
                mysql_query($sql, $db_connect)
                  	or die("Fatal Error: ".mysql_error());
            }
       
     
            if ($comrownum++ %2 == 0)
                print "<tr bgcolor=\"#BBBBBB\">";
            else
                print "<tr bgcolor=\"#FFFFFF\">";
            printf("    <td>%s</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%s</td></tr>\n",
                   $comrow["pn_tid"],
                   $comrow["pn_sid"],
                   $comrow["pn_date"],
                   $comrow["pn_name"],
                   $comrow["pn_email"],
                   $comrow["pn_url"],
                   $comrow["pn_host_name"],
                   $comrow["pn_subject"],
                   $comrow["pn_comment"]);

        }
        print "</table>\n";
        print "</td>\n";
        print "</tr>\n";
    }

?>
</table>

<hr>

<h2>Updating Comment Counts</h2>
<table><tr><th>SID</th><th>Comment count</th></tr>
<?php
    mysql_select_db($destdb, $db_connect);
    $tidyresult = mysql_query("select * from $desttable_prefix" . "posts", $db_connect)
                    or die("Fatal Error: ".mysql_error());

    while ($myrow = mysql_fetch_array($tidyresult))
    {
	    $mypostid=$myrow['ID'];
	    $countsql="select COUNT(*) from $desttable_prefix" . "comments"
	   		 . " WHERE `comment_post_ID` = " . $mypostid;
	    $countresult=mysql_query($countsql) or die("Fatal Error: ".mysql_error());
	    $commentcount=mysql_result($countresult,0,0);
	    $countsql="UPDATE `" . $desttable_prefix . "posts`
	    	SET `comment_count` = '" . $commentcount .
	    	"' WHERE `ID` = " . $mypostid . " LIMIT 1";
	    if($submit)
	    {
	    	$countresult=mysql_query($countsql) or die("Fatal Error: ".mysql_error());
	    }
	    if($commentcount > 0)
	    {
		    echo "<tr><td>$mypostid</td><td>$commentcount</tr></tr>\n";
	    }
    }
?>
</table>

<hr>

<h2>Updating Category Counts</h2>
<table><tr><th>Category</th><th>Category count</th></tr>
<?php
    mysql_select_db($destdb, $db_connect);
    $tidyresult = mysql_query("select * from $desttable_prefix" . "categories", $db_connect)
                    or die("Fatal Error: ".mysql_error());

    while ($myrow = mysql_fetch_array($tidyresult))
    {
	    $mypostid=$myrow['cat_ID'];
	    $countsql="select COUNT(*) from $desttable_prefix" . "post2cat"
	   		 . " WHERE `category_id` = " . $mypostid;
	    $countresult=mysql_query($countsql) or die("Fatal Error: ".mysql_error());
	    $commentcount=mysql_result($countresult,0,0);
	    $countsql="UPDATE `" . $desttable_prefix . "categories`
	    	SET `category_count` = '" . $commentcount .
	    	"' WHERE `cat_ID` = " . $mypostid . " LIMIT 1";
	    if($submit)
	    {
	  	  $countresult=mysql_query($countsql) or die("Fatal Error: ".mysql_error());
	    }
	    if($commentcount > 0)
	    {
		    echo "<tr><td>$mypostid</td><td>$commentcount</tr></tr>\n";
	    }
    }
?>
</table>

<hr>

<form method="post" action="<?php echo $PHP_SELF?>">
    <input type="submit" name="submit" value="Convert PN to Wordpress">
</form>


</body>
</html>
 
