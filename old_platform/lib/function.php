<?php
/* RZWeb di Roberto Zaniol
 * -----------------------------------------------------------------------------------------
 * This software contains confidential proprietary information belonging 
 * to RZWeb di Roberto Zaniol. No part of this information may be used, reproduced, 
 * or stored without prior written consent of RZWeb di Roberto Zaniol. 
 * -----------------------------------------------------------------------------------------
 * 24-lug-2015
 * File: lib/function.php
 * Project: Piattaforma Tutor81-TutorItalia 
 * 
 * Author: Roberto Zaniol :: info@rzweb.it
 * 
 */
/**
  Chmods files and folders with different permissions.

  This is an all-PHP alternative to using: \n
  <tt>exec("find ".$path." -type f -exec chmod 644 {} \;");</tt> \n
  <tt>exec("find ".$path." -type d -exec chmod 755 {} \;");</tt>

  @author Jeppe Toustrup (tenzer at tenzer dot dk)
  @param $path An either relative or absolute path to a file or directory
  which should be processed.
  @param $filePerm The permissions any found files should get.
  @param $dirPerm The permissions any found folder should get.
  @return Returns TRUE if the path if found and FALSE if not.
  @warning The permission levels has to be entered in octal format, which
  normally means adding a zero ("0") in front of the permission level. \n
  More info at: http://php.net/chmod.
 */
function recurseChmod($path, $filePerm = 0644, $dirPerm = 0755) {
    // Check if the path exists
    if (!file_exists($path)) {
        return false;
    }

    // See whether this is a file
    if (is_file($path)) {
        // Chmod the file with our given filepermissions
        chmod($path, $filePerm);

        // If this is a directory...
    } elseif (is_dir($path)) {
        // Then get an array of the contents
        $foldersAndFiles = scandir($path);

        // Remove "." and ".." from the list
        $entries = array_slice($foldersAndFiles, 2);

        // Parse every result...
        foreach ($entries as $entry) {
            // And call this function again recursively, with the same permissions
            recurseChmod($path . "/" . $entry, $filePerm, $dirPerm);
        }

        // When we are done with the contents of the directory, we chmod the directory itself
        chmod($path, $dirPerm);
    }

    // Everything seemed to work out well, return true
    return true;
}

function recurseCopy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while (false !== ( $file = readdir($dir))) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if (is_dir($src . '/' . $file)) {
                recurse_copy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
    return true;
}

function toAscii($str, $replace = array(), $delimiter = '_') {
    if (!empty($replace)) {
        $str = str_replace((array) $replace, ' ', $str);
    }

    $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
    $clean = strtolower(trim($clean, '-'));
    $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

    return $clean;
}

function generatePassword($length = 14, $strength = 15) { //$strength = somma dei codice degli if per scegliere quali utilizzare
    $vowels = 'aeuy';
    $consonants = 'bdghjmnpqrstvz';
    if ($strength & 1) {
        $consonants .= 'BDGHJLMNPQRSTVWXZ';
    }
    if ($strength & 2) {
        $vowels .= "AEUY";
    }
    if ($strength & 4) {
        $consonants .= '23456789';
    }
    if ($strength & 8) {
        $consonants .= '@#$%';
    }

    $password = '';
    $alt = time() % 2;
    for ($i = 0; $i < $length; $i++) {
        if ($alt == 1) {
            $password .= $consonants[(rand() % strlen($consonants))];
            $alt = 0;
        } else {
            $password .= $vowels[(rand() % strlen($vowels))];
            $alt = 1;
        }
    }
    return $password;
}

/**
 * Prepara l'header per il download
 * 
 * @param type $filename Nome che avrà il file quando scaricato
 * @param type $application_type
 */
function download_send_headers($filename, $application_type = "application/octet-stream") {
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    // force download  
    header("Content-Type: application/force-download");
    header("Content-Type: $application_type");
    header("Content-Type: application/download");

    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
}

/**
 * Restituisce un oggetto file formattato come un csv con la riga delle 
 * intestazioni delle colonne ricavata dalle chiavi dell'array
 * 
 * @param array $array
 * @return type
 */
function array2csv(array &$array)
{
   if (count($array) == 0) {
     return null;
   }
   ob_start();
   $df = fopen("php://output", 'w');
   fputcsv($df, array_keys(reset($array)));
   foreach ($array as $row) {
      fputcsv($df, $row);
   }
   fclose($df);
   return ob_get_clean();
}


// SANITIZZAZIONI

function sanitizeArray($args, $filter){
    if (is_array($args)) {
        $args = array_intersect_key($args, $filter);
        if (!empty($args)){
            array_walk($args, function($value, $key){
                $value = trim($value);
            });
            return array_filter(filter_var_array($args, $filter), function($var){return !is_null($var);}); 
        }
    }
    return false;
}



// specifiche di tutor81

function authorizeByRole($user_id) {
    require_once 'check_session.php';
    $user_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);
    if ($_SESSION['user']['role'] == 1000)
        return true;
    else {
        require_once 'class_user.php';
        $user_obj = new T81User();
        $learner = $user_obj->getDetail($user_id);
        if ($_SESSION['user']['role'] == 2 || $_SESSION['user']['role'] == 8 || $_SESSION['user']['role'] == 16) {
            if ($_SESSION['user']['company']['id'] == $learner['company_id'])
                return true;
            else
                return false;
        } elseif ($_SESSION['user']['role'] == 1 || $_SESSION['user']['role'] == 32) {
            $user_company = $user_obj->getUserCompany($user_id);
            $trainer_organization = $user_obj->getUserCompany($user_company['owner_user_id']);
            if ($_SESSION['user']['company']['id'] == $trainer_organization['id'])
                return true;
            else
                return false;
        } elseif ($_SESSION['user']['id'] == $user_id) {
            return true;
        } else {
            return false;
        }
    }
}

function localize_error($error){
    $error_text = '';
    switch ($error){
        case 'EXISTING_TAX_CODE':
            $error_text = "Codice fiscale già esistente";
            break;
        default:
            $error_text = $error;
            break;
    }
    return $error_text;
}

function validate_date($date) {
      $d = DateTime::createFromFormat('Y-m-d', $date);
      return $d && $d->format('Y-m-d') === $date;
}

function validate_datetime($datetime) {
      $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
      return $d && $d->format('Y-m-d H:i:s') === $datetime;
}

function is_first_day_month($date) {
    if ($date instanceof DateTime && $date->format('j') == 1) {
        return true;
    }
    return false;
}

function is_first_day_odd_month($date) {
    if ($date instanceof DateTime && $date->format('j') == 1 && $date->format('n') % 2 != 0) {
        return true;
    }
    return false;
}