<?php
session_start();
require_once '../config/db.php';

// ตรวจสอบว่าเป็น Admin หรือไม่
if (!isset($_SESSION['logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// จัดการลบผู้ใช้งาน
if (isset($_GET['delete_user'])) {
    $del_user_id = $_GET['delete_user'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$del_user_id]);
    header("Location: index.php");
    exit;
}

// จัดการลบโปรเจกต์
if (isset($_GET['delete_project'])) {
    $del_prj_id = $_GET['delete_project'];
    $stmt = $pdo->prepare("SELECT file_path FROM projects WHERE id = ?");
    $stmt->execute([$del_prj_id]);
    $prj = $stmt->fetch();
    if ($prj && !empty($prj['file_path']) && file_exists('../uploads/' . $prj['file_path'])) {
        unlink('../uploads/' . $prj['file_path']);
    }
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$del_prj_id]);
    header("Location: index.php");
    exit;
}

// ดึงข้อมูลผู้ใช้ทั้งหมด
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลโปรเจกต์ทั้งหมด
$projects = $pdo->query("SELECT * FROM projects ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - คลังข้อมูลโปรเจกต์ SDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .admin-header { background: #0A2540; color: white; border-bottom: 4px solid #dc3545; }
    </style>
</head>
<body>

    <header class="admin-header py-3 mb-4 shadow">
        <div class="container d-flex justify-content-between align-items-center">
            <h4 class="fw-bold mb-0"><i class="fa-solid fa-user-shield me-2 text-danger"></i>ระบบจัดการหลังบ้าน (Admin Dashboard)</h4>
            <div>
                <a href="../index.php" class="btn btn-outline-light rounded-pill btn-sm me-2"><i class="fa-solid fa-house me-1"></i> ไปหน้าหลัก</a>
                <a href="../logout.php" class="btn btn-danger rounded-pill btn-sm fw-bold"><i class="fa-solid fa-right-from-bracket me-1"></i> ออกจากระบบ</a>
            </div>
        </div>
    </header>

    <div class="container mb-5">
        
        <!-- รายชื่อผู้ใช้งานในระบบ -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-users me-2 text-primary"></i>จัดการสมาชิกในระบบ (<?= count($users) ?> คน)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>Username / รหัส</th>
                                <th>อีเมล</th>
                                <th>สิทธิ์ (Role)</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= $u['id'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($u['fullname']) ?></td>
                                    <td><?= htmlspecialchars($u['username']) ?></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <?php if ($u['role'] === 'admin'): ?>
                                            <span class="badge bg-danger">ADMIN</span>
                                        <?php elseif ($u['role'] === 'teacher'): ?>
                                            <span class="badge bg-success">TEACHER</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">STUDENT</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($u['role'] !== 'admin'): ?>
                                            <a href="index.php?delete_user=<?= $u['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('ยืนยันลบผู้ใช้งานนี้?')"><i class="fa-solid fa-trash"></i> ลบ</a>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- รายการโปรเจกต์ทั้งหมด -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-folder-open me-2 text-warning"></i>จัดการโปรเจกต์ทั้งหมด (<?= count($projects) ?> รายการ)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>ชื่อโปรเจกต์</th>
                                <th>ประเภท</th>
                                <th>ผู้จัดทำ</th>
                                <th>อาจารย์ที่ปรึกษา</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $p): ?>
                                <tr>
                                    <td><?= $p['id'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($p['title']) ?></td>
                                    <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($p['category']) ?></span></td>
                                    <td><?= htmlspecialchars($p['student_name']) ?></td>
                                    <td><?= htmlspecialchars($p['advisor_name']) ?></td>
                                    <td class="text-center">
                                        <a href="index.php?delete_project=<?= $p['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('ยืนยันลบโปรเจกต์นี้?')"><i class="fa-solid fa-trash"></i> ลบ</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</body>
</html>