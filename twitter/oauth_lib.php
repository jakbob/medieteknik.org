<?php

include_once('../wp-blog-header.php');

class TwitterOAuth {

var $CONSUMER_KEY = '9zVGzb3B4FdNZZxKnd7TFQ';
var $CONSUMER_SECRET = 'dQgvLJJChdnpU0RUGV17QVIe7wRQxJYW0v1Mzhzs';
var $TOKEN;
var $TOKEN_SECRET;
var $request_token_url = 'http://twitter.com/oauth/request_token';
var $access_token_url =  'http://twitter.com/oauth/access_token';
var $authorize_url = 'http://twitter.com/oauth/authorize';

function TwitterOAuth($token_key, $token_secret) {
  $this->TOKEN = $token_key;
  $this->TOKEN_SECRET = $token_secret;

}

function request_token() {
  return $this->oauth_request($this->request_token_url);
}

function oauth_request($url, $method='GET', $extra_parameters=array()) {
  //Get a temporary request token
  
  $timestamp = time();
  
  $params = array();
  $params['oauth_consumer_key'] = $this->CONSUMER_KEY;
  $params['oauth_signature_method'] = 'HMAC-SHA1';
  $params['oauth_timestamp'] = (string) $timestamp;
  $params['oauth_nonce'] = wp_create_nonce($timestamp);
  $params['oauth_version'] = '1.0';
  $params['oauth_token'] = $this->TOKEN;
  
  //Add the extra parameters, if any
  $all_params = array_merge($params, $extra_parameters);
  
  $url_params = $this->concaternate_parameters($all_params);

  $signature = $this->generate_signature($method, $url, $url_params);
  
  $params['oauth_signature'] = $signature;
  $params['realm'] = $url;
  
  //make sure everything is urlencoded
  //foreach ($params as $key => $value) {
  //  $params[$key] = rawurlencode(urldecode($value));
  //}
    
  return $this->oauth_call($url, $method, $params, $this->concaternate_parameters($extra_parameters));
 
}

function authorize($token) {
    $full_url = $this->authorize_url . '?oauth_token=' . $token;
    header("location: $full_url");
}

function access_token() {
  return $this->oauth_request($this->access_token_url, 'POST');
}


function generate_signature($http_method, $request_url, $request_parameters) {
  //make a signature basestring for our signature
  $signature_base_string = $http_method . '&' . 
    urlencode($request_url) . '&' .
    urlencode($request_parameters);
    
  $signature_key = $this->CONSUMER_SECRET . '&' . $this->TOKEN_SECRET;
  
  $signature = base64_encode(hash_hmac('sha1', $signature_base_string, $signature_key, true));
  
  return urlencode($signature);
}

function concaternate_parameters($parameters) {
  //make the parameters into a big string
  ksort($parameters);
  $param_string = '';
  foreach ($parameters as $k => $v) {
    $param_string .= rawurlencode($k) . '=' . rawurlencode($v) . '&';
  }
  //remove last &
  $param_string = rtrim($param_string, '&');
  
  return $param_string;
}

function oauth_call($url, $method, $parameters, $data) {
    $auth_header = "Authorization: OAuth";
  foreach ($parameters as $k => $v) {
    $auth_header .= " $k=\"$v\", \n ";
  }
  
  
  //Now use curl to call twitter
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array($auth_header));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
  if ($data) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  }

  $response = curl_exec($ch);
  $response_header = curl_getinfo($ch);
  curl_close($ch);
  
  
  $return_array = array();
  foreach(explode('&', $response) as $line) {
    if ($line) {
      $parts = explode('=', $line);
      $return_array[$parts[0]] = $parts[1];
    }
  }
  $return_array['status'] = (int) $response_header['http_code'];
  
  return $return_array;

}
}

?>
