<?php
/**
 * wpxpMetaboxDisplay
 * Displays the Background Media Metabox below page editor
 * @filesource wpxp-background-media/admin/classes/metabox-display.php
 * @author Alex Diokou
 * @copyright WPXPertise
 * @url https://wpxpertise.com
 * @version 1.0.0
 * @package wpxp-background-media
 */

defined('ABSPATH') or die('No script direct access please!');

//Description tooltips on fieldset labels


class wpxpMetaboxDisplay{
  private $descriptions;

  public function __construct(){
    $this->nonceStr='wpxp_background_media';
    $this->setMetaboxDescriptions();
  }

  /**
  *
  */
  public function displayMeta($post, $metabox){
    echo $this->showMetaBoxes($post, $metabox);
  }

  private function setMetaboxDescriptions(){
    require_once(WPXP_BGM_PATH.'admin/inc/descriptions.php');

    $this->descriptions=$wpxp_bgm_descriptions;
  }

  /**
  * 
  */
  private function showMetaBoxes($post, $metabox){
    $media_attributes= $this->getMediaAttributes($post->ID);
    $nitems=count($media_attributes);

    $Str='<div id="wpxp_bg_media">';
    $Str.=$this->getMediaHTML($post, $media_attributes);
    $Str.='<input type="hidden" name="wpxp_nitems" value="'.$nitems.'">
          <input type="button" name="btn_wpxp_add_media"
            id="btn_wpxp_add_media" value="'.
            __('Add New Background Media', 'wpxp-background-media').'">
      </div>';

    $Str.='<script> 
    var wpxp_bgmedia_template=\''.$this->getFieldsTemplate($post).'\';';
    $Str.='</script>';

    return $Str;
  }

  /**
   * Page Background image/color attributes
   * @param int $post_id
   * @return array $attributes
   */
  private function getMediaAttributes($post_id){
    $media= get_post_meta($post_id, 'wpxp_background_media', true);

    if(!is_array($media) || empty($media)){
      return array();
    }

    $defaults=$this->getDefaultAttributes();
    $attributes=array();
    foreach($media as $key => $row){
      if(is_array($row)){
        $attributes[$key]=array_merge($defaults[0], $row);
      }
      else{
        $attributes[]=$media;
        break;
      }
    }

    return $attributes;
  }

  /**
  * Media attributes of each page is a two dimensional array
  */
  private function getDefaultAttributes(){
    global $post;
    $selector='#'.$post->post_name;

    return array(
      array(
        'media_type' => 'image', 'embed_code' => '', 
        'dock' => 'left top', 'cover' => 100, 
        'side' => 'left', 'max_height' => 'viewport_height',
        'bgcolor' => '#fff', 'color' => '#000', 
        'overlay' => '', 'mobile_overlay' => 'Y', 
        'opacity' => 40, 'video_opactity' => 40,
        'overlay_color' => '#fff', 'overlay_bgcolor' => '#000',
        'selector' => $selector, 'attachment' => 'local',
        'media_id' => 0,
      )
    );
  }

  /**
   * Background Image Focal Points
   * @return multitype:string
   */
  private function getDockingPositions(){
    return array('Left Top', 'Center Top', 'Right Top',
                  'Left Center', 'Center Center', 'Right Center',
                  'Left Bottom ', 'Center Bottom', 'Right Bottom'
              );
  }

  /**
  *
  */
  private function getMediaHTML($post, $media_attributes){
    $Str='';
    foreach ($media_attributes as $k => $row){
      $Str.=$this->getMetaBoxHTML($post, $row, $k);
    }

    return $Str;
  }

  /**
   * Show HTML form for setting background media
   */
  public function getMetaBoxHTML($post, $row, $k) {
    $nonce= wp_create_nonce($this->nonceStr);
    $row_number=is_numeric($k)? $k+1 :'';
    $row_class=($k==0)? '':'collapse';
    $title=$post->post_title;

    $Str='<fieldset data-row="'.$k.'" class="'.$row_class.'">
    <legend>'.__('Background Image/Video', 'wpxp-background-media').' #'.$row_number.'</legend>
    <span class="wpxp_delete_fieldset" title="'.
    __('Delete', 'wpxp-background-media').'">&nbsp;</span>
    <span class="wpxp_toggle_arrow" title="'.
    __('Toggle open/close', 'wpxp-background-media').'">&nbsp;</span>'.
    $this->getMediaSelectionField($k, $row['media_type']).
    $this->getTargetSelectorField($row['selector'], $k).
    '<div class="wpxp_background_image"';
    $Str.=($row['media_type']=='video')?' style="display:none;"':'';
    $Str.='><section>'.
    '<input type="hidden" name="wpxp_bgmedia_nonce" value="'.$nonce.'">
    <input type="hidden" name="wpxp_media_id_'.$k.'" value="'.$row['media_id'].'" id="wpxp_media_id_'.$k.'">'.
    $this->getHiddenFields($row, $k).
    $this->getDockingOptionField($row['dock'], $k).
    $this->getAttachmentOptionField($row['attachment'], $k).
    $this->getPercentCoverageField($row['cover'], $k).
    $this->getSideCoverageField($row['side'], $k).
    $this->getMaximumHeight($row['max_height'], $k).
    '</section><section>'.
    $this->getBackgrounColorField($row['bgcolor'], $k).
    $this->getNormalTextColorField($row['color'], $k).
    $this->getOverlayFields($row, $k).
    '</section>
    <div class="wpxp_preview" id="wpxp_preview_'.$k.'">';

    if($row['media_id']>0){
      $Str.=$this->getBackgroundImageStr($row['media_id']);
    }

    $Str.='<div class="clear">&nbsp;</div>
    <!-- .wpxp_preview --></div>';
    $Str.=$this->getSelectMediButtonField();
    $Str.='<!-- .wpxp_background_image --></div>'.
    $this->getBackgroundVideoField($k, $row).
    '</fieldset>';

    return $Str;
  }

  private function getAttributeValue($row, $name, $default=''){
    if(is_array($row) && isset($row[$name]) && trim($row[$name])!=''){
      return $row[$name];
    }

    return $default;
  }

  /**
  * Used by JS to generate new row of BG Media
  * Only need one row of $default_attributes array
  */
  private function getFieldsTemplate($post){
    $default_attributes=$this->getDefaultAttributes();
    $Str=$this->getMetaBoxHTML($post, $default_attributes[0], '');
    $Str=preg_replace('/(\n+]\t+|\s{2,})/', '', $Str);
    return $Str;
  }

  /**
  *
  */
  private function getDockingOptionField($dock, $k){
    $Str='<div class="wpxpbg_field">
    <label for="dock">'.
    __('Focal Point', 'wpxp-background-media').'
    <span title="'.$this->getLabelDescription('focal_point').'">?</span>
    </label>
    <select name="wpxp_dock_'.$k.'" size="1" required>';

    $docks=$this->getDockingPositions();

    foreach($docks as $value){
      $Str.='<option value="'.$value.'"';
      $Str.=($value==$dock) ? ' selected' : '';
      $Str.='>'.$value.'</option>';
    }

    $Str.='</select></div>';

    return $Str;
  }

  /**
   * Background Image Attachment
   */
  private function getAttachmentOptionField($attachment, $k){
    $options=array('fixed', 'local', 'scroll');

    $Str='<div class="wpxpbg_field">
      <label for="dock">'.
      __('Background Attachment', 'wpxp-background-media').'
      <span title="'.$this->getLabelDescription('bg_attachment').'">?</span>
      </label> 
      <select name="wpxp_attachment_'.$k.'" size="1">';

    foreach($options as $option){
      $Str.='<option value="'.$option.'"';
      $Str.=($attachment==$option) ? ' selected' : '';
      $Str.='>'.ucwords($option).'</option>';
    }
    $Str.='</select></div>';

    return $Str;
  }

  /**
   *
   */
  private function getPercentCoverageField($cover, $k){
    $fullcover=($cover!=50)?' checked':'';
    $halfcover=($cover==50)?' checked':'';

    return '<div class="row wpxpbg_field">
      <label for="wpxp_cover">Percentage '.
      __('Covered', 'wpxp-background-media').'
      <span title="'.$this->getLabelDescription('percentage').'">?</span>
      </label><section class="fieldgroup">
      <input type="radio" name="wpxp_cover_'.$k.'" value="50"'.$halfcover.'>
      <span class="option">50%</span>
      <input type="radio" name="wpxp_cover_'.$k.'" value="100"'.$fullcover.'>
      <span class="option">100%</span></section>
      </div>';
  }

  /**
  *
  */
  private function getSideCoverageField($side, $k){
    $leftcover=($side!='right')?' checked':'';
    $rightcover=($side=='right')?' checked':'';

    return '<div class="row wpxpbg_field">
      <label for="wpxp_cover">'.
      __('Side Covered', 'wpxp-background-media').'
      <span title="'.$this->getLabelDescription('side_covered').'">?</span>
      </label><section class="fieldgroup">
      <input type="radio" name="wpxp_side_'.$k.'" value="left"'.$leftcover.'><span class="option">'.
      __('Left', 'wpxp-background-media').'</span>
      <input type="radio" name="wpxp_side_'.$k.'" value="right"'.$rightcover.'><span class="option">'.
      __('Right', 'wpxp-background-media').'</span></section>
      </div>';
  }

  private function getMaximumHeight($max_height, $k){
    //Full height of the web browser viewport 100vh
    $viewport_height=('viewport_height'==$max_height)? 'checked':'';
    //The height/width ratio e.g. 65vw
    $bgmedia_ratio=('bgmedia_ratio'==$max_height)? 'checked': '';
    //The height of the HTML element the BG media applies to
    $container_height=('container_height'==$max_height)? 'checked': '';

    return '<div class="row wpxpbg_field">
      <label for="wpxp_max_height">'.
      __('Maximum Height', 'wpxp-background-media').'
      <span title="'.$this->getLabelDescription('max_height').'">?</span>
      </label><section class="fieldgroup">
      <input type="radio" name="wpxp_max_height_'.$k.'" value="viewport_height" '.$viewport_height.'><span>'.
      __('Viewport', 'wpxp-background-media').'</span>
      <input type="radio" name="wpxp_max_height_'.$k.'" value="bgmedia_ratio" '.$bgmedia_ratio.'><span>'.
      __('BG Media', 'wpxp-background-media').'</span>
      <input type="radio" name="wpxp_max_height_'.$k.'" value="container_height"'.$container_height.'><span>'.
      __('Container', 'wpxp-background-media').'</span></section>
      </div>';
  }

  /**
  *
  */
  private function getBackgrounColorField($bgcolor, $k){
    return '<div class="row wpxpbg_field">
    <label>'.__('Background Color', 'wpxp-background-media').'
    <span title="'.$this->getLabelDescription('selector_bgcolor').'">?</span>
    </label>
      <input type="text" name="wpxp_bgcolor_'.$k.'" value="'.$bgcolor.'" maxlength="7" size="10">
      </div>';
  }

  /**
  *
  */
  private function getNormalTextColorField($color, $k){
    return '<div class="row wpxpbg_field">
    <label>'.__('Normal Text Color', 'wpxp-background-media').'
    <span title="'.$this->getLabelDescription('text_color').'">?</span>
    </label>
      <input type="text" name="wpxp_color_'.$k.'" value="'.$color.'"
      maxlength="7" size="10">
    </div>';
  }

  /**
  *
  */
  private function getOverlayFields($attributes, $k){
    $has_overlay=($attributes['overlay']=='Y')?' checked':'';
    $has_mobile_overlay=($attributes['mobile_overlay']=='Y')?' checked':'';

    return '<div class="wpxpbg_field">
    <label>'.__('Show overlay on', 'wpxp-background-media').'
    <span title="'.$this->getLabelDescription('show_overlay').'">?</span>
    </label><section class="fieldgroup">
    <input type="checkbox" name="wpxp_overlay_'.$k.'" value="Y" '.$has_overlay.'>
    <span class="option">'.
        __('Large Screen', 'wpxp-background-media').'</span>
        <input type="checkbox" name="wpxp_mobile_overlay_'.$k.'" value="Y" '.
        $has_mobile_overlay.'><span class="option">'.
        __('Small Screen', 'wpxp-background-media').'</span></section>
    </div>
    <div class="wpxpbg_field">
    <label>'.__('Overlay Background Color', 'wpxp-background-media').'
    <span title="'.$this->getLabelDescription('overlay_bgcolor').'">?</span>
    </label>
    <input type="text" name="wpxp_overlay_bgcolor_'.$k.'" value="'.
        $attributes['overlay_bgcolor'].'" size="10" maxlength="7">
    </div>
    <div class="wpxpbg_field">
    <label>'.__('Overlay Opacity', 'wpxp-background-media').'
    <span title="'.$this->getLabelDescription('overlay_opacity').'">?</span>
    </label>
    <input type="number" name="wpxp_opacity_'.$k.'" value="'.$attributes['opacity'].'" min="0" max="100" size="3" maxlength="3"><span> %</span>
    </div>
    <div class="wpxpbg_field">
    <label>'.__('Overlay Text Color', 'wpxp-background-media').'
    <span title="'.$this->getLabelDescription('overlay_text_color').'">?</span>
    </label>
    <input type="text" name="wpxp_overlay_color_'.$k.'" value="'.$attributes['overlay_color'].'"
      maxlength="7" size="10">
    </div>';
  }

  /**
   *
   */
  private function getTargetSelectorField($selector, $k){
    return '<div class="wpxpbg_field">
    <label>'.__('CSS Selector', 'wpxp-background-media').' 
    <span title="'.$this->getLabelDescription('css_selector').'">?</span>
    </label>
      <input type="text" name="wpxp_selector_'.$k.'" maxlength="60" size="38" value="'.$selector.'" required>
      </div>';
  }

  /**
   *
   */
  private function getSelectMediButtonField(){
    return '<div class="wpxpbg_selectmedia">
    <input type="button" name="btnselectmedia" value="'.
    __('Select Background Image', 'wpxp-background-media').'" 
    class="btnselectmedia" class="button"><br>
    </div>';
  }

  /**
  * Store current fields values in hidden fields
  * To be compared corresponding values submitted in visible fields
  * Determines if change occured to update graphics.css file
  */
  private function getHiddenFields($attributes, $k){
    $fields=array('media_type', 'media_id', 'dock', 'cover', 'side',
                  'max_height', 'bgcolor', 'color', 'overlay', 'mobile_overlay',
                  'opacity', 'video_opacity', 'overlay_bgcolor', 'video_overlay_bgcolor', 'overlay_color',
                  'selector', 'attachment'
                );

    $Str='';
    foreach($fields as $key){
        $field_name='old_wpxp_'.$key.'_'.$k;
        $Str.='<input type="hidden" name="'.$field_name.'"
        value="';
        $Str.=array_key_exists($key, $attributes)? $attributes[$key]:'';
        $Str.='">';

    }
    return $Str;
  }

  /**
   *
   */
  private function getMediaSelectionField($k, $media_type){
    $Str='<div class="wpxp_media_selection wpxpbg_field">
    <label for="wpxp_media_type">'.
    __('Media Type', 'wpxp-background-media').'
    <span title="'.$this->getLabelDescription('media_type').'">?</span>
    </label>
    <select name="wpxp_media_type_'.$k.'" class="wpxp_media_type" required>
    <option value="">Select Media Type</option>
    <option value="image"';
    $Str.=($media_type!='video')? ' selected' : '';
    $Str.='>'.
    __('Image', 'wpxp-background-media').'</option>
    <option value="video"';
    $Str.=($media_type=='video') ? ' selected' : '';
    $Str.='>'.
    __('Video', 'wpxp-background-media').'</option>
    </select>
    </div>';

    return $Str;
  }

  /**
   *
   */
  private function getBackgroundVideoField($k, $row){
    $row_number=is_numeric($k)? $k+1 :'';
    $style=($row['media_type']!='video')? ' style="display:none;"' : '';
    
    $Str='<div class="wpxp_background_video" '.$style.'>'.
    '<div class="wpxpbg_field"><label>'.
      __('Embed Code', 'wpxp-background-media').'
    <span title="'.$this->getLabelDescription('embed_code').'">?</span></label>
    <textarea name="wpxp_embed_code_'.$k.'" cols="60" rows="5" class="wpxp_embed_code">'.esc_html(stripslashes($row['embed_code'])).
    '</textarea>
    </div>
    <div class="wpxpbg_field"><label>'.
    __('Overlay Background Color', 'wpxp-background-media').'
    <span title="'.$this->getLabelDescription('overlay_bgcolor').'">?</span>
    </label>
    <input type="text" name="wpxp_video_overlay_bgcolor_'.$k.'" value="';
      
    $Str.=isset($row['video_overlay_bgcolor'])?$row['video_overlay_bgcolor']:'';

    $Str.='" size="10" maxlength="7">
    </div>
    <div class="wpxpbg_field"><label>'.
    __('Overlay Opacity', 'wpxp-background-media').'
    <span title="'.$this->getLabelDescription('overlay_opacity').'">?</span>
    </label>
    <input type="number" name="wpxp_video_opacity_'.$k.'" value="';

    $Str.=isset($row['video_opacity'])? $row['video_opacity']:0;

    $Str.= '" min="0" max="100"
          size="3" maxlength="3"><span> %</span>
        </div>
        <div class="clear"></div>
       <!-- .wpxp_background_video -->  </div>';

    return $Str;
  }

  /**
   * Background Image HTML Code
   * @param int $media_id
   * @param string $size
   * @param string $title
   * @return string
   */
  private function getBackgroundImageStr($media_id){
    $imgStr=__('No Image Selected', 'wpxp-background-media');
    $media_id=is_numeric($media_id)? (int) $media_id: 0;

    if($media_id>0){
      $image=wp_get_attachment_image_src($media_id, array(300, 0));

      if(is_array($image) && !empty($image)){
        $imgStr='<img src="'.$image[0].'" width="'.$image[1].'" height="'.$image[2].'">';
      }
    }

    return $imgStr;
  }

  /**
   * Provides description of metabox field
   */
  private function getLabelDescription($label_name){
    //All descriptions from "admin/inc/descriptions.php"
    if(array_key_exists($label_name, $this->descriptions)){
      return $this->descriptions[$label_name];
    }

    return '';
  }

}//end class
