<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = '';
$success = '';

// Lấy thông tin nhân viên
$user_id = $_SESSION['user_id'];
$employee = getEmployeeById($conn, $user_id);

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = $_POST['ho_ten'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($ho_ten) || empty($email)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } elseif ($email !== $employee['email']) {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE email = ? AND nhanvien_id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Email đã tồn tại';
        }
    }
    
    // Kiểm tra mật khẩu nếu muốn thay đổi
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $error = 'Vui lòng nhập mật khẩu hiện tại';
        } elseif (!verifyPassword($current_password, $employee['password'])) {
            $error = 'Mật khẩu hiện tại không đúng';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Mật khẩu mới không khớp';
        }
    }
    
    if (empty($error)) {
        try {
            if (!empty($new_password)) {
                // Cập nhật thông tin và mật khẩu
                $hashedPassword = hashPassword($new_password);
                $stmt = $conn->prepare("
                    UPDATE NhanVien 
                    SET ho_ten = ?, sdt = ?, email = ?, password = ?
                    WHERE nhanvien_id = ?
                ");
                $stmt->execute([$ho_ten, $sdt, $email, $hashedPassword, $user_id]);
            } else {
                // Chỉ cập nhật thông tin
                $stmt = $conn->prepare("
                    UPDATE NhanVien 
                    SET ho_ten = ?, sdt = ?, email = ?
                    WHERE nhanvien_id = ?
                ");
                $stmt->execute([$ho_ten, $sdt, $email, $user_id]);
            }
            
            $success = 'Cập nhật thông tin thành công';
            
            // Cập nhật lại thông tin nhân viên
            $employee = getEmployeeById($conn, $user_id);
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-lg rounded-4 border-0">
                <div class="card-header bg-gradient-primary text-white rounded-top-4 d-flex align-items-center" style="background: linear-gradient(90deg, #4e73df 0%, #1cc88a 100%);">
                    <i class="bi bi-person-circle me-2 fs-3"></i>
                    <h2 class="mb-0 fw-bold flex-grow-1">Hồ sơ cá nhân</h2>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success d-flex align-items-center mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?></div>
                    <?php endif; ?>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card shadow-sm rounded-4 border-0 h-100">
                                <div class="card-header bg-light rounded-top-4 d-flex align-items-center">
                                    <i class="bi bi-person-lines-fill me-2 fs-4 text-primary"></i>
                                    <h5 class="mb-0 fw-semibold">Thông tin cá nhân</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" autocomplete="off">
                                        <div class="mb-4">
                                            <label for="ho_ten" class="form-label fw-semibold">Họ tên <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                                <input type="text" class="form-control form-control-lg" id="ho_ten" name="ho_ten" value="<?php echo htmlspecialchars($employee['ho_ten']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-4">
                                            <label for="sdt" class="form-label fw-semibold">Số điện thoại</label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                                <input type="text" class="form-control form-control-lg" id="sdt" name="sdt" value="<?php echo htmlspecialchars($employee['sdt'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="mb-4">
                                            <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                                <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-4">
                                            <label for="username" class="form-label fw-semibold">Tên đăng nhập</label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                                                <input type="text" class="form-control form-control-lg" id="username" value="<?php echo htmlspecialchars($employee['username']); ?>" readonly>
                                            </div>
                                            <div class="form-text">Tên đăng nhập không thể thay đổi</div>
                                        </div>
                                        <hr>
                                        <h5 class="fw-bold mb-3"><i class="bi bi-key me-2"></i>Đổi mật khẩu</h5>
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                                            <input type="password" class="form-control form-control-lg" id="current_password" name="current_password">
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">Mật khẩu mới</label>
                                            <input type="password" class="form-control form-control-lg" id="new_password" name="new_password">
                                        </div>
                                        <div class="mb-4">
                                            <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                                            <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password">
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save me-2"></i>Cập nhật</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-sm rounded-4 border-0 h-100 mb-4">
                                <div class="card-header bg-light rounded-top-4 d-flex align-items-center">
                                    <i class="bi bi-truck me-2 fs-4 text-success"></i>
                                    <h5 class="mb-0 fw-semibold">Thông tin phương tiện</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($employee['phuongtien_id']): ?>
                                        <?php $vehicle = getVehicleById($conn, $employee['phuongtien_id']); ?>
                                        <table class="table table-borderless mb-0">
                                            <tr>
                                                <th class="text-secondary" style="width: 40%">Loại:</th>
                                                <td><?php echo htmlspecialchars($vehicle['loai']); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-secondary">Biển số:</th>
                                                <td><?php echo htmlspecialchars($vehicle['bien_so']); ?></td>
                                            </tr>
                                        </table>
                                        <form method="POST" action="/delivery-management/employee/vehicles.php" class="mt-3">
                                            <input type="hidden" name="action" value="return">
                                            <button type="submit" class="btn btn-warning btn-lg w-100"><i class="bi bi-x-circle me-2"></i>Trả phương tiện</button>
                                        </form>
                                    <?php else: ?>
                                        <div class="alert alert-info d-flex align-items-center"><i class="bi bi-info-circle me-2"></i>Bạn chưa được gán phương tiện.</div>
                                        <a href="/delivery-management/employee/vehicles.php" class="btn btn-primary btn-lg w-100"><i class="bi bi-truck me-2"></i>Chọn phương tiện</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card shadow-sm rounded-4 border-0 mt-4">
                                <div class="card-header bg-light rounded-top-4 d-flex align-items-center">
                                    <i class="bi bi-cash-coin me-2 fs-4 text-info"></i>
                                    <h5 class="mb-0 fw-semibold">Thông tin lương</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $stmt = $conn->prepare("
                                        SELECT * FROM Luong 
                                        WHERE nhanvien_id = ?
                                        ORDER BY thang DESC
                                        LIMIT 3
                                    ");
                                    $stmt->execute([$user_id]);
                                    $salaries = $stmt->fetchAll();
                                    ?>
                                    <?php if (empty($salaries)): ?>
                                        <div class="alert alert-info d-flex align-items-center"><i class="bi bi-info-circle me-2"></i>Chưa có thông tin lương.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle rounded-4 overflow-hidden">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Tháng</th>
                                                        <th>Lương cơ bản</th>
                                                        <th>Lương theo đơn</th>
                                                        <th>Tổng lương</th>
                                                        <th>Ngày trả</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($salaries as $salary): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($salary['thang']); ?></td>
                                                            <td><?php echo formatCurrency($salary['luong_co_ban']); ?></td>
                                                            <td><?php echo formatCurrency($salary['luong_theo_order'] ?? 0); ?></td>
                                                            <td><?php echo formatCurrency($salary['tong_luong']); ?></td>
                                                            <td><?php echo $salary['ngay_tra'] ? formatDate($salary['ngay_tra']) : 'Chưa trả'; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div> <!-- row g-4 -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
