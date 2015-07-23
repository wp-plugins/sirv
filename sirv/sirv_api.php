<?

// require the AWS SDK for PHP library 
require 'aws_sdk/aws-autoloader.php'; 
 
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;


/**
* Create and return new S3 Client
*
* @param string $host usualy http://s3.sirv.com
* @param string $aws_key your AWS Key
* @param string $aws_secret_key your AWS SECRET KEY
*
* @return S3Client object
*/
function get_s3client($host, $aws_key, $aws_secret_key){
    return S3Client::factory(array( 
        'base_url' => $host, 
        'key'      => $aws_key, 
        'secret'   => $aws_secret_key )); 
}
 
 
/**
*  Get information about all files in folder
*
* @param string $bucket Your bucket name
* @param string $folder Folder name where need get information about files. Using format "fodler1/" or "Spin/11/"
* @param S3Client $s3client 
* @param boolean $isIterable if True than return Iterator. By default set to false
*
* @return object or Iterator
*/
function get_object_list($bucket, $folder, $s3client, $isIterable=false){
    $command = $s3client->getCommand('ListObjects', array(
    'Bucket' => $bucket,
    'Prefix'  => $folder,
    ));

    if ($isIterable){
        return $s3client->getIterator($command);
    }else{
        return $command->getResult();    
    }  
}


/**
* Upload files to the Sirv cloud
*
* @param string $bucket Your bucket name
* @param string $folder Folder name where files will be uploaded. Using format "fodler1/" or "Spin/11/"
* @param S3Client $s3client 
* @param Array[] $array_paths_to_files Array of file's paths that need upload on server. like "/var/www/images/image1.jpg"
*/
function upload_files($bucket, $folder, $s3client, $array_paths_to_files){

    foreach ($array_paths_to_files as $file_path) {
        $commands[] = $s3client->getCommand('PutObject', array(
            'Bucket' => $bucket,
            'Key'    => $folder.basename($file_path),
            'SourceFile' => $file_path
        ));
    }
    try{
        $s3client->execute($commands);

        //or in loop
        /* foreach ($commands as $command) {
            $result = $command->getResult();
            // Do something with result
        }*/

    }catch(S3Exception $e){
    echo $e->getMessage() . "<br>";
    }
}


//use Guzzle\Http\EntityBody;
/**
* Upload files to the Sirv cloud through web interface
*
* @param string $bucket Your bucket name
* @param s3client $s3client 
* @param $filename name of file like image1.jpg or with prefix like /folder1/folder2/image1.jpg
* @param $file streem with binary data
*/
function upload_web_file($bucket, $s3client, $filename, $file){
    try {
/*        $result = $s3client->putObject(array(
            'Bucket' => $bucket,
            'Key'    => $folder . $filename,
            'Body'   => fopen($file, 'rb'),
            'ACL'    => 'public-read',
            'ContentLength' => $file_size
        ));*/

    $result = $s3client -> upload($bucket, $filename, fopen($file, 'rb'), 'public-read');

    } catch(Exception $e) {
        echo $e->getMessage() . "<br>";
    }
}

 
/**
* Delete files from the Sirv cloud
*
* @param string $bucket Your bucket name
* @param string $folder Folder name where files should be deleted. Using format "fodler1/" or "Spin/11/"
* @param S3Client $s3client
* @param Array[] $array_filenames Array of files that should be deleted from the Sirv cloud. Format is "image1.jpg"
*/
function delete_files($bucket, $folder, $s3client, $array_filenames){

    if(count($array_filenames)>0){
        foreach ($array_filenames as $filename) {
            $objects_to_delete[] = array('Key' => $folder.$filename);
        }
        $result = $s3client->deleteObjects(array(
            'Bucket' => $bucket,
            'Objects' => $objects_to_delete,
        ));

        print_r($result);
    }
}


/**
* Delete all files in folder and can delete files in subfolders
*
* @param string $bucket Your bucket name
* @param string $folder Folder name where files should be deleted. Using format "fodler1/" or "Spin/11/"
* @param S3Client $s3client
* @param boolean $isRecursive True if need delete files in subfolders. By default isRecursive = false
*/
function delete_folder($bucket, $folder, $s3client, $isRecursive=false){
    $filenames = $s3client->listObjects(array(
            'Bucket' => $bucket,
            'Prefix' => $folder
        ))->getPath('Contents/*/Key');

    delete_files($bucket, $folder, $s3client, $filenames);

    if ($isRecursive){
        $prefixes = $s3client->listObjects(array(
            'Bucket' => $bucket,
            'Prefix' => $folder
        ))->getPath('CommonPrefixes/*/Prefix');

        if (count($prefixes) > 0){
            foreach ($prefixes as $prefix) {
                delete_folder($bucket, $folder.$prefix, $s3client, $isRecursive=true);
            }
        }
    
    }
} 
 

/**
* Check if file exists on server or not
* 
* @param string $bucket Your bucket name
* @param string $folder Folder name where need check file. Using format like "fodler1/" or "Spin/11/"
* @param S3Client $s3client
* @param string $filenane File name that need to check on server. Using format like "image.png"
*
* @return boolean true if file exists or false if not
*/
function isFileExists($bucket, $folder, $s3client, $filenane){
    $iter_object = get_object_list($bucket, $folder, $s3client, true);

    foreach ($iter_object as $object) {
        if ($object['Key'] === $filenane){
            return true;
        } 
    }
    return false;
}
 

/**
* Create folder on the Sirv cloud
*
* @param string $bucket Your bucket name
* @param string $folder Folder name that need create. For example if need create folder in other folder: "other/New_folder/"
*
* @return string message if folder already exists or nothing
*/
function create_folder($bucket, $folder, $s3client){
    try{
        $result = $s3client->putObject(array( 
        'Bucket'       => $bucket,
        'Key'          => $folder,
        'Body'       => "",
        ));
        //echo $result;
    }catch(S3Exception $e){
    echo $e->getMessage() . "<br>";
    }
}

?>