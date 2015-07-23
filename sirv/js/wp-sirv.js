jQuery(function($){

    $(document).ready(function(){


        function render_view(json_obj){

            var bucket = json_obj.bucket;
            var dirs = json_obj.dirs;
            var images = json_obj.contents;

            if(dirs !== null){
                var documentFragment = $(document.createDocumentFragment());
                for(var i = 0; i < dirs.length; i++){
                    var dir = dirs[i].Prefix.split("/");
                    //from prefix like test/example1 create folder name example1
                    dir = dir[dir.length-2];
                    if(dir[0] != '.'){
                        var elemBlock = $('<li><a class="sirv-link" href="#" data-link="'+dirs[i].Prefix+'">'+
                            '<img src="'+ ajax_object.assets_path +'/ico-folder.png" />'+
                            '<span>'+dir+'</span></a></li>\n');
                        documentFragment.append(elemBlock);
                    }
                }
                $('#dirs').append(documentFragment);
            }

            if(images !== null){
                var documentFragment = $(document.createDocumentFragment());
                for(var i = 0; i < images.length; i++){
                    var valid_images_ext = ["jpg", "png", "gif", "bmp","jpeg","PNG","JPG","JPEG","GIF","BMP"];
                    var image = images[i].Key;

                    var fileName = image.replace(/.*\/(.*)/gm,'$1');

                    var image_ext = image.substr((~-image.lastIndexOf(".") >>> 0) + 2)
                    if( image_ext != ''){
                        if(valid_images_ext.indexOf(image_ext) != -1){
                            var elemBlock = $('<li class="sirv-image-container"><a href="#" title="'+fileName+'"><img class="sirv-image" src="https://'+bucket+'.sirv.com/'+image+'?thumbnail=100"'+
                                ' data-id="'+ md5('//'+bucket+'.sirv.com/'+image) +'" data-type="image" data-original="https://'+bucket+'.sirv.com/'+image+'" /><span>'+fileName+'</span></a></li>\n');
                            documentFragment.append(elemBlock);
                        }else if(image_ext == 'spin'){
                            //https://obdellus.sirv.com/test/test.spin?thumbnail=132&image=true
                            $('<li class="sirv-spin-container"><a href="#" title="'+fileName+'"><img class="sirv-image" data-id="'+ md5('//'+bucket+'.sirv.com/'+image) +
                                '" data-original="https://'+bucket+'.sirv.com/'+image+'" data-type="spin" '+
                                'src="https://'+bucket+'.sirv.com/'+image+'?thumbnail=100&image=true" /><span>'+fileName+'</span></a></li>\n').appendTo('#spins');
                        }
                    }
                }
                $('#images').append(documentFragment);
            }
        }


        function erase_view(){

            unbindEvents();
            $('#dirs').empty();
            $('#images').empty();
            $('#spins').empty();
            $('.breadcrumb').empty();
        }


        function render_breadcramb(current_dir){

            $('<li><a href="#" class="sirv-link" data-link="/">Home</a></li>').appendTo('.breadcrumb');
            if(current_dir != "/"){
                var dirs = current_dir.split("/");
                var temp_dir = "";
                for(var i=0; i < dirs.length - 1; i++){
                    temp_dir += dirs[i] + "/";
                    $('<li><a href="#" class="sirv-link" data-link="'+ temp_dir +'">'+ dirs[i] +'</a></li>').appendTo('.breadcrumb');
                }
            }
        }


        function set_current_dir(current_dir){

            $('#filesToUpload').attr('data-current-folder', current_dir);
        }


        function bindEvents(){

                $('.sirv-link').bind('click', getContentFromSirv);
                $('.sirv-image').bind('click', selectImages);
                $('.insert').bind('click', insert);
                $('.create-gallery').bind('click', createGallery);
                $('.clear-selection').bind('click', clearSelection);
        };


        function unbindEvents(){

            $('.insert').unbind('click');
            $('.create-gallery').unbind('click');
            $('.sirv-link').unbind('click');
            $('.sirv-image').unbind('click');
            $('.clear-selection').unbind('click');
        }


        function getContentFromSirv(pth){
            var path;

            if(!pth || typeof(pth) == 'object' || pth == undefined){
                try {
                    path = $(this).attr('data-link');
                    if(path == undefined){
                        path = '';
                    }
                }catch(err) {
                    path = '';
                }
            }else{
                path = pth;
            }

            $.post(ajax_object.ajaxurl, {
                action: 'sirv_get_aws_object',
                path: path
            }).done(function(data){
                    
                //debug
                console.log(data);

                var json_obj = $.parseJSON(data);
                erase_view();
                render_breadcramb(json_obj.current_dir);
                set_current_dir(json_obj.current_dir);
                render_view(json_obj);
                restoreSelections(false);
                bindEvents();
                patchMediaBar();

            }).fail(function(){
                console.log("Ajax failed!");
            });

        }


        function patchMediaBar(){

            if($('#chrome_fix', top.document).length <= 0){
                $('head', top.document).append($('<style id="chrome_fix">.media-frame.hide-toolbar .media-frame-toolbar {display: none;}</style>'));
            }
        }


        //create folder
        $('.create-folder').on('click', function(){
            var newFolderName = window.prompt("Enter folder name:");
            if(newFolderName != null || newFolderName != ''){
                if(!newFolderName){
                    //some code here
                }

                var data = {}

                data['action'] = 'sirv_add_folder';
                data['current_dir'] = $('#filesToUpload').attr('data-current-folder');
                data['new_dir'] = newFolderName;

                $.ajax({
                    url: ajax_object.ajaxurl,
                    type: 'POST',
                    data: data
                }).done(function(response){
                    //show error message
                    console.log(response);

                    getContentFromSirv(data.current_dir);
                });
            }
        });


        //upload images
        $('#filesToUpload').on('change', function(event){

            var current_dir = $('#filesToUpload').attr('data-current-folder');
            var files = event.target.files;
            var data = new FormData();

            data.append('action', 'sirv_upload_files');
            data.append('current_dir', current_dir);

            $.each(files, function(key, value)
            {
                data.append(key, value);
            });

            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                contentType: false,
                processData: false,
                data: data,
                beforeSend: function(){
                    $('.loading-ajax-text').text("Files is uploading...");
                }
            }).done(function(response){
                $('.loading-ajax-text').text("Files is loading...");
                //console.log(response);

                getContentFromSirv(current_dir);
            });
        });


        function selectImages(){

            if($(this).hasClass('selected')){
                $(this).removeClass('selected');
                $(this).closest('li').removeClass('selected');
                $($('img[data-id='+ $(this).attr('data-id')+ ']').closest('li.selected-miniature')).remove();

            } else{
                $(this).addClass('selected');
                $(this).closest('li').addClass('selected');
                $('.selected-miniatures-container').append('<li class="selected-miniature"><img class="selected-miniature-img" data-id="'+ $(this).attr('data-id') +
                    '" data-original="'+ $(this).attr('data-original') +'" data-type="'+ $(this).attr('data-type') +
                    '" data-caption="" src="'+ $(this).attr('data-original') +'?thumbnail=40&image=true"' +' /></li>\n');
            } 

            if ($('.selected-miniature-img').length > 0){
                $('.selection-content').addClass('items-selected');
                $('.count').text($('.selected-miniature-img').length + " selected");
            } else $('.selection-content').removeClass('items-selected');
        };


        function restoreSelections(isAddImages){

            $('.selected').removeClass('selected');

            if(isAddImages){
                $('.selected-miniatures-container').empty();

                if($('.gallery-img').length > 0){
                    var galleryItems = $('.gallery-img');

                    $.each(galleryItems, function(index, value){
                        $('.selected-miniatures-container').append('<li class="selected-miniature"><img class="selected-miniature-img" data-id="'+ $(this).attr('data-id') +
                            '" data-original="'+ $(this).attr('data-original') +'" data-type="'+ $(this).attr('data-type') + '"'+
                            '  data-caption="'+ $(this).parent().siblings('span').children().val() +'"'+
                            '  src="'+ $(this).attr('data-original') +'?thumbnail=40&image=true"' +' /></li>\n');
                    });
                }
            }

            if($('.selected-miniature-img').length > 0){
                $('.count').text($('.selected-miniature-img').length + " selected");
                
                if($('.selection-content').not('.items-selected')){
                    $('.selection-content').addClass('items-selected');
                }

                var selectedImages = $('.selected-miniature-img');

                $.each(selectedImages, function(index, value){
                    $('.sirv-image[data-id="' + $(value).attr('data-id') +'"]').closest('li').addClass('selected');
                });
            }else{
                $('.selection-content').removeClass('items-selected');
            }
        }


        function clearSelection(){

            $(".selected-miniatures-container").empty();
            $('.selected').removeClass('selected');
            $('.selection-content').removeClass('items-selected');
            $('.count').text($('.selected-miniature-img').length + " selected");
        }


        function insert(){

                var html = '';

                if($('#gallery-flag').is(":checked")){
                    if($('.insert').hasClass('edit-gallery')){
        
                        save_shorcode_to_db('sirv_update_sc', window.top.sirv_sc_id);
                        window.parent.switchEditors.go("content", "html");
                        window.parent.switchEditors.go("content", "tmce");

                    }else{

                        var id = save_shorcode_to_db('sirv_save_shortcode_in_db');
                        html = '[sirv-gallery id='+ id +']';
                    }

                }else{

                var links = $('.gallery-img');

                var galleryAlign = $('#gallery-align').val();
                galleryAlign = galleryAlign == '' ? '' : 'class="align' + galleryAlign.replace('sirv-', '')+'"';

                var profile = $('#gallery-profile').val();
                profile = profile == false ? '' : '&profile='+profile;

                $.each(links, function(index, value){
                    html += '<a href="' + $(value).attr('data-original') + '">'+
                    '<img '+ galleryAlign +' src="' + $(value).attr('src') + profile + '" alt="'+ $(this).parent().siblings('span').children().val() +'"></a>';
                });
                }
                //some strange issue with firefox. If return empty string, than shortcode html block will broken. So return string only if not empty.
                if(html != ''){
                    window.parent.send_to_editor(html);
                }
                window.parent.tb_remove();
                window.location.reload();
        }


        function createGallery(){
            $('.selection-content').hide();
            $('.gallery-creation-content').show();

            if($('.selected-miniature-img').length > 0){
                var selectedImages = $('.selected-miniature-img');
                var documentFragment = $(document.createDocumentFragment());
                $.each(selectedImages, function(index, value){
                    var elemBlock = $('<li class="gallery-item"><div><div><a class="delete-image delete-image-icon" href="#" title="Remove"></a>'+
                                                        '<img class="gallery-img" src="'+ $(value).attr('data-original') +'?thumbnail=150&image=true"'+
                                                        ' data-id="'+ $(value).attr('data-id') +'"'+
                                                        'data-order="'+ index +'"'+
                                                        'data-original="'+ $(value).attr('data-original') +
                                                        '" data-type="'+ $(value).attr('data-type') +'" alt=""></div>'+
                                                        '<span><input type="text" placeholder="Text caption.."'+
                                                        ' data-setting="caption" class="image-caption" value="'+ $(value).attr('data-caption') +'" /></span></div></li>\n');
                    documentFragment.append(elemBlock);
                });

                $('.gallery-container').append(documentFragment);


                //bind events
                $('.delete-image').bind('click', removeFromGalleryView);
                $('.select-images').bind('click', function(){selectMoreImages(false)});
            }

        }


        function removeFromGalleryView(){
            $(this).closest('li.gallery-item').remove();
        }

        function clearGalleryView(){
            $('.gallery-container').empty();
        }


        function selectMoreImages(isEditGallery){
            $('.create-gallery>span').text('Add images');
            $('.gallery-creation-content').hide();
            $('.selection-content').show();
            restoreSelections(true);
            if(isEditGallery){
                //getData();
                getContentFromSirv();
            }
            clearGalleryView();
            $('.delete-image').unbind('click');
            $('.select-images').unbind('click');
        }


        function reCalcOrder(){
            $('.gallery-img').each(function(index){
                $(this).attr('data-order', index);
            });
        }

         $( ".gallery-container" ).sortable({
            revert: true,
            cursor: "move",
            scroll: false,
            stop: function( event, ui ) {
                reCalcOrder();
            }
        });


        function getShortcodeData(){
            var shortcode_data = {}
            shortcode_data['width'] = $('#gallery-width').val();
            shortcode_data['thumbs_height'] = $('#gallery-thumbs-height').val();
            shortcode_data['gallery_styles'] = $('#gallery-styles').val();
            shortcode_data['align'] = $('#gallery-align').val();
            shortcode_data['profile'] = $('#gallery-profile').val();
            shortcode_data['use_as_gallery'] = $('#gallery-flag').is(":checked");
            shortcode_data['use_sirv_zoom'] = $('#gallery-zoom-flag').is(":checked");
            shortcode_data['link_image'] = $('#gallery-link-img').is(":checked");
            shortcode_data['show_caption'] = $('#gallery-show-caption').is(":checked");

            var images = []
            $('.gallery-img').each(function(){
                var tmp = {};
                var tmp_url = $(this).attr('data-original'); 
                tmp['url'] = tmp_url.replace(/http(?:s)*:/, '');
                tmp['order'] = $(this).attr('data-order');
                tmp['caption'] = $(this).parent().siblings('span').children().val() ;
                tmp['type'] = $(this).attr('data-type');

                images.push(tmp);
            });

            shortcode_data['images'] = images;

            return shortcode_data;
        }


        function save_shorcode_to_db(action, row_id){

            row_id = row_id || -1;
            var id,
                data = {}

            data['action'] = action;
            data['shortcode_data'] = getShortcodeData();
            if (row_id != -1) {
                data['row_id'] = row_id;
            };

            $.ajax({
                url: ajax_object.ajaxurl,
                type: 'POST',
                async: false,
                data: data
            }).done(function(response){
                id = response;
            });

            return id;
        }


        function editGallery(){
            $('.selection-content').hide();
            $('.gallery-creation-content').show();
            $('.edit-gallery>span').text('Save');
            $('.insert>span').text('Update');

            var id = window.top.sirv_sc_id;

            var data = {}
            data['action'] = 'sirv_get_row_by_id';
            data['row_id'] = id;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                async: false,
                dataType: 'json'
            }).done(function(response){

                $('#gallery-width').val(response['width']);
                $('#gallery-thumbs-height').val(response['thumbs_height']);
                $('#gallery-styles').val(response['gallery_styles']);
                $("#gallery-align").val(response['align']);
                $("#gallery-profile").val(response['profile']);
                $('#gallery-flag').prop('checked', $.parseJSON(response['use_as_gallery']));
                $('#gallery-zoom-flag').prop('checked', $.parseJSON(response['use_sirv_zoom']));
                $('#gallery-link-img').prop('checked', $.parseJSON(response['link_image']));
                $('#gallery-show-caption').prop('checked', $.parseJSON(response['show_caption']));

                checkFlagStates();

                function stripslashes(str) {
                    str=str.replace(/\\'/g,'\'');
                    str=str.replace(/\\"/g,'&quot;');
                    str=str.replace(/\\0/g,'\0');
                    str=str.replace(/\\\\/g,'\\');
                    return str;
                    }
   
                var images = response['images'];
                var documentFragment = $(document.createDocumentFragment());
                for(var i = 0; i < images.length; i++){
                    var caption = stripslashes(images[i]['caption']);

                    var elemBlock = $('<li class="gallery-item"><div><div><a class="delete-image delete-image-icon" href="#" title="Remove"></a>'+
                                                        '<img class="gallery-img" src="https:'+ images[i]['url'] +'?thumbnail=150&image=true"'+
                                                        ' data-id="'+ md5(images[i]['url']) +'"'+
                                                        'data-order="'+ images[i]['order'] +'"'+
                                                        'data-original="https:'+ images[i]['url'] +
                                                        '" data-type="'+ images[i]['type'] +'" alt=""></div>'+
                                                        '<span><input type="text" placeholder="Text caption..."'+
                                                        ' data-setting="caption" class="image-caption" value="'+ caption +'" /></span></div></li>\n');
                    documentFragment.append(elemBlock);
                    
                }

                function checkFlagStates(){
                    if($('#gallery-flag').is(':checked')){
                        $('#gallery-zoom-flag').removeAttr("disabled");
                        $('#gallery-styles').removeAttr("disabled");
                        $('#gallery-thumbs-height').removeAttr("disabled"); 
                        $('#gallery-show-caption').removeAttr("disabled"); 
                    }
                    if(!$('#gallery-zoom-flag').not(':checked')){
                        $('#gallery-link-img').removeAttr("disabled"); 
                    }
                }

                $('.gallery-container').append(documentFragment);

                //bind events
                $('.delete-image').bind('click', removeFromGalleryView);
                $('.select-images').bind('click', function(){selectMoreImages(true)});
                $('.insert').bind('click', insert);
            });
         };

        //show loading bar on ajax events
        $(document).ajaxStart(function () {
            $('.loading-ajax').show();
        }).ajaxStop(function () {
                $('.loading-ajax').hide();
        }).ajaxError(function () {
            $('.loading-ajax').hide();
        });

        $('#gallery-flag').click(function() {
            if($(this).is(":checked")){
                $('#gallery-zoom-flag').removeAttr("disabled");
                $('#gallery-styles').removeAttr("disabled");
                $('#gallery-thumbs-height').removeAttr("disabled");
                $('#gallery-link-img').removeAttr("disabled"); 
                $('#gallery-show-caption').removeAttr("disabled");                 
            }else{
                $('#gallery-zoom-flag').attr('disabled', true)
                $('#gallery-zoom-flag').attr('checked', false);
                $('#gallery-link-img').attr('disabled', true)
                $('#gallery-link-img').attr('checked', false);
                $('#gallery-show-caption').attr('disabled', true)
                $('#gallery-show-caption').attr('checked', false);
                $('#gallery-styles').attr('disabled', true);
                $('#gallery-thumbs-height').attr('disabled', true);
            }
        });

        $('#gallery-zoom-flag').click(function() {
            if($(this).is(":checked")){
                $('#gallery-link-img').attr("disabled", true);
                $('#gallery-link-img').attr("checked", false);                
            }else{
                $('#gallery-link-img').attr("disabled", false);
                $('#gallery-link-img').attr("checked", false);

            }
        });

        // Initialization

        patchMediaBar();
        
        //check if run shortcode edition
        if(window.top.sirv_edit_flag !== undefined && window.top.sirv_edit_flag == true){
            window.top.sirv_edit_flag = false;
            $('.insert').addClass('edit-gallery');
            editGallery();

        }else{
            getContentFromSirv();
        }

    });
});