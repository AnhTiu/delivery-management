<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Nếu đã đăng nhập, chuyển hướng đến trang phù hợp
if (isLoggedIn()) {
    header("Location: " . (isAdmin() ? "/delivery-management/admin/index.php" : "/delivery-management/employee/index.php"));
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin đăng nhập';
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM NhanVien WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password'])) {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['nhanvien_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'] ?? 'nhanvien';
                
                // Chuyển hướng đến trang phù hợp
                header("Location: " . ($_SESSION['role'] === 'admin' ? "/delivery-management/admin/index.php" : "/delivery-management/employee/index.php"));
                exit();
            } else {
                $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
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
    <title>Đăng nhập - Hệ Thống Quản Lý Vận Chuyển</title>
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
        .login-card {
            max-width: 420px;
            width: 100%;
            border-radius: 1.5rem;
            box-shadow: 0 0 32px rgba(0,0,0,0.12);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(90deg, #4e73df 0%, #1cc88a 100%);
            color: #fff;
            padding: 2rem 1.5rem 1rem 1.5rem;
            text-align: center;
        }
        .login-header .bi {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        .login-body {
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
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-truck"></i>
            <h2 class="fw-bold mb-1">Quản Lý Vận Chuyển</h2>
            <span class="badge bg-light text-primary mb-2"><i class="bi bi-person-circle me-1"></i> Đăng nhập</span>
            <p class="text-white-50 mb-0">Vui lòng đăng nhập để tiếp tục</p>
        </div>
        <div class="login-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" action="" autocomplete="off">
                <div class="mb-4">
                    <label for="username" class="form-label fw-semibold">Tên đăng nhập</label>
                    <div class="input-group input-group-lg"></div>
                        <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control form-control-lg" id="username" name="username" required autofocus>
                    </div>
                </div>
                <div class="mb-4"></div>
                    <label for="password" class="form-label fw-semibold">Mật khẩu</label>
                    <div class="input-group input-group-lg"></div>
                        <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                    </div>
                </div>
                <div class="d-grid gap-2 mb-3"></div>
                    <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập</button>
                </div>
                <div class="text-center"></div>
                    <a href="register.php" class="text-decoration-none text-primary"><i class="bi bi-person-plus me-1"></i>Đăng ký tài khoản mới</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
