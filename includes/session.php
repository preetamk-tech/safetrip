<?php

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../index.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header("Location: ../index.php");
        exit();
    }
}
?>
