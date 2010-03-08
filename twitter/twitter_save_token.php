<?php
session_start();

require_once('oauth_lib.php');

function Medieteknik_Save_Token() {
  //assume we have been authorized and returned
  
  //first check if we have a logged in user
  //äckliga wordpress vill att man gör såhär
  global $user_ID;
  get_currentuserinfo();
  
  if (!$user_ID) {
    die('Du är inte inloggad i wordpress. LOGGA IN FFS!!!!');
  }
  
  //get our temporary token
  $token = $_SESSION['oauth_token'];
  $secret = $_SESSION['oauth_token_secret'];
  
  //kolla att vi faktiskt har en token
  if (!$token || !$secret) {
    die('Kunde inte hitta en sparad token. Gör om gör rätt!');
  }
  
  //request a permanent token
  $oauth = new TwitterOAuth($token, $secret);
  $new_token = $oauth->access_token();
  
  //check our reply
  if (!$new_token['oauth_token'] || !$new_token['oauth_token_secret']) {
    die('Du blev inte autoriserad. Prova igen?');
  }
  
  //insert token and secret into the usermeta database
  update_usermeta($user_ID, 'twitter_token', $new_token['oauth_token']);
  update_usermeta($user_ID, 'twitter_token_secret', $new_token['oauth_token_secret']);
  
  //echo 'Välkommen ' . $new_token['screen_name'];
  //istället redirecta till index
  header('location: index.php');
}

Medieteknik_Save_Token();

?>
