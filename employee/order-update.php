<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = '';
$success = '';

// Lấy thông tin nhân viên
$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /employee/orders.php");
    exit();
}

$id = $_GET['id'];

try {
    // Kiểm tra xem đơn hàng có thuộc về nhân viên này không
    $stmt = $conn->prepare("SELECT COUNT(*) FROM DonHang WHERE order_id = ? AND nhanvien_id = ?");
    $stmt->execute([$id, $user_id]);
    if ($stmt->fetchColumn() == 0) {
        header("Location: /employee/orders.php");
        exit();
    }
    
    // Lấy thông tin đơn hàng
    $stmt = $conn->prepare("SELECT * FROM DonHang WHERE order_id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    
    // Kiểm tra xem đơn hàng có đang ở trạng thái "đang giao" không
    if ($order['trang_thai'] !== 'dang_giao') {
        header("Location: /employee/order-view.php?id=$id");
        exit();
    }
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trang_thai = $_POST['trang_thai'] ?? '';
    
    if (empty($trang_thai)) {
        $error = 'Vui lòng chọn trạng thái';
    } else {
        try {
            // Cập nhật trạng thái đơn hàng
            $stmt = $conn->prepare("UPDATE DonHang SET trang_thai = ? WHERE order_id = ?");
            $stmt->execute([$trang_thai, $id]);
            
            $success = 'Cập nhật trạng thái đơn hàng thành công';
            
            // Cập nhật lại thông tin đơn hàng
            $stmt = $conn->prepare("SELECT * FROM DonHang WHERE order_id = ?");
            $stmt->execute([$id]);
            $order = $stmt->fetch();
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
        <div class="col-lg-10">
            <div class="card shadow-lg rounded-4 border-0 mb-4">
                <div class="card-header bg-gradient-primary text-white rounded-top-4 d-flex align-items-center" style="background: linear-gradient(90deg, #4e73df 0%, #1cc88a 100%);">
                    <i class="bi bi-pencil-square me-2 fs-3"></i>
                    <h2 class="mb-0 fw-bold flex-grow-1">Cập nhật trạng thái đơn hàng <span class="badge bg-light text-primary ms-2">#<?php echo $id; ?></span></h2>
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
                                    <i class="bi bi-info-circle me-2 fs-4 text-primary"></i>
                                    <h5 class="mb-0 fw-semibold">Thông tin đơn hàng</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless mb-0">
                                        <tr>
                                            <th class="text-secondary" style="width: 40%">Mã đơn hàng:</th>
                                            <td><span class="badge bg-primary bg-opacity-75 fs-6">#<?php echo $order['order_id']; ?></span></td>
                                        </tr>
                                        <tr>
                                            <th class="text-secondary">Khách hàng:</th>
                                            <td><?php echo htmlspecialchars($order['khach_hang']); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-secondary">Địa chỉ:</th>
                                            <td><?php echo htmlspecialchars($order['dia_chi']); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-secondary">Ngày giao:</th>
                                            <td><span class="badge bg-info text-dark"><?php echo formatDate($order['ngay_giao']); ?></span></td>
                                        </tr>
                                        <tr>
                                            <th class="text-secondary">Trạng thái hiện tại:</th>
                                            <td><span class="badge bg-warning text-dark">Đang giao</span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-sm rounded-4 border-0 h-100">
                                <div class="card-header bg-light rounded-top-4 d-flex align-items-center">
                                    <i class="bi bi-arrow-repeat me-2 fs-4 text-success"></i>
                                    <h5 class="mb-0 fw-semibold">Cập nhật trạng thái</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" autocomplete="off">
                                        <div class="mb-4">
                                            <label for="trang_thai" class="form-label fw-semibold">Trạng thái mới <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-lg" id="trang_thai" name="trang_thai" required>
                                                <option value="">-- Chọn trạng thái --</option>
                                                <option value="hoan_thanh">Hoàn thành</option>
                                                <option value="huy">Hủy</option>
                                            </select>
                                        </div>
                                        <div class="d-flex gap-3 mt-3">
                                            <button type="submit" class="btn btn-primary btn-lg flex-grow-1"><i class="bi bi-save me-2"></i>Cập nhật</button>
                                            <a href="/delivery-management/employee/order-view.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary btn-lg flex-grow-1"><i class="bi bi-arrow-left me-2"></i>Quay lại</a>
                                        </div>
                                    </form>
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
