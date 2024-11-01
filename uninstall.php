<?php
if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

function wpxpBGMUninstallPlugin(){
  if(! defined('WP_UNINSTALL_PLUGIN')){return; }
  //
  delete_option('wpxp_background_graphics');
  //Delete all post_meta with specified key value
  delete_post_meta_by_key('wpxp_background_video');
  delete_post_meta_by_key('wpxp_background_media');
  delete_option('wpxp_bgm_layout_break_points');
  delete_option('wpxp_bgm_overlay');
}

register_uninstall_hook(__FILE__, 'wpxpBGMUninstallPlugin');