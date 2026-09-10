<?php
session_start();
require_once 'config/db.php';

// เช็กว่าได้ล็อกอินหรือยัง
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'student';

// ดึงข้อมูลผู้ใช้งานล่าสุดจากฐานข้อมูล
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// จัดการการส่งคอมเมนต์ (สำหรับอาจารย์)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_comment']) && ($user_role === 'teacher' || $user_role === 'admin')) {
    $project_id = $_POST['project_id'];
    $comment_text = trim($_POST['comment_text']);
    if (!empty($comment_text)) {
        $stmt = $pdo->prepare("INSERT INTO comments (project_id, user_id, comment_text) VALUES (:pid, :uid, :txt)");
        $stmt->execute([':pid' => $project_id, ':uid' => $user_id, ':txt' => $comment_text]);
    }
    header("Location: profile.php");
    exit;
}

// จัดการการลบโปรเจกต์ (สำหรับนักศึกษาลบงานตัวเอง)
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$del_id]);
    $prj = $stmt->fetch();

    if ($prj && ($user_role === 'admin' || ($user_role === 'student' && $prj['student_name'] === $user['fullname']))) {
        if (!empty($prj['file_path']) && file_exists('uploads/' . $prj['file_path'])) {
            unlink('uploads/' . $prj['file_path']);
        }
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$del_id]);
    }
    header("Location: profile.php");
    exit;
}

// ดึงโปรเจกต์ตาม Role
$my_projects = [];
if ($user_role === 'teacher') {
    // อาจารย์: ดึงโปรเจกต์ที่ชื่ออาจารย์ที่ปรึกษาตรงกับชื่ออาจารย์
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE advisor_name LIKE ? ORDER BY id DESC");
    $stmt->execute(['%' . $user['fullname'] . '%']);
    $my_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($user_role === 'student') {
    // นักศึกษา: ดึงโปรเจกต์ที่นักศึกษาเป็นผู้จัดทำ
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE student_name LIKE ? ORDER BY id DESC");
    $stmt->execute(['%' . $user['fullname'] . '%']);
    $my_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Admin: ดึงทั้งหมด
    $stmt = $pdo->query("SELECT * FROM projects ORDER BY id DESC");
    $my_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลส่วนตัว - คลังข้อมูลโปรเจกต์ SDU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f4f6f9; }
        .hero-header { background: linear-gradient(135deg, #0A2540 0%, #0066CC 100%); color: white; border-bottom: 4px solid #EAAA00; }
        .card-profile { border: none; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .avatar-circle { width: 90px; height: 90px; background-color: #0066CC; color: white; font-size: 2.5rem; display: flex; align-items: center; justify-content: center; border-radius: 50%; border: 4px solid #EAAA00; margin: 0 auto 15px; }
        .card-project { border: none; border-radius: 12px; }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <header class="hero-header py-3 mb-4 shadow-sm">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="text-white text-decoration-none d-flex align-items-center gap-2">
                <i class="fa-solid fa-arrow-left fs-5"></i>
                <h4 class="fw-bold mb-0"><i class="fa-solid fa-graduation-cap me-2"></i>คลังข้อมูลโปรเจกต์ SDU</h4>
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="index.php" class="btn btn-outline-light rounded-pill px-3 btn-sm"><i class="fa-solid fa-house me-1"></i> กลับหน้าแรก</a>
                <a href="logout.php" class="btn btn-danger rounded-pill px-3 btn-sm fw-bold"><i class="fa-solid fa-right-from-bracket me-1"></i> ออกจากระบบ</a>
            </div>
        </div>
    </header>

    <div class="container mb-5">
        <div class="row g-4">
            
            <!-- การ์ดแสดงข้อมูลส่วนตัว (ฝั่งซ้าย) -->
            <div class="col-lg-4">
                <div class="card card-profile p-4 text-center">
                    <div class="avatar-circle shadow">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($user['fullname']) ?></h5>
                    <p class="text-muted small mb-3">@<?= htmlspecialchars($user['username']) ?></p>
                    
                    <div class="mb-3">
                        <?php if ($user['role'] === 'teacher'): ?>
                            <span class="badge bg-success px-3 py-2 fs-6 rounded-pill"><i class="fa-solid fa-chalkboard-user me-1"></i> อาจารย์</span>
                        <?php elseif ($user['role'] === 'admin'): ?>
                            <span class="badge bg-danger px-3 py-2 fs-6 rounded-pill"><i class="fa-solid fa-user-shield me-1"></i> ผู้ดูแลระบบ (Admin)</span>
                        <?php else: ?>
                            <span class="badge bg-primary px-3 py-2 fs-6 rounded-pill"><i class="fa-solid fa-user-graduate me-1"></i> นักศึกษา</span>
                        <?php endif; ?>
                    </div>

                    <hr class="my-3 opacity-25">

                    <div class="text-start small">
                        <div class="mb-2">
                            <strong class="text-secondary"><i class="fa-solid fa-envelope me-2 text-primary"></i>อีเมล:</strong>
                            <div class="text-dark fw-semibold mt-1"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                        <div class="mb-2">
                            <strong class="text-secondary"><i class="fa-solid fa-id-card me-2 text-primary"></i>รหัสประจำตัว:</strong>
                            <div class="text-dark fw-semibold mt-1"><?= htmlspecialchars($user['username']) ?></div>
                        </div>
                        <div>
                            <strong class="text-secondary"><i class="fa-solid fa-clock me-2 text-primary"></i>วันที่สมัครสมาชิก:</strong>
                            <div class="text-dark fw-semibold mt-1"><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?> น.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- การ์ดแสดงรายการโปรเจกต์ (ฝั่งขวา) -->
            <div class="col-lg-8">
                <div class="card card-profile p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold text-dark mb-0">
                            <?php if ($user_role === 'teacher'): ?>
                                <i class="fa-solid fa-list-check text-success me-2"></i>โปรเจกต์ที่เป็นที่ปรึกษา (<?= count($my_projects) ?>)
                            <?php else: ?>
                                <i class="fa-solid fa-folder-open text-primary me-2"></i>ผลงานโปรเจกต์ของฉัน (<?= count($my_projects) ?>)
                            <?php endif; ?>
                        </h5>
                    </div>

                    <?php if (empty($my_projects)): ?>
                        <div class="alert alert-light text-center py-5 rounded-3 border">
                            <i class="fa-solid fa-folder-minus fs-1 text-muted mb-3 d-block"></i>
                            <h6 class="text-muted mb-0">
                                <?= $user_role === 'teacher' ? 'ยังไม่มีโปรเจกต์ที่คุณเป็นอาจารย์ที่ปรึกษา' : 'คุณยังไม่มีรายการโปรเจกต์ในระบบ' ?>
                            </h6>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($my_projects as $row): ?>
                                <div class="col-12">
                                    <div class="card card-project border shadow-sm p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-warning text-dark"><?= htmlspecialchars($row['category']) ?></span>
                                            <?php if ($user_role === 'student' || $user_role === 'admin'): ?>
                                                <a href="profile.php?delete_id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm border-0" onclick="return confirm('ยืนยันที่จะลบโปรเจกต์นี้?')">
                                                    <i class="fa-solid fa-trash me-1"></i> ลบโปรเจกต์
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($row['title']) ?></h5>
                                        <p class="text-muted small mb-2"><?= htmlspecialchars($row['description']) ?></p>

                                        <div class="small text-muted mb-2">
                                            <div><i class="fa-solid fa-user me-1 text-primary"></i> <strong>ผู้จัดทำ:</strong> <?= htmlspecialchars($row['student_name']) ?></div>
                                            <div><i class="fa-solid fa-user-tie me-1 text-success"></i> <strong>อาจารย์ที่ปรึกษา:</strong> <?= htmlspecialchars($row['advisor_name']) ?></div>
                                        </div>

                                        <!-- ส่วนแสดงความคิดเห็น/ข้อเสนอแนะ -->
                                        <div class="bg-light p-3 rounded my-2">
                                            <small class="fw-bold text-dark d-block mb-1"><i class="fa-solid fa-comments me-1"></i> ความคิดเห็นอาจารย์ที่ปรึกษา:</small>
                                            <?php
                                            $c_stmt = $pdo->prepare("SELECT c.*, u.fullname FROM comments c JOIN users u ON c.user_id = u.id WHERE c.project_id = ? ORDER BY c.id ASC");
                                            $c_stmt->execute([$row['id']]);
                                            $comments = $c_stmt->fetchAll();
                                            ?>
                                            <?php if (empty($comments)): ?>
                                                <small class="text-muted d-block fst-italic">ยังไม่มีความคิดเห็น</small>
                                            <?php else: ?>
                                                <?php foreach ($comments as $c): ?>
                                                    <div class="small border-bottom py-1">
                                                        <strong><?= htmlspecialchars($c['fullname']) ?>:</strong> <?= htmlspecialchars($c['comment_text']) ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>

                                            <!-- ฟอร์มสำหรับอาจารย์คอมเมนต์ -->
                                            <?php if ($user_role === 'teacher' || $user_role === 'admin'): ?>
                                                <form method="POST" class="mt-2">
                                                    <input type="hidden" name="project_id" value="<?= $row['id'] ?>">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" name="comment_text" class="form-control" placeholder="เขียนข้อแนะนำเพิ่มเติม..." required>
                                                        <button type="submit" name="action_comment" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i>ส่งความคิดเห็น</button>
                                                    </div>
                                                </form>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex gap-2 mt-2">
                                            <?php if (!empty($row['github_link'])): ?>
                                                <a href="<?= htmlspecialchars($row['github_link']) ?>" target="_blank" class="btn btn-outline-dark btn-sm"><i class="fa-brands fa-github me-1"></i> GitHub</a>
                                            <?php endif; ?>
                                            <?php if (!empty($row['file_path'])): ?>
                                                <a href="uploads/<?= htmlspecialchars($row['file_path']) ?>" download class="btn btn-primary btn-sm"><i class="fa-solid fa-download me-1"></i> ดาวน์โหลดเอกสาร</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</body>
</html>