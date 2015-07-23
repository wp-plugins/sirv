<?php 

function check_empty_options(){
    $host = 'http://' . get_option('AWS_HOST');
    $bucket = get_option('AWS_BUCKET');
    $key = get_option('AWS_KEY');
    $secret_key = get_option('AWS_SECRET_KEY');

    if(empty($host) || empty($bucket) || empty($key) || empty($secret_key)){
        echo '<div class="sirv-warning"><a href="admin.php?page=sirv/sirv.php">Enter your Sirv S3 settings</a> to view your images on Sirv.</div>';
        return;
    }

}

function get_profiles(){
    require_once 'sirv_api.php';

    $host = 'http://' . get_option('AWS_HOST');
    $bucket = get_option('AWS_BUCKET');
    $key = get_option('AWS_KEY');
    $secret_key = get_option('AWS_SECRET_KEY');   

    $s3client = get_s3client($host, $key, $secret_key);
    $obj = get_object_list($bucket, 'Profiles/', $s3client);

    foreach ($obj->get("Contents") as $key => $value) {
        $tmp = str_replace('Profiles/', '', $value['Key']);
        if (!empty($tmp)){
            $tmp = basename($tmp, '.profile');
            echo "<option value='{$tmp}'>{$tmp}</option>";
        }
    }
}

check_empty_options();

?>
    <div class="loading-ajax">
        <span class="sirv-loading-icon"></span>
    </div>
    <div class="content">
        <div class="selection-content">
            <div class="sirv-items-container">
                <div class="nav">
                    <div class="tools-panel">
                        <div class="btn btn-success create-folder">
                            <span>New folder...</span>
                        </div>
                        <div class="btn btn-success fileinput-button">
                            <span>Upload images...</span>
                            <input id="filesToUpload" data-current-folder="/" type="file" name="files[]" multiple="">
                        </div>
                    </div>
                    <ol class="breadcrumb">
                    </ol>
                    <div class="clearfix"></div>
                </div>
                <div class="sirv-dirs">
                    <ul class="items-list" id="dirs"></ul>
                </div>
                <div class="sirv-spins">
                    <ul class="items-list" id="spins"></ul>
                </div>
                <div class="sirv-images">
                    <ul class="items-list" id="images"></ul>
                </div>
            </div>
            <div class="selected-images">
                <div class="selection-info">
                    <span class="count"> 1 selected</span>
                    <a class="clear-selection" href="#">Clear</a>
                </div>
                <div class="selection-view">
                    <ul class="selected-miniatures-container">
                        <!-- <li class="selected-miniature">
                            <img "selected-miniature-img" src="" />
                        </li> -->
                    </ul>
                </div>
                <div class="create-gallery">
                	<div class="btn btn-success">
                    	<span>Continue...</span>
                	</div>
            	</div>
            </div>
        </div>
        <div class="gallery-creation-content">
            <div class="gallery-images">
                <ul class="gallery-container">
                </ul>
            </div>
            <div class="sidebar-right">
                <div class="gallery-options">                    
                    <h1>Gallery otions</h1>
                    
                    <label><input id="gallery-flag" type="checkbox">Insert as gallery</label>
                    <label><input id="gallery-zoom-flag" type="checkbox" disabled />Use Sirv Zoom</label>
                    <label><input id="gallery-link-img" type="checkbox" disabled />Link to big image</label>
                    <label><input id="gallery-show-caption" type="checkbox" disabled />Show caption</label>

                    <label>Width:
                    <input id="gallery-width" type="text" size="5" value="auto" /></label>

                    <label>Align:
                    <select id="gallery-align">
                        <option value="">-</option>
                        <option value="sirv-left">Left</option>
                        <option value="sirv-right">Right</option>
                        <option value="sirv-center">Center</option>
                    </select></label>
                    
                    <label>Profile:
                    <select id="gallery-profile">
                        <option value="">-</option>
                        <?php
                            get_profiles();
                        ?>
                    </select></label>
                    <a href="https://my.sirv.com/#/browse/Profiles" class="create-profile" target="_blank">Create profile [↗]</a>
                    
                    <label>Thumbnail height:
                    <input id="gallery-thumbs-height" type="text" value="50" size="5" disabled /></label>
                    
                    <!--
                    <label>Extra styles:
                    <input id="gallery-styles" type="text" size="5" disabled /></label>
                    -->
                    <div class="gallery-controls">
                    <div class="btn btn-success select-images">
                        <span>Back</span>
                    </div>
                    <div class="btn btn-success insert">
                        <span>Insert into page</span>
                    </div>
                </div>
                    
                </div>
                
            </div>
        </div>
    </div>
<?php

?>