<?php
require_once('../wp-blog-header.php');
?>
<html>
<head>
<title>Twittra med media!</title>
<script type="text/javascript" src="jquery.js"></script>  
<script type="text/javascript">
$(document).ready(update_tweets);

function update_tweets() {
  $("#twitterContainer").empty()
  $.getJSON('http://search.twitter.com/search.json?q=%23medieteknik&rpp=5&callback=?',
    function(json, status) {
      $(json.results).each(function(i){
        if (i % 2 == 0) {
          var $color = 'lightblue'
        } else {
          var $color = 'white'
        }
        var $tweet = '<div class="tweet" style="padding-bottom:10px; padding-top:10px; background-color:' + $color + ';">\
        Från:'+this.from_user+'<br />\n'+this.text+'</div>';
        $($tweet).appendTo("#twitterContainer");
      });
    });
}

</script>

</head>
<body>
<button onclick="update_tweets();">Uppdatera</button><br >
<div id="twitterContainer"></div>
<?php

require_once('../wp-blog-header.php');

//kolla om användaren kopplat sitt twitterkonto
global $user_ID;
get_currentuserinfo();
//get the saved token for this user
$token = get_usermeta($user_ID, 'twitter_token');
$secret = get_usermeta($user_ID, 'twitter_token_secret');

if ($user_ID <= 0) {
?>
<a href='../wp-admin/'>Logga in!</a>
<?php
} else if (!$token && !$secret) {
?>
<a href='twitter_authorize.php'>Tryck här för att koppla ditt twitterkonto!</a>
<?php
} else {
//användaren har kopplat sitt twitterkonto! Hurra!
?>
<form action='twitter_post_status.php' method=get style="padding-top:20px;">
<b>Uppdatera din twitterstatus</b><br />
<input type="text" name="status" /> <br>
<input type="checkbox" name="hashtag"> Twittra med #medieteknik <br />
<input type="submit" value="Twittra!">
</form>

<?php
}

?>
</body>
</html>
