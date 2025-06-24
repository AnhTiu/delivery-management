<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = '';
$success = '';

// Lấy thông tin nhân viên
$user_id = $_SESSION['user_id'];
$employee = getEmployeeById($conn, $user_id);

// Xử lý trả phương tiện
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return') {
    try {
        $stmt = $conn->prepare("UPDATE NhanVien SET phuongtien_id = NULL WHERE nhanvien_id = ?");
        $stmt->execute([$user_id]);
        
        $success = 'Trả phương tiện thành công';
        
        // Cập nhật lại thông tin nhân viên
        $employee = getEmployeeById($conn, $user_id);
    } catch (PDOException $e) {
        $error = 'Lỗi hệ thống: ' . $e->getMessage();
    }
}

// Xử lý chọn phương tiện
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'select') {
    $phuongtien_id = $_POST['phuongtien_id'] ?? '';
    
    if (empty($phuongtien_id)) {
        $error = 'Vui lòng chọn phương tiện';
    } else {
        try {
            // Kiểm tra xem phương tiện đã được gán cho nhân viên khác chưa
            $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE phuongtien_id = ? AND nhanvien_id != ?");
            $stmt->execute([$phuongtien_id, $user_id]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Phương tiện này đã được gán cho nhân viên khác';
            } else {
                // Gán phương tiện cho nhân viên
                $stmt = $conn->prepare("UPDATE NhanVien SET phuongtien_id = ? WHERE nhanvien_id = ?");
                $stmt->execute([$phuongtien_id, $user_id]);
                
                $success = 'Chọn phương tiện thành công';
                
                // Cập nhật lại thông tin nhân viên
                $employee = getEmployeeById($conn, $user_id);
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Lấy danh sách phương tiện
try {
    // Lấy danh sách phương tiện chưa được gán
    $stmt = $conn->query("
        SELECT * FROM PhuongTien 
        WHERE phuongtien_id NOT IN (
            SELECT phuongtien_id FROM NhanVien 
            WHERE phuongtien_id IS NOT NULL
        )
        ORDER BY loai, bien_so
    ");
    $vehicles = $stmt->fetchAll();
    
    // Lấy thông tin phương tiện hiện tại
    $currentVehicle = null;
    if ($employee['phuongtien_id']) {
        $currentVehicle = getVehicleById($conn, $employee['phuongtien_id']);
    }
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-lg rounded-4 border-0">
                <div class="card-header bg-gradient-primary text-white rounded-top-4 d-flex align-items-center" style="background: linear-gradient(90deg, #4e73df 0%, #1cc88a 100%);">
                    <i class="bi bi-truck me-2 fs-3"></i>
                    <h2 class="mb-0 fw-bold flex-grow-1">Quản lý phương tiện</h2>
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
                                    <i class="bi bi-truck me-2 fs-4 text-success"></i>
                                    <h5 class="mb-0 fw-semibold">Phương tiện hiện tại</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($currentVehicle): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <i class="bi bi-truck" style="font-size: 3rem;"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($currentVehicle['loai']); ?></h5>
                                                <p class="text-muted mb-0">Biển số: <?php echo htmlspecialchars($currentVehicle['bien_so']); ?></p>
                                            </div>
                                        </div>
                                        <form method="POST" action="" class="mb-0">
                                            <input type="hidden" name="action" value="return">
                                            <button type="submit" class="btn btn-warning btn-lg w-100" onclick="return confirm('Bạn có chắc chắn muốn trả phương tiện này?')"><i class="bi bi-x-circle me-2"></i>Trả phương tiện</button>
                                        </form>
                                    <?php else: ?>
                                        <div class="alert alert-info d-flex align-items-center"><i class="bi bi-info-circle me-2"></i>Bạn chưa được gán phương tiện.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php if (!$currentVehicle && !empty($vehicles)): ?>
                        <div class="col-md-6">
                            <div class="card shadow-sm rounded-4 border-0 h-100">
                                <div class="card-header bg-light rounded-top-4 d-flex align-items-center">
                                    <i class="bi bi-plus-circle me-2 fs-4 text-primary"></i>
                                    <h5 class="mb-0 fw-semibold">Chọn phương tiện</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" autocomplete="off">
                                        <input type="hidden" name="action" value="select">
                                        <div class="mb-4">
                                            <label for="phuongtien_id" class="form-label fw-semibold">Phương tiện <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-lg" id="phuongtien_id" name="phuongtien_id" required>
                                                <option value="">-- Chọn phương tiện --</option>
                                                <?php foreach ($vehicles as $vehicle): ?>
                                                    <option value="<?php echo $vehicle['phuongtien_id']; ?>"><?php echo htmlspecialchars($vehicle['loai'] . ' - ' . $vehicle['bien_so']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-truck me-2"></i>Chọn phương tiện</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php elseif (!$currentVehicle && empty($vehicles)): ?>
                        <div class="col-md-6">
                            <div class="card shadow-sm rounded-4 border-0 h-100">
                                <div class="card-header bg-light rounded-top-4 d-flex align-items-center">
                                    <i class="bi bi-exclamation-circle me-2 fs-4 text-warning"></i>
                                    <h5 class="mb-0 fw-semibold">Chọn phương tiện</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning d-flex align-items-center"><i class="bi bi-exclamation-triangle me-2"></i>Hiện tại không có phương tiện nào khả dụng. Vui lòng liên hệ quản trị viên.</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div> <!-- row g-4 -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
