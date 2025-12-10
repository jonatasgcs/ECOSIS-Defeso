<?php
session_start();
session_destroy();
header("php/admin_feedback.php");
exit;
