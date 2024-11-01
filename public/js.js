jQuery(document).ready(function($){
  if(wpxp_css_url){
    var graphics_css='<link href="'+wpxp_css_url+'" rel="stylesheet"/>';
    $('head').append(graphics_css);
  }

  /**
   * 
   * @param {object} selectors 
   */
  function wpxpSelectorOverlay(selectors){
    var targetElt, cover, side;
    if(!selectors){return; }
    for(var i=0; i<selectors.length; i++){
      if($(selectors[i]).length==0){continue;}
      targetElt=$(selectors[i]['selector']);

      targetElt.addClass('has_overlay');
      targetElt.css({position:'relative', overflow:'hidden'});

      targetElt.children().each(function(elt){
        if($(this).css('position')!='absolute' && $(this).css('position')!='fixed'){
            $(this).css({position:'relative',zIndex:100});
        }
      });

      targetElt.append('<div class="wpxp_overlay">&nbsp;</div><div class="wpxp_content_overlay">&nbsp;</div>');

      cover= selectors[i]['cover'];
      side= selectors[i]['side'];
      //Wrap HTML content of selector with div
      wpxpSelectorHalfCovered(targetElt, cover, side);
    }
  }

  /**
   * 
   * @param {htmlElement} elt 
   * @param {int} cover 
   * @param {string} side 
   */
  function wpxpSelectorHalfCovered(elt, cover, side){
     if(cover==50){
       if(side=='left'){
         elt.wrapInner('<div class="wpxp_copytoright"></div>');
       }
       else if(side=='right'){
        elt.wrapInner('<div class="wpxp_copytoleft"></div>');
       }
     }
  }

  /**
   * 
   * @param {object} bgvideo 
   */
  function wpxpBackgroundVideo(bgvideo){
    if(!bgvideo || !bgvideo.selector || !bgvideo.embed_code){return; }
    wpxpSelectorOverlay(['bgvideo.selector']);

    $(bgvideo.selector).prepend('<div class="wpxp_bgvideo">'+
    bgvideo.embed_code+'<div class="wpxp_overlay" style="'+ 
    wpxpOverLayStle(bgvideo.bgcolor, bgvideo.opacity)+
    '"></div></div>');
    
    $('.wpxp_bgvideo iframe').css({position:'absolute',top:0,left:0,zIndex:1});
  }

  /**
   * 
   * @param {string} bgcolor 
   * @param {int} opacity 
   */
  function wpxpOverLayStle(bgcolor, opacity){
   return 'z-index:2;background-color:'+bgcolor+';'+
   'opacity:'+ opacity/100 +';'+
   '-ms-filter:"progid:DXImageTransform.Microsoft.Alpha(Opacity='+opacity+')";'+
   'filter: alpha(opacity='+opacity+');';
  }

  wpxpSelectorOverlay(wpxp_image_selectors);
  wpxpBackgroundVideo(wpxp_bgvideo);
});
