<?php
/**
 * WPXP Background Media Plugin
 * 
 * Defines helper methods used by other classes
 * All methods are and the class encapsulation
 * is just to avoid potential name collisions
 */

defined('ABSPATH') or die('No script direct access please!');


final class wpxp_Helper {
    static $errors=array();

    static function getDBPrefix($custom_prefix=''){
        global $wpdb;

        return $wpdb->prefix.$custom_prefix;
    }


    static function getErrors(){
        return self::$errors;
    }


    //
    static function showErrors($errors){
      if(is_array($errors)){
          $errors=array_merge($errors, self::$errors);
      }
      else{
          $errors=self::$errors;
      }

      if(count($errors)==0){ return '';}

      $Str='';
      foreach($errors as $error){
          if(trim($error)==''){continue;}
        $Str.='<li>'.__($error, 'wpxpc-lead-capture').'</li>';
      }

      if($Str!=''){
          $Str='<ul>'.$Str.'</ul>';
      }

      return $Str;
    }


    /**
     *
     * @return Integer
     */
    static function getCursor() {
        $cs = self::sanitizedPOST('cs', 'int');
        $cursor = (is_numeric($cs) && $cs > 0) ? (int) $cs : 0;

        return $cursor;
    }


    static function getNumberPages($table, $limit, $where='') {
        global $wpdb;
        $where=($where!='')?"WHERE $where ":"";

        $np = self::sanitizedPOST('np', 'int');
        $npages = (is_numeric($np) && $np > 0) ? (int) $np : 0;

        if($npages==0){
            $count=$wpdb->get_var("Select count(*) From $table $where");

            if($count===FALSE){
                self::$errors[]=$wpdb->last_error;
            }
            elseif(is_numeric($count)){
                //make sure $limit is a non-zero integer
                $limit=(is_numeric($limit) && $limit>=1)? (int) $limit: 25;

                //number of pages
                $npages=ceil($count/$limit);
            }
        }

        return $npages;
    }


    /**
     *
     * @return Integer
     */
    static function getRecordID(){
        $id=self::sanitizedPOST('ID', 'int');
        $id=($id!==FALSE && is_numeric($id))? (int) $id:0;

        return $id;
    }


    static function getPOSTed($field){
       $value=(isset($_POST[$field]))?trim($_POST[$field]):'';
       return $value;
    }


    static function getSanitizeFilterFlag($key){
        $flags=array('email'=> FILTER_SANITIZE_EMAIL,
                    'encoded'=> FILTER_SANITIZE_ENCODED,
                    'magic_quotes'=> FILTER_SANITIZE_MAGIC_QUOTES,
                    'float'=> FILTER_SANITIZE_NUMBER_FLOAT,
                    'int'=> FILTER_SANITIZE_NUMBER_INT,
                    'special_chars'=> FILTER_SANITIZE_SPECIAL_CHARS,
                    'string'=> FILTER_SANITIZE_STRING,
                    'url'=> FILTER_SANITIZE_URL,
                    'html'=> FILTER_SANITIZE_FULL_SPECIAL_CHARS,

                    //FILTER_UNSAFE_RAW
        );

       if(array_key_exists($key, $flags)){
           return $flags[$key];
       }

        return FILTER_SANITIZE_STRING;
    }

    static function getFilterInputOptions($key){
        $options=array(
            'email' => array(),
            'encoded' => array(FILTER_FLAG_ENCODE_HIGH),
            'magic_quotes' => array(),
            'float' => array(FILTER_FLAG_ALLOW_THOUSAND),
            'int' => array(),
            'special_chars' => array(FILTER_FLAG_ENCODE_HIGH),
            'string' => array(FILTER_FLAG_ENCODE_HIGH),
            'url' => array(),
            'html' => array(FILTER_FLAG_ENCODE_HIGH),
        );

        if(!array_key_exists($key, $options)){
            return $options['encoded'];
        }

        return $options[$key];
    }


    /**
     *
     * @param string $field
     * @return boolean
     */
    static function sanitizedPOST($field, $fieldtype='string') {
        $flag=self::getSanitizeFilterFlag($fieldtype);
        $options=self::getFilterInputOptions($fieldtype);
        $val = filter_input(INPUT_POST, $field, $flag, $options);

        if ($val !== NULL && $val !== FALSE) {
            return $val;
        }

        return FALSE;
    }


    /**
     *
     * @param type $field
     * @return boolean
     */
    static function sanitizedGET($field, $fieldtype='string') {
        $flag=self::getSanitizeFilterFlag($fieldtype);
        $options=self::getFilterInputOptions($fieldtype);
        $val = filter_input(INPUT_GET, $field, $flag, $options);

        if ($val !== NULL && $val !== FALSE) {
            return $val;
        }

        return FALSE;
    }


    /**
     *
     * @param type $field
     * @return boolean
     */
    static function sanitizedSERVER($field) {
        $val = filter_input(INPUT_SERVER, $field, FILTER_SANITIZE_MAGIC_QUOTES);
        if ($val !== NULL && $val !== FALSE) {
            return $val;
        }

        return FALSE;
    }


    /**
     *
     * @param array $defaults
     * @return array
     */
    static function getPostedData($defaults) {
        $data = array();
        foreach ($defaults as $field => $default) {
            $val = (($val = self::sanitizedPOST($field)) !== FALSE) ? $val : $default;
            $data[$field] = $val;
        }

        return $data;
    }


    /**
     *
     * @param array $data
     * @param string $current_value
     * @return string
     */
    static function getSelectOptionStr($data, $current_value) {
        $Str = '';
        if (is_array($data) && !empty($data)) {
            foreach ($data as $key => $label) {
                $Str.='<option value="' . $key . '" ';
                $Str.=($current_value == $key) ? 'selected' : '';
                $Str.='>' . $label . '</option>';
            }
        }

        return $Str;
    }



    /**
     *
     * @param string $message
     * @return string
     */
    static function getExcerpt($html, $maxlength = 150) {
        $text = strip_tags($html);
        $textLength = strlen($text);

        if ($textLength <= $maxlength) {
            return $html;
        }

        $chars =array('space' => ' ', 'comma' => ',', 'semicolon' => ';',
                      'colon' => ':', 'period' => '.', 'exclamation' => '!',
                      'question' => '?'
                    );

        $start = (int) (0.9 * $maxlength);

        $positions = self::nextCharPositions($text, $chars, $start);
        //We want to use the position closest to the maxlength value
        asort($positions);

        foreach($positions as $key => $position){
            if($position !==FALSE){
                return substr($text, 0, $position) . ' ...';
            }
        }

        return $text;
    }


    /**
     *
     * @param string $text
     * @param array $chars
     * @param int $start
     * @return array
     */
    static function nextCharPositions($text, $chars, $start) {
        $positions = [];
        foreach ($chars as $key => $char) {
            $positions[$key] = strpos($text, $char, $start);
        }

        return $positions;
    }


    /**
     *
     * @return string
     */
    static function getNewID($length=15) {
        $length=(is_numeric($length) && $length>=5)?(int) $length:15;

        return bin2hex(random_bytes($length));
    }


    /**
     *
     * @return string
     */
   static function getShortID(){
        $rawid=date("siHYmd").rand(0,10000);
        return base_convert ($rawid, 10, 36);
    }


    /**
    * @param string $action
    * @param int $npages
    * @param int $cursor
    * @return string
    */
   static function getPaginationBar($action, $npages, $cursor, $tab=''){
        if($npages < 2){
            return '';
        }

        $Str='<div class="pagination-bar">';
        $Str.=($npages>1)?'Pages: &nbsp; ':'';

        for($i=0; $i < $npages; $i++ ){
            $k=$i + 1;
            if($i == $cursor){
                $Str.='<span class="current-page">' . $k . '</span>';
            }
            else{
                $Str.='<a href="#" class="btn_page" data-action="' .
                $action . '" data-tab="'.$tab.'" data-np="' . $npages . '"
                data-cs="' . $i . '">' . $k . '</a>';
            }

        }

        $Str.='</div>';

        return $Str;
    }


   static function convertKeyToWords($key){
        $find=['-', '_'];
        $replace=' ';
        $key=str_replace($find, ' ', $key);

        return ucwords($key);
    }


    /**
     *
     * @param string $photo_url
     * @param int $media_id
     * @return string
     */
   static function getPhoto($photo_url, $media_id, $size='thumb'){

        if($media_id > 0 && ($Str=self::getImageTag($media_id, $size))!=''){
            return $Str;
        }
        elseif (trim($photo_url)!='' && ($size = getimagesize($photo_url)) !==FALSE){
             return '<img src="'.$photo_url.'" '.$size[3].'">';
        }

        $default_photo=get_template_directory_uri().'/images/generic_black_BW.jpg';

        return  '<img src="'.$default_photo.'" style="width:95%; max-width:150px; height:auto;">';
    }


    /**
     *
     * @param int $media_id
     * @param string $size
     * @param string $title
     * @return string
     */
   static function getImageTag($media_id, $size, $title=''){
        $imgStr='';

        if($media_id>0){
            $imgStr=get_image_tag($media_id, $title, $title, 'none', $size );
        }

        return $imgStr;
    }


   static function getImageSourceSet($media_id, $title){
        //Retrieve info about media file
        $meta_data=wp_get_attachment_metadata($media_id, false );

        //Meta data does not specify the uploads folder URL
        $upload_dir = wp_upload_dir();
        $url=$upload_dir['baseurl'];


        if(is_array($meta_data) && isset($meta_data['sizes'])){
            $ratio=number_format( (100 * $meta_data['height'] / $meta_data['width']), 2);

            //Retrieve folder name from the original file
            $length=strrpos($meta_data['file'], '/');
            $folder=substr($meta_data['file'], 0, $length);

            //Fallback image for browsers not supporting srcset attribute
            $Str='<img src="'.$url.'/'.$meta_data['file'].'" alt="'.$title.'" ';
            $Str.='style="width:100%; height: '.$ratio.'vw" ';

            $sizes=$meta_data['sizes'];
            $sourceset=$url.'/'.$meta_data['file'].' '.$meta_data['width'].'w';

            foreach($sizes as $size => $row){
                $sourceset.=($sourceset!='')?', ':'';
                $sourceset.=$url.'/'.$folder.'/'.$row['file'].' '.$row['width'].'w';
            }

            $Str.='srcset="'.$sourceset.'" ';

            $Str.='sizes="(max-width:'.$meta_data['width'].'px) 100vw, '.$meta_data['width'].'px" ';
            $Str.='>';

            return $Str;
        }

        return false;
    }


   static function getFacebookFeeds($data, $message_length){
        $Str='';

        if( ! is_array($data)){
            return '';
        }

        // URL of the FB page ID feeds came from
        $url='https://facebook.com/' . $data['fb_id'];
        // User FB profile photo
        $profile_picture=$data['profile_picture'];

        $feeds=$data['feeds'];

        foreach($feeds as $k => $row){
            $Str.='<div class="facebook_feed"><header>
            <a href="' . $url .
                '" target="_blank" class="profile_picture"><img src="' .
                $profile_picture . '"></a>
            <a href="' . $url . '" target="_blank" class="page_name">' .
                $row['page_name'] . '</a>
            <span class="feed_date">'.date('F j \a\t g:ma',$row['created_time']).'</span>
            </header>
            <section class="feed_content">';
            if(isset($row['picture_url']) && trim($row['picture_url']) != ''){
                $Str.='<figure><a href="' . $url . '" target="_blank"><img src="' .
                $row['picture_url'] . '"></a></figure>';
            }

            $Str.='<p>' .self::getExcerpt($row['message'], $message_length).
            '<a  href="' . $url . '" class="facebook_logo" target="_blank">&nbsp</a></p>
            </section>
            </div>';
        }

        if($Str!=''){
            $Str='<div class="feed_row" data-network="facebook">'.$Str.'</div>';
        }

        return $Str;
    }


    /**
     *
     * @param string $SearchStr
     * @return string
     */
    static function cleanSearchKeywords($SearchStr){
        $stop_words=self::getStopWords();
        $SearchStr= preg_replace($stop_words, "", $SearchStr);

        return trim($SearchStr);
    }

    /**
     * Array of common stop words to remove from search
     *
     * @return array
     */
    static function getStopWords(){
        require ('stop_words.php');

        return $stop_words;
    }

     /**
     * 
     */
    static function logErrorMessage($table, $message){
        global $wpdb;

        $data=array('message'=> $message, 'entry_date'=> date('Y-m-d H:i:s'));
        $wpdb->insert($table, $data, '%s');
    }
    

  /**
   * 
   */
  static function getRGBA($bgcolor, $opacity){
    $opacity=is_numeric($opacity)? number_format($opacity/100, 2):0;
    
    return self::getRGBColor($bgcolor).','.$opacity;
  }

  /**
   * Convert hex color format (#fff) into RGB format
   */
  static function getRGBColor($color){
    $rgb='0,0,0'; //default color

    if(strpos($color, '#')!==FALSE){
      if(strlen($color)==4){
        $rgb=hexdec($color[1]).','.
        hexdec($color[2]).','.
        hexdec($color[3]);
      }
      else if(strlen($color)==7){
        $rgb=hexdec(substr($color, 1, 2)).','.
        hexdec(substr($color, 3, 2)).','.
        hexdec(substr($color, 5, 2));
      }
    }
    else if(substr_count($color, ',')==2){
      $rgb=$color;
    }

    return $rgb;
  }
}

//end class
