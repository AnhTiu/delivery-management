<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

// Lấy thống kê tổng quan
try {
    // Tổng số nhân viên
    $stmt = $conn->query("SELECT COUNT(*) as total FROM NhanVien");
    $totalEmployees = $stmt->fetch()['total'];
    
    // Tổng số phương tiện
    $stmt = $conn->query("SELECT COUNT(*) as total FROM PhuongTien");
    $totalVehicles = $stmt->fetch()['total'];
    
    // Tổng số đơn hàng
    $stmt = $conn->query("SELECT COUNT(*) as total FROM DonHang");
    $totalOrders = $stmt->fetch()['total'];
    
    // Đơn hàng theo trạng thái
    $stmt = $conn->query("SELECT trang_thai, COUNT(*) as count FROM DonHang GROUP BY trang_thai");
    $ordersByStatus = $stmt->fetchAll();
    
    // Đơn hàng gần đây
    $stmt = $conn->query("
        SELECT d.*, nv.ho_ten as nhanvien_name 
        FROM DonHang d
        LEFT JOIN NhanVien nv ON d.nhanvien_id = nv.nhanvien_id
        ORDER BY d.order_id DESC LIMIT 5
    ");
    $recentOrders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container py-5" style="background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%); min-height: 100vh;">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold mb-2 text-gradient">Tổng quan hệ thống</h1>
            <p class="lead text-muted mb-4">Thống kê nhanh về nhân viên, phương tiện, đơn hàng</p>
        </div>
    </div>
    <div class="row g-4 mb-4 justify-content-center">
        <div class="col-md-4 col-12 mb-4">
            <div class="card border-0 shadow-sm bg-gradient-primary text-white rounded-4 h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="bi bi-people fs-1 mb-2"></i>
                    <h5 class="card-title">Nhân viên</h5>
                    <h2 class="mb-0 fw-bold"><?php echo $totalEmployees; ?></h2>
                    <a href="/delivery-management/admin/employees.php" class="btn btn-light btn-sm mt-3 rounded-pill">Xem chi tiết <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-12 mb-4">
            <div class="card border-0 shadow-sm bg-gradient-success text-white rounded-4 h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="bi bi-truck fs-1 mb-2"></i>
                    <h5 class="card-title">Phương tiện</h5>
                    <h2 class="mb-0 fw-bold"><?php echo $totalVehicles; ?></h2>
                    <a href="/delivery-management/admin/vehicles.php" class="btn btn-light btn-sm mt-3 rounded-pill">Xem chi tiết <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-12 mb-4">
            <div class="card border-0 shadow-sm bg-gradient-info text-white rounded-4 h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <i class="bi bi-box-seam fs-1 mb-2"></i>
                    <h5 class="card-title">Đơn hàng</h5>
                    <h2 class="mb-0 fw-bold"><?php echo $totalOrders; ?></h2>
                    <a href="/delivery-management/admin/orders.php" class="btn btn-light btn-sm mt-3 rounded-pill">Xem chi tiết <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-gradient-warning text-white rounded-top-4">
                    <h5 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Đơn hàng theo trạng thái</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Trạng thái</th>
                                    <th>Số lượng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordersByStatus as $status): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $statusText = '';
                                            $badgeClass = '';
                                            switch ($status['trang_thai']) {
                                                case 'dang_giao': $statusText = 'Đang giao'; $badgeClass = 'bg-info'; break;
                                                case 'hoan_thanh': $statusText = 'Hoàn thành'; $badgeClass = 'bg-success'; break;
                                                case 'huy': $statusText = 'Hủy'; $badgeClass = 'bg-danger'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?> px-3 py-2"><?php echo $statusText; ?></span>
                                        </td>
                                        <td><?php echo $status['count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-gradient-info text-white rounded-top-4">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Đơn hàng gần đây</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Khách hàng</th>
                                    <th>Nhân viên</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['khach_hang']); ?></td>
                                        <td><?php echo $order['nhanvien_name'] ?? 'Chưa gán'; ?></td>
                                        <td>
                                            <?php 
                                            $statusText = '';
                                            $badgeClass = '';
                                            switch ($order['trang_thai']) {
                                                case 'dang_giao': $statusText = 'Đang giao'; $badgeClass = 'bg-info'; break;
                                                case 'hoan_thanh': $statusText = 'Hoàn thành'; $badgeClass = 'bg-success'; break;
                                                case 'huy': $statusText = 'Hủy'; $badgeClass = 'bg-danger'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?> px-3 py-2"><?php echo $statusText; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<style>
.text-gradient {
    background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-fill-color: transparent;
}
.bg-gradient-primary { background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%) !important; }
.bg-gradient-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important; }
.bg-gradient-info { background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%) !important; }
.bg-gradient-warning { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%) !important; }
.table-gradient {
    background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
}
.rounded-4 { border-radius: 1.5rem !important; }
.rounded-top-4 { border-top-left-radius: 1.5rem !important; border-top-right-radius: 1.5rem !important; }
</style>
