<?php
// FIX: logout.php was referenced by every page but never existed in the project.
session_start();
session_unset();
session_destroy();
header("Location: index.php");
exit();
