<?php
/**
 * WPXP Background Media Plugin
 * @filesource wpxp-background-media/admin/classes/settings.php
 * @author Alex Diokou
 * @copyright WPXPertise
 * @url https://wpxpertise.com
 * @version 1.0.0 
 */

defined('ABSPATH') or die('No script direct access please!');

class wpxpBGMSettings{
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
      return $this->updateLayoutBreakingPoints();
    }

    return $this->editLayoutBreakingPoints();
  }

  /**
   * 
   */
  private function getPOSTedData(){
    $nitems=wpxp_Helper::sanitizedPOST('nitems', 'int');
    $nitems=is_numeric($nitems)? (int) $nitems: 0;
    $data=array();

    for($i=0; $i<$nitems; $i++){
      if(($row=$this->getPOSTedImageSizeInfo($i))!==FALSE){
        $data[]=$row;
      }
    }

    if(!empty($data) && count($data)>0){
      return $this->sortLayoutBreakpoints($data);
    }

    return $data;
  }

  /**
   * 
   */
  private function getPOSTedImageSizeInfo($k){
    $expected_fields=$this->getExpectedFields();

    foreach($expected_fields as $field_name => $field_type){
      ${$field_name}=wpxp_Helper::sanitizedPOST($field_name.'_'.$k, $field_type);
    }

    $info=array();

    if($size!==FALSE && ($size=trim($size))!=''){
      $info['size']=str_replace('-', '_', sanitize_title($size));

      $info['max']=is_numeric($max) ? (int) $max : 0;
    
      $info['mobile']=($mobile!='N')? 'Y':'N';

      if(isset($info['max']) && $info['max'] > 0){
        return $info;
      }
    }

    return false;
  }

  /**
   * 
   */
  private function getExpectedFields(){
    return array('size' => 'string',
              'max' => 'int', 'mobile' => 'string', 
        );
  }

  /**
   * 
   */
  private function sortLayoutBreakpoints($data){
    usort($data, array($this, 'sortByMaxWidth'));
    foreach($data as $index => $row){
      if($index>0){
        $data[$index]['min']=$min_width;
      }

      $min_width=$row['max']+1;
    }
    $n=count($data)-1;
    $max_width=$data[$n]['max'];
    $data[]=array ('size'=> 'full', 'min' => $max_width+1, 'mobile' => 'N');

    return $data;
  }

  /**
   * 
   */
  private function sortByMaxWidth($a, $b){
    if($a['max']==$b['max']){return 0;}
    return ($a['max'] > $b['max']) ? 1: -1;
  }

  /**
   * 
   */
  private function editLayoutBreakingPoints(){
    global $wpxp_bgm_config;

    $options=get_option('wpxp_bgm_layout_break_points');
    if(!is_array($options) || empty($options)){
      $options=$wpxp_bgm_config['layout_break_points'];
    }

    return array('html' => $this->getLayoutBreakPointForm($options));
  }

  /**
   * 
   */
  private function updateLayoutBreakingPoints(){
    $response=array();
    $data=$this->getPOSTedData();

    if(!is_array($data) || empty($data)){
      $this->errors[]='Invalid data submitted';
      do_action('wpxp_bgm_update_image_sizes');
      
      return $response;
    }

    $options=get_option('wpxp_bgm_layout_break_points');
    if(is_array($options)){
      $options=array_merge($options, $data);
    }
    else{
      $options=$data;
    }

    if(update_option('wpxp_bgm_layout_break_points', $data, 'no')!==FALSE){
      $this->errors[]='Info saved successfully';
      return $this->editLayoutBreakingPoints();
    }
    else{
      $this->errors[]='No change detected';
    }

    return $response;
  }

  /**
   * 
   */
  private function getLayoutBreakPointForm($options){
    if(!is_array($options) || empty($options)){
      return '';
    }

    $fields='';
    $index=0;

    foreach($options as $index => $row){
      //Full size is default on large screen
      if($row['size']=='full'){continue; }
      $fields.=$this->getLayoutBreakPointHTMLRow($row, $index);
      $index++;
    }

    if($fields!=''){
      $Str='<form method="post" action="wpxp_bgm_settings" name="wpxp_bgmform" class="wpxpbgmform">
      <input type="hidden" name="todo" value="update">
      <input type="hidden" name="nitems" value="'.$index.'">'.
      $fields.
      '<button>'.__('Save Changes', 'wpxp-background-media').'</button>
      </form>';
      $Str.='<script>var wpxp_bgm_row=\''.$this->getLayoutBreakPointTemplate().'\';</script>';
    }

    return $Str;
  }

  /**
   * 
   */
  private function getLayoutBreakPointHTMLRow($row, $index){
    $label=ucwords(str_replace('_', ' ', $row['size']));
    $row_num=$index+1;
    
    $Str='<fieldset>
    <legend>'.__('Layout Breakpoint', 'wpxp-background-media').' #'.$row_num.'</legend>
    <section>
    <p><label for="'.$row['size'].'">'.
    __('Screen size', 'wpxp-background-media').'</label>
    <input type="text" name="size_'.$index.'" value="'.$row['size'].'" size="20" maxlength="20" data-name="size">';

    foreach($row as $key => $value){
      if($key=='max'){
        $Str.='<label>'.__('Maximum width', 'wpxp-background-media').'</label>
        <input type="number" name="'.$key.'_'.$index.'" value="'.$value.'" size="5" maxlength="5" minimum="0" maximum="5000" step="1" data-name="'.$key.'">';
      }
      else if($key=='mobile'){
        $Str.='<p>
        <label>'.__('Target Screen Size', 'wpxp-background-media').'</label>
        <input type="radio" name="mobile_'.$index.'" value="Y" data-name="'.$key.'"';
        $Str.=($value=='Y')? ' checked': '';
        $Str.='><span>'.__('Mobile Device', 'wpxp-background-media').'</span>
        <input type="radio" name="mobile_'.$index.'" value="N" data-name="'.$key.'"';
        $Str.=($value!='Y')? ' checked': '';
        $Str.='><span>'.__('Desktop/Laptop', 'wpxp-background-media').'</span>
        </p>';
      }
    }

    $Str.='</section>
    <span class="delete" title="'.__('Delete', 'wpxp-background-media').'"></span><span class="collapse" title="'.__('Open/Close', 'wpxp-background-media').'"></span>
    </fieldset>';

    return $Str;
  }

  /**
   * Layout Breakpoint Template to generate new row
   */
  private function getLayoutBreakPointTemplate(){
    $keys=array_keys($this->getExpectedFields());
    $row=array_fill_keys($keys, '');
    $Str=$this->getLayoutBreakPointHTMLRow($row, '');
    
    return preg_replace('/(\n|\r|\t{2,}|\s{2,})/', '', $Str);
  }

  /**
   * 
   */
  private function getNavigationBar(){
    return '<a href="#" class="btn_new"
    data-action="'.$this->action.'" data-todo="new">'.__('Add Layout Breakpoint','wpxp-background-media').'</a>';
  }

}//end class