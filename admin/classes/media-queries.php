<?php
/**
 * WPXP Background Media Plugin
 * @filesource wpxp-background-media/admin/classes/media-queries.php
 * @author Alex Diokou
 * @copyright WPXPertise
 * @url https://wpxpertise.com
 * @version 1.0.0
 * @package wpxp-background-media
 */

defined('ABSPATH') or die('No script direct access please!');

class wpxpMediaQueries{

  public function __construct(){

  }

  public function updateOverlaySettings(){
    $graphics=get_option('wpxp_background_graphics');
    if(!is_array($graphics) || empty($graphics)){return; }
    $this->updateGraphicMediaQueries($graphics);
  }

  /**
   * 
   */
  public function update($post_bgmedia_data, $post_id){
    $this->updateGraphicOptions($post_bgmedia_data, $post_id);
  }

  /**
   * 
   */
  private function updateGraphicOptions($post_bgmedia_data, $post_id){
    $graphics=get_option('wpxp_background_graphics');
    //
    $graphics= is_array($graphics) ? $graphics: array();
    //Unique identifier because the slug may change
    $page_key='page_'.$post_id;

    $data=array();

    foreach($post_bgmedia_data as $k => $row){
      if($row['media_type']=='video' || $row['media_id']==0){
        //Skip if BG video or image ID not specified
        continue;
      }
      else{
        $imageSizes=$this->getImageInfo($row['media_id']);
      }

      $row['sizes']=$imageSizes;

      $data[]=$row;
    }


    //Put all the pertinent info in array
    $graphics[$page_key]=$data;
    //
    $graphics=$this->cleanGraphics($graphics);
    //
    update_option('wpxp_background_graphics', $graphics, 'no');
    //
    $this->updateGraphicMediaQueries($graphics);
  }

  /**
  * Remove -autosave- and -revision- versions
  */
  private function cleanGraphics($graphics){
    if(!is_array($graphics)){ return array(); }
    $data=array();

    foreach($graphics as $key => $page){
      foreach($page as $row){
        if(isset($row['slug']) && 
          strpos($row['slug'], '-autosave-')===FALSE && 
          strpos($row['slug'], '-revision-')===FALSE){
          $data[$key][]=$row;
        }
      }
    }

    return $data;
  }

  /**
   * CSS Media Queries for defined layout breaking points
   * for each page with a specified background image
   * @param array $graphics
   */
  private function updateGraphicMediaQueries($graphics){
    $Str='';

    $layout_break_points=$this->getMediaQueriesBreakingPoints();

    foreach($layout_break_points as $attributes){
      $size=$attributes['size'];
      $Str.='@media all '.$this->getMediaQueryLimits($attributes).'{';
      $mobile=isset($attributes['mobile'])? $attributes['mobile'] : 'N';

      //Loop through all pages
      foreach($graphics as $key => $page){
        //Loop through a given page BG images
        foreach($page as $row){
          //Background color and image CSS Selector
          $Str.=$this->getPageCSSSelector($row, $size, $mobile);
          //Overlay on top of background image
          $Str.=$this->getOverlayStyle($row, $size, $mobile);
        }
      }

      $Str.='} ';
    }

    if($Str!=''){
      $this->updateGraphicStyleFile($Str);
    }
  }

  /**
   * Given the media_id, retrieve all information matching each breaking point width
   *
   * @param int $media_id
   * @return array on success otherwise false
   */
  private function getImageInfo($media_id){
    $breaking_points=$this->getMediaQueriesBreakingPoints();

    if(!is_array($breaking_points) || empty($breaking_points)){
        return false;
    }

    $info=array();
    
    foreach($breaking_points as $attributes){
      $size=$attributes['size'];
        //if 'max' key not specified then use original image attributes
        $dimensions=(isset($attributes['max']) && is_numeric($attributes['max']))? array($attributes['max'], 0): 'full';

        $image=wp_get_attachment_image_src($media_id, $dimensions);

        if(is_array($image)){
          //Sets the minimum height of target selector
          $ratio=number_format((100*$image[2]/$image[1]),3);
          //Eliminate gap between BG images on opposite sides
          $ratio=floor($ratio);
          //0: url, 1: width, 2: height
          $info[$size]=array('url' => $this->stripHttpFromURL($image[0]),
            'width' =>$image[1], 'height' =>$image[2], 'ratio'=> $ratio);
        }
    }

    return $info;
  }

  /**
  * To make image URL agnostic of http or https protocol
  */
  private function stripHttpFromURL($url){
    return substr($url, strpos ($url , ':')+1);
  }


  /**
   * Minimum and Maximum width of CSS Media Query
   * @param array $limits
   * @return string
   */
  private function getMediaQueryLimits($limits){
    $Str='';
    if(isset($limits['min']) && is_numeric($limits['min'])){
      $Str.='and (min-width:'.$limits['min'].'px)';
    }
    if(isset($limits['max']) && is_numeric($limits['max'])){
      $Str.='and (max-width:'.$limits['max'].'px)';
    }

    return $Str;
  }


  /**
   * Page Overlay Selector
   * @param unknown $slug
   */
  private function getPageOverlayStyle($css_selector, $height, $opacity, $bgcolor, $use_rgba) {
    if(trim($css_selector)==''){ return '';}

    $Str=$css_selector.' .wpxp_overlay {display:block;';

    if($use_rgba){
      $Str.=$this->getRGBA($bgcolor, $opacity);
    }
    else{
      $Str.=$this->getBackgroundColorAndOpactity($bgcolor, $opacity);
    }

    $Str.='height:'.$height.';}';

    return $Str;
  }

  /**
   * 
   */
  private function getBackgroundColorAndOpactity($bgcolor, $opacity){
    return 'background-color:'.$bgcolor.';'.
          $this->getLayerOpacityCSS($opacity);
  }

  /**
   * 
   */
  private function getRGBA($bgcolor, $opacity){
    return 'background-color:rgba('.
    wpxp_Helper::getRGBA($bgcolor, $opacity).')';
  }

  /**
   * CSS style of page overlay
   * @param array $row media attributes
   * @param string $size screen size
   * @return string
   */
  private function getOverlayStyle($row, $size, $mobile){
    //Whether the overlay uses RGBA background color or bgcolor and opacity
    $use_rgba=(isset($row['use_rgba']) && $row['use_rgba']=='Y')? true:false;

    if(trim($row['selector'])==''){ return '';}

    $target_selector=$row['selector'];

    $opacity=$this->getOverlayOpacity($row, $mobile);

    $overlay_bgcolor=isset($row['overlay_bgcolor'])? $row['overlay_bgcolor']: $this->overlay_bgcolor;

    $overlay_height=$this->getOverlayHeight($row, $size, $mobile);

    if($opacity>0){
      $Str=$this->getPageOverlayStyle($target_selector, $overlay_height, $opacity, $overlay_bgcolor, $use_rgba);
    }
    else {
      $Str=$target_selector.' .wpxp_overlay,'.
      $target_selector.' .wpxp_content_overlay{display:none}';
    }

    return $Str;
  }

  /**
   * Text Color when overlay applied to page
   * @param array $row
   * @return string
   */
  private function getPageOverLayTextColor($row){
      if(isset($row['overlay_color']) && trim($row['overlay_color'])!=''){
          return $row['overlay_color'];
      }

      return '#fff';
  }

  /**
   *
   * @param array $row
   * @param string $size (e.g. medium, large, full)
   * @param string $mobile whether to apply to mobile screen sizes
   * @return string
   */
  private function getPageTextStyle($row, $size, $mobile){
    if(trim($row['selector'])==''){ return ''; }

    $overlay_text_color=$this->getPageOverLayTextColor($row);
    $opacity=$this->getOverlayOpacity($row, $mobile);

    $Str='';
    if($opacity>0){
      //We assume the overlay is dark so copy should be white
      $Str='color:'.$overlay_text_color.'}';
    }
    else if(isset($row['color']) && $row['color']!=''){
      $Str.='color:'.$row['color'].'} ';
    }

    return $Str;
  }

  /**
   *
   * @param array $row
   * @param string $mobile
   */
  private function getOverlayOpacity($row, $mobile){
    $overlay=($mobile=='Y')? $row['mobile_overlay'] : $row['overlay'];

    if($overlay=='Y'){
      if(is_numeric($row['opacity']) && $row['opacity']>0){
        return  $row['opacity'];
      }
    }

    return 0;
  }

  /**
   *
   * @param array $row
   * @param string $size predefined image sizes (e.g. medium, large, full)
   */
  private function getOverlayHeight($row, $size, $mobile){
    if(!isset($row['sizes'][$size])){
        return 0;
    }

    if($mobile=='Y'){
      //$height=$row['sizes'][$size]['height'].'px';
      $height='100vh';
    }
    else{
      $ratio=$row['sizes']['full']['ratio'];
      $cover=$row['cover'];
      //$height=floor($ratio * $cover/100).'vw';
      $height='100%';
    }

    return $height;
  }

  /**
   * CSS selector
   * @param array $row
   * @param string $size
   *
   * @return string
   */
  private function getPageCSSSelector($row, $size, $mobile){
    $url=$this->getImageURL($row, $size);

    if($url=='' || trim($row['selector'])==''){
      return '';
    }

    $Str=$row['selector'].'{';
    if(isset($row['bgcolor']) && $row['bgcolor']!=''){
      $Str.='background:'.$row['bgcolor'];
    }
    $Str.=' url('.$url.') no-repeat '.
      $this->getBackgroundPosition($row, $size, $mobile).
      $this->getBackgroundAttachment($row, $mobile).
      $this->getBackgroundSize($row, $size, $mobile, $url).
      $this->getMinimumHeight($row, $size, $mobile).
      $this->getMaximumHeight($row, $size).
      //Appearance of text on top of the overlay
      $this->getPageTextStyle($row, $size, $mobile);
      '}';
    $Str.=$this->getMediaTextStyle($row['selector'], $row['cover'], $row['side'], $mobile);

    return $Str;
  }


  /**
   * Defined the docking position and focal point
   * in case the bgimage needs to be clipped
   * @param array $row
   * @param string $size
   * @return string
   */
  private function getBackgroundPosition($row, $size, $mobile){
    //Horizontal Vertical Positioning of BG image
    $position=(trim($row['dock'])!='')?strtolower($row['dock']):'top left';

    if($mobile=='Y'){
      //Screen width of 1024px or less are entirely covered
      $Str=strtolower($position).';';
    }
    else if($row['cover']==100){
      $Str=strtolower($position).';';
    }
    else{
      //top left or top right
      $Str=($row['side']=='left')? '0 0;': '100% 0;';
    }

    return $Str;
  }

  /**
   * Defines how the background image moves against 
   * content in the target container
   */
  private function getBackgroundAttachment($row, $mobile){
    if(!isset($row['attachment']) || trim($row['attachment'])==''){
      return '';
    }

    //Fixed attachment creates problem on mobile devices
    if($mobile=='Y'){
      return 'background-attachment: scroll;';
    }
    
    return 'background-attachment:'.$row['attachment'].';';    
  }

  /**
   * Full URL of the image according to the size
   * @param array $row
   * @param string $size
   * @return string
   */
  private function getImageURL($row, $size){
    if(!isset($row['sizes']) || empty($row['sizes'])){
      return '';
    }

    if(isset($row['sizes'][$size])){
      $url=$row['sizes'][$size]['url'];
    }
    else{
      $url=$row['sizes']['full']['url'];
    }

    return $url;
  }


  /**
   * How much page width is covered by the bgimage
   * @param array $row
   * @param string $size
   * @return string
   */
  private function getBackgroundSize($row, $size, $mobile, $url){
    $Str=$css_cover='';

    $cover=$row['cover'];

    //Mobile screen completely covered by bg image
    //Or if 100% cover is specified
    if($mobile=='Y'){
      $css_cover='cover';
    }
    else{
      // $css_cover=($cover >=100)? '100% auto;': $cover.'% auto;';
      $css_cover=($cover >=100)? 'cover;': $cover.'% auto;';
    }

    $Str='-webkit-background-size:'.$css_cover.';';
    $Str.='-moz-background-size:'.$css_cover.';';
    $Str.='-o-background-size:'.$css_cover.';';
    $Str.='background-size:'.$css_cover.';';

    if($url!=''){
      //IE8- hacks
      $Str.='filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\''.$url.'\', sizingMethod=\'scale\');';
      $Str.='-ms-filter: "progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\''.$url.'\', sizingMethod=\'scale\')";';
    }

    return $Str;
  }

  /**
   * Set maximum height of HTML element the BG media applies to
   */
  private function getMaximumHeight($row, $size){
    if(!isset($row['max_height'])){return ''; }

    if('viewport_height' == $row['max_height']){
      //Do nothing. It's the default value
      return '';
    }
    else if('bgmedia_ratio' == $row['max_height']){
      //Then it will be determined by the BG Media
      if(isset($row['sizes'][$size])){
        $max_height=$height=$row['sizes'][$size]['height'];
        //max height equals to the BG image height
        return 'min-height:auto; max-height:'.$max_height.'px;';
      }
    }
    else if('container_height' == $row['max_height']){
      //Simply cancels out min-height set by BG Media
      //The maximum height will either be equal to that of the container or of the necessary height to show the content in that container.
      return 'min-height:auto; overflow:hidden;'; 
    }

    return '';
  }

  /**
   * Set minimum page height to fully show the bg image
   * @param array $row
   * @param string $size
   * @return string
   */
  private function getMinimumHeight($row, $size, $mobile){
    if($mobile=='Y'){
      $Str='min-height:100vh;';
    }
    else{
      /*
      Taking the width/height ratio from the original image, "full" ensures that even if the give $size image is not registered, we can stil set the correct value of $ratio.
      */
      $ratio=$row['sizes']['full']['ratio'];
      $cover=$row['cover'];
      $min_height=number_format($ratio * $cover/100, 2);
      $Str='min-height:'.$min_height.'vw;';
    }

    return $Str;
    }

  /**
   * Save CSS string in "graphics.css" file
   * @param string $Str CSS Stylesheet of BG graphics media queries
   */
  private function updateGraphicStyleFile($Str){
    $filepath=WPXP_BGM_PATH.'/public/graphics.css';

    if(($fh=fopen($filepath , 'w'))!==FALSE){
      $Str=$this->getOverlayCSS().' '.$Str;
      fwrite($fh, $Str);
      //Release the file handle
      fclose($fh);
    }
    else{
      _x('Make sure the "graphics.css" file exists and writable', 'wpxp-background-media');
    }

  }

  /**
   * Background Video CSS styles
   * Will be prepended to "public/graphics.css"
   */
  private function getOverlayCSS(){
    global $wpxp_bgm_config;
    
    $options=get_option('wpxp_bgm_overlay');

    if(!is_array($options) || empty($options)){
      $options=$wpxp_bgm_config['wpxp_overlay'];
    }
    extract($options);

    $Str=$this->getBGImageOverlayCSS($overlay_bgcolor, $overlay_opacity);
    //
    $Str.=$this->getContentOverlayCSS(
              $content_overlay_bgcolor, $content_overlay_opacity, 
              $max_width, $show_content_overlay
            );
    //Target Selector With BG Image
    $Str.=$this->getHasOverlayCSS();
    //BG Video
    $Str.=$this->getBGVideoOverlayCSS();

     return $Str;
  }

  /**
   * Basic CSS style of BG Image/Video overlay
   * These will be overridden by values from BG Media metabox
   */
  private function getBGImageOverlayCSS($bgcolor, $opacity){
    return '.wpxp_overlay, .wpxp_content_overlay{width:100%;height:100%;min-height:100%;margin:0;padding:0;position:absolute;top:0;left:0;z-index:2;display:none;background-color:'.$bgcolor.';'.$this->getLayerOpacityCSS($opacity).'}';
  }

  /**
   * Basic CSS styles of Content Overlay
   */
  private function getContentOverlayCSS(
    $bgcolor, $opacity, $max_width, $show_overlay)
    {
    $Str='.wpxp_content_overlay{max-width:'.$max_width.';right:0;margin:0 auto;padding:0;z-index:3;background-color:'.$bgcolor.';'.$this->getLayerOpacityCSS($opacity);
      $Str.=($show_overlay=='N')?'display:none;!important': 'display:block;';
      $Str.='} ';

    return $Str;
  }

  /**
   * Layer Opacity CSS including Microsoft Alpha
   */
  private function getLayerOpacityCSS($opacity){
    return 'opacity:'.number_format($opacity/100,2).';
    -ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity='.$opacity.')";filter:alpha(opacity='.$opacity.');';
  }

  /**
   * 
   */
  private function getHasOverlayCSS(){
    $Str='.has_overlay{position:relative!important;overflow:hidden!important;}';

    return $Str;
  }

  /**
   * 
   */
  private function getBGVideoOverlayCSS(){
    return '.wpxp_bgvideo{display:block;position:absolute;z-index:0;width:100%; height:auto;min-height:100%;} .wpxp_bgvideo *{position:absolute; z-index:1;} .wpxp_bgvideo .wpxp_overlay{display:block;width:100%; min-height:100vh;z-index:2;position:absolute;}';
  }

  /**
   * CSS style of container when the background image covers only the left half of the target element
   */
  private function getMediaTextStyle($parent_selector, $cover, $side, $mobile){
    $Str='';
    //Pass
    if($cover==100){return $Str; }

    if($mobile=='Y'){
      $Str=$parent_selector;
      $Str.=($side=='left')? ' .wpxp_copytoright' : ' .wpxp_copytoleft';
      $Str.='{box-sizing:border-box;width:100%;margin:0; padding:1em;overflow:hidden;position:relative;}';
      //Add a gap between sections
      $Str.=$parent_selector.'.has_overlay{margin-bottom:1em;}';
    }
    else if($side=='left'){
      $Str=$parent_selector.' .wpxp_copytoright{box-sizing:border-box;width:50%; margin:0 0 0 auto; padding:0 1em;overflow:hidden;position:relative;}';
    }
    else if($side=='right'){
      $Str=$parent_selector.' .wpxp_copytoleft{box-sizing:border-box;overflow:hidden;width:50%; margin:0 auto 0 0; padding:0 1em;position:relative;}';

      //To tuck section content against background image on the right
      $Str.=$parent_selector.' .wpxp_copytoleft > *:first-child{margin-left:auto;margin-right:0;}';
    }
    
    return $Str;
  }

  /**
   *
   * @return array of CSS breaking points
   */
  private function getMediaQueriesBreakingPoints(){
    global $wpxp_bgm_config;

    $layout_break_points=get_option('wpxp_bgm_layout_break_points');

    if($layout_break_points===FALSE || empty($layout_break_points)){
      $layout_break_points=$wpxp_bgm_config['layout_break_points'];
    }

    return $layout_break_points;
  }


}//end class