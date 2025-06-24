<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = '';
$success = '';

// Lấy thông tin nhân viên
$user_id = $_SESSION['user_id'];

// Lấy danh sách đơn hàng của nhân viên
try {
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    $query = "
        SELECT * FROM DonHang
        WHERE nhanvien_id = ?
    ";
    $params = [$user_id];
    
    if (!empty($status)) {
        $query .= " AND trang_thai = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY ngay_giao DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy danh sách đơn hàng: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-lg rounded-4 border-0">
                <div class="card-header bg-gradient-primary text-white rounded-top-4 d-flex align-items-center" style="background: linear-gradient(90deg, #4e73df 0%, #1cc88a 100%);">
                    <i class="bi bi-list-task me-2 fs-3"></i>
                    <h2 class="mb-0 fw-bold flex-grow-1">Đơn hàng của tôi</h2>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success d-flex align-items-center mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?></div>
                    <?php endif; ?>
                    <form method="GET" action="" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <select class="form-select form-select-lg" name="status" onchange="this.form.submit()">
                                <option value="" <?php echo !isset($_GET['status']) || $_GET['status'] === '' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                                <option value="dang_giao" <?php echo isset($_GET['status']) && $_GET['status'] === 'dang_giao' ? 'selected' : ''; ?>>Đang giao</option>
                                <option value="hoan_thanh" <?php echo isset($_GET['status']) && $_GET['status'] === 'hoan_thanh' ? 'selected' : ''; ?>>Hoàn thành</option>
                                <option value="huy" <?php echo isset($_GET['status']) && $_GET['status'] === 'huy' ? 'selected' : ''; ?>>Hủy</option>
                            </select>
                        </div>
                        <?php if (isset($_GET['status']) && $_GET['status'] !== ''): ?>
                            <div class="col-md-2">
                                <a href="/delivery-management/employee/orders.php" class="btn btn-outline-secondary btn-lg w-100"><i class="bi bi-x-circle me-1"></i>Xóa bộ lọc</a>
                            </div>
                        <?php endif; ?>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle rounded-4 overflow-hidden">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Khách hàng</th>
                                    <th>Địa chỉ</th>
                                    <th>Ngày giao</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Không có đơn hàng nào</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><span class="badge bg-primary bg-opacity-75 fs-6">#<?php echo $order['order_id']; ?></span></td>
                                            <td><?php echo htmlspecialchars($order['khach_hang']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($order['dia_chi'], 0, 30) . (strlen($order['dia_chi']) > 30 ? '...' : '')); ?></td>
                                            <td><span class="badge bg-info text-dark"><?php echo formatDate($order['ngay_giao']); ?></span></td>
                                            <td>
                                                <?php 
                                                $statusText = '';
                                                $badgeClass = '';
                                                switch ($order['trang_thai']) {
                                                    case 'dang_giao':
                                                        $statusText = 'Đang giao';
                                                        $badgeClass = 'bg-warning text-dark';
                                                        break;
                                                    case 'hoan_thanh':
                                                        $statusText = 'Hoàn thành';
                                                        $badgeClass = 'bg-success';
                                                        break;
                                                    case 'huy':
                                                        $statusText = 'Hủy';
                                                        $badgeClass = 'bg-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?> fs-6"><?php echo $statusText; ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/delivery-management/employee/order-view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-info"><i class="bi bi-eye"></i> Xem</a>
                                                    <?php if ($order['trang_thai'] === 'dang_giao'): ?>
                                                        <a href="/delivery-management/employee/order-update.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Cập nhật</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
