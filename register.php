<?php
session_start();
require_once 'config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'student';
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $faculty = trim($_POST['faculty'] ?? '');
    $major = trim($_POST['major'] ?? '');
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_code = trim($_POST['admin_code'] ?? '');

    // ตรวจสอบรหัส Admin
    if ($role === 'admin' && $admin_code !== 'SDUADMIN2026') {
        $error = "รหัสยืนยันผู้ดูแลระบบ (Admin Code) ไม่ถูกต้อง!";
    } elseif ($password !== $confirm_password) {
        $error = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน!";
    } else {
        // เช็ก Username หรือ Email ซ้ำ
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "รหัสประจำตัว/Username หรือ Email นี้ถูกใช้งานแล้ว!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (role, fullname, email, faculty, major, username, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$role, $fullname, $email, $faculty, $major, $username, $hashed_password])) {
                $success = "สมัครสมาชิกสำเร็จ! กำลังนำคุณไปหน้าเข้าสู่ระบบ...";
                header("refresh:2;url=login.php");
            } else {
                $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูล!";
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
    <title>ลงทะเบียนเข้าใช้งาน - คลังข้อมูลโปรเจกต์ SDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #0A2540 0%, #0056b3 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px 0;
        }
        .register-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 480px;
            padding: 35px;
            border-top: 5px solid #EAAA00;
        }
        .btn-primary-custom {
            background-color: #0066CC;
            border: none;
            padding: 10px;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-primary-custom:hover { background-color: #004b99; }
    </style>
</head>
<body>

<div class="register-card">
    <div class="text-center mb-4">
        <h4 class="fw-bold text-dark">ลงทะเบียนเข้าใช้งาน</h4>
        <p class="text-muted small mb-0">ระบบจัดสรรคลังข้อมูลโปรเจกต์ มหาวิทยาลัยสวนดุสิต</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success py-2 small"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label small fw-bold">ประเภทผู้ใช้งาน *</label>
            <select name="role" id="roleSelect" class="form-select" onchange="toggleAdminCode()">
                <option value="student">นักศึกษา</option>
                <option value="teacher">อาจารย์</option>
                <option value="admin">ผู้ดูแลระบบ (Admin)</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold">ชื่อ - นามสกุล *</label>
            <input type="text" name="fullname" class="form-control" placeholder="เช่น นายสมชาย ใจดี" required>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold">อีเมล *</label>
            <input type="email" name="email" class="form-control" placeholder="example@mail.com" required>
        </div>

        <!-- ตัวเลือก คณะ -->
        <div class="mb-3">
            <label class="form-label small fw-bold">คณะ *</label>
            <select name="faculty" id="facultySelect" class="form-select" onchange="updateMajors()" required>
                <option value="">-- เลือกคณะ --</option>
                <option value="วิทยาศาสตร์และเทคโนโลยี">คณะวิทยาศาสตร์และเทคโนโลยี</option>
                <option value="วิทยาการจัดการ">คณะวิทยาการจัดการ</option>
                <option value="ครุศาสตร์">คณะครุศาสตร์</option>
                <option value="มนุษยศาสตร์และสังคมศาสตร์">คณะมนุษยศาสตร์และสังคมศาสตร์</option>
                <option value="พยาบาลศาสตร์">คณะพยาบาลศาสตร์</option>
                <option value="โรงเรียนการเรือน">โรงเรียนการเรือน</option>
                <option value="โรงเรียนการท่องเที่ยวและการบริการ">โรงเรียนการท่องเที่ยวและการบริการ</option>
            </select>
        </div>

        <!-- ตัวเลือก สาขาวิชา/หลักสูตร -->
        <div class="mb-3">
            <label class="form-label small fw-bold">สาขาวิชา / หลักสูตร *</label>
            <select name="major" id="majorSelect" class="form-select" required>
                <option value="">-- กรุณาเลือกคณะก่อน --</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold" id="usernameLabel">รหัสนักศึกษา *</label>
            <input type="text" name="username" class="form-control" placeholder="กรอกรหัสประจำตัว" required>
        </div>

        <div class="mb-3" id="adminCodeGroup" style="display: none;">
            <label class="form-label small fw-bold text-danger">รหัสยืนยันผู้ดูแลระบบ (Admin Code) *</label>
            <input type="password" name="admin_code" class="form-control border-danger" placeholder="กรอกรหัสยืนยัน Admin">
        </div>

        <div class="mb-3">
            <label class="form-label small fw-bold">รหัสผ่าน *</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-bold">ยืนยันรหัสผ่าน *</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary-custom text-white w-100 mb-3">ยืนยันการลงทะเบียน</button>

        <div class="text-center small">
            <span class="text-muted">มีบัญชีอยู่แล้ว?</span> <a href="login.php" class="text-decoration-none fw-bold">เข้าสู่ระบบ</a>
        </div>
    </form>
</div>

<script>
// ข้อมูลสาขาตามคณะ
const majorsByFaculty = {
    "วิทยาศาสตร์และเทคโนโลยี": ["วิทยาการคอมพิวเตอร์", "เทคโนโลยีสารสนเทศ", "อนามัยสิ่งแวดล้อม", "เทคโนโลยีประกอบอาหาร"],
    "วิทยาการจัดการ": ["การตลาด", "การเงิน", "การจัดการ", "บัญชี"],
    "ครุศาสตร์": ["การศึกษาปฐมวัย", "ประถมศึกษา", "จิตวิทยาการแนะแนว"],
    "มนุษยศาสตร์และสังคมศาสตร์": ["ภาษาอังกฤษ", "ภาษาไทย", "การออกแบบบรรจุภัณฑ์"],
    "พยาบาลศาสตร์": ["พยาบาลศาสตร์"],
    "โรงเรียนการเรือน": ["เทคโนโลยีการประกอบอาหารและและการประกอบอาหาร", "โภชนาการและการประกอบอาหาร"],
    "โรงเรียนการท่องเที่ยวและการบริการ": ["การท่องเที่ยว", "การโรงแรม", "ธุรกิจการบิน"]
};

function updateMajors() {
    const facultySelect = document.getElementById('facultySelect');
    const majorSelect = document.getElementById('majorSelect');
    const selectedFaculty = facultySelect.value;

    majorSelect.innerHTML = '<option value="">-- เลือกสาขาวิชา --</option>';

    if (selectedFaculty && majorsByFaculty[selectedFaculty]) {
        majorsByFaculty[selectedFaculty].forEach(major => {
            const option = document.createElement('option');
            option.value = major;
            option.textContent = major;
            majorSelect.appendChild(option);
        });
    }
}

function toggleAdminCode() {
    const role = document.getElementById('roleSelect').value;
    const adminGroup = document.getElementById('adminCodeGroup');
    const usernameLabel = document.getElementById('usernameLabel');

    if (role === 'admin') {
        adminGroup.style.display = 'block';
        usernameLabel.innerText = 'Username (ชื่อผู้ใช้) *';
    } else if (role === 'teacher') {
        adminGroup.style.display = 'none';
        usernameLabel.innerText = 'รหัสอาจารย์ *';
    } else {
        adminGroup.style.display = 'none';
        usernameLabel.innerText = 'รหัสนักศึกษา *';
    }
}
</script>

</body>
</html>