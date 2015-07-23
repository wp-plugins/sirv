 /* global tinymce */
 
 /* The filter takes an associative array of external plugins for
 * TinyMCE in the form 'plugin_name' => 'url'.
 *
 * The url should be absolute, and should include the js filename
 * to be loaded. For example:
 * 'myplugin' => 'http://mysite.com/wp-content/plugins/myfolder/mce_plugin.js'.
 */


tinymce.PluginManager.add('sirvgallery', function( editor ) {

    var jq;
    
    function replaceGalleryShortcodes( content ) {
        return content.replace( /\[sirv-gallery id=(\d)\]/g, function( match, id ) {
            return html( match, id );
        });
    }

    function html(sc, id ) {
        sc = window.encodeURIComponent( sc );
        var html = '';
        var data = {}
            data['action'] = 'sirv_get_row_by_id';
            data['row_id'] = id;

            jq.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                async: false,
                dataType: 'json'
            }).done(function(response){

                //console.log(response);

                var img_data = response['images'];
                var images = '';
                var count = img_data.length > 4 ? 4 : img_data.length;

                for(var i = 0; i < count; i++){
                    images += '<img src="https:'+ img_data[i]['url'] +'?thumbnail=132&image=true" alt="'+ img_data[i]['caption'] +'" />'
                }//contenteditable=false
                html =  '<div class="sirv-sc-view" data-id="'+ id +'" data-shortcode="'+ sc +'" contenteditable=false >'+ 
                        '<div class="sirv-overlay" data-id="'+ id + '">'+
                        '<span class="sirv-overlay-text">Sirv gallery: '+ img_data.length +' image'+((img_data.length>1)?'s':'')+'</span>'+
                        '<a href="#" title="Delet gallery" class="sirv-delete-sc-view sc-view-button sc-buttons-hide">delete</a><a href="#" data-id="'+id+'" title="Edit gallery" class="sirv-edit-sc-view sc-view-button sc-buttons-hide">edit</a>'+
                        '</div>'+ images + '</div>';
            });

/*            html =  '<div class="sirv-sc-view" data-id="'+ id +'" data-shortcode="'+ sc +'" contenteditable=false >'+ 
                        '<div class="sirv-overlay" data-id="'+ id + '">'+
                        '<span class="sirv-edit-sc-view sc-view-button sc-buttons-hide">edit</span><span class="sirv-delete-sc-view sc-view-button sc-buttons-hide">delete</span>'+
                        '<span class="sirv-overlay-text">Sirv gallery</span></div></div>';  */        
        return html;
    }


    function restoreMediaShortcodes( content ) {
        function getAttr( str, name ) {
            name = new RegExp( name + '=\"([^\"]+)\"' ).exec( str );
            return name ? window.decodeURIComponent( name[1] ) : '';
        }

        return content.replace( /(<div class="sirv-sc-view.*?" .*?>)<div.*?>.*?<\/div>.*?<\/div>/g, function( match, div ) {
            var data = getAttr( div, 'data-shortcode' );

            if ( data ) {
                return  data;
            }

            return match;
        });
    }


    editor.on( 'mouseup', function( event ) {
        var dom = editor.dom,
            node = event.target;

        function selectView(){
            dom.addClass( dom.select( 'div.sirv-sc-view' ), 'selected' );
            dom.removeClass( dom.select( '.sirv-edit-sc-view' ), 'sc-buttons-hide' );
            dom.removeClass( dom.select( '.sirv-delete-sc-view' ), 'sc-buttons-hide' );
        }

        function unselect() {
            dom.removeClass( dom.select( 'div.sirv-sc-view' ), 'selected' );
            dom.addClass( dom.select( '.sirv-edit-sc-view' ), 'sc-buttons-hide' );
            dom.addClass( dom.select( '.sirv-delete-sc-view' ), 'sc-buttons-hide' );
        }
        //if ( node.nodeName === 'DIV' && dom.hasClass( node, 'sirv-overlay' ) ) {
        if ( dom.hasClass( node, 'sirv-overlay') || dom.hasClass( node, 'sc-view-button') ) {
            // Don't trigger on right-click
            if ( event.button !== 2 ) {
                 selectView();
            } else {
                unselect();
            }
        }else{
            unselect();
        }
    });

    function deleteView(event){

    }

    // Display sirv-gallery, instead of div in the element path
    editor.on( 'ResolveName', function( event ) {
        var dom = editor.dom,
            node = event.target;

        if ( node.nodeName === 'DIV' && dom.hasClass( node, 'sirv-sc-view' ) ) {
            event.name = 'sirv-gallery';
        }
    });


    editor.onClick.add(function(editor, e) {
        if(e.target.className == 'sirv-edit-sc-view sc-view-button'){
            //var id = editor.dom.getAttrib(editor.dom.select( 'div.sirv-sc-view'), 'data-id');
            var id = editor.dom.getAttrib(e.target, 'data-id');
            if(typeof sirv_edit_flag === 'undefined'){
                jq('head').append(jq('<style>#TB_iframeContent{width: 100% !important;}\n#TB_window{left: 0 !important; margin-left: auto !important; width: 85% !important;'+
                    'margin-right: auto !important; right: 0 !important;}</style><script type="text/javascript">var sirv_edit_flag = true; var sirv_sc_id='+ id +'</script>'));
            }else{
                sirv_edit_flag = true;
                sirv_sc_id = id;
            }
            tb_show('Edit sirv gallery', 'media-upload.php?type=sirv&TB_iframe=true');

        }else if(e.target.className == 'sirv-delete-sc-view sc-view-button'){

            var content = editor.getContent({format : 'raw'});
            console.log(content);
            content = content.replace(/(<div class="sirv-sc-view.*?" .*?>)<div.*?>.*?<\/div>.*?<\/div>/g, '');
            editor.setContent(content);
        }
      });



    editor.on( 'BeforeSetContent', function( event ) {
        event.content = replaceGalleryShortcodes( event.content );
    });


    editor.on( 'PostProcess', function( event ) {
        if ( event.get ) {
            event.content = restoreMediaShortcodes( event.content );
        }
    });


     editor.on('preInit', function() {
        jq = editor.getWin().parent.jQuery;
        
    });

});