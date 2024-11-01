<?php
/**
 * @filesource wpxp-background-media/admin/inc/config.php
 * @author Alex Diokou
 * @copyright WPXPertise
 * @url https://wpxpertise.com
 * @version 1.0.0
 * @package wpxp-background-media
 */

defined('ABSPATH') or die('No script direct access please!');

$wpxp_bgm_config=array();

//Custom DB table prefix
$wpxp_bgm_config['prefix']='wpxp_bgm_';

//Plugin Version
$wpxp_bgm_config['version']='1.0.0';

//Active theme Layout Break Points
$wpxp_bgm_config['layout_break_points']=array(
  array('size'=> 'small_screen', 'max'=> 640, 'mobile'=> 'Y'),
  array('size'=> 'medium_large', 'min'=> 641, 'max'=> 768, 'mobile'=> 'N'),
  array('size'=> 'large', 'min' => 769, 'max' => 1024, 'mobile' => 'Y'),
  array('size'=> 'standard_hd', 'min'=> 1025, 'max'=> 1280, 'mobile'=> 'N'),
  array('size'=> 'full_hd', 'min'=> 1281, 'max'=> 1920,  'mobile'=> 'N'),
  array('size'=> 'full', 'min'=> 1921, 'mobile'=> 'N'),
);

$wpxp_bgm_config['wpxp_overlay']=array(
  'overlay_bgcolor' => '#000', 
  'overlay_opacity' => 40,
  'content_overlay_bgcolor' => '#000', 
  'content_overlay_opacity' => 40,
  'max_width' => '100%', 
  'show_content_overlay' => 'Y'
);
