jQuery(document).ready(function($){
  const ajax_obj=wpxp_bgm_ajax_obj;
  const menu_name=ajax_obj['admin_panel']['menu_name'];
  const tabsID=ajax_obj['admin_panel']['tabsID'];
  const contentID=ajax_obj['admin_panel']['contentID'];
  const messageID=ajax_obj['admin_panel']['messageID'];
  const navbarID=ajax_obj['admin_panel']['navbarID'];

  $(tabsID+' .tab').click(function (evt) {
    evt.preventDefault();
    var action=$(this).attr('data-action');
    var dataStr='action='+ action;
    ajaxPost(dataStr);

    $(tabsID+' li').removeClass('selected');
    $(this).parent('li').addClass('selected');
    $('#toplevel_page_'+menu_name+' .wp-submenu li a').removeClass('current');
    $('#adminmenu li.wp-has-submenu ul.wp-submenu li').removeClass('current');
    $('#adminmenu li.wp-has-submenu ul.wp-submenu li a[href="admin.php?page='+action+'"]').parent('li').addClass('current');
  });

  /**
   * Ajax requests handlers
   */
  function ajaxPost(dataStr) {
    dataStr+='&_ajax_nonce='+ajax_obj.nonce;
    var url=ajax_obj.ajax_url;
    jQuery.post(url, dataStr, function(response){
      processAjaxResponse(response);
    }).
    fail(function(response){
        displayMessage(ajax_obj.request_failed);
    });

    showLoader();
  }

  /**
   * Handles AJAX response
   */
  function processAjaxResponse(response){
    if(response.html){
      $(contentID).html(response.html);
    }
    //
    displayNavigation(response.navigation);
    //Error or success message
    displayMessage(response.message);
  }

  /**
   * Reveal loader animation
   */
  function showLoader(){
    var loader='<div class="loader">'+ajax_obj.loading+'</div>';
    $(messageID).html(loader);
  }

  /**
   * Show secondary links such as "Add New"
   */
  function displayNavigation(navigation){
    navigation=navigation||'';
    $(navbarID).html(navigation);
  }

  /**
   * Success notification message to show
   */
  function displayMessage(message) {
    if(message){
      $(messageID).html(message);
    }
    else {
      $(messageID).html('');
    }
  }

  /**
   * Update index of all fields in a fieldset to match its row index
   * Then updates value of "nitems" hidden field
   */
  function updateFieldIndexes(){
    var nitems=0;
    $(contentID + ' .wpxpbgmform fieldset').each(function(row_index){
      $(this).find('input').each(function(k){
        var indexed_name=$(this).attr('data-name')+'_'+row_index;
        $(this).attr('name', indexed_name);
      });
      //
      updateFieldsetLegend($(this), row_index);
      nitems++;
    });

    $(contentID + ' .wpxpbgmform input[name="nitems"]').val(nitems);
  }

  /**
   * 
   * @param {fieldset} elt current fieldset element
   * @param {int} index index of current fieldset
   */
  function updateFieldsetLegend(elt, index){
    var legend=elt.children('legend').first().html();
    var row_num=index+1;
    legend=legend.replace(/#\d+/, '#'+row_num, legend);
    elt.children('legend').first().html(legend);
  }

  /**
   * Add new fieldset to define layout breakpoint
   */
  function addLayoutBreakPoint(){
    $(contentID + ' .wpxpbgmform fieldset').last().after(wpxp_bgm_row);
    updateFieldIndexes();
  }

  /**
   * Handle form submissions
   */
  $(contentID).on('submit', '.wpxpbgmform', function (evt) {
    evt.preventDefault();
    var dataStr=$(this).serialize();
    dataStr+='&action='+$(this).attr('action');

    ajaxPost(dataStr);
    return false;
  });

  //new entry
  $(navbarID).on('click', '.btn_new', function (evt) {
    evt.preventDefault();
    addLayoutBreakPoint();
    return false;
  });
  
  //Toggle open/close dashboard description section
  $(contentID).on('click', '.dashboard h3', function (evt) {
    $(this).parent('li').toggleClass('close');
  });

  //Remove fieldset in layout breakpoints settings
  $(contentID).on('click', '.delete', function(evt){
    if(confirm('Are you sure you want to delete this background item?')){
      $(this).parent('fieldset').remove();
      updateFieldIndexes();
    }
  });

  //Collapse open/close fieldset in layout breakpoint settings
  $(contentID).on('click', '.collapse', function(evt){
    $(this).parent('fieldset').toggleClass('collapsed');
  });

  /*
  ==========================================================================
      * Background Image Selection from WP Media Library
      * Background Media Meta Data Form Management
  ==========================================================================
  */
	var wpxp_frame;
	var imgContainer, imgIdInput;

  function selectBackgroundMedia(elt){
    var parentElt=elt.parents('fieldset').first();
    var rowIndex=parentElt.attr('data-row');
    //
    console.log('Row index:'+rowIndex);
    //
    imgContainer=$('#wpxp_preview_'+rowIndex);
    //
    imgIdInput=$('#wpxp_media_id_'+rowIndex);

    // If the media frame already exists, reopen it.
    if (wpxp_frame ) {
      wpxp_frame.open();
      return;
    }

    // Create a new media frame
    wpxp_frame = wp.media({
      title: 'Select or Upload Media',
      button: {
        text: 'Use this media'
      },
      multiple: false  // Set to true to allow multiple files to be selected
    });

    // When an image is selected in the media frame...
    wpxp_frame.on( 'select', function() {
    // Get media attachment details from the frame state
    var attachment = wpxp_frame.state().get('selection').first().toJSON();
    var imgInfo;

    //To choose which image size to display
    var imgSizes=attachment['sizes'];

    //Choose "medium" for large images otherwise show original size
    if(imgSizes['full']['width']> 640 || imgSizes['full']['height']> 640){
      imgInfo=imgSizes['medium'] || imgSizes['medium_large'] || imgSizes['large'];
    } 
    else{
      imgInfo=imgSizes['full'];
      console.log('I am here in full');
    }

    //ID of image to update the wpxp_media_id field with
    imgInfo['id']=attachment.id;

    updateMediaForm(imgInfo);
    //reset media frame
      wpxp_frame=null;
    });

    // Finally, open the modal on click
    wpxp_frame.open();
  }
  
  /**
   * Displays thumbnail of selected background image
   * Updates the "media_id" value to that of the selected image
   * @param {object} imgInfo 
   */
  function updateMediaForm(imgInfo){
	  // Send the attachment URL to our custom image input field.
	  imgContainer.html('<img src="'+imgInfo['url']+'" width="'+ imgInfo['width'] +'" height="'+ imgInfo['height'] +'" alt="'+ imgInfo['title'] +'" />' );

	  // Send the attachment id to our hidden input
	  imgIdInput.val(imgInfo['id']);
  }

	/**
	 * Show or hide fields related to background image or video
	 */
  function switchMediaSelection(elt){
    var option=elt.children('option:selected').first().val();
    var parentElt=elt.parents('fieldset');
    var bgimg_container=parentElt.children('.wpxp_background_image').first();
    var bgvideo_container=parentElt.children('.wpxp_background_video').first();

    //Show/Hide the BG image/video container
    if(option=='image'){
      bgimg_container.show(200);
      bgvideo_container.hide(200);
    }
    else if(option=='video'){
      bgvideo_container.show(200);
      bgimg_container.first().hide(200);
    }
  }
  
  //Update hidden field value keeping tally of fieldsets
  function updatNumberOfFieldsets(){
    var nitems=$('#wpxp_bg_media').children('fieldset').length;
    $('input[name="wpxp_nitems"]').val(nitems);
    //Ensure the index of each fieldset matches the new ordering
    updatFieldsetItems();
  }

  /**
   * Update the index in fields names to match current fieldset
   * row index in the BG Media metabox
   */
  function updatFieldsetItems(){
    var row_index;
    $('#wpxp_bg_media fieldset').each(function(index){
      //data-row attribute crucial for targeting all elements
      //inside the current fieldset
      $(this).attr('data-row', index);

      row_index=index+1;
      var Str=$(this).html();
      //Add row index value to field name
      Str=Str.replace(/name="([^"]+_)"/gi, 'name="$1'+index+'"');
      //Add row index value to ID attributes
      Str=Str.replace(/id="([^"]+_)"/gi, 'id="$1'+index+'"');
      //Row number on Legend
      Str=Str.replace(/Video\s+#\d*/gi, 'Video #'+row_index);
      $(this).html(Str);
    });
  }
  
  //Append new fieldset to define another BG Image/Video
  function addNewMediaFieldSet(elt){
    //prepend fieldset
    elt.before(wpxp_bgmedia_template);
    updatFieldsetItems();
    updatNumberOfFieldsets();
  }

  //Button to select image from media library
  $('#wpxp_bg_media').on('click', '.btnselectmedia', function(evt){
	  selectBackgroundMedia($(this));
  });

  //Arrow to toggle open/close parent fieldset
  $('#wpxp_bg_media').on('click', '.wpxp_toggle_arrow', function(evt){
    $(evt.target).parent('fieldset').toggleClass('collapse');
    $(evt.target).toggleClass('wpxp_toggle_down');
  });
  
  //Arrow to toggle open/close parent fieldset
  $('#wpxp_bg_media').on('click', '.wpxp_delete_fieldset', function(evt){
    if(confirm('Are you sure you want to delete this background item?')){
      $(evt.target).parent('fieldset').remove();
      updatNumberOfFieldsets();
    }
  });

  //Button to add new background media fieldset
  $('#btn_wpxp_add_media').click(function(evt){
    addNewMediaFieldSet($(this));
  });

  //Switch media type selection (image/video)
  $('#wpxp_bg_media').on('change', '.wpxp_media_type', function(evt){
    switchMediaSelection($(this));
  });

});
