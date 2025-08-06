<?php
// Define um nome de sessão exclusivo para garantir que a sessão correta seja destruída
session_name('VALE_PALETE_SESSID');
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit();