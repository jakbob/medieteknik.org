<?php
/*
Plugin Name: Twitter Reply
Author: Jakob Florell
Version: 0.1

*/
session_start(); //required later

require_once('oauth_lib.php');
  
function Medieteknik_authorize_twitter() {
    $oauth = new TwitterOAuth('', '');
    $temp_token = $oauth->request_token();
    
    //Make sure twitter liked our request
    if (!$temp_token['oauth_token']) {
      die('Twitter ville inte samarbeta.');
    }
    
    //Save the token and secret on the server for further use
    $_SESSION['oauth_token'] = $temp_token['oauth_token'];
    $_SESSION['oauth_token_secret'] = $temp_token['oauth_token_secret'];
    
    //and send our user away to authorize
    $oauth->authorize($temp_token['oauth_token']);

}

Medieteknik_authorize_twitter();

?>
