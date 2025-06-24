<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Lấy thông tin nhân viên
$user_id = $_SESSION['user_id'];
$employee = getEmployeeById($conn, $user_id);

// Lấy thông tin đơn hàng của nhân viên
try {
    // Đơn hàng đang giao
    $stmt = $conn->prepare("
        SELECT * FROM DonHang 
        WHERE nhanvien_id = ? AND trang_thai = 'dang_giao'
        ORDER BY ngay_giao
    ");
    $stmt->execute([$user_id]);
    $pendingOrders = $stmt->fetchAll();
    
    // Đơn hàng gần đây
    $stmt = $conn->prepare("
        SELECT * FROM DonHang 
        WHERE nhanvien_id = ? AND trang_thai != 'dang_giao'
        ORDER BY ngay_giao DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recentOrders = $stmt->fetchAll();
    
    // Thông tin phương tiện
    $vehicle = null;
    if ($employee['phuongtien_id']) {
        $vehicle = getVehicleById($conn, $employee['phuongtien_id']);
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
                    <i class="bi bi-speedometer2 me-2 fs-3"></i>
                    <h2 class="mb-0 fw-bold flex-grow-1">Tổng quan nhân viên</h2>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?></div>
                    <?php endif; ?>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card shadow-sm rounded-4 border-0 h-100">
                                <div class="card-header bg-light rounded-top-4 d-flex align-items-center">
                                    <i class="bi bi-person-circle me-2 fs-4 text-primary"></i>
                                    <h5 class="mb-0 fw-semibold">Thông tin cá nhân</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0">
                                            <i class="bi bi-person-circle" style="font-size: 3rem;"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($employee['ho_ten']); ?></h5>
                                            <p class="text-muted mb-0"><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($employee['email']); ?></p>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <a href="/delivery-management/employee/profile.php" class="btn btn-primary btn-lg w-100"><i class="bi bi-pencil me-2"></i>Chỉnh sửa hồ sơ</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-sm rounded-4 border-0 h-100">
                                <div class="card-header bg-light rounded-top-4 d-flex align-items-center">
                                    <i class="bi bi-truck me-2 fs-4 text-success"></i>
                                    <h5 class="mb-0 fw-semibold">Phương tiện</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($vehicle): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <i class="bi bi-truck" style="font-size: 3rem;"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($vehicle['loai']); ?></h5>
                                                <p class="text-muted mb-0">Biển số: <?php echo htmlspecialchars($vehicle['bien_so']); ?></p>
                                            </div>
                                        </div>
                                        <form method="POST" action="/delivery-management/employee/vehicles.php" class="mb-0">
                                            <input type="hidden" name="action" value="return">
                                            <button type="submit" class="btn btn-warning btn-lg w-100"><i class="bi bi-x-circle me-2"></i>Trả phương tiện</button>
                                        </form>
                                    <?php else: ?>
                                        <div class="alert alert-info d-flex align-items-center"><i class="bi bi-info-circle me-2"></i>Bạn chưa được gán phương tiện.</div>
                                        <a href="/delivery-management/employee/vehicles.php" class="btn btn-primary btn-lg w-100"><i class="bi bi-truck me-2"></i>Chọn phương tiện</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div> <!-- row g-4 -->
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-lg rounded-4 border-0">
                <div class="card-header bg-gradient-info text-white rounded-top-4 d-flex align-items-center" style="background: linear-gradient(90deg, #36b9cc 0%, #4e73df 100%);">
                    <i class="bi bi-truck-flatbed me-2 fs-4"></i>
                    <h5 class="mb-0 fw-bold flex-grow-1">Đơn hàng đang giao</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingOrders)): ?>
                        <div class="alert alert-info d-flex align-items-center"><i class="bi bi-info-circle me-2"></i>Không có đơn hàng nào đang giao.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle rounded-4 overflow-hidden">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Khách hàng</th>
                                        <th>Địa chỉ</th>
                                        <th>Ngày giao</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingOrders as $order): ?>
                                        <tr>
                                            <td><span class="badge bg-primary bg-opacity-75 fs-6">#<?php echo $order['order_id']; ?></span></td>
                                            <td><?php echo htmlspecialchars($order['khach_hang']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($order['dia_chi'], 0, 30) . (strlen($order['dia_chi']) > 30 ? '...' : '')); ?></td>
                                            <td><span class="badge bg-info text-dark"><?php echo formatDate($order['ngay_giao']); ?></span></td>
                                            <td>
                                                <a href="/delivery-management/employee/order-view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-info btn-sm me-1"><i class="bi bi-eye"></i></a>
                                                <a href="/delivery-management/employee/order-update.php?id=<?php echo $order['order_id']; ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-lg rounded-4 border-0">
                <div class="card-header bg-gradient-success text-white rounded-top-4 d-flex align-items-center" style="background: linear-gradient(90deg, #1cc88a 0%, #36b9cc 100%);">
                    <i class="bi bi-clock-history me-2 fs-4"></i>
                    <h5 class="mb-0 fw-bold flex-grow-1">Đơn hàng gần đây</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentOrders)): ?>
                        <div class="alert alert-info d-flex align-items-center"><i class="bi bi-info-circle me-2"></i>Không có đơn hàng nào gần đây.</div>
                    <?php else: ?>
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
                                    <?php foreach ($recentOrders as $order): ?>
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
                                                <a href="/delivery-management/employee/order-view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-info btn-sm"><i class="bi bi-eye"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 text-end">
                            <a href="/delivery-management/employee/orders.php" class="btn btn-primary btn-lg"><i class="bi bi-list me-2"></i>Xem tất cả đơn hàng</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
