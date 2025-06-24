<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Xử lý xóa đơn hàng
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM DonHang WHERE order_id = ?");
        $stmt->execute([$id]);
        $success = 'Xóa đơn hàng thành công';
    } catch (PDOException $e) {
        $error = 'Lỗi khi xóa đơn hàng: ' . $e->getMessage();
    }
}

// Lấy danh sách đơn hàng
try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    
    $query = "
        SELECT dh.*, nv.ho_ten as nhanvien_name 
        FROM DonHang dh
        LEFT JOIN NhanVien nv ON dh.nhanvien_id = nv.nhanvien_id
        WHERE 1=1
    ";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (dh.khach_hang LIKE ? OR dh.dia_chi LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status)) {
        $query .= " AND dh.trang_thai = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY dh.order_id DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy danh sách đơn hàng: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container py-5" style="background: linear-gradient(135deg, #f8ffae 0%, #43c6ac 100%); min-height: 100vh;">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold mb-2 text-gradient">Quản lý đơn hàng</h1>
            <p class="lead text-muted mb-4">Danh sách và thao tác quản lý đơn hàng hệ thống</p>
            <a href="/delivery-management/admin/order-add.php" class="btn btn-lg btn-gradient-primary shadow rounded-pill px-5 mb-3">
                <i class="bi bi-plus"></i> Tạo đơn hàng
            </a>
        </div>
    </div>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center w-75 mx-auto mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success text-center w-75 mx-auto mb-4"><?php echo $success; ?></div>
    <?php endif; ?>
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
            <form method="GET" action="" class="input-group input-group-lg shadow rounded-pill overflow-hidden">
                <input type="text" class="form-control border-0" name="search" placeholder="Tìm kiếm khách hàng, địa chỉ..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <select class="form-select border-0" name="status" onchange="this.form.submit()" style="max-width:180px;">
                    <option value="" <?php echo !isset($_GET['status']) || $_GET['status'] === '' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                    <option value="dang_giao" <?php echo isset($_GET['status']) && $_GET['status'] === 'dang_giao' ? 'selected' : ''; ?>>Đang giao</option>
                    <option value="hoan_thanh" <?php echo isset($_GET['status']) && $_GET['status'] === 'hoan_thanh' ? 'selected' : ''; ?>>Hoàn thành</option>
                    <option value="huy" <?php echo isset($_GET['status']) && $_GET['status'] === 'huy' ? 'selected' : ''; ?>>Hủy</option>
                </select>
                <button class="btn btn-gradient-primary px-4" type="submit"><i class="bi bi-search"></i></button>
                <?php if (isset($_GET['search']) || isset($_GET['status'])): ?>
                    <a href="/delivery-management/admin/orders.php" class="btn btn-outline-secondary px-4">Xóa bộ lọc</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-11">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-gradient text-white">
                                <tr>
                                    <th class="text-center">ID</th>
                                    <th>Khách hàng</th>
                                    <th>Địa chỉ</th>
                                    <th>Ngày giao</th>
                                    <th>Nhân viên</th>
                                    <th>Trạng thái</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">Không có đơn hàng nào</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td class="text-center fw-bold text-gradient">#<?php echo $order['order_id']; ?></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($order['khach_hang']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($order['dia_chi'], 0, 30) . (strlen($order['dia_chi']) > 30 ? '...' : '')); ?></td>
                                            <td><?php echo formatDate($order['ngay_giao']); ?></td>
                                            <td>
                                                <?php if ($order['nhanvien_id']): ?>
                                                    <a href="/delivery-management/admin/employee-view.php?id=<?php echo $order['nhanvien_id']; ?>">
                                                        <?php echo htmlspecialchars($order['nhanvien_name']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Chưa gán</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusText = '';
                                                $badgeClass = '';
                                                switch ($order['trang_thai']) {
                                                    case 'dang_giao':
                                                        $statusText = 'Đang giao';
                                                        $badgeClass = 'bg-warning';
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
                                                <span class="badge <?php echo $badgeClass; ?> px-3 py-2"><?php echo $statusText; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/delivery-management/admin/order-view.php?id=<?php echo $order['order_id']; ?>" class="btn btn-gradient-info rounded-pill px-3"><i class="bi bi-eye"></i></a>
                                                    <a href="/delivery-management/admin/order-edit.php?id=<?php echo $order['order_id']; ?>" class="btn btn-gradient-primary rounded-pill px-3"><i class="bi bi-pencil"></i></a>
                                                    <a href="/delivery-management/admin/orders.php?delete=<?php echo $order['order_id']; ?>" class="btn btn-gradient-danger rounded-pill px-3" onclick="return confirmDelete('Bạn có chắc chắn muốn xóa đơn hàng này?')"><i class="bi bi-trash"></i></a>
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
.btn-gradient-info {
    background: linear-gradient(90deg, #43cea2 0%, #185a9d 100%);
    color: #fff;
    border: none;
}
.btn-gradient-info:hover { background: linear-gradient(90deg, #185a9d 0%, #43cea2 100%); color: #fff; }
.btn-gradient-danger {
    background: linear-gradient(90deg, #f7971e 0%, #ffd200 100%);
    color: #fff;
    border: none;
}
.btn-gradient-danger:hover { background: linear-gradient(90deg, #ffd200 0%, #f7971e 100%); color: #fff; }
.table-gradient {
    background: linear-gradient(90deg, #11998e 0%, #38ef7d 100%);
}
.rounded-4 { border-radius: 1.5rem !important; }
</style>
<script>
function confirmDelete(msg) {
    return confirm(msg);
}
</script>
