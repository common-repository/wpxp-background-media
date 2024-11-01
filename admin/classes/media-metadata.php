<?php
/**
 * WPXP Background Media Metabox Display
 * @filesource wpxp-background-media/admin/classes/media-metadata.php
 * @author Alex Diokou
 * @copyright WPXPertise
 * @url https://wpxpertise.com
 * @version 1.0.0 
 * @package wpxp-background-media
 */


defined('ABSPATH') or die('No script direct access please!');

class wpxpMediaMetaData{
    private $has_changed=false;

    public function __construct(){

    }

    /**
     * 
     */
    public function update($post_id, $post){
        $this->updateMetaData($post_id, $post->post_name);
    }

    /**
    * Update media queries information
    */
    private function updateMediaQueries($data, $post_id){
        require_once('media-queries.php');
        $mq=new wpxpMediaQueries();
        $mq->update($data, $post_id);
    }


    /**
     * Save/update Background media meta data
     */
    private function updateMetaData($post_id, $post_name){
      if (!current_user_can('publish_pages')){
        return $post_id;
      }
      else if(!isset($_POST['wpxp_nitems'])){
        return;
      }
      else if(strpos($post_name, '-revision-') !== FALSE ||                 strpos($post_name, '-autosave-') !== FALSE ){
        //exclude revisions and autosave
        return;
      }

      //All submitted data including hidden _old_field value
      //Field names still prefixed with wpxp_
      $metadata=$this->getPOSTedMetaData();
      $this->hasChanged($metadata);

      $data=array();
      foreach($metadata as $fieldset_data){
        $row=$this->getMediaData($fieldset_data, $post_id, $post_name);
 
        if(is_array($row) && !empty($row)){
          $data[]=$row;
        }
      }

      //Saves BG media attributes of current page
      update_post_meta($post_id, 'wpxp_background_media', $data);
      
      if($this->has_changed){
        $this->updateMediaQueries($data, $post_id);
      }  
    }


    /**
     *
     * @return multitype:Ambigous <string, unknown> Ambigous <number, string>
     */
    private function getPOSTedMetaData(){
      $nitems=$this->getNumberItems();
      $data=array();

      if($nitems<=0){
        $this->has_changed=true;
        return $data;
      }
      
      $expected_fields=$this->getExpectedFields();

      for($i=0; $i<$nitems; $i++){
        //Skip any row set for removal
        if(wpxp_Helper::sanitizedPOST('wpxp_remove_'.$i, 'string')=='Y'){
          $this->has_changed=true;
          continue;
        }

        $row=array();  
        foreach($expected_fields as $field_name => $attributes){
          $field_type=$attributes['type'];
          $val=wpxp_Helper::sanitizedPOST($field_name.'_'.$i, $field_type);
          $val=($val!==FALSE)? $val: (($field_type=='int')? 0: '');

          $row[$field_name]=$val;
        }

        $data[]=$row;
      }

      //all submitted field including _old values
      return $data;
    }
    
    /**
     * Strip 'wpxp_' prefix from field name
     */
    private function getMediaData($fieldset_data, $post_id, $post_name){
      $data=array('post_id' => $post_id, 'slug' => $post_name);
      $bgmedia_fields=$this->getBackgroundMediaFields($post_name);

      if(!is_array($fieldset_data) || empty($fieldset_data)){ 
        $data=array_merge($this->getDefaultValues($bgmedia_fields), $data);
        return $data; 
      }

      foreach($bgmedia_fields as $key => $attributes){
        $field_name='wpxp_'.$key;
        $val=isset($fieldset_data[$field_name])? $fieldset_data[$field_name]:'';
        $val=($attributes['type']=='int' && !is_numeric($val))? 0 : $val;

        $data[$key]=$val;
      }
      //
      $data['media_id']=($data['media_type']=='video')? 0 : $data['media_id'];
  
      return $data;
    }

    /**
     * 
     * @return multitype:string
     */
    private function getExpectedFields(){
      global $post;
      $bgmedia_fields=$this->getBackgroundMediaFields($post->post_name);

      $expected_fields=array();
      foreach($bgmedia_fields as $key => $attributes){
        $field_name='wpxp_'.$key;
        $expected_fields[$field_name]=$attributes;
        $expected_fields['old_'.$field_name]=$attributes;
      }

      return $expected_fields;
    }

    /**
     * 
     */
    private function getDefaultValues($bgmedia_fields){
      $data=array();
      foreach($bgmedia_fields as $key => $attributes){
        $data[$key]=$attributes['default'];
      }

      return $data;
    }

  /**
   * Detect whether a value has changed
   * @param array $metadata
   * @return boolean
   */
  private function hasChanged($metadata){
    foreach($metadata as $row){
      foreach($row as $key => $value){
        $old_key='old_'.$key;
        if(isset($row[$old_key]) && $row[$old_key]!=$value){
          $this->has_changed=true;
          break;
        }
      }
    }
  }

    /**
    *
    */
    private function getNumberItems(){
        $nitems=wpxp_Helper::sanitizedPOST('wpxp_nitems', 'int');
        $nitems=is_numeric($nitems)? (int) $nitems : 0;
        return $nitems;
    }

    private function getBackgroundMediaFields($post_name){
      $selector='#'.$post_name;
      return array(
        //BG Media Type
        'media_type'=> array('type' =>  'string', 'default' => 'image'),
        //BG Media Type
        'embed_code'=> array('type' =>'html', 'default' => '') ,
        //CSS Background image position
        'dock'=> array('type' =>'string', 'default' => 'top left'),
        //ID of the media (image/video)
        'media_id'=> array('type' =>'int', 'default' => 0),
        //% of container covered
        'cover'=>  array('type' =>'int', 'default' => 100), 
        //Page side covered
        'side'=> array('type' =>'string', 'default' => 'left'), 
        //How far the HTML container expands vertically
        'max_height' => array('type' => 'string', 'default' => 'viewport_height'),
        //Bgcolor
        'bgcolor'=> array('type' =>'string', 'default' => '#000'), 
        //Foreground text color
        'color'=> array('type' =>'string', 'default' => '#000'),
        //Overalay shading
        'overlay'=> array('type' =>'string', 'default' => ''),
        //overlay on mobile display
        'mobile_overlay'=> array('type' =>'string', 'default' => 'Y'),
        //opacity of the overlay shading
        'opacity'=> array('type' =>'int', 'default' => '40'),
        'video_opacity'=> array('type' =>'int', 'default' => '40'),
        //Foreground text color when overlay applied
        'overlay_color'=> array('type' =>'string', 'default' => '#eee'),
        //BG color of the overlay layer
        'overlay_bgcolor'=> array('type' =>'string', 'default' => '#000'),
        'video_overlay_bgcolor'=> array('type' => 'string', 'default' => '#000'),
        //CSS selector to apply the BG image to
        'selector' => array('type' =>'string', 'default' => $selector),
        //CSS BG Attachement 
        'attachment' => array('type' =>'string', 'default' => 'local'),
        //
        //'remove' => array('type' =>'string', 'default' => ''),
      );
    }

    /**
     * Subfolder where image in located under the media "uploads" folder
     * @param string $original_image
     * @return string
     */
    private function getImageFolder($original_image){
        $length=strrpos($original_image, '/');

        return substr($original_image, 0, $length);
    }

} //end class
