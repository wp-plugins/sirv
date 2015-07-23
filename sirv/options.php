<script>
    jQuery(function($){
        $(document).ready(function(){
            $('.test-connect').on('click', function(){
                var host = $('input[name=AWS_HOST]').val(),
                    bucket = $('input[name=AWS_BUCKET]').val(),
                    key = $('input[name=AWS_KEY]').val(),
                    secret_key = $('input[name=AWS_SECRET_KEY]').val();
                    
                $('.show-result').text("Testing connection...");

                $.post(ajaxurl, {
                action: 'sirv_check_connection',
                host: host,
                bucket: bucket,
                key: key,
                secret_key: secret_key
            }).done(function(data){
                    
                //debug
                //console.log(data);
                $('.show-result').text(data);

            }).fail(function(){
                console.log("Ajax failed!");
            });
            })
        });
    });    
</script>

<div class="wrap">
    <style type="text/css">
        .optiontable.form-table input[type="text"] {max-width: 100%; min-width: 360px;}
        .show-result{font-weight: 600; line-height: 1.3; text-align: left;}
    </style>
    <h2>Sirv settings</h2>
    <form action="options.php" method="post" id="">
        <?php wp_nonce_field('update-options'); ?>
        
        <!--<p><a target="_blank" href="https://my.sirv.com/#/signup">Create Sirv account</a> or <a target="_blank" href="https://my.sirv.com/#/signin">Log in Sirv</a></p>-->
        <p>Enter your Sirv S3 settings, then you can embed images into your WordPress pages and posts.<br />
            <ol>
                <li><a target="_blank" href="https://my.sirv.com/#/signup">Create Sirv account</a> or <a target="_blank" href="https://my.sirv.com/#/signin">login to your Sirv account</a></li>
                <li>Copy and paste <a href="https://my.sirv.com/#/account/">your S3 settings</a> into the fields below.</li>
            </ol>
        </p>

        <table class="optiontable form-table">
        
        	<tr><th><label>S3 Endpoint: </label></th><td><input type="text" name="AWS_HOST" value="<?php echo get_option('AWS_HOST'); ?>"></td></tr>
        	<tr><th><label>S3 Bucket: </label></th><td><input type="text" name="AWS_BUCKET" value="<?php echo get_option('AWS_BUCKET'); ?>"></td></tr>
        	<tr><th><label>S3 Key: </label></th><td><input type="text" name="AWS_KEY" value="<?php echo get_option('AWS_KEY'); ?>"></td></tr>
        	<tr><th><label>S3 Secret: </label></th><td><input type="text" name="AWS_SECRET_KEY" value="<?php echo get_option('AWS_SECRET_KEY'); ?>"></td></tr>
        	
        	<tr><th></th><td colspan="2">
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="AWS_KEY,AWS_SECRET_KEY,AWS_HOST, AWS_BUCKET" />

            <input type="submit" name="submit" class="button-primary" value="<?php _e('Save Settings') ?>" />
            <input type="button" class="button-primary test-connect" value="Test connection">
            <div style="display: inline; margin-left: 5px;" class="show-result"></div>        	
        	</td></tr>

        </table>

        <p><h2>Upload and embed images</h2></p>
        <ol>
        <li>Click the Add Media button on a page or post:</li><br />

        <img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/add_media.png" ?>" /><br /><br />

        <li>Click on Sirv and either upload new images or embed existing images (displayed via <a href="https://my.sirv.com/#/browse">your Sirv account</a>).</li><br />

        <img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/sirv-tab.png" ?>" /><br /><br />

        <li>Choose the images you wish to embed:</li><br />

        <img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/choose_images.jpg" ?>" /><br /><br />

        <li>Choose your options and click the Insert into page:</li><br />

        <img src="<?php echo plugin_dir_url( __FILE__ ) . "assets/insert_into_page.jpg" ?>" /><br /><br />

        </ol>

        <p>In need of inspiration? <a href='https://sirv.com/demos/'>Check out these demos</a></p>

        <p>Contact <a href="https://sirv.com/contact/">Sirv support</a> if you need help.</p> 

    </form>
</div>
