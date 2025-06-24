<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

// Lấy thông tin nhân viên
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /delivery-management/admin/employees.php");
    exit();
}

$id = $_GET['id'];

try {
    // Lấy thông tin nhân viên
    $stmt = $conn->prepare("
        SELECT nv.*, pt.loai, pt.bien_so 
        FROM NhanVien nv
        LEFT JOIN PhuongTien pt ON nv.phuongtien_id = pt.phuongtien_id
        WHERE nv.nhanvien_id = ?
    ");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        header("Location: /delivery-management/admin/employees.php");
        exit();
    }
    
    // Lấy danh sách đơn hàng của nhân viên
    $stmt = $conn->prepare("
        SELECT * FROM DonHang 
        WHERE nhanvien_id = ?
        ORDER BY ngay_giao DESC
    ");
    $stmt->execute([$id]);
    $orders = $stmt->fetchAll();
    
    // Lấy thông tin lương
    $stmt = $conn->prepare("
        SELECT * FROM Luong 
        WHERE nhanvien_id = ?
        ORDER BY thang DESC
    ");
    $stmt->execute([$id]);
    $salaries = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container py-5" style="background: linear-gradient(135deg, #f8ffae 0%, #43c6ac 100%); min-height: 100vh;">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold mb-2 text-gradient">Thông tin nhân viên</h1>
            <p class="lead text-muted mb-4">Chi tiết nhân viên và các thông tin liên quan</p>
            <div class="d-flex justify-content-center gap-2 mb-2">
                <a href="/delivery-management/admin/employee-edit.php?id=<?php echo $id; ?>" class="btn btn-gradient-primary rounded-pill px-4"><i class="bi bi-pencil"></i> Chỉnh sửa</a>
                <a href="/delivery-management/admin/employees.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left"></i> Quay lại</a>
            </div>
        </div>
    </div>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger text-center w-75 mx-auto mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <div class="row mb-4 justify-content-center">
        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-white border-0 rounded-top-4">
                    <h5 class="card-title mb-0 text-gradient">Thông tin cá nhân</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr><th>Họ tên:</th><td><?php echo htmlspecialchars($employee['ho_ten']); ?></td></tr>
                        <tr><th>Email:</th><td><?php echo htmlspecialchars($employee['email']); ?></td></tr>
                        <tr><th>SĐT:</th><td><?php echo htmlspecialchars($employee['sdt']); ?></td></tr>
                        <tr><th>Username:</th><td><?php echo htmlspecialchars($employee['username']); ?></td></tr>
                        <tr><th>Vai trò:</th><td><?php echo $employee['role'] === 'admin' ? '<span class="badge bg-danger">Admin</span>' : '<span class="badge bg-info">Nhân viên</span>'; ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-white border-0 rounded-top-4">
                    <h5 class="card-title mb-0 text-gradient">Thông tin phương tiện</h5>
                </div>
                <div class="card-body">
                    <?php if ($employee['phuongtien_id']): ?>
                        <span class="badge bg-success px-3 py-2">
                            <?php echo htmlspecialchars($employee['loai'] . ' - ' . $employee['bien_so']); ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary px-3 py-2">Chưa gán phương tiện</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg rounded-4 mb-4">
                <div class="card-header bg-white border-0 rounded-top-4">
                    <h5 class="card-title mb-0 text-gradient">Đơn hàng đã giao</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <div class="text-center text-muted">Không có đơn hàng nào</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-gradient text-white">
                                    <tr>
                                        <th>ID</th>
                                        <th>Khách hàng</th>
                                        <th>Địa chỉ</th>
                                        <th>Ngày giao</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['order_id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['khach_hang']); ?></td>
                                            <td><?php echo htmlspecialchars($order['dia_chi']); ?></td>
                                            <td><?php echo htmlspecialchars($order['ngay_giao']); ?></td>
                                            <td>
                                                <?php
                                                $status = $order['trang_thai'];
                                                $badge = 'bg-secondary';
                                                if ($status === 'hoan_thanh') $badge = 'bg-success';
                                                elseif ($status === 'dang_giao') $badge = 'bg-info';
                                                elseif ($status === 'huy') $badge = 'bg-danger';
                                                ?>
                                                <span class="badge <?php echo $badge; ?> text-capitalize px-3 py-2"><?php echo str_replace('_', ' ', $status); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-white border-0 rounded-top-4">
                    <h5 class="card-title mb-0 text-gradient">Lịch sử lương</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($salaries)): ?>
                        <div class="text-center text-muted">Không có dữ liệu lương</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-gradient text-white">
                                    <tr>
                                        <th>Tháng</th>
                                        <th>Lương cơ bản</th>
                                        <th>Lương theo đơn</th>
                                        <th>Ngày trả</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salaries as $salary): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($salary['thang']); ?></td>
                                            <td><?php echo number_format($salary['luong_co_ban']); ?> đ</td>
                                            <td><?php echo number_format($salary['luong_theo_order']); ?> đ</td>
                                            <td><?php echo htmlspecialchars($salary['ngay_tra']); ?></td>
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
.btn-gradient-primary {
    background: linear-gradient(90deg, #6a11cb 0%, #2575fc 100%);
    color: #fff;
    border: none;
}
.btn-gradient-primary:hover { background: linear-gradient(90deg, #2575fc 0%, #6a11cb 100%); color: #fff; }
.table-gradient {
    background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
}
.rounded-4 { border-radius: 1.5rem !important; }
.rounded-top-4 { border-top-left-radius: 1.5rem !important; border-top-right-radius: 1.5rem !important; }
</style>
