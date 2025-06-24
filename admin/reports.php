<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';

// Lấy thống kê đơn hàng theo nhân viên
try {
    $stmt = $conn->query("
        SELECT nv.nhanvien_id, nv.ho_ten,
            COUNT(dh.order_id) as total_orders,
            SUM(CASE WHEN dh.trang_thai = 'hoan_thanh' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN dh.trang_thai = 'dang_giao' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN dh.trang_thai = 'huy' THEN 1 ELSE 0 END) as canceled_orders
        FROM NhanVien nv
        LEFT JOIN DonHang dh ON nv.nhanvien_id = dh.nhanvien_id
        GROUP BY nv.nhanvien_id, nv.ho_ten
        ORDER BY total_orders DESC
    ");
    $ordersByEmployee = $stmt->fetchAll();
    
    // Lấy thống kê doanh thu theo tháng
    // Lấy thống kê doanh thu theo tháng
    $stmt = $conn->query("
        SELECT 
            DATE_FORMAT(dh.ngay_giao, '%Y-%m') as month,
            COUNT(DISTINCT dh.order_id) as total_orders,
            SUM(CASE WHEN dh.trang_thai = 'hoan_thanh' THEN 1 ELSE 0 END) as completed_orders,
            COALESCE(
                SUM(
                    CASE 
                        WHEN dh.trang_thai = 'hoan_thanh' THEN (
                            SELECT COALESCE(SUM(ct2.so_luong * ct2.don_gia), 0)
                            FROM ChiTietDonHang ct2 
                            WHERE ct2.order_id = dh.order_id
                        )
                        ELSE 0 
                    END
                ), 0
            ) as revenue
        FROM DonHang dh
        GROUP BY DATE_FORMAT(dh.ngay_giao, '%Y-%m')
        ORDER BY month DESC
    ");
    $revenueByMonth = $stmt->fetchAll();
    
    // Lấy thống kê trạng thái đơn hàng
    $stmt = $conn->query("
        SELECT 
            trang_thai,
            COUNT(*) as count
        FROM DonHang
        GROUP BY trang_thai
    ");
    $ordersByStatus = $stmt->fetchAll();
    
    // Lấy thống kê phương tiện được sử dụng nhiều nhất
    $stmt = $conn->query("
        SELECT 
            pt.phuongtien_id,
            pt.loai,
            pt.bien_so,
            COUNT(dh.order_id) as order_count
        FROM PhuongTien pt
        JOIN NhanVien nv ON pt.phuongtien_id = nv.phuongtien_id
        JOIN DonHang dh ON nv.nhanvien_id = dh.nhanvien_id
        GROUP BY pt.phuongtien_id, pt.loai, pt.bien_so
        ORDER BY order_count DESC
        LIMIT 5
    ");
    $topVehicles = $stmt->fetchAll();
    
    // Lấy tổng tiền các đơn theo từng nhân viên
    $stmt = $conn->query("
        SELECT 
            nv.nhanvien_id,
            nv.ho_ten,
            SUM(ct.so_luong * ct.don_gia) as total_value
        FROM NhanVien nv
        JOIN DonHang dh ON nv.nhanvien_id = dh.nhanvien_id
        JOIN ChiTietDonHang ct ON dh.order_id = ct.order_id
        WHERE dh.trang_thai = 'hoan_thanh'
        GROUP BY nv.nhanvien_id, nv.ho_ten
        ORDER BY total_value DESC
    ");
    $totalValueByEmployee = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy dữ liệu báo cáo: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container py-5" style="background: linear-gradient(135deg, #f8ffae 0%, #43c6ac 100%); min-height: 100vh;">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold mb-2 text-gradient">Báo cáo & Thống kê</h1>
            <p class="lead text-muted mb-4">Tổng hợp số liệu vận chuyển, doanh thu, nhân viên, phương tiện</p>
        </div>
    </div>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center w-75 mx-auto mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-white border-0 rounded-top-4">
                    <h5 class="card-title mb-0 text-gradient">Đơn hàng theo nhân viên</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-gradient text-white">
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Tổng đơn</th>
                                    <th>Hoàn thành</th>
                                    <th>Đang giao</th>
                                    <th>Hủy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ordersByEmployee)): ?>
                                    <tr><td colspan="5" class="text-center">Không có dữ liệu</td></tr>
                                <?php else: ?>
                                    <?php foreach ($ordersByEmployee as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['ho_ten']); ?></td>
                                            <td><?php echo $item['total_orders']; ?></td>
                                            <td><?php echo $item['completed_orders']; ?></td>
                                            <td><?php echo $item['pending_orders']; ?></td>
                                            <td><?php echo $item['canceled_orders']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-white border-0 rounded-top-4">
                    <h5 class="card-title mb-0 text-gradient">Doanh thu theo tháng</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-gradient text-white">
                                <tr>
                                    <th>Tháng</th>
                                    <th>Tổng đơn</th>
                                    <th>Hoàn thành</th>
                                    <th>Doanh thu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($revenueByMonth)): ?>
                                    <tr><td colspan="4" class="text-center">Không có dữ liệu</td></tr>
                                <?php else: ?>
                                    <?php foreach ($revenueByMonth as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['month']); ?></td>
                                            <td><?php echo $item['total_orders']; ?></td>
                                            <td><?php echo $item['completed_orders']; ?></td>
                                            <td><?php echo formatCurrency($item['revenue'] ?? 0); ?></td>
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
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-white border-0 rounded-top-4">
                    <h5 class="card-title mb-0 text-gradient">Trạng thái đơn hàng</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-gradient text-white">
                                <tr>
                                    <th>Trạng thái</th>
                                    <th>Số lượng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($ordersByStatus)): ?>
                                    <tr><td colspan="2" class="text-center">Không có dữ liệu</td></tr>
                                <?php else: ?>
                                    <?php foreach ($ordersByStatus as $item): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                $statusText = '';
                                                $badgeClass = '';
                                                switch ($item['trang_thai']) {
                                                    case 'dang_giao': $statusText = 'Đang giao'; $badgeClass = 'bg-info'; break;
                                                    case 'hoan_thanh': $statusText = 'Hoàn thành'; $badgeClass = 'bg-success'; break;
                                                    case 'huy': $statusText = 'Hủy'; $badgeClass = 'bg-danger'; break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?> px-3 py-2"><?php echo $statusText; ?></span>
                                            </td>
                                            <td><?php echo $item['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-white border-0 rounded-top-4">
                    <h5 class="card-title mb-0 text-gradient">Phương tiện được sử dụng nhiều nhất</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-gradient text-white">
                                <tr>
                                    <th>Phương tiện</th>
                                    <th>Biển số</th>
                                    <th>Số đơn hàng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topVehicles)): ?>
                                    <tr><td colspan="3" class="text-center">Không có dữ liệu</td></tr>
                                <?php else: ?>
                                    <?php foreach ($topVehicles as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['loai']); ?></td>
                                            <td><?php echo htmlspecialchars($item['bien_so']); ?></td>
                                            <td><?php echo $item['order_count']; ?></td>
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
    <div class="row mb-4">
        <div class="col-lg-12 mb-4">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-white border-0 rounded-top-4">
                    <h5 class="card-title mb-0 text-gradient">Tổng giá trị đơn hàng theo nhân viên</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead class="table-gradient text-white">
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Tổng giá trị đơn hàng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($totalValueByEmployee)): ?>
                                    <tr><td colspan="2" class="text-center">Không có dữ liệu</td></tr>
                                <?php else: ?>
                                    <?php foreach ($totalValueByEmployee as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['ho_ten']); ?></td>
                                            <td><?php echo formatCurrency($item['total_value']); ?></td>
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
<style>
.text-gradient {
    background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-fill-color: transparent;
}
.table-gradient {
    background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
}
.rounded-4 { border-radius: 1.5rem !important; }
.rounded-top-4 { border-top-left-radius: 1.5rem !important; border-top-right-radius: 1.5rem !important; }
</style>
