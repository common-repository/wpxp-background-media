<?php
/**
 * WPXP Background Media Plugin
 * @filesource wpxp-background-media/admin/classes/dashboard.php
 * @author Alex Diokou
 * @copyright WPXPertise
 * @url https://wpxpertise.com
 * @version 1.0.0 
 * @package wpxp-background-media
 */

defined('ABSPATH') or die('No script direct access please!');

class wpxpBGMDashboard{
  private $errors=array();

  public function __construct(){

  }

  public function getErrors(){
    return $this->errors;
  }

  /**
   * 
   */
  public function processRequest(){
    $response=$this->handleRequest();
    $response['message'] = wpxp_Helper::showErrors($this->errors);

    return $response;
  }

  /**
   * 
   */
  private function handleRequest(){
    $html='<ul class="dashboard">'.
    $this->getIntro().
    $this->getLayoutBreakPoint().
    $this->getScreenSize().
    $this->setMaxiumWidth().
    //$this->getSetImageSize().
    $this->setTargetScreenSize().
    $this->getRemoveLayoutBreakPoint().
    $this->getAddLayoutBreakPoint().
   // $this->getApplyLayoutBreakPoint().
    $this->getBackgroundImageOverlay().
    $this->getContentOverlay().
    '</ul>';

    return array('html' => $html);
  }

  /**
   * 
   */
  private function getIntro(){
    $Str='<li><h3>'.__('Intro', 'wpxp-background-media').'</h3>
    <section>
    <p>Each section below gives you detailed information about each field in the "layout breakpoint" fieldset.</p>

    <p>After you\'ve defined all layout breakpoints and saved the changes.</p>

    <p>Afterward, you can open any static page then click the "Add New Background Media" button appearing under the WordPress text editor.</p>
    </section>
    </li>';

    return $Str;
  }

  /**
   * 
   */
  private function getLayoutBreakPoint(){
    $Str='<li class="close">
    <h3>'.__('Layout Breakpoint', 'wpxp-background-media').'</h3>
    <section>
    <p>Each layout breakpoint defines the point at which your website layout changes according the width of web browser window.</p>

    <p>Those layout breaking points can be retrieved from your active theme\'s stylesheet.</p>
    </section>
    </li>';

    return $Str;
  }

  /**
   * 
   */
  private function getScreenSize(){
    $Str='<li class="close">
    <h3>'.__('Screen Size', 'wpxp-background-media').'</h3>
    <section>
    <p>The "Screen Size" field allows WordPress to name the image size matching the maximum width specified.</p>

    <p>That image name is sanitized to only allow letters, numbers and the underscore. If the sanitized name matches one of the names reserved by WordPress (e.g. small, thumb, medium, large) then you should set the maximum width to match the corresponding default value set by WordPress.</p>

    <p>For instance the image size "large" is by default set to 1024 pixels wide. You can find that value by going to "Settings > Media" in the sidebar menu of your WordPress control panel.</p>

    <p>You can use any default image name so long as its corresponding image width matches the values defined in "Settings > Media".</p>

    <p>The size name "full" is also reserved for the original image size uploaded to your WordPress media library.</p>
    </section>
    </li>';

    return $Str;
  }

  /**
   * 
   */
  private function setMaxiumWidth(){
    $Str='<li class="close">
    <h3>'.__('Maximum Width', 'wpxp-background-media').'</h3>
    <section>
    <p>This value determines the layout breakpoint to be set in the media queries.</p>

    <p>There is no need to specify the minimum width. The media queries will be written from the smallest value of "max-width" to the highest.</p>

    <p>If, for instance, the largest value of maximum is 1920 (pixels), then an additional media query directive "@media all and (min-with:1921px){ ... }" will be automatically created to covers screen sizes larger then 1920px;</p>
    </section>
    </li>';

    return $Str;
  }

  /**
   * 
   */
  private function getSetImageSize(){
    $Str='<li class="close">
    <h3>'.__('Set Image Size', 'wpxp-background-media').'</h3>
    <section>
    <p>If you tick the checkbox labeled "Set image size" then WordPress will create a cropped version with that width whenever you upload an image to the media library. </p>

    <p>However, the original image must be larger than the specified width for WordPress to create that copy.</p>

    <p>WordPress will automatically set height of that copy to keep the sames width:height ratio as the original image.</p>
    </section>
    </li>';

    return $Str;
  }


  private function setTargetScreenSize(){
    $Str='<li class="close">
    <h3>'.__('Target Screen Size', 'wpxp-background-media').'</h3>
    <section>
    <p>This option lets you choose with screen sizes (Desktops and laptops, or Tablets and Smartphones) does the current layout breakpoint applies to.</p>

    <p>When you later define a background image, it will always cover the entire with of its target "CSS Selector".</p>

    <p>On the other hand, if you set the background image to only cover 50% of the target "CSS selector", then only half of that container will be covered on desktops/laptops. The whole width of the target CSS selector will always be covered on small screens (tablets, smartphones).</p>
    </section>
    </li>';

    return $Str;
  }

  /**
   * 
   */
  private function getRemoveLayoutBreakPoint(){
    $Str='<li class="close">
    <h3>'.__('Remove Layout Breakpoint', 'wpxp-background-media').'</h3>
    <section>
    <p>To delete a layout breakpoint, click on the red crossed circle icon then confirm your action. Then press the "Save Changes" button to commit those changes.
    </p>
    </section>
    </li>';

    return $Str;
  }

  /**
   * 
   */
  private function getAddLayoutBreakPoint(){
    $Str='<li class="close">
    <h3>'.__('Add Layout BreakPoint', 'wpxp-background-media').'</h3>
    <section>
    <p>'.__('You can define as many layout breakpoints as needed. However those breakpoints should be dictated by your active WordPress theme.', 'wpxp-background-media').'</p>

    <p>'.__('Therefore, you should check with your theme designer to get a list of breakpoints.', 'wpxp-background-media').'</p>

    <p>'.__('Alternatively, you can take a peek in the "style.css" of your active WordPress theme to search for "@media"; you would see the "max-width" value in each media query directive. Those are the values you need to define your layout breakpoints here.', 'wpxp-background-media').'</p>

    <p>'.__('Click "Settings" tab above to set those breakpoints. The "Add Layout Breakpoint" link at the bottom of that screen allows you to add a new breakpoint.', 'wpxp-background-media').'</p>
    </section>
    </li>';

    return $Str;
  }

  private function getApplyLayoutBreakPoint(){
    $Str='<li class="close">
    <h3>'.__('Apply Layout BreakPoint', 'wpxp-background-media').'</h3>
    <section>
    <p>'.__('Once you are done setting all layout breakpoints and saved those updates, you need to switch the current theme off then on again.', 'wpxp-background-media').'</p>

    <p>'.__('You can do so from the "Apperarance" menu then choose "Themes". That opens the themes page which displays the list of currently installed theme.', 'wpxp-background-media').'</p>

    <p>'.__('Take notice of the current theme; it\'s labeled "Active" next to that theme\'s name. You can then proceed to activate any other theme listed; that switches the theme currently used on your website.', 'wpxp-background-media').'</p>

    <p>'.__('To avoid your website visitors finding out about that sudden change in your website design, quickly switch back to the previously active theme. That will restored your website appearance to its original look-and-feel.', 'wpxp-background-media').'</p>
    <p></p>
    </section>
    </li>';

    return $Str;
  }

  /**
   * 
   */
  private function getBackgroundImageOverlay(){
    $Str='<li class="close">
    <h3>'.__('Background Media Overlay', 'wpxp-background-media').'</h3>
    <section>
    <p></p>
    <p></p>
    <p>'.__('For now, the background media overlay settings are overridden by those in the "Background Image/Video" metabox under the WordPress page editor. But, we suggest you set those values anyway.', 'wpxp-background-media').'</p>
    </section>
    </li>';

    return $Str;
  }

  /**
   * 
   */
  private function getContentOverlay(){
    $Str='<li class="close">
    <h3>'.__('Content Overlay', 'wpxp-background-media').'</h3>
    <section>
    <p>'.__('The content overlay allows you to lighten (#fff) or darken (#000) the overall overlay of the background image or video.', 'wpxp-background-media').'</p>

    <p>'.__('You can hence reduce the tint or the shade created by the "Background Media Overlay".', 'wpxp-background-media').'</p>

    <p>'.__('You can also choose to center the content overlay by setting its maximum with to a pixel value or a percentage less than 100%;', 'wpxp-background-media').'</p>
    </section>
    </li>';

    return $Str;
  }




}//end class