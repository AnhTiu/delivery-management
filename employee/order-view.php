<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$error = '';

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
    
    // Lấy chi tiết đơn hàng
    $stmt = $conn->prepare("SELECT * FROM ChiTietDonHang WHERE order_id = ?");
    $stmt->execute([$id]);
    $orderDetails = $stmt->fetchAll();
    
    // Tính tổng giá trị đơn hàng
    $total = 0;
    foreach ($orderDetails as $detail) {
        $total += $detail['so_luong'] * $detail['don_gia'];
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
                <div class="card-header bg-gradient-primary text-white rounded-top-4 d-flex align-items-center justify-content-between" style="background: linear-gradient(90deg, #4e73df 0%, #1cc88a 100%);">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-receipt me-2 fs-3"></i>
                        <h2 class="mb-0 fw-bold flex-grow-1">Chi tiết đơn hàng <span class="badge bg-light text-primary ms-2">#<?php echo $id; ?></span></h2>
                    </div>
                    <div class="btn-toolbar">
                        <?php if ($order['trang_thai'] === 'dang_giao'): ?>
                            <a href="/delivery-management/employee/order-update.php?id=<?php echo $id; ?>" class="btn btn-primary btn-lg me-2"><i class="bi bi-pencil me-1"></i>Cập nhật trạng thái</a>
                        <?php endif; ?>
                        <a href="/delivery-management/employee/orders.php" class="btn btn-outline-secondary btn-lg"><i class="bi bi-arrow-left me-1"></i>Quay lại</a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?></div>
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
                                            <th class="text-secondary">Trạng thái:</th>
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
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card shadow-sm rounded-4 border-0 h-100">
                                <div class="card-header bg-light rounded-top-4 d-flex align-items-center">
                                    <i class="bi bi-truck me-2 fs-4 text-success"></i>
                                    <h5 class="mb-0 fw-semibold">Thông tin giao hàng</h5>
                                </div>
                                <div class="card-body">
                                    <?php
                                    $employee = getEmployeeById($conn, $user_id);
                                    $vehicle = null;
                                    if ($employee['phuongtien_id']) {
                                        $vehicle = getVehicleById($conn, $employee['phuongtien_id']);
                                    }
                                    ?>
                                    <table class="table table-borderless mb-0">
                                        <tr>
                                            <th class="text-secondary" style="width: 40%">Nhân viên giao hàng:</th>
                                            <td><?php echo htmlspecialchars($employee['ho_ten']); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-secondary">Số điện thoại:</th>
                                            <td><?php echo htmlspecialchars($employee['sdt'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-secondary">Phương tiện:</th>
                                            <td>
                                                <?php if ($vehicle): ?>
                                                    <?php echo htmlspecialchars($vehicle['loai'] . ' - ' . $vehicle['bien_so']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Chưa có phương tiện</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div> <!-- row g-4 -->
                    <div class="card shadow-sm rounded-4 border-0 mt-4">
                        <div class="card-header bg-light rounded-top-4 d-flex align-items-center">
                            <i class="bi bi-box-seam me-2 fs-4 text-info"></i>
                            <h5 class="mb-0 fw-semibold">Chi tiết sản phẩm</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($orderDetails)): ?>
                                <div class="alert alert-info d-flex align-items-center"><i class="bi bi-info-circle me-2"></i>Đơn hàng chưa có sản phẩm nào.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle rounded-4 overflow-hidden">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Tên sản phẩm</th>
                                                <th>Số lượng</th>
                                                <th>Đơn giá</th>
                                                <th>Thành tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orderDetails as $detail): ?>
                                                <tr>
                                                    <td><?php echo $detail['ct_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($detail['ten_san_pham']); ?></td>
                                                    <td><?php echo $detail['so_luong']; ?></td>
                                                    <td><?php echo formatCurrency($detail['don_gia']); ?></td>
                                                    <td><?php echo formatCurrency($detail['so_luong'] * $detail['don_gia']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="4" class="text-end">Tổng cộng:</th>
                                                <th><?php echo formatCurrency($total); ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div> <!-- card-body -->
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
