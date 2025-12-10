<?php
session_start();
session_destroy();
header("php/login.php");
exit;
