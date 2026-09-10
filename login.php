<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // ล็อกอินแล้วส่งกลับมาหน้าหลักทันที
            header("Location: index.php");
            exit;
        } else {
            $error = 'รหัสประจำตัว/ชื่อผู้ใช้ หรือรหัสผ่านไม่ถูกต้อง';
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - SDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: linear-gradient(135deg, #0A2540 0%, #0066CC 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-login { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); border-top: 5px solid #EAAA00; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 420px;">
        <div class="card card-login p-4">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-dark">เข้าสู่ระบบ</h4>
                <p class="text-muted small">ระบบจัดการคลังข้อมูลโปรเจกต์ สวนดุสิต</p>
            </div>

            <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= $error ?></div><?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">รหัสประจำตัว / Username</label>
                    <input type="text" name="username" class="form-control" required autofocus placeholder="รหัสนักศึกษา / รหัสอาจารย์ / Admin">
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold">รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control" required placeholder="กรอกรหัสผ่าน">
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mb-2" style="background-color: #0066CC; border: none;">
                    เข้าสู่ระบบ
                </button>
                <div class="text-center mt-3">
                    <a href="register.php" class="small text-decoration-none fw-bold" style="color: #0066CC;">สมัครสมาชิก</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>