<?php
/**
 * @filesource wpxp-background-media/admin/inc/descriptions.php
 * @author Alex Diokou
 * @copyright WPXPertise
 * @url https://wpxpertise.com
 * @version 1.0.0
 * @package wpxp-background-media
 */

defined('ABSPATH') or die('No script direct access please!');

$wpxp_bgm_descriptions=array(
      'media_type' => __('Use Image or Video as background media', 'wpxp-background-media'),

      'css_selector' => __('CSS selector the background media applies to. Use current page slug and content element ID or class (e.g. #about .intro). '),

      'focal_point' => __('Position of focal point in the image to align the background media', 'wpxp-background-media'),

      'bg_attachment' => __('How the background image behaves against the foreground content', 'wpxp-background-media'),

      'percentage' => __('Percentage of the CSS Selector width covered by the background image', 'wpxp-background-media'),

      'side_covered' => __('Which side of the CSS selector width to cover when percentage is set to 50. The foreground content is displayed on the other side.', 'wpxp-background-media'),

      'max_height' => __('Maximum height of HTML element the background media applies to. Viewport, to cover to entire width and height of the screen. BG Media,to match the height of the background image. Container, leaves your active theme decide about the maximum with of the HTML element the background image applies to.', 'wpxp-background-media'),

      'selector_bgcolor' => __('Background color of the CSS selector container', 'wpxp-background-media'),

      'text_color' => __('Normal text color when overlay is not present.', 'wpxp-background-media'),

      'overlay_text_color'=> __('Color of text when displayed above the overlay of the background media', 'wpxp-background-media'),

      'show_overlay' => __('On which screen sizes the overlay will be shown above the background media.', 'wpxp-background-media'),

      'overlay_bgcolor' => __('Background color of the overlay on the background media.', 'wpxp-background-media'),

      'overlay_opacity' => __('Opacity of the overlay; between 0 and 100. Set to 0 to show the background image or video without any overlay.', 'wpxp-background-media'),

      'embed_code' => __('Video Embed HTML Code', 'wpxp-background-media'),
    );