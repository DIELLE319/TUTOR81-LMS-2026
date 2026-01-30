<?php
if (!empty($_FILES)) {
    // 5 minutes execution time
    @set_time_limit(5 * 60);
    
    // Get parameters
    $chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
    $chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
    $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';
    
    // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
    if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
        if ($_FILES['file']['size'] > 5000000){ // max file dimension in bytes
            die('{"jsonrpc" : "2.0", "error" : {"code": 600, "message": "File troppo grande."}, "id" : "id"}');
        }
        $upload_type = filter_input(INPUT_POST, 'upload_type', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        if ($upload_type === "test_in_the_presence") {
            $learning_project_user_id = filter_input(INPUT_POST, 'learning_project_user_id', FILTER_SANITIZE_NUMBER_INT);
            $dest_path = "../media/test_in_presenza/test_licenza_$learning_project_user_id.pdf";
        } elseif ($upload_type === "classroom_scheduled_brochure") {
            $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
            if ($user_id > 0){
                $dest_path = "../media/user_store/$user_id/classroom/temp/";
                if ( ! is_dir($dest_path)) {
                    mkdir($dest_path, 0755, TRUE);
                } else {
                    array_map("unlink", glob($dest_path . "brochure_*"));
                }
                $subfix = filter_input(INPUT_POST, 'subfix', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
                $dest_path = $dest_path . 'brochure_' . $subfix;
            }
        }
        move_uploaded_file($_FILES['file']['tmp_name'], $dest_path);
    } else{
	die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Errore nel caricamento del file."}, "id" : "id"}');
    }
    die("SUCCESS");
}