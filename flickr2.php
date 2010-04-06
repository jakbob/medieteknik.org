<?php
/*
Plugin Name: The Flickr Robot
Description: Sparar nya foton från utvalda flickr-grupper
Version: 2.0
Author: Jakob Florell

*/

/*
IMPORTANT:
Hur gör man för att lägga till nya gallerier?
1. Skapa en wordpress page med rätt template
2. Gå till funktionen Medieteknik_Flickr_Robot().
I arrayet $galleries lägger du till en ny rad med 3 fält:
groupid, IDt för den flickrpool som ska indexeras
pageid, IDt för den wordpress sida där bilderna skall listas
mediatag, mediataggen som ska användas för att hitta bilderna

*/

//temp, gör så att den kan köras utan att vara aktiverad av wordpress
/*
include_once('../../wp-blog-header.php');
*/
require_once(ABSPATH . 'wp-admin/includes/image.php'); //required for image metadata

//Schedule hourly checks
register_activation_hook(__FILE__, 'mt_flickr_activate');
register_deactivation_hook(__FILE__, 'mt_flickr_deactivate');
add_action('mt_flickr', 'mt_update_flickr');


class Medieteknik_Flickr_Robot {

//important information used in various places
var $flickr_api_key = "56c398661779ce460475b861cae0f7bf";
var $flickr_api_secret = "c1b33f16401f516d";
var $flickr_group_id = '1032326@N22';
var $flickr_author = 4;
var $flickr_category = 12;
var $photos_per_page = 10;
var $galleries;
  
function Medieteknik_Flickr_Robot () {
  //Setup all the info we need to build separate galleries
  $this->galleries = array(
    array('groupid' => '1032326@N22', 'pageid' => 2953, 'mediatag' => 'fotoklubben'),
  );
}

function testtest() {
  $file = fopen(WP_CONTENT_DIR . '/bajs', 'a');
  fwrite($file, date('D, j/n Y H:i:s') . "\n");
}

function get_photos($groupid, $photo_number=1) {
  /*
    Function for retrieving photos from flickr
  */

  //parameters to use
  $params = array(
  'api_key' => $this->flickr_api_key,
  'method' => 'flickr.groups.pools.getPhotos',
  'group_id' => $groupid,
  'per_page' => $this->photos_per_page,
  'page' => (string) $photo_number,
  'extras' => 'url_m,path_alias,url_t,url_s,url_o',
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
    
    //Temporary error message
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

function add_photo($photo, $gallery) {
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
  $photo_post['post_parent'] = $gallery['pageid'];
  if ($photo['url_o']) {
    $photo_post['guid'] = $photo['url_o']; //magically linking the photo
  } else {
    $photo_post['guid'] = $photo['url_m']; //for some reason url_o isnt always available
  }
  
  //$postid = wp_insert_post($photo_post);
  $postid = wp_insert_attachment($photo_post);
  /* Update metadata now */
  $this->set_metadata($postid, $photo);

  //now tag with mediatags
  wp_set_object_terms($postid, array($gallery['mediatag']), MEDIA_TAGS_TAXONOMY);

  //and we should be done. Horay!
}
function set_metadata($post_id, $photo) {
  $metadata = array();
  if ($photo['url_o']) {
    $metadata['width'] = $photo['width_o'];
    $metadata['height'] = $photo['height_o'];
    $metadata['file'] = basename($photo['url_o']);
  } else {
    $metadata['width'] = $photo['width_m'];
    $metadata['height'] = $photo['height_m'];
    $metadata['file'] = basename($photo['url_m']);
  
  }
  $metadata['sizes'] = array();
  $metadata['sizes']['thumbnail'] = array(
    'file' => basename($photo['url_t']),
    'width' => $photo['width_t'],
    'height' => $photo['height_t'],
  );
  $metadata['sizes']['medium'] = array(
    'file' => basename($photo['url_s']),
    'width' => $photo['width_s'],
    'height' => $photo['height_s'],
  );
  $metadata['sizes']['large'] = array(
    'file' => basename($photo['url_m']),
    'width' => $photo['width_m'],
    'height' => $photo['height_m'],
  );
  $metadata['image_meta'] = array('credit' => $photo['pathalias']);
  
  wp_update_attachment_metadata($post_id, $metadata);
}

function add_new_recursive($startnum, $old_photos, $gallery) {
  /*
    Get photos from flickr, check if they're new or already in the database
    Then recursively call this functin again to ensure that all new pictures are added 
    simultaniously
  */
 
  //now search for new photos
  $new_photos = $this->get_photos($gallery['groupid'], $startnum);
  if (!$new_photos) {
    //something is wrong
    return;
  }
  
  $old = false; //flag if some of the photos are already inte tha database
  foreach ($new_photos as $photo) {
    if ($photo && $this->check_if_new($photo, $old_photos))
    {
      //if the photo is new, 
      //add it to the wordpress database
      $this->add_photo($photo, $gallery);
    } else {
      $old = true;
    }
  }
  if (!$old) {
    //If no photos were old, there might be more to find
    $this->add_new_recursive($startnum + 1, $old_photos, $gallery);
  }
}

function main() {
  //The main action hook function
  
  //first, dig out the photos we already have
  $args = array(
  'orderby' => 'date',
  'order' => 'DESC'
  );
  
  //Check and add new photos
  foreach ($this->galleries as $gallery) {
    $args['media_tags'] = $gallery['mediatag'];

    $old_photos = get_attachments_by_media_tags($args);
    $this->add_new_recursive(1, $old_photos, $gallery);
  }
}
}

//functions for CRON scheduling
function mt_flickr_activate() {
  wp_schedule_event(time(), 'hourly', 'mt_flickr');
}

function mt_flickr_deactivate() {
  wp_clear_scheduled_hook('mt_flickr');
}

function mt_update_flickr() {
  $m = new Medieteknik_Flickr_Robot();
  $m->main();
} 

?>
