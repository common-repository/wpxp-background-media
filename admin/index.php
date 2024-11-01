<?php
/**
 * WPXP Background Media Plugin
 * @filesource wpxp-background-media/admin/index.php
 * @author Alex Diokou
 * @copyright WPXPertise
 * @url https://wpxpertise.com
 * @version 1.0.0
* @package wpxp-background-media
 */

defined('ABSPATH') or die('No script direct access please!');


class wpxpBGMediaAdmin{
  private $errors=array();
  private $action;
  private $nonceStr;

  public function __construct($version, $menu_position){
    $this->version=$version;
    $this->menu_position=$menu_position;
    $this->nonceStr='wpxp-background-media';
    $this->setActionValue();
    
    add_action('init', array($this, 'init'));
  }

  public function init(){
    //Stylesheet and JS for both the admin and bgmedia metabox
    add_action('admin_enqueue_scripts', array($this,'enqueue_ajax_scripts'));
    add_filter('image_size_names_choose', array($this, 'showCustomImageSizes'));

    if(current_user_can('activate_plugins')){
      add_action('admin_menu', array($this, 'createPluginMenu'));
      add_action('wp_ajax_wpxp_bgm_dashboard', array($this,'getAjaxResponse'));
      add_action('wp_ajax_wpxp_bgm_settings', array($this,'getAjaxResponse'));
      add_action('wp_ajax_wpxp_bgm_overlay', array($this,'getAjaxResponse'));
    }
  }

  /**
  * To display Custom Sizes in Media Library "Attachment Display Settings"
  */
  public function showCustomImageSizes($sizes){
    $options=get_option('wpxp_bgm_layout_break_points');

    if(is_array($options) && !empty($options)){
      foreach($options  as $row){
        $key=$row['size'];
        $sizes[$key]=__(wpxp_Helper::convertKeyToWords($key),'wpxp-background-media');
      }
    }

    return $sizes;
  }

  /**
  *
  */
  public function createPluginMenu(){
    //Access limited to users the admin role
    $capabilities='activate_plugins';
    $menu_slug='wpxp_bgm_menu';
    //
    $icon_url=plugin_dir_url(dirname(__FILE__)).'images/menu_icon.png';
    $icon_url='';
    //
    add_menu_page(__('Background Media Settings', 'wpxp-background-media'), 
      'WPXP BG Media', $capabilities, $menu_slug, array($this, 'openPage'), $icon_url, $this->menu_position
      );
  }

  /**
  *
  */
  public function enqueue_ajax_scripts($hook) {
    $media='all';

    wp_enqueue_style('wpxp_bgm_style', plugins_url('/css/style.css', __FILE__), array(), $this->version, $media);

    wp_enqueue_script('wpxp_bgm_ajax_script', plugins_url('/js/admin.js', __FILE__), array('jquery'), $this->version, false);

    wp_localize_script('wpxp_bgm_ajax_script', 'wpxp_bgm_ajax_obj',
      array('ajax_url' => admin_url('admin-ajax.php'), 
        'nonce' => wp_create_nonce($this->nonceStr), 
        'loading' => __('processing request...', 'wpxp-background-media'),
        'request_failed' => __('Request failed', 'wpxp-background-media'),
        'admin_panel' => array('menu_name' => 'wpxp_bgm_menu', 
            'tabsID' => '#wpxp_bgm_tabs', 'contentID' => '#wpxp_bgm_content', 
            'messageID' => '#wpxp_bgm_message', 'navbarID' => '#wpxp_bgm_navbar'
          )
    ));
  }

  /**
   *  Sets the "action" parameter of he incoming POST or GET request
   */
  private function setActionValue(){
    $action=wpxp_Helper::sanitizedPOST('action');
    $this->action=($action)?$action:'wpxp_bgm_dashboard';
  }

  /**
  *
  */
  public function openPage() {
    if(!current_user_can('publish_posts')){
        return ['html' => __('Action not allowed', 'wpxp-lead-capture')];
    }

    if(current_user_can('publish_pages')){
      $info=$this->handleAction();

      $Str='<div class="wrap" id="wpxp_bgm_wrapper">
      <h2 id="wpxp_bgm_header">'.
      __('Background Media Settings', 'wpxp-background-media').'</h2>
      <ul id="wpxp_bgm_tabs">
      <li class="';
      $Str.=($this->action=='wpxp_bgm_dashboard')? 'selected': '';
      $Str.='"><a href="" class="tab" data-action="wpxp_bgm_dashboard">'.
      __('Dashboard', 'wpxp-background-media').'</a></li>
      <li class="';
      $Str.=($this->action=='wpxp_bgm_settings')? 'selected': '';
      $Str.='"><a href="" class="tab" data-action="wpxp_bgm_settings">'.
      __('Settings', 'wpxp-background-media').'</a></li>
      <li class="';
      $Str.=($this->action=='wpxp_bgm_overlay')? 'selected': '';
      $Str.='"><a href="" class="tab" data-action="wpxp_bgm_overlay">'.
      __('Overlay', 'wpxp-background-media').'</a></li>
      </ul>
      <div id="wpxp_bgm_message">'.$info['message'].'</div>
      <div id="wpxp_bgm_content">'.$info['html'].'</div>
      <div id="wpxp_bgm_navbar"></div>
      <div>';

      echo $Str;
    }
  }

  /**
   * Callback function of all the AJAX related hooks
   */
  public function getAjaxResponse(){
    $nonce= $_REQUEST['_ajax_nonce'];

    //dies if third-party referer
    check_ajax_referer($this->nonceStr, '_ajax_nonce', true);

    if(!wp_verify_nonce($nonce, $this->nonceStr)){
      $response=array('message'=> 'Invalid Request');
      wp_send_json_error($response);
    }
    else if(current_user_can('publish_pages')){
      $response=$this->handleAction();
      wp_send_json($response);
    }
    else{
      $url=wp_login_url();
      wp_redirect( $url );
      exit;
    }
  }

  /**
   * 
   */
  private function handleAction() {
    if($this->action=='wpxp_bgm_settings'){
      //Load file containing PHP class to use
      require_once('classes/settings.php');
      //Instance of that PHP class
      $wpxp= new wpxpBGMSettings();
      //
      $response=$wpxp->processRequest();
    }
    elseif($this->action=='wpxp_bgm_overlay'){
      require_once('classes/overlay.php');
      $wpxp= new wpxpBGMOverlay();
      $response=$wpxp->processRequest();
    }
    else{
      require_once('classes/dashboard.php');
      $wpxp= new wpxpBGMDashboard();
      //
      $response=$wpxp->processRequest();
    }
    
    return $response;
  }

}//end class