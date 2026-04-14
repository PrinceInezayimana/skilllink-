<?php
// FIX 1: Removed duplicate `include '../src/config.php'`
// FIX 2: session_start() MUST come before require of auth.php
// FIX 3: auth.php is included AFTER config.php and functions.php
session_start();
require_once '../src/config.php';
require_once '../src/functions.php';
require_once '../src/auth.php'; // handles registration logic (POST only)
// After auth.php processes the POST it redirects; if we reach here
// with a GET it means someone navigated to /register.php directly —
// send them to login.php which now hosts the register form.
header("Location: login.php");
exit();
