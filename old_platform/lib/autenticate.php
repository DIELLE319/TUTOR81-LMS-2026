<?php
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$tos_authorized = filter_input(INPUT_POST, 'tos_authorized', FILTER_VALIDATE_BOOLEAN);//isset($_POST['tos_authorized']);
$first_access = false;


function auth($tos_authorized, $username, $password, $first_access) {
    require_once dirname(__FILE__) . '/../config.php';
    require_once 'reset_session.php';
    require_once 'class_user.php';
    require_once 'class_company.php';

    $user_obj = new T81User();
    $company_obj = new T81Company();

    $user_dett = $user_obj->loginWithUsernameAndTaxCode($username, $password); // verifico se ha inserito il codice fiscale
    if ($user_dett) { // la password inserita è il codice fiscale
        $user_dett = $user_obj->loginWithUsernameAndPassword($username, strtoupper(trim($password))); // verifico se il codice fiscale inserito corrisponde alla password (1° accesso)
        if ($user_dett) $first_access = true;
    } else {

        $user_dett = $user_obj->loginWithUsernameAndPassword($username, $password); // la password inserita non è il codice fiscale
    }

    if (!$user_dett) {
        header("location: ../ec-login.php?err=1");
        exit();
    } elseif ($user_dett['role'] == 4) {
        header("location: ../ec-login.php?err=2");
        exit();
    } else {

        $company = $user_obj->getUserCompany($user_dett['id']);
        $plan = $company_obj->getCompanyPlan($company['id']);
        if (!$plan){
            header("location: ../ec-login.php?err=4");
            exit();
        }
        
        if ($company['is_tutor']) {
            $companies = $company_obj->getCompanyByTutorCompany($company['id']);
            $company['is_tutor_with_single_company'] = count($companies) == 1;
            $tutor = $company;
        } else {
            $tutor = $user_obj->getUserCompany($company['owner_user_id']);
        }


        // se l'ente formativo è deleted, $user_dett['tutor_company_id'] è false
        if ($tutor['deleted']) {
            header("location: ../ec-login.php?err=2");
            exit();
        }

        if ($tos_authorized) {
            $user_authorization = $user_obj->getTosUserAuthorization($user_dett['id']);
            if (!$user_authorization)
                $user_obj->addTosUserAuthorization($user_dett['id']);
            else if ($user_authorization['authorized'] != 1)
                $user_obj->setTosUserAuthorizationByUser($user_dett['id'], 1);
        } else {
            header("location: ../ec-login.php?err=3");
            exit();
        }
        @ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        @ini_set('session.cookie_lifetime', 0);
        session_start();
        session_cache_expire(30);
        session_unset();
        session_regenerate_id(true);
        $_SESSION['last_access'] = time();
        $_SESSION['admin_sessionid'] = session_id();
        $_SESSION['admin_logged'] = true;
        $_SESSION['user']['role'] = $user_dett['role'];
        $_SESSION['user']['id'] = $user_dett['id'];
        $_SESSION['user']['name'] = $user_dett['name'];
        $_SESSION['user']['surname'] = $user_dett['surname'];
        $_SESSION['user']['username'] = $user_dett['username'];
        $_SESSION['user']['email'] = $user_dett['email'];
        $_SESSION['user']['tax_code'] = $user_dett['tax_code'];
        $_SESSION['user']['company'] = $company;// azienda dell'utente
        $_SESSION['user']['tutor_id'] = $tutor['id']; // id dell'ente formativo dell'utente
        $_SESSION['user']['plan'] = $plan;// piano abbonamento dell'azienda dell'utente
        $_SESSION['tutor'] = $tutor; // ente formativo selezionato
        $_SESSION['company'] = $company; // id dell'azienda selezionata
        $_SESSION['HTTPS'] = key_exists('HTTPS', $_SERVER) ? $_SERVER['HTTPS'] : false;
        $_SESSION['first_access'] = $first_access;

        // modified like this
        if ($user_dett['role'] == 1) header("location: ../bk-homepage.php?scelta=home");
        else {
            //header("location: ../bk-index.php?scelta=attivaCorso");
            header("location: ../bk-homepage.php?scelta=home");
        }
        exit();
    }
}

auth($tos_authorized, $username, $password, $first_access);
