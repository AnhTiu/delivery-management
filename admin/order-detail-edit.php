<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Lấy thông tin chi tiết đơn hàng
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /delivery-management/admin/orders.php");
    exit();
}

$id = $_GET['id'];

try {
    $stmt = $conn->prepare("SELECT * FROM ChiTietDonHang WHERE ct_id = ?");
    $stmt->execute([$id]);
    $detail = $stmt->fetch();
    
    if (!$detail) {
        header("Location: /delivery-management/admin/orders.php");
        exit();
    }
    
    // Lấy thông tin đơn hàng
    $stmt = $conn->prepare("SELECT * FROM DonHang WHERE order_id = ?");
    $stmt->execute([$detail['order_id']]);
    $order = $stmt->fetch();
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten_san_pham = $_POST['ten_san_pham'] ?? '';
    $so_luong = $_POST['so_luong'] ?? '';
    $don_gia = $_POST['don_gia'] ?? '';
    
    if (empty($ten_san_pham) || empty($so_luong) || empty($don_gia)) {
        $error = 'Vui lòng nhập đầy đủ thông tin sản phẩm';
    } elseif (!is_numeric($so_luong) || $so_luong <= 0) {
        $error = 'Số lượng phải là số dương';
    } elseif (!is_numeric($don_gia) || $don_gia <= 0) {
        $error = 'Đơn giá phải là số dương';
    } else {
        try {
            // Cập nhật thông tin sản phẩm
            $stmt = $conn->prepare("
                UPDATE ChiTietDonHang 
                SET ten_san_pham = ?, so_luong = ?, don_gia = ?
                WHERE ct_id = ?
            ");
            $stmt->execute([$ten_san_pham, $so_luong, $don_gia, $id]);
            
            $success = 'Cập nhật thông tin sản phẩm thành công';
            
            // Cập nhật lại thông tin chi tiết đơn hàng
            $stmt = $conn->prepare("SELECT * FROM ChiTietDonHang WHERE ct_id = ?");
            $stmt->execute([$id]);
            $detail = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container py-5" style="background: linear-gradient(135deg, #f8ffae 0%, #43c6ac 100%); min-height: 100vh;">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold mb-2 text-gradient">Chỉnh sửa sản phẩm</h1>
            <p class="lead text-muted mb-4">Cập nhật thông tin sản phẩm trong đơn hàng</p>
        </div>
    </div>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center w-75 mx-auto mb-4"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success text-center w-75 mx-auto mb-4"><?php echo $success; ?></div>
    <?php endif; ?>
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-lg rounded-4">
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="ten_san_pham" class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ten_san_pham" name="ten_san_pham" value="<?php echo htmlspecialchars($detail['ten_san_pham']); ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="so_luong" class="form-label fw-semibold">Số lượng <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="so_luong" name="so_luong" min="1" value="<?php echo $detail['so_luong']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="don_gia" class="form-label fw-semibold">Đơn giá (VNĐ) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="don_gia" name="don_gia" min="0" step="1000" value="<?php echo $detail['don_gia']; ?>" required>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-lg btn-gradient-primary rounded-pill shadow"><i class="bi bi-save"></i> Cập nhật</button>
                            <a href="/delivery-management/admin/order-view.php?id=<?php echo $detail['order_id']; ?>" class="btn btn-lg btn-outline-secondary rounded-pill">Quay lại chi tiết đơn hàng</a>
                        </div>
                    </form>
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
.rounded-4 { border-radius: 1.5rem !important; }
</style>
