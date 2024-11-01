<?php
/**
 * WPXP Background Media Plugin
* @filesource wpxp-background-media/admin/classes/overlay.php
 * @author Alex Diokou
 * @copyright WPXPertise
 * @url https://wpxpertise.com
 * @version 1.0.0 
 */

defined('ABSPATH') or die('No script direct access please!');

class wpxpBGMOverlay{
  private $errors=array();

  public function __construct(){

  }

  /**
   * 
   */
  public function getErrors(){
    return $this->errors;
  }

  /**
   * 
   */
  public function processRequest(){
    $todo=wpxp_Helper::sanitizedPOST('todo', 'string');
    $response=$this->handleRequest($todo);
    $response['message'] = wpxp_Helper::showErrors($this->errors);
    $response['navigation']=$this->getNavigationBar();

    return $response;
  }

  /**
   * 
   */
  private function handleRequest($todo){
    if($todo=='update'){
      return $this->updateOverlay();
    }

    return $this->editOverlay();
  }

  /**
   * 
   */
  private function getPOSTedData(){
    $expected_fields=$this->getExpectedFields();
    $data=array();
    foreach($expected_fields as $field_name => $field_type){
      $data[$field_name]=wpxp_Helper::sanitizedPOST($field_name, $field_type);
    }

    $data['show_content_overlay']=($data['show_content_overlay']!='N')? 'Y':'N';

    return $data;
  }


  /**
   * 
   */
  private function getExpectedFields(){
    return array('overlay_bgcolor' => 'string', 
                 'overlay_opacity' => 'int', 
                 'content_overlay_bgcolor' => 'string', 
                 'content_overlay_opacity' => 'int',
                 'max_width' => 'string',
                 'show_content_overlay' => 'string'
        );
  }

  /**
   * 
   */
  private function editOverlay(){
    global $wpxp_bgm_config;

    $options=get_option('wpxp_bgm_overlay');
    if(!is_array($options) || empty($options)){
      $options=$wpxp_bgm_config['wpxp_overlay'];
    }

    return array('html' => $this->getOverlayForm($options));
  }

  /**
   * 
   */
  private function updateOverlay(){
     $data=$this->getPOSTedData();
     if(update_option('wpxp_bgm_overlay', $data, 'no')!==FALSE){
      $this->errors[]='Info saved successfully';
      do_action('wpxp_bgm_update_overlay');
     }
     else{
       $this->errors[]='No change detected';
     }

     return array();
  }



private function getOverlayForm($row){
    $Str='<form action="wpxp_bgm_overlay" method="post" name="wpxp_overlay_form"
    class="wpxpbgmform">
    <input type="hidden" name="todo" value="update">
    <fieldset><legend>'.__('Background Media Overlay', 'wpxp-background-media').'</legend>
    <p><label>'.__('Background Color', 'wpxp-background-media').'</label>
    <input type="text" name="overlay_bgcolor" size="10" maxlength="7" value="'.$row['overlay_bgcolor'].'">
    </p>
    <p><label>'.__('Opacity', 'wpxp-background-media').'</label>
    <input type="number" name="overlay_opacity" maxlength="3" min="0" max="100" step="1" value="'.$row['overlay_opacity'].'"><span>%</span>
    </p>
    </fieldset>
    <fieldset>
    <legend>'.__('Content Overlay', 'wpxp-background-media').'</legend>
    <p><label>'.__('Background Color', 'wpxp-background-media').'</label>
    <input type="text" name="content_overlay_bgcolor" size="20" maxlength="7" value="'.$row['content_overlay_bgcolor'].'">
    </p>
    <p><label>'.__('Opacity', 'wpxp-background-media').'</label>
    <input type="number" name="content_overlay_opacity" maxlength="3" min="0" max="100" step="1" value="'.$row['content_overlay_opacity'].'"><span>%</span>
    </p>
    <p><label>'.__('Maximum Width', 'wpxp-background-media').'</label>
    <input type="text" name="max_width" maxlength="6" size="10" value="'.$row['max_width'].'"><span>(% or px)</span>
    </p>
    <p><label>'.__('Show Content Overlay', 'wpxp-background-media').'</label>
    <input type="radio" name="show_content_overlay" value="Y"';
    $Str.=($show_content_overlay!='N')?' checked':'';
    $Str.='><span>'.__('Yes', 'wpxp-background-media').'</span>
    <input type="radio" name="show_content_overlay" value="N"';
    $Str.=($show_content_overlay=='N')?' checked':'';
    $Str.='><span>'.__('No', 'wpxp-background-media').'</span>
    </fieldset>
    <button>'.__('Save Changes', 'wpxp-background-media').'</button>
    </form>';


  return $Str;
}













  /**
   * 
   */
  private function getNavigationBar(){
    return '';
  }

}