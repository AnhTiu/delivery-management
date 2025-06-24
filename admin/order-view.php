<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Lấy thông tin đơn hàng
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /delivery-management/admin/orders.php");
    exit();
}

$id = $_GET['id'];

try {
    // Lấy thông tin đơn hàng
    $stmt = $conn->prepare("
        SELECT dh.*, nv.ho_ten as nhanvien_name 
        FROM DonHang dh
        LEFT JOIN NhanVien nv ON dh.nhanvien_id = nv.nhanvien_id
        WHERE dh.order_id = ?
    ");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header("Location: /delivery-management/admin/orders.php");
        exit();
    }
    
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

// Xử lý xóa chi tiết đơn hàng
if (isset($_GET['delete_detail']) && is_numeric($_GET['delete_detail'])) {
    $detail_id = $_GET['delete_detail'];
    try {
        $stmt = $conn->prepare("DELETE FROM ChiTietDonHang WHERE ct_id = ? AND order_id = ?");
        $stmt->execute([$detail_id, $id]);
        
        $success = 'Xóa sản phẩm thành công';
        
        // Cập nhật lại danh sách chi tiết đơn hàng
        $stmt = $conn->prepare("SELECT * FROM ChiTietDonHang WHERE order_id = ?");
        $stmt->execute([$id]);
        $orderDetails = $stmt->fetchAll();
        
        // Tính lại tổng giá trị đơn hàng
        $total = 0;
        foreach ($orderDetails as $detail) {
            $total += $detail['so_luong'] * $detail['don_gia'];
        }
    } catch (PDOException $e) {
        $error = 'Lỗi khi xóa sản phẩm: ' . $e->getMessage();
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container py-5" style="background: linear-gradient(135deg, #f8ffae 0%, #43c6ac 100%); min-height: 100vh;">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold mb-2 text-gradient">Chi tiết đơn hàng #<?php echo $id; ?></h1>
            <div class="d-flex justify-content-center gap-2 mb-2">
                <a href="/delivery-management/admin/order-edit.php?id=<?php echo $id; ?>" class="btn btn-gradient-primary rounded-pill px-4"><i class="bi bi-pencil"></i> Chỉnh sửa</a>
                <a href="/delivery-management/admin/order-detail-add.php?id=<?php echo $id; ?>" class="btn btn-gradient-info rounded-pill px-4"><i class="bi bi-plus"></i> Thêm sản phẩm</a>
                <a href="/delivery-management/admin/orders.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left"></i> Quay lại</a>
            </div>
        </div>
    </div>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center w-75 mx-auto mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success text-center w-75 mx-auto mb-4"><?php echo $success; ?></div>
    <?php endif; ?>
    <div class="row mb-4 justify-content-center">
        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-white border-0 rounded-top-4">
                    <h5 class="card-title mb-0 text-gradient">Thông tin đơn hàng</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr><th style="width: 30%">Mã đơn hàng:</th><td><?php echo $order['order_id']; ?></td></tr>
                        <tr><th>Khách hàng:</th><td><?php echo htmlspecialchars($order['khach_hang']); ?></td></tr>
                        <tr><th>Địa chỉ:</th><td><?php echo htmlspecialchars($order['dia_chi']); ?></td></tr>
                        <tr><th>Ngày giao:</th><td><?php echo formatDate($order['ngay_giao']); ?></td></tr>
                        <tr><th>Trạng thái:</th><td><?php 
                            $statusText = '';
                            $badgeClass = '';
                            switch ($order['trang_thai']) {
                                case 'dang_giao': $statusText = 'Đang giao'; $badgeClass = 'bg-info'; break;
                                case 'hoan_thanh': $statusText = 'Hoàn thành'; $badgeClass = 'bg-success'; break;
                                case 'huy': $statusText = 'Hủy'; $badgeClass = 'bg-danger'; break;
                            }
                        ?>
                        <span class="badge <?php echo $badgeClass; ?> px-3 py-2"><?php echo $statusText; ?></span></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-header bg-white border-0 rounded-top-4">
                    <h5 class="card-title mb-0 text-gradient">Thông tin giao hàng</h5>
                </div>
                <div class="card-body">
                    <?php if ($order['nhanvien_id']): ?>
                        <table class="table table-borderless mb-0">
                            <tr><th style="width: 30%">Nhân viên giao hàng:</th><td><a href="/delivery-management/admin/employee-view.php?id=<?php echo $order['nhanvien_id']; ?>"><?php echo htmlspecialchars($order['nhanvien_name']); ?></a></td></tr>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">Đơn hàng chưa được gán cho nhân viên nào.</div>
                        <a href="/delivery-management/admin/order-edit.php?id=<?php echo $id; ?>" class="btn btn-gradient-primary rounded-pill px-4"><i class="bi bi-person-plus"></i> Gán nhân viên</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-lg rounded-4 mb-4">
        <div class="card-header bg-white border-0 rounded-top-4">
            <h5 class="card-title mb-0 text-gradient">Chi tiết sản phẩm</h5>
        </div>
        <div class="card-body">
            <?php if (empty($orderDetails)): ?>
                <div class="alert alert-info">Đơn hàng chưa có sản phẩm nào.</div>
                <a href="/delivery-management/admin/order-detail-add.php?id=<?php echo $id; ?>" class="btn btn-gradient-primary rounded-pill px-4"><i class="bi bi-plus"></i> Thêm sản phẩm</a>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-gradient text-white">
                            <tr>
                                <th>ID</th>
                                <th>Tên sản phẩm</th>
                                <th>Số lượng</th>
                                <th>Đơn giá</th>
                                <th>Thành tiền</th>
                                <th>Thao tác</th>
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
                                    <td>
                                        <a href="/delivery-management/admin/order-detail-edit.php?id=<?php echo $detail['ct_id']; ?>" class="btn btn-gradient-primary btn-sm rounded-pill px-3"><i class="bi bi-pencil"></i></a>
                                        <a href="/delivery-management/admin/order-view.php?id=<?php echo $id; ?>&delete_detail=<?php echo $detail['ct_id']; ?>" class="btn btn-gradient-danger btn-sm rounded-pill px-3" onclick="return confirmDelete('Bạn có chắc chắn muốn xóa sản phẩm này?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Tổng cộng:</th>
                                <th><?php echo formatCurrency($total); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
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
.rounded-top-4 { border-top-left-radius: 1.5rem !important; border-top-right-radius: 1.5rem !important; }
</style>
<script>
function confirmDelete(msg) { return confirm(msg); }
</script>
