<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Lấy thông tin phương tiện
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /delivery-management/admin/vehicles.php");
    exit();
}

$id = $_GET['id'];

try {
    $stmt = $conn->prepare("SELECT * FROM PhuongTien WHERE phuongtien_id = ?");
    $stmt->execute([$id]);
    $vehicle = $stmt->fetch();
    
    if (!$vehicle) {
        header("Location: /delivery-management/admin/vehicles.php");
        exit();
    }
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loai = $_POST['loai'] ?? '';
    $bien_so = $_POST['bien_so'] ?? '';
    
    if (empty($loai) || empty($bien_so)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        try {
            // Kiểm tra biển số đã tồn tại chưa (nếu thay đổi)
            if ($bien_so !== $vehicle['bien_so']) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM PhuongTien WHERE bien_so = ? AND phuongtien_id != ?");
                $stmt->execute([$bien_so, $id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Biển số đã tồn tại';
                }
            }
            
            if (empty($error)) {
                // Cập nhật thông tin phương tiện
                $stmt = $conn->prepare("UPDATE PhuongTien SET loai = ?, bien_so = ? WHERE phuongtien_id = ?");
                $stmt->execute([$loai, $bien_so, $id]);
                
                $success = 'Cập nhật thông tin phương tiện thành công';
                
                // Cập nhật lại thông tin phương tiện
                $stmt = $conn->prepare("SELECT * FROM PhuongTien WHERE phuongtien_id = ?");
                $stmt->execute([$id]);
                $vehicle = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card shadow-lg rounded-4 border-0">
                <div class="card-header bg-gradient-primary text-white rounded-top-4 d-flex align-items-center" style="background: linear-gradient(90deg, #4e73df 0%, #1cc88a 100%);">
                    <i class="fas fa-truck-moving fa-lg me-2"></i>
                    <h3 class="mb-0 fw-bold flex-grow-1">Chỉnh sửa phương tiện <span class="badge bg-light text-primary ms-2">#<?php echo $vehicle['phuongtien_id']; ?></span></h3>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div><?php echo $error; ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <div><?php echo $success; ?></div>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="" autocomplete="off">
                        <div class="mb-4">
                            <label for="loai" class="form-label fw-semibold">Loại phương tiện <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light"><i class="fas fa-car"></i></span>
                                <input type="text" class="form-control rounded-end" id="loai" name="loai" value="<?php echo htmlspecialchars($vehicle['loai']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="bien_so" class="form-label fw-semibold">Biển số <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light"><i class="fas fa-id-card"></i></span>
                                <input type="text" class="form-control rounded-end" id="bien_so" name="bien_so" value="<?php echo htmlspecialchars($vehicle['bien_so']); ?>" required>
                            </div>
                        </div>
                        <div class="d-flex gap-3 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg px-4"><i class="fas fa-save me-2"></i>Cập nhật</button>
                            <a href="/delivery-management/admin/vehicles.php" class="btn btn-outline-secondary btn-lg px-4"><i class="fas fa-arrow-left me-2"></i>Quay lại</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
