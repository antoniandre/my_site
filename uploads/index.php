<?php
 
// @todo: increase the security of uploaded images.
 
// files storage folder
$dir = '../uploads/';
 
$_FILES['file']['type'] = strtolower($_FILES['file']['type']);
 
if ($_FILES['file']['type'] == 'image/png'
|| $_FILES['file']['type'] == 'image/jpg'
|| $_FILES['file']['type'] == 'image/gif'
|| $_FILES['file']['type'] == 'image/jpeg'
|| $_FILES['file']['type'] == 'image/pjpeg')
{
    // Setting file's mysterious name.
    $filename = md5(date('YmdHis')).'.jpg';
    $file = $dir.$filename;
 
    // Copying.
    move_uploaded_file($_FILES['file']['tmp_name'], $file);
 
    // Displaying file.
    $array = [
        'filelink' => '../../uploads/'.$filename
    ];

    echo stripslashes(json_encode($array));
}
 
?>