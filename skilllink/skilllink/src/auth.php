<?php
// FIX 1: Wrong path - was '../config/database.php', should be '../../database/config/database.php'
// This file lives in src/, so the database config is two levels up then into database/config/.
// However, auth.php is included from public pages (register.php), so we use $conn (mysqli)
// from config.php instead of $pdo (PDO) to keep the codebase consistent.
// FIX 2: Removed require of database.php (PDO) since all pages use config.php (mysqli/$conn).
// FIX 3: session_start() guard — session is already started by the calling page.
// FIX 4: register handler now accepts 'name' field (required by the users table).
// FIX 5: register redirect corrected to the actual login path (relative from public/).
// FIX 6: login redirect for non-employer now goes to student/dashboard.php (not /freelancer/).
// FIX 7: Duplicate connection removed (auth.php was requiring database.php AND config.php).

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* REGISTER */
if (isset($_POST['register'])) {
    // FIX: Added name field — users table requires name (NOT NULL)
    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email']);
    $password = hash_password($_POST['password']);
    $role     = $_POST['role'];

    // FIX: Check for duplicate email before inserting
    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $_SESSION['reg_error'] = "Email already registered. Please login or use a different email.";
        $check->close();
        header("Location: register.php");
        exit();
    }
    $check->close();

    // FIX: Use mysqli ($conn) instead of PDO ($pdo) — consistent with config.php
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $role);
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();

        // Create student record automatically when student registers
        if ($role === 'student') {
            $s = $conn->prepare("INSERT INTO students (user_id) VALUES (?)");
            $s->bind_param("i", $user_id);
            $s->execute();
            $s->close();
            // Welcome notification
            push_notification($conn, $user_id, "Welcome to SkillLink Rwanda! Complete your profile to attract employers.", "student/profile.php");
        } elseif ($role === 'employer') {
            // Welcome notification for employer
            push_notification($conn, $user_id, "Welcome! Your employer account is ready. Start posting jobs.", "employer/dashboard.php");
        }

        // FIX: Correct redirect path (was '/public/login.php', which is an absolute path
        // that only works if the server root is the skilllink folder — use relative path instead)
        header("Location: login.php");
        exit();
    } else {
        $stmt->close();
        $_SESSION['reg_error'] = "Registration failed. Please try again.";
        header("Location: register.php");
        exit();
    }
}

/* LOGIN */
if (isset($_POST['login'])) {
    $email    = sanitize($_POST['email']);
    $password = $_POST['password'];

    // FIX: Use mysqli ($conn) instead of PDO ($pdo)
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user && verify_password($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];

        if ($user['role'] === 'employer') {
            // FIX: Correct path (relative from public/)
            header("Location: employer/dashboard.php");
        } elseif ($user['role'] === 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            // FIX: Was '/public/freelancer/dashboard.php' — corrected to student
            header("Location: student/dashboard.php");
        }
        exit();
    }

    // FIX: Use relative redirect (not absolute /public/login.php)
    header("Location: login.php?error=1");
    exit();
}
