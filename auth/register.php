<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Chỉ admin mới có quyền tạo tài khoản
requireAdmin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = $_POST['ho_ten'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'nhanvien';

    if (empty($ho_ten) || empty($email) || empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } else {
        try {
            // Kiểm tra username đã tồn tại chưa
            $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Tên đăng nhập đã tồn tại';
            } else {
                // Kiểm tra email đã tồn tại chưa
                $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Email đã tồn tại';
                } else {
                    // Tạo tài khoản mới
                    $hashedPassword = hashPassword($password);
                    $stmt = $conn->prepare("
                        INSERT INTO NhanVien (ho_ten, sdt, email, username, password, role) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$ho_ten, $sdt, $email, $username, $hashedPassword, $role]);
                    
                    $success = 'Tạo tài khoản thành công';
                }
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản - Hệ Thống Quản Lý Vận Chuyển</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #4e73df 0%, #1cc88a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            max-width: 500px;
            width: 100%;
            border-radius: 1.5rem;
            box-shadow: 0 0 32px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(90deg, #4e73df 0%, #1cc88a 100%);
            color: #fff;
            padding: 2rem 1.5rem 1rem 1.5rem;
            text-align: center;
        }
        .register-header .bi {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .register-body {
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            background: #fff;
        }
        .form-control-lg {
            font-size: 1.1rem;
            border-radius: 0.75rem;
        }
        .btn-lg {
            border-radius: 0.75rem;
            font-size: 1.1rem;
        }
        .alert {
            border-radius: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <i class="bi bi-person-plus"></i>
            <h2 class="fw-bold mb-1">Đăng ký tài khoản</h2>
            <span class="badge bg-light text-primary mb-2"><i class="bi bi-person-gear me-1"></i> Chỉ dành cho Admin</span>
            <p class="text-white-50 mb-0">Tạo tài khoản nhân viên hoặc admin mới</p>
        </div>
        <div class="register-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success d-flex align-items-center mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?></div>
            <?php endif; ?>
            <form method="POST" action="" autocomplete="off">
                <div class="mb-4">
                    <label for="ho_ten" class="form-label fw-semibold">Họ tên <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control form-control-lg" id="ho_ten" name="ho_ten" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="sdt" class="form-label fw-semibold">Số điện thoại</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                        <input type="text" class="form-control form-control-lg" id="sdt" name="sdt">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control form-control-lg" id="email" name="email" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="username" class="form-label fw-semibold">Tên đăng nhập <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                        <input type="text" class="form-control form-control-lg" id="username" name="username" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Mật khẩu <span class="text-danger">*</span></label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="role" class="form-label fw-semibold">Vai trò</label>
                    <select class="form-select form-select-lg" id="role" name="role">
                        <option value="nhanvien">Nhân viên</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="d-flex gap-3 mt-3">
                    <button type="submit" class="btn btn-primary btn-lg flex-grow-1"><i class="bi bi-person-plus me-2"></i>Đăng ký</button>
                    <a href="/delivery-management/admin/employees.php" class="btn btn-outline-secondary btn-lg flex-grow-1"><i class="bi bi-arrow-left me-2"></i>Quay lại</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
