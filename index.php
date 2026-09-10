<?php
session_start();
require_once 'config/db.php';

$is_logged_in = $_SESSION['logged_in'] ?? false;
$user_id = $_SESSION['user_id'] ?? null;
$user_fullname = $_SESSION['fullname'] ?? '';
$user_username = $_SESSION['username'] ?? '';
$user_email = $_SESSION['email'] ?? '';
$user_role = $_SESSION['role'] ?? 'guest';

// --- จัดการ ACTIONS (เพิ่ม/ลบ โปรเจกต์ & คอมเมนต์) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    
    // 1. อาจารย์คอมเมนต์
    if (isset($_POST['action_comment']) && ($user_role === 'teacher' || $user_role === 'admin')) {
        $project_id = $_POST['project_id'];
        $comment_text = trim($_POST['comment_text']);
        if (!empty($comment_text)) {
            $stmt = $pdo->prepare("INSERT INTO comments (project_id, user_id, comment_text) VALUES (:pid, :uid, :txt)");
            $stmt->execute([':pid' => $project_id, ':uid' => $user_id, ':txt' => $comment_text]);
        }
        header("Location: index.php");
        exit;
    }

    // 2. นักศึกษา/Admin เพิ่มโปรเจกต์
    if (isset($_POST['action_add_project'])) {
        $title = trim($_POST['title']);
        $category = $_POST['category'];
        $student_name = ($user_role === 'student') ? $user_fullname : trim($_POST['student_name']);
        $advisor_name = trim($_POST['advisor_name']);
        $description = trim($_POST['description']);
        $github_link = trim($_POST['github_link']);
        $file_path = '';

        if (isset($_FILES['project_file']) && $_FILES['project_file']['error'] === UPLOAD_ERR_OK) {
            $file_name = time() . '_' . $_FILES['project_file']['name'];
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            move_uploaded_file($_FILES['project_file']['tmp_name'], $upload_dir . $file_name);
            $file_path = $file_name;
        }

        $stmt = $pdo->prepare("INSERT INTO projects (title, category, student_name, advisor_name, description, github_link, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $category, $student_name, $advisor_name, $description, $github_link, $file_path]);
        header("Location: index.php");
        exit;
    }
}

// 3. ลบโปรเจกต์ (นักศึกษาลบของตัวเองได้ / Admin ลบได้หมด)
if (isset($_GET['delete_id']) && $is_logged_in) {
    $del_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$del_id]);
    $prj = $stmt->fetch();

    if ($prj && ($user_role === 'admin' || ($user_role === 'student' && $prj['student_name'] === $user_fullname))) {
        if (!empty($prj['file_path']) && file_exists('uploads/' . $prj['file_path'])) {
            unlink('uploads/' . $prj['file_path']);
        }
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$del_id]);
    }
    header("Location: index.php");
    exit;
}

// ดึงรายการโปรเจกต์ทั้งหมด + คอมเมนต์
$stmt = $pdo->query("SELECT * FROM projects ORDER BY id DESC");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คลังข้อมูลโปรเจกต์ SDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8f9fa; }
        .hero-header { background: linear-gradient(135deg, #0A2540 0%, #0066CC 100%); color: white; border-bottom: 4px solid #EAAA00; }
        .card-project { border: none; border-radius: 12px; }
        .role-badge { font-size: 0.75rem; padding: 3px 8px; border-radius: 20px; font-weight: 600; }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <header class="hero-header py-3 mb-4 shadow-sm">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-0"><i class="fa-solid fa-graduation-cap me-2"></i>คลังข้อมูลโปรเจกต์ SDU</h4>
            </div>

            <!-- เมนูขวามือ -->
            <div>
                <?php if ($is_logged_in): ?>
                    <div class="dropdown">
                        <button class="btn btn-light rounded-pill dropdown-toggle fw-bold text-dark px-3" type="button" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-circle-user text-primary me-1 fs-5"></i> <?= htmlspecialchars($user_fullname) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li><h6 class="dropdown-header">สิทธิ์การใช้งาน: <span class="badge bg-primary"><?= strtoupper($user_role) ?></span></h6></li>
                            <li><a class="dropdown-item fw-bold" href="profile.php"><i class="fa-solid fa-id-card me-2 text-primary"></i>ข้อมูลส่วนตัว</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger fw-bold" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>ออกจากระบบ</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="register.php" class="btn btn-outline-light rounded-pill px-3 me-2">สมัครสมาชิก</a>
                    <a href="login.php" class="btn btn-warning rounded-pill fw-bold px-3">เข้าสู่ระบบ</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container mb-5">
        
        <!-- แถบปุ่มสำหรับเพิ่มโปรเจกต์ (นักศึกษา / Admin) -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">รายการโปรเจกต์ทั้งหมด (<?= count($projects) ?>)</h5>
            <?php if ($is_logged_in && ($user_role === 'student' || $user_role === 'admin')): ?>
                <button class="btn btn-success fw-bold rounded-pill" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                    <i class="fa-solid fa-plus me-1"></i> เพิ่มโปรเจกต์ใหม่
                </button>
            <?php endif; ?>
        </div>

        <!-- รายการโปรเจกต์ -->
        <div class="row g-4">
            <?php foreach ($projects as $row): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-project shadow-sm h-100 p-3">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($row['category']) ?></span>
                                <?php if ($is_logged_in && ($user_role === 'admin' || ($user_role === 'student' && $row['student_name'] === $user_fullname))): ?>
                                    <a href="index.php?delete_id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm border-0" onclick="return confirm('ยืนยันลบโปรเจกต์นี้?')"><i class="fa-solid fa-trash"></i></a>
                                <?php endif; ?>
                            </div>
                            <h5 class="fw-bold text-dark"><?= htmlspecialchars($row['title']) ?></h5>
                            <p class="text-muted small flex-grow-1"><?= htmlspecialchars($row['description']) ?></p>
                            
                            <hr class="my-2 opacity-25">
                            <div class="small text-muted mb-2">
                                <div><i class="fa-solid fa-user me-1 text-primary"></i> <strong>ผู้จัดทำ:</strong> <?= htmlspecialchars($row['student_name']) ?></div>
                                <div><i class="fa-solid fa-user-tie me-1 text-success"></i> <strong>ที่ปรึกษา:</strong> <?= htmlspecialchars($row['advisor_name']) ?></div>
                            </div>

                            <!-- ส่วนแสดง/เขียนคอมเมนต์อาจารย์ -->
                            <div class="bg-light p-2 rounded mb-3">
                                <small class="fw-bold text-dark d-block mb-1"><i class="fa-solid fa-comments me-1"></i> ความคิดเห็นอาจารย์:</small>
                                <?php
                                $c_stmt = $pdo->prepare("SELECT c.*, u.fullname FROM comments c JOIN users u ON c.user_id = u.id WHERE c.project_id = ? ORDER BY c.id ASC");
                                $c_stmt->execute([$row['id']]);
                                $comments = $c_stmt->fetchAll();
                                ?>
                                <?php foreach ($comments as $c): ?>
                                    <div class="small border-bottom py-1">
                                        <strong><?= htmlspecialchars($c['fullname']) ?>:</strong> <?= htmlspecialchars($c['comment_text']) ?>
                                    </div>
                                <?php endforeach; ?>

                                <?php if ($is_logged_in && ($user_role === 'teacher' || $user_role === 'admin')): ?>
                                    <form method="POST" class="mt-2">
                                        <input type="hidden" name="project_id" value="<?= $row['id'] ?>">
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="comment_text" class="form-control" placeholder="เขียนเสนอแนะ..." required>
                                            <button type="submit" name="action_comment" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i></button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex gap-2 mt-auto">
                                <?php if (!empty($row['file_path'])): ?>
                                    <a href="uploads/<?= htmlspecialchars($row['file_path']) ?>" download class="btn btn-primary btn-sm w-100"><i class="fa-solid fa-download me-1"></i> ดาวน์โหลดไฟล์</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal: ฟอร์มเพิ่มโปรเจกต์ -->
    <div class="modal fade" id="addProjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">เพิ่มโปรเจกต์ใหม่</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action_add_project" value="1">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ชื่อโปรเจกต์ *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ประเภท *</label>
                            <select name="category" class="form-select">
                                <option value="Project">Project</option>
                                <option value="Mini Project">Mini Project</option>
                            </select>
                        </div>
                        <?php if ($user_role !== 'student'): ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ชื่อผู้จัดทำ *</label>
                            <input type="text" name="student_name" class="form-control" required>
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">อาจารย์ที่ปรึกษา *</label>
                            <input type="text" name="advisor_name" class="form-control" required placeholder="ระบุชื่ออาจารย์ที่ปรึกษา">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">รายละเอียด</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">ลิงก์ GitHub / ผลงาน</label>
                            <input type="url" name="github_link" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">อัปโหลดไฟล์ (PDF, ZIP)</label>
                            <input type="file" name="project_file" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>