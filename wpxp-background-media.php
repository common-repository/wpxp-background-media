<?php
/**
* Plugin Name: WPXP Background Media
* Version: 1.0.0
* Description: Lets you set a background image or video to a whole page or just elements of that page. You can set as many background images/videos as you want on each page. Make sure you qualify each CSS selector with the current page slug (e.g. #home .intro).
* Author: Alex Diokou
* Author URI: https://wpxpertise.com
* Plugin URI: https://wpxpertise.com/resources/plugins/wpxp-background-media
 * Text Domain: wpxp-background-media
 * License: GPLv2 or later
 */

/*
Copyright (C) 2019 Alex Diokou, https://wpxpertise.com

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; If not, see <http://www.gnu.org/licenses/>
*/


defined('ABSPATH') or die('No script direct access please!');

define('WPXP_BGM_PATH', plugin_dir_path(__FILE__));

if(!class_exists('wpxp_Helper')){
  require_once('admin/inc/helpers.php');
}

require_once('admin/inc/config.php');

class wpxpBackgroundMedia{
	private $selectors=array();
  private $prefix;
  private $version;
  private $nonceStr;

  private $foregroundcolor='#000';
  private $overlay_bgcolor='#000';
  private $errors=array();

  public function __construct(){
    $this->nonceStr='wpxp-background-media';
    add_action('plugins_loaded', array($this, 'init'));
  }

  /**
   * 
   */
  public function enqueueScripts(){
    wp_enqueue_script('wpxp_bgm_public', plugins_url('public/js.js', __FILE__),                  array('jquery'), $this->version, true);
  }

  /**
   *
   */
  public function init(){
    //Reserved to users who can edit static pages
    if(current_user_can('publish_pages')){
      $this->setMetaboxActions();
    }

    //Reserved for users with admin privileges
    if(current_user_can('activate_plugins')){
      //Triggered when BGM Layout Breaking Point Settings updated
      add_action('wpxp_bgm_update_image_sizes', array($this, 'setImageSizes'));
      //Action triggered when Overlay Settings updated
      add_action('wpxp_bgm_update_overlay', 
                  array($this,'updateOverlaySettings'));
    }

    add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
    add_action('wp_footer', array($this, 'wpxpOverLayScript'));
  }

  /**
   * 
   */
  public function updateOverlaySettings(){
    require_once('admin/classes/media-queries.php');
    $wpxp=new wpxpMediaQueries();
    $wpxp->updateOverlaySettings();
  }

  /**
   * 
   */
  private function setMetaboxActions(){
    add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
    add_action('add_meta_boxes', array($this, 'createMetaBox'));
    add_action('save_post', array($this, 'updateMetaData'), 10, 2 );
  }

  /**
  * Show background media metaboxes
  */
  public function createMetabox(){
    if( ! current_user_can('publish_pages')){
      return;
    }

    require_once('admin/classes/metabox-display.php');
    $mb=new wpxpMetaboxDisplay();

    add_meta_box('wpxp_background_media', __('Background Media',  'wpxp-background-media'), array($mb, 'displayMeta'),
      array('page', 'lead_capture')
    );
  }

  /**
  * Update media metadata and "graphics.css" file
  */
  public function updateMetaData($post_id, $post){
    if( ! current_user_can('publish_pages')){
      return;
    }

    require_once('admin/classes/media-metadata.php');
    $md=new wpxpMediaMetaData();
    $md->update($post_id, $post);
  }

  /**
   * Stylesheet for the appearance of BG media metaboxes
   * Javascript to add/remove metaboxes
   */
  public function enqueueAdminScripts(){
    if( ! current_user_can('publish_pages')){
      return;
    }
    
    $version='1.0.0';
    $in_footer=true;
    $media='screen';
    //expecting "page"
    $post_type=wpxp_Helper::sanitizedGET('post');
    // expecting "edit"
    $action=wpxp_Helper::sanitizedGET('action');

    if($post_type===FALSE || $action===FALSE){return; }
    wp_enqueue_media();
    wp_nonce_field( basename( __FILE__ ), $this->nonceStr );
  }

	/**
    * Use JS to append graphics.css file to document "head"
    * It ensures those styles will take precedence
    * Also need to set the overlay on specified $selectors
	*/
	public function wpxpOverLayScript(){
    global $post;
    $media_info=$this->getTargetSelectors($post->ID);
    $Str='<script>';

    if(!is_array($media_info) || (empty($media_info['images']) && 
    empty($media_info['video']))){
      $Str.='var wpxp_css_url=\'\', wpxp_image_selectors=[], wpxp_bgvideo={};';
    }
    else{
      $selectors=$media_info['images'];
      $bgvideo=$media_info['video'];
      $Str.='var wpxp_bgvideo='.json_encode($bgvideo).';
      var wpxp_css_url=\''.plugins_url('public/graphics.css', __FILE__).'\';
      var wpxp_image_selectors='.json_encode($selectors).';';
    }
    
    $Str.='</script>';

    echo $Str;
	}

	/**
	 *
	 */
	private function getTargetSelectors($post_id){
    $meta_data=get_post_meta($post_id, 'wpxp_background_media', true);
	
		if(!is_array($meta_data) || empty($meta_data) ){
			return false;
    }
    
    $selectors=array();
    $video_data=array();

		foreach($meta_data as $row){
      if(!is_array($row) || !array_key_exists('media_type', $row)){continue;}

      if($row['media_type']=='image' && $row['media_id']>0){
        $selectors[]=array('selector' => $row['selector'], 'cover' => $row['cover'], 'side' => $row['side']);
      }
      else if($row['media_type']=='video' && $row['selector']!=''){
        $video_data=array('embed_code'=> html_entity_decode($row['embed_code']),
                          'selector' => $row['selector'],
                          'bgcolor' => $row['video_overlay_bgcolor'],
                          'opacity' => $row['video_opacity'],
                        );
      }
		}

		return array('images' => $selectors, 'video' => $video_data);
	}

/* =====================================================================
                          Plugin Configuration
   =====================================================================
*/

  /**
  *
  * @param string $networkwide
  */
  public function wpxpActivatePlugin($networkwide) {
    if( ! current_user_can('activate_plugins')){
      return;
    }
    $this -> propagateToNetwork('activatePlugin', $networkwide);
  }

  /**
   *
   * @param string $networkwide
   */
  public function wpxpDeactivatePlugin($networkwide) {
    if( ! current_user_can('activate_plugins')){
      return;
    }
    $this -> propagateToNetwork('deactivatePlugin', $networkwide);
  }

  /**
   *
   * @param unknown $networkwide
   */
  public function wpxpUninstallPlugin($networkwide){
    if( ! current_user_can('activate_plugins')){
      return;
    }
    $this-> propagateToNetwork('uninstallPlugin', $networkwide);
  }

  /**
   *
   * @param int $blog_id
   * @param int $user_id
   * @param string $domain
   * @param string $path
   * @param int $site_id
   * @param string $meta
   */
  public function wpxp_ActivateNewBlog($blog_id, $user_id, $domain, $path,    $site_id, $meta ) {
    global $wpdb;

    if(is_plugin_active_for_network('wpxp-background-media/wpxp-background-media.php')) {
      $old_blog = $wpdb->blogid;
      switch_to_blog($blog_id);
      //
      $this->activatePlugin();
      //
      switch_to_blog($old_blog);
    }
  }

  /**
   *
   * @param string $pfunction
   * @param string $networkwide
   */
  private function propagateToNetwork($myfunction, $networkwide) {
    global $wpdb;

    if (function_exists('is_multisite') && is_multisite()) {
      // check if it is a network activation - if so, run the activation function
      // for each blog id
      if ($networkwide) {
        $old_blog = $wpdb->blogid;
        // Get all blog ids
        $blogids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

        foreach ($blogids as $blog_id) {
          switch_to_blog($blog_id);
          call_user_func(array($this, $myfunction), $networkwide);
        }

        switch_to_blog($old_blog);
        return;
      }
    }

    call_user_func(array($this, $myfunction), $networkwide);
  }

  /**
   * Create an option element used to save the CSS
   */
  private function activatePlugin(){
    $options=get_option('wpxp_background_graphics');

    if($options===FALSE){
      $graphics=array();
      add_option('wpxp_background_graphics', $graphics, '', 'no');
    }
    $this->renameOption('wpxp_layout_break_points', 'wpxp_bgm_layout_break_points');

    $this->setLayoutBreakingPoints();
  }

  /**
   * Will be removed at the next version of this plugin
   */
  private function renameOption($old_name, $new_name){
    global $wpdb;

    $sql=$wpdb->prepare("UPDATE $wpdb->options SET option_name=%s WHERE option_name=%s LIMIT 1", $new_name, $old_name);

    if($wpdb->query($sql)===FALSE){
      $this->errors[]=$wpdb->last_error;
    }
  }

  /**
   * Do nothing when plugin is deactivated
   * Too much work already invested in setting each page graphics
   * Keep data in case the plugin is reactivated
   */
  private function deactivatePlugin(){
    return;
  }

  /**
   * Remove options and post_meta created by this plugin
   * House cleaning before WordPress deletes all the files
   */
  private function uninstallPlugin(){
    //
    delete_option('wpxp_background_graphics');
    //Delete all post_meta with specified key value
    delete_post_meta_by_key('wpxp_background_video');
    delete_post_meta_by_key('wpxp_background_media');
    delete_option('wpxp_bgm_layout_break_points');
    delete_option('wpxp_bgm_overlay');
  }

  /**
   * Retrieve current plugin version from header info
   */
  private function setPLuginVersion(){
    global $wpxp_bgm_config;

    $headers=get_plugin_data(__FILE__, false, false);

    if(is_array($headers) && isset($headers['Version']) && 
        trim($headers['Version'])!=''){
      $this->version=$headers['Version'];
    }
    else{
      $this->version=$wpxp_bgm_config['version'];
    }
  }

  /**
   * Stores layout breaking points info in DB
   * But does not overwrites already present record if non null
   */
  private function setLayoutBreakingPoints(){
    global $wpxp_bgm_config;
  
    $options=get_option('wpxp_bgm_layout_break_points');
    //
    if(!is_array($options) || empty($options)){
      $options=$wpxp_bgm_config['layout_break_points'];
      update_option('wpxp_bgm_layout_break_points', $options, 'no'); 
    }
  }

  //WordPress Reserved Image Size Names
  private function getReservedImageSizeNames(){
    return array('thumb', 'thumbnail', 'medium', 'medium_large', 
                 'large', 'post-thumbnail'
                );
  }

  /**
   * Create Image Sizes for Layout Break Points
   * not matching WordPress default images widths
   */
  public function setImageSizes(){
    $options= get_option('wpxp_bgm_layout_break_points');

    if(!is_array($options) && empty($options)){return; }
    //Exclude reserved names
    $reserves_names=$this->getReservedImageSizeNames();

    foreach($options as $row){
      $size=$row['size'];
      if(isset($row['max'])){
        remove_image_size($size);
        add_image_size($size, $row['max']); //unlimited height
      }
    }
  }

 
}//end class

$wpxpBM=new wpxpBackgroundMedia();

if(is_admin()){
  register_activation_hook(__FILE__, array($wpxpBM,'wpxpActivatePlugin'));
  register_deactivation_hook(__FILE__, array($wpxpBM,'wpxpDeactivatePlugin'));
  add_action('wpmu_new_blog', array($wpxpBM,'wpxp_ActivateNewBlog'), 10, 6);
  //
  require_once('uninstall.php');
  //Handle Admin Panel AJAX Requests
  require_once('admin/index.php');
  $wpxpBGAdmin=new wpxpBGMediaAdmin('1.0.0', 99);
}