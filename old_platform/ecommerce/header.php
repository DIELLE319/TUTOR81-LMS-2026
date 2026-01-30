<?php 
require_once BASE_LIBRARY_PATH . 'set_session.php';
require_once BASE_LIBRARY_PATH . 'get_session.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Piattaforma Tutor81</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="robots" content="noindex, nofollow">

    <meta name="description" content="Sign in">
    <meta name="author" content="RZWeb">
    <link rel="icon" href="/favicon.ico"/>
    <link rel="icon" href="/img/favicon.png" type="image/png"/>

    <!-- Fav and touch icons -->
    <link rel="shortcut icon" href="/img/favicon.png">
    <link rel="apple-touch-icon" href="/img/icon57.png" sizes="57x57">
    <link rel="apple-touch-icon" href="/img/icon72.png" sizes="72x72">
    <link rel="apple-touch-icon" href="/img/icon76.png" sizes="76x76">
    <link rel="apple-touch-icon" href="/img/icon114.png" sizes="114x114">
    <link rel="apple-touch-icon" href="/img/icon120.png" sizes="120x120">
    <link rel="apple-touch-icon" href="/img/icon144.png" sizes="144x144">
    <link rel="apple-touch-icon" href="/img/icon152.png" sizes="152x152">
    <link rel="apple-touch-icon" href="/img/icon180.png" sizes="180x180">

    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/plugins.css">
    <link rel="stylesheet" href="/css/isLoading.css"/>
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/themes.css">
    
    <style>
        /*
        =================================================================
        THEME
        =================================================================
        */

        /* Default Color Theme specific colors */
        .themed-color {
            color: <?= $color_light ?>;
        }

        .themed-border {
            border-color: <?= $color_light ?>;
        }

        .themed-background {
            background-color: <?= $color_light ?>;
        }

        .themed-color-dark {
            color: <?= $color_dark ?>;
        }

        .themed-border-dark {
            border-color: <?= $color_dark ?>;
        }

        .themed-background-dark {
            background-color: <?= $color_dark ?>;
        }
        
    </style>


    <script src="/js/jquery-1.11.1.min.js"></script>
<!--        <script src="js/vendor/jquery.min.js"></script>-->
<!--    <script src="/js/jquery.validate.min.js"></script>-->
    <!--    <script src="js/bootstrap.min.js"></script>-->
    <script src="/js/vendor/bootstrap.min.js"></script>
    <script src="/js/jquery.isloading.min.js"></script>
    <script src="/js/plugins.js"></script>
    <script src="/js/app.min.js"></script>

    <script src="/js/tutorgest.js"></script>

    <!--[if lt IE 9]>
    <script src="/js/html5shiv.js"></script>
    <script src="/js/placeholders.jquery.min.js"></script>
    <![endif]-->

    <!-- Modernizr (browser feature detection library) -->
    <script src="/js/vendor/modernizr.min.js"></script>
</head>
