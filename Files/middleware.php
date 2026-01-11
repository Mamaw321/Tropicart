<?php

function requireLogin(){
    if (empty($_SESSION['uid'])) {
        header('Location: index.php'); exit;
    }
}

function requireVerified(){
    requireLogin();
    if (empty($_SESSION['verified'])){
        header('Location: verify.php'); exit;
    }
}