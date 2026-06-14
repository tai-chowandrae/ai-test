<?php
session_start();

if (empty($_SESSION['UserId'])) {
    header('Location: /login', true, 302);
    exit;
}

header('Location: /dashboard', true, 302);
exit;
