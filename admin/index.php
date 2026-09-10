<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../login.php");
    exit;
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($action === 'delete' && !empty($id)) {
    $stmt = $pdo->prepare("SELECT file_path FROM projects WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($project && !empty($project['file_path']) && file_exists("../uploads/" . $project['file_path'])) {
        unlink("../uploads/" . $project['file_path']);
    }

    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $category = $_POST['category'];
    $student_name = trim($_POST['student_name']);
    $advisor_name = trim($_POST['advisor_name']);
    $description = trim($_POST['description']);
    $github_link = trim($_POST['github_link']);
    $edit_id = $_POST['edit_id'] ?? '';
    $file_path = $_POST['old_file'] ?? '';

    if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['project_file']['tmp_name'];
        $file_name = time() . '_' . $_FILES['project_file']['name'];
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
            $file_path = $file_name;
        }
    }

    if (!empty($edit_id)) {
        $sql = "UPDATE projects SET title = :title, category = :category, student_name = :student_name, advisor_name = :advisor_name, description = :description, github_link = :github_link, file_path = :file_path WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $title, ':category' => $category, ':student_name' => $student_name,
            ':advisor_name' => $advisor_name, ':description' => $description,
            ':github_link' => $github_link, ':file_path' => $file_path, ':id' => $edit_id
        ]);
    } else {
        $sql = "INSERT INTO projects (title, category, student_name, advisor_name, description, github_link, file_path) VALUES (:title, :category, :student_name, :advisor_name, :description, :github_link, :file_path)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $title, ':category' => $category, ':student_name' => $student_name,
            ':advisor_name' => $advisor_name, ':description' => $description, ':github_link' => $github_link, ':file_path' => $file_path
        ]);
    }
    header("Location: index.php");
    exit;
}

$edit_item = null;
if ($action === 'edit' && !empty($id)) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $edit_item = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->query("SELECT * FROM projects ORDER BY id DESC");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการหลังบ้าน - SDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f7fa; }
        .navbar-admin { background-color: #0A2540; border-bottom: 3px solid #EAAA00; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark navbar-admin py-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fa-solid fa-sliders me-2"></i>ระบบจัดการข้อมูล Admin</a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-light small">ผู้ใช้: <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
                <a href="../index.php" class="btn btn-outline-light btn-sm" target="_blank"><i class="fa-solid fa-globe me-1"></i> ดูหน้าเว็บหลัก</a>
                <a href="../logout.php" class="btn btn-danger btn-sm"><i class="fa-solid fa-right-from-bracket me-1"></i> ออกจากระบบ</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="card border-0 shadow-sm p-4 rounded-3">
                    <h5 class="fw-bold mb-3 text-primary"><?= $edit_item ? 'แก้ไขข้อมูลโปรเจกต์' : 'เพิ่มโปรเจกต์ใหม่' ?></h5>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="edit_id" value="<?= $edit_item['id'] ?? '' ?>">
                        <input type="hidden" name="old_file" value="<?= $edit_item['file_path'] ?? '' ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">ชื่อโปรเจกต์ <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($edit_item['title'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">ประเภท <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="Project" <?= ($edit_item['category'] ?? '') === 'Project' ? 'selected' : '' ?>>Project</option>
                                <option value="Mini Project" <?= ($edit_item['category'] ?? '') === 'Mini Project' ? 'selected' : '' ?>>Mini Project</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">ชื่อผู้จัดทำ <span class="text-danger">*</span></label>
                            <input type="text" name="student_name" class="form-control" value="<?= htmlspecialchars($edit_item['student_name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">อาจารย์ที่ปรึกษา <span class="text-danger">*</span></label>
                            <input type="text" name="advisor_name" class="form-control" value="<?= htmlspecialchars($edit_item['advisor_name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">รายละเอียด</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($edit_item['description'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">ลิงก์ GitHub / ผลงาน</label>
                            <input type="url" name="github_link" class="form-control" value="<?= htmlspecialchars($edit_item['github_link'] ?? '') ?>">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold">อัปโหลดไฟล์โปรเจกต์ (PDF, ZIP, ฯลฯ)</label>
                            <input type="file" name="project_file" class="form-control">
                            <?php if (!empty($edit_item['file_path'])): ?>
                                <small class="text-muted d-block mt-1">ไฟล์ปัจจุบัน: <?= htmlspecialchars($edit_item['file_path']) ?></small>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold"><?= $edit_item ? 'บันทึกการแก้ไข' : 'บันทึกข้อมูล' ?></button>
                        <?php if ($edit_item): ?>
                            <a href="index.php" class="btn btn-light w-100 mt-2 text-muted">ยกเลิก</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card border-0 shadow-sm p-4 rounded-3">
                    <h5 class="fw-bold mb-3">รายการโปรเจกต์ทั้งหมด (<?= count($projects) ?>)</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ชื่อโปรเจกต์</th>
                                    <th>ประเภท</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $row): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($row['title']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($row['student_name']) ?></small>
                                        </td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['category']) ?></span></td>
                                        <td>
                                            <a href="index.php?action=edit&id=<?= $row['id'] ?>" class="btn btn-warning btn-sm me-1"><i class="fa-solid fa-pen"></i></a>
                                            <a href="index.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ยืนยันการลบข้อมูลนี้?')"><i class="fa-solid fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>