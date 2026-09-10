<?php
session_start();
require_once 'config/db.php';

// ตั้งค่ารหัสผ่านพิเศษสำหรับ Admin (เปลี่ยนตรงนี้ได้ตามต้องการ)
define('ADMIN_SECRET_KEY', 'SDUADMIN2026'); 

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'student';
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']); // รหัสนักศึกษา / รหัสอาจารย์ / Username Admin
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_key = trim($_POST['admin_key'] ?? '');

    if (empty($fullname) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
    } elseif ($password !== $confirm_password) {
        $error = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน';
    } elseif ($role === 'admin' && $admin_key !== ADMIN_SECRET_KEY) {
        $error = 'รหัสยืนยันสิทธิ์ Admin ไม่ถูกต้อง';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
        $stmt->execute([':username' => $username, ':email' => $email]);
        
        if ($stmt->fetch()) {
            $error = 'รหัสประจำตัว/ชื่อผู้ใช้ หรืออีเมลนี้ มีในระบบแล้ว';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (fullname, email, username, password, role) VALUES (:fullname, :email, :username, :password, :role)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([
                ':fullname' => $fullname,
                ':email' => $email,
                ':username' => $username,
                ':password' => $hashed_password,
                ':role' => $role
            ])) {
                $success = 'สมัครสมาชิกสำเร็จ! กำลังนำคุณไปหน้าเข้าสู่ระบบ...';
                header("refresh:2;url=login.php");
            } else {
                $error = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - SDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: linear-gradient(135deg, #0A2540 0%, #0066CC 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 30px 0; }
        .card-register { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); border-top: 5px solid #EAAA00; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 480px;">
        <div class="card card-register p-4">
            <div class="text-center mb-4">
                <h4 class="fw-bold text-dark">ลงทะเบียนเข้าใช้งาน</h4>
                <p class="text-muted small">ระบบจัดการคลังข้อมูลโปรเจกต์ มหาวิทยาลัยสวนดุสิต</p>
            </div>

            <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= $error ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success py-2 small"><?= $success ?></div><?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">ประเภทผู้ใช้งาน <span class="text-danger">*</span></label>
                    <select name="role" id="roleSelect" class="form-select" onchange="toggleRoleFields()" required>
                        <option value="student">นักศึกษา</option>
                        <option value="teacher">อาจารย์</option>
                        <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                    </select>
                </div>

                <div class="mb-3" id="adminKeyBox" style="display: none;">
                    <label class="form-label small fw-semibold text-danger">รหัสยืนยันสิทธิ์ Admin <span class="text-danger">*</span></label>
                    <input type="password" name="admin_key" class="form-control border-danger" placeholder="กรอกรหัสยืนยันที่ได้รับจากระบบ">
                    <small class="text-muted" style="font-size: 0.75rem;">* สำหรับผู้ดูแลระบบเท่านั้น (Default: SDUADMIN2026)</small>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">ชื่อ - นามสกุล <span class="text-danger">*</span></label>
                    <input type="text" name="fullname" class="form-control" required placeholder="นายสมชาย สายลุย">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">อีเมล <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required placeholder="example@sdu.ac.th">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold" id="usernameLabel">รหัสนักศึกษา <span class="text-danger">*</span></label>
                    <input type="text" name="username" id="usernameInput" class="form-control" required placeholder="เช่น 6811011940024">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">รหัสผ่าน <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required placeholder="ตั้งรหัสผ่าน">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="กรอกรหัสผ่านอีกครั้ง">
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mb-2" style="background-color: #0066CC; border: none;">
                    ยืนยันการลงทะเบียน
                </button>
                <div class="text-center mt-3">
                    <span class="small text-muted">มีบัญชีอยู่แล้ว?</span> 
                    <a href="login.php" class="small text-decoration-none fw-bold" style="color: #0066CC;">เข้าสู่ระบบ</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleRoleFields() {
            const role = document.getElementById('roleSelect').value;
            const adminKeyBox = document.getElementById('adminKeyBox');
            const usernameLabel = document.getElementById('usernameLabel');
            const usernameInput = document.getElementById('usernameInput');

            if (role === 'admin') {
                adminKeyBox.style.display = 'block';
                usernameLabel.innerHTML = 'ชื่อผู้ใช้งาน (Admin Username) <span class="text-danger">*</span>';
                usernameInput.placeholder = 'เช่น admin_sdu';
            } else if (role === 'teacher') {
                adminKeyBox.style.display = 'none';
                usernameLabel.innerHTML = 'รหัสประจำตัวอาจารย์ <span class="text-danger">*</span>';
                usernameInput.placeholder = 'เช่น T68001';
            } else {
                adminKeyBox.style.display = 'none';
                usernameLabel.innerHTML = 'รหัสนักศึกษา <span class="text-danger">*</span>';
                usernameInput.placeholder = 'เช่น 6811011940024';
            }
        }
    </script>
</body>
</html>