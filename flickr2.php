<?php
/*
Plugin Name: The Flickr Robot
Description: Sparar nya foton från utvalda flickr-grupper
Version: 2.0
Author: Jakob Florell

*/

/*
Jag är väldigt upprörd över att vi inte kan använda namespaces
så jag antar att jag måste göra en såndär ful klass-inkapsling :(
*/

//temp, gör så att den kan köras utan att vara aktiverad av wordpress
//include_once('../../wp-blog-header.php');

class Medieteknik_Flickr_Robot {

var $flickr_api_key = "56c398661779ce460475b861cae0f7bf";
var $flickr_api_secret = "c1b33f16401f516d";
var $flickr_group_id = '1032326@N22';
var $flickr_author = 4;
var $flickr_category = 12;
var $flickr_media_tag = 'flickr';
var $photos_per_page = 10;
  
function Medieteknik_Flickr_Robot () {
  //I don't know the best approach here, copied this from tutorial
  add_action('flickr_picture_update', array($this, 'testtest'));
  $file = fopen(WP_CONTENT_DIR . '/bajs', 'a');
  fwrite($file, date('ekiekieki D, j/n Y H:i:s') . "\n");
}

function testtest() {
  $file = fopen(WP_CONTENT_DIR . '/bajs', 'a');
  fwrite($file, date('D, j/n Y H:i:s') . "\n");
}

static function activate() {
  //when activated, schedule event
  wp_schedule_event(time(), 'hourly', 'flickr_picture_update');  
  $file = fopen(WP_CONTENT_DIR . '/bajs', 'a');
  fwrite($file, date('asdf D, j/n Y H:i:s') . "\n");
  
  //skapa nytt objekt
  global $medieteknik_flickr;
  $medieteknik_flickr = new Medieteknik_Flickr_Robot();
}

static function deactivate() {
  //also clear schedule when deactivated
	wp_clear_scheduled_hook('flickr_picture_update');
}
  
function get_photos($photo_number=1) {
  //parameters to use
  $params = array(
  'api_key' => $this->flickr_api_key,
  'method' => 'flickr.groups.pools.getPhotos',
  'group_id' => $this->flickr_group_id,
  'per_page' => $this->photos_per_page,
  'page' => (string) $photo_number,
  'extras' => 'url_m,path_alias',
  'format' => 'php_serial',
  );
  //make the parameters nice for api call
  $url_params = array();
  foreach ($params as $k => $v) {
    $url_params[] = urlencode($k) . '=' . urlencode($v);
  }
  
  //make the api call
  $url = 'http://api.flickr.com/services/rest/?' . implode('&', $url_params);
  $response = file_get_contents($url);
  
  $resp_array = unserialize($response);

  if ($resp_array['stat'] == 'ok') {
    //Everything ok! Return the photo object
    return $resp_array['photos']['photo'];
  } else {
    //Something went wrong with the flickr call
    //We say this by returning NULL
    
    //JÄTTETEMP!
    echo "Nu blev det nåt fel med flickr! Ojojoj!";
    
    return NULL;
    
  }
}

function check_if_new($new_photo, $old_photos) {
  /*
  If the new photo has been added to the old_photos, return False, else True
  Parameter 1 is an array representation of a flickr photo
  Parameter 2 is an array of wordpress post, in some weird ArrayObj form
  */
  foreach ($old_photos as $oldie) {
    if ($oldie->guid == $new_photo['url_m']) {
      //The photo is already in the database!
      return False;
    }  
  }
  return True;
}

function add_photo($photo) {
  /*
  Add a flickr photo to wordpress in form of a attachment
  */

  $photo_post = array();
  $photo_post['post_title'] = $photo['title'];
  $photo_post['post_content'] = '';
  $photo_post['post_type'] = 'attachment';
  $photo_post['post_status'] = 'publish';
  $photo_post['post_author'] = $this->flickr_author;
  $photo_post['post_category'] = array($this->flickr_category, );
  $photo_post['post_mime_type'] = 'image/jpeg'; //assuming jpeg, is this ok?
  $photo_post['guid'] = $photo['url_m']; //magically linking the photo
  
  //$postid = wp_insert_post($photo_post);
  $postid = wp_insert_attachment($photo_post);
  /* wordpress now recomends me to update metadata for the image. 
  But I can't do that the usual way. */
  
  //now tag with mediatags
  wp_set_object_terms($postid, array($this->flickr_media_tag), MEDIA_TAGS_TAXONOMY);

  //and we should be done. Horay!
}

function add_new_recursive($startnum, $old_photos) {
  //now search for new photos
  $new_photos = $this->get_photos($startnum);
  if (!$new_photos) {
    //something is wrong
    return;
  }
  
  $old = false; //flag if some of the photos are already inte tha database
  foreach ($new_photos as $photo) {
    if ($photo && $this->check_if_new($new_photo, $old_photos))
    {
      //if the photo is new, 
      //add it to the wordpress database
      $this->add_photo($photo);
    } else {
      $old = true;
    }
  }
  if (!$old) {
    //If no photos were old, there might be more to find
    $this->add_new_recursive($startnum + 1, $old_photos);
  }
}

function main() {
  //The main action hook function
  
  //first, dig out the photos we already have
  $args = array(
  'post_type' => 'attachment',
  'post_category' => $this->flickr_category,
  'orderby' => 'date',
  'order' => 'DESC'
  );
  $old_photos = get_posts($args);
  
  //Check and add new photos
  $this->add_new_recursive(1, $old_photos);
}
  
}

//$flickr = new Medieteknik_Flickr_Robot();
register_activation_hook(__FILE__, array('Medieteknik_Flickr_Robot', 'activate'));
register_deactivation_hook(__FILE__, array('Medieteknik_Flickr_Robot', 'deactivate'));
?>
