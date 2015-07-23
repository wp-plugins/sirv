<?php

/**
 * Plugin Name: Sirv
 * Plugin URI: http://sirv.com
 * Description: Instantly resize or crop images to any size. Add watermarks, titles, text and image effects. Embed them as images or galleries. They are responsive and can be delivered by CDN for faster loading. Look for the Sirv tab when you embed media.  <a href="admin.php?page=sirv/sirv.php">Settings</a>
 * Version: 1.0
 * Author: sirv.com
 * Author URI: sirv.com
 * License: GPLv2
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


// load shortcodes
require_once (dirname (__FILE__) . '/sirv/shortcodes.php');

//create shortcode's table on plugin activate
register_activation_hook( __FILE__, 'sirv_activation_callback' );

function sirv_activation_callback(){
    sirv_create_plugin_tables();

    $notices= get_option('sirv_admin_notices', array());
    $notices[]= 'Congratulations! You\'ve just installed Sirv for WordPress! Now connect to Sirv account <a href="admin.php?page=sirv/sirv.php">here</a> so you can start using it.';
    update_option('sirv_admin_notices', $notices);
}


//show message on activation
add_action('admin_notices', 'sirv_admin_notices');

function sirv_admin_notices() {
  if ($notices= get_option('sirv_admin_notices')) {
    foreach ($notices as $notice) {
      echo "<div class='updated'><p>$notice</p></div>";
    }
    delete_option('sirv_admin_notices');
  }
}

function sirv_create_plugin_tables(){
    global $wpdb;

    $table_name = $wpdb->prefix . 'sirv_shortcodes';
    $sql = "CREATE TABLE $table_name (
      id int unsigned NOT NULL auto_increment,
      width varchar(20) DEFAULT 'auto',
      thumbs_height varchar(20) DEFAULT NULL,
      gallery_styles varchar(255) DEFAULT NULL,
      align varchar(30) DEFAULT '',
      profile varchar(100) DEFAULT 'false',
      link_image varchar(10) DEFAULT 'false',
      show_caption varchar(10) DEFAULT 'false',
      use_as_gallery varchar(10) DEFAULT 'false',
      use_sirv_zoom varchar(10) DEFAULT 'false',
      images text DEFAULT NULL,
      PRIMARY KEY (id)) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

register_deactivation_hook(__FILE__, 'sirv_drop_plugin_tables');

function sirv_drop_plugin_tables(){
    global $wpdb;

    $table_name = $wpdb->prefix . 'sirv_shortcodes';
    $sql = "DROP TABLE IF EXISTS $table_name"; 

    $wpdb->query($sql);

    delete_sirv_settings();
}


$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'sirv_plugin_settings_link' );

function sirv_plugin_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=sirv/sirv.php">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 


//include plugin for tinyMCE to show sirv gallery shortcode in visual mode
add_filter('mce_external_plugins', 'sirv_tinyMCE_plugin_shortcode_view');

function sirv_tinyMCE_plugin_shortcode_view () {
     return array('sirvgallery' => plugins_url('sirv/js/wp-sirv-shortcode-view.js', __FILE__));
}


//create menu for wp plugin and register settings
add_action("admin_menu", "sirv_create_menu");

function sirv_create_menu(){
    add_menu_page('Sirv Plugin Settings', 'Sirv', 'manage_options', __FILE__, 'sirv_settings_page',plugins_url('/sirv/assets/icon.png', __FILE__));
    add_action( 'admin_init', 'register_sirv_settings' );
}


//add styles for tinyMCE plugin
add_action('admin_init', 'sirv_tinyMCE_plugin_shortcode_view_styles');

function sirv_tinyMCE_plugin_shortcode_view_styles(){
    add_editor_style( plugins_url('/sirv/css/wp-sirv-shortcode-view.css', __FILE__) );
}


function register_sirv_settings(){
    register_setting( 'sirv-settings-group', 'AWS_KEY' );
    register_setting( 'sirv-settings-group', 'AWS_SECRET_KEY' );
    register_setting( 'sirv-settings-group', 'AWS_HOST' );
    register_setting( 'sirv-settings-group', 'AWS_BUCKET' );

    update_option('AWS_HOST', 's3.sirv.com');

}


function delete_sirv_settings(){
    delete_option( 'AWS_KEY' );
    delete_option( 'AWS_SECRET_KEY' );
    delete_option( 'AWS_HOST' );
    delete_option( 'AWS_BUCKET' );
}


function sirv_settings_page(){
    include('sirv/options.php');
}


// create new tab Sirv
add_filter( 'media_upload_tabs', 'sirv_tab' );

function sirv_tab( $tabs ) {
    $tabs['sirv'] = "Insert from Sirv";
    return $tabs;
}


// upload scripts, css and iframe with content when tab selected
add_action( 'media_upload_sirv', 'sirv_tab_content' );

function sirv_tab_content() {

    wp_enqueue_style('sirv_style', plugins_url('/sirv/css/wp-sirv.css', __FILE__));
    wp_enqueue_script( 'sirv_logic', plugins_url('/sirv/js/wp-sirv.js', __FILE__), array( 'jquery', 'jquery-ui-sortable' ), '1.0.0');
    wp_localize_script( 'sirv_logic', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'assets_path' => plugins_url('/sirv/assets', __FILE__) ) );
    wp_enqueue_script( 'sirv_logic-md5', plugins_url('/sirv/js/wp-sirv-md5.min.js', __FILE__), array(), '1.0.0');

    wp_iframe( 'sirv_frame' );
}


// load iframe
function sirv_frame(){

    include('sirv/template.php');
}


// remove http(s) from host in sirv options
add_action( 'admin_notices', 'sirv_check_option');

function sirv_check_option(){
    global $pagenow;
    if ($pagenow == 'admin.php' && $_GET['page'] =='sirv/sirv.php') {
        if ( (isset($_GET['updated']) && $_GET['updated'] == 'true') || (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') ) {
            update_option('AWS_HOST', preg_replace('/(http|https)\:\/\/(.*)/ims', '$2', get_option('AWS_HOST')));
        }
    }
}



//-------------------------------------------------------------Ajax requests-------------------------------------------------------------------------//

//use ajax request to show data from sirv
add_action( 'wp_ajax_sirv_get_aws_object', 'sirv_get_aws_object_callback' );

function sirv_get_aws_object_callback() {

    if (isset($_POST['path'])){
        $data = $_POST['path'];
        if (empty($data)) $data = '/';
    }

    require_once 'sirv/sirv_api.php';
    require_once 'sirv/options-service.php';


    $host = getValue::getOption('AWS_HOST');
    $bucket = getValue::getOption('AWS_BUCKET');
    $key = getValue::getOption('AWS_KEY');
    $secret_key = getValue::getOption('AWS_SECRET_KEY');
    

    $s3client = get_s3client($host, $key, $secret_key);
    $obj = get_object_list($bucket, $data, $s3client);


    $cleared_object = array("bucket" => $obj->get("Name"),
                            "current_dir" => $obj->get("Prefix"), 
                            "contents" => $obj->get("Contents"), 
                            "dirs" => $obj->get("CommonPrefixes"));

    echo json_encode($cleared_object);

    wp_die(); // this is required to terminate immediately and return a proper response
}


//use ajax to upload images on sirv.com
add_action('wp_ajax_sirv_upload_files', 'sirv_upload_files_callback');

function sirv_upload_files_callback(){
    require_once 'sirv/sirv_api.php';
    require_once 'sirv/options-service.php';


    if(!(is_array($_POST) && is_array($_FILES) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    $current_dir = $_POST['current_dir'];

    $host = getValue::getOption('AWS_HOST');
    $bucket = getValue::getOption('AWS_BUCKET');
    $key = getValue::getOption('AWS_KEY');
    $secret_key = getValue::getOption('AWS_SECRET_KEY');

    $s3client = get_s3client($host, $key, $secret_key);

    for($i=0; $i<count($_FILES); $i++) {

      $filename = $current_dir . basename( $_FILES[$i]["name"]);
      $file = $_FILES[$i]["tmp_name"];

      echo upload_web_file($bucket, $s3client, $filename, $file);

    }

    wp_die();

}


//use ajax to store gallery shortcode in DB
add_action('wp_ajax_sirv_save_shortcode_in_db', 'sirv_save_shortcode_in_db');

function sirv_save_shortcode_in_db(){

    if(!(is_array($_POST) && isset($_POST['shortcode_data']) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'sirv_shortcodes';

    $data = $_POST['shortcode_data'];
    $data['images'] = serialize($data['images']);

    $wpdb->insert($table_name, $data);

    echo $wpdb->insert_id;


    wp_die();
}


//use ajax to get data from DB by id
add_action('wp_ajax_sirv_get_row_by_id', 'sirv_get_row_by_id');

function sirv_get_row_by_id(){

    if(!(is_array($_POST) && isset($_POST['row_id']) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'sirv_shortcodes';

    $id = $_POST['row_id'];

    $row =  $wpdb->get_row("SELECT * FROM $table_name WHERE id = $id", ARRAY_A);

    $row['images'] = unserialize($row['images']);

    echo json_encode($row);

    //echo json_encode(unserialize($row['images']));


    wp_die();
}


//use ajax to save edited shortcode
add_action('wp_ajax_sirv_update_sc', 'sirv_update_sc');

function sirv_update_sc(){

    if(!(is_array($_POST) && isset($_POST['row_id']) && isset($_POST['shortcode_data']) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'sirv_shortcodes';

    $id = $_POST['row_id'];
    $data = $_POST['shortcode_data'];
    $data['images'] = serialize($data['images']);


    $row =  $wpdb->update($table_name, $data, array( 'ID' => $id ));

    echo $row;


    wp_die();
}


//use ajax to add new folder in sirv
add_action('wp_ajax_sirv_add_folder', 'sirv_add_folder');

function sirv_add_folder(){

    if(!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    require_once 'sirv/sirv_api.php';
    require_once 'sirv/options-service.php';

    $current_dir = $_POST['current_dir'];
    $new_dir = $_POST['new_dir'];

    $host = getValue::getOption('AWS_HOST');
    $bucket = getValue::getOption('AWS_BUCKET');
    $key = getValue::getOption('AWS_KEY');
    $secret_key = getValue::getOption('AWS_SECRET_KEY');

    $s3client = get_s3client($host, $key, $secret_key);

    echo create_folder($bucket, $current_dir.$new_dir.'/', $s3client);

    wp_die();
}


//use ajax to check customer login details
add_action( 'wp_ajax_sirv_check_connection', 'sirv_check_connection' );

function sirv_check_connection() {

    if(!(is_array($_POST) && defined('DOING_AJAX') && DOING_AJAX)){
        return;
    }

    require_once 'sirv/sirv_api.php';

    $host = "http://" . $_POST['host'];
    $bucket = $_POST['bucket'];
    $key = $_POST['key'];
    $secret_key = $_POST['secret_key'];    

    try{
        $s3client = get_s3client($host, $key, $secret_key);  
        $obj = get_object_list($bucket, '/', $s3client);
        
        if($s3client->doesBucketExist("obdellus") != 1){
            echo "Connection failed. Please check your bucket name.";    
        };
        echo "Connection: OK";
    }catch(Exception $e){
        echo "Connection failed. Please check sirv details.";
    }


    wp_die();
}

?>