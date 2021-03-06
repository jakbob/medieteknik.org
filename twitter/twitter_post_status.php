<?php

require_once('oauth_lib.php');

function Medieteknik_post_tweet($tweet, $hashtag) {
  //get current user
  global $user_ID;
  get_currentuserinfo();
  
  //get the saved token for this user
  $token = get_usermeta($user_ID, 'twitter_token');
  $secret = get_usermeta($user_ID, 'twitter_token_secret');
  
  $oauth = new TwitterOAuth($token, $secret);
  $url = 'http://api.twitter.com/1/statuses/update.xml';
  
  if ($hashtag && strpos(strtolower($tweet), '#medieteknik') == false) {
    //add the hashtag
    $tweet = rtrim($tweet) . ' #medieteknik';
  }
  
  $params = array('status' => $tweet,);
  $reply = $oauth->oauth_request($url, 'POST', $params);
  
  //Check our request came out ok
  if ($reply['status'] != 200) {
    if ($reply['status'] == 403) {
      die('Twitter tillåter endast 150 statusuppdateringar i timmen. Försök igen senare');
    } else {
      die('Något är galet med twitter. Försök igen senare eller kontakta sidansvarig');
    }
  }
  
  header('location: index.php');
}

Medieteknik_post_tweet($_GET['status'], $_GET['hashtag']);

?>
