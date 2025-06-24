<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Lấy thông tin lương
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /delivery-management/admin/salaries.php");
    exit();
}

$id = $_GET['id'];

try {
    $stmt = $conn->prepare("
        SELECT l.*, nv.ho_ten 
        FROM Luong l
        JOIN NhanVien nv ON l.nhanvien_id = nv.nhanvien_id
        WHERE l.luong_id = ?
    ");
    $stmt->execute([$id]);
    $salary = $stmt->fetch();
    
    if (!$salary) {
        header("Location: /delivery-management/admin/salaries.php");
        exit();
    }
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $luong_co_ban = $_POST['luong_co_ban'] ?? '';
    $luong_theo_order = $_POST['luong_theo_order'] ?? null;
    $ngay_tra = !empty($_POST['ngay_tra']) ? $_POST['ngay_tra'] : null;
    
    if (empty($luong_co_ban)) {
        $error = 'Vui lòng nhập lương cơ bản';
    } elseif (!is_numeric($luong_co_ban) || $luong_co_ban < 0) {
        $error = 'Lương cơ bản phải là số dương';
    } elseif (!empty($luong_theo_order) && (!is_numeric($luong_theo_order) || $luong_theo_order < 0)) {
        $error = 'Lương theo đơn phải là số dương';
    } else {
        try {
            // Cập nhật thông tin lương
            $stmt = $conn->prepare("
                UPDATE Luong 
                SET luong_co_ban = ?, luong_theo_order = ?, ngay_tra = ?
                WHERE luong_id = ?
            ");
            $stmt->execute([$luong_co_ban, $luong_theo_order, $ngay_tra, $id]);
            
            $success = 'Cập nhật thông tin lương thành công';
            
            // Cập nhật lại thông tin lương
            $stmt = $conn->prepare("
                SELECT l.*, nv.ho_ten 
                FROM Luong l
                JOIN NhanVien nv ON l.nhanvien_id = nv.nhanvien_id
                WHERE l.luong_id = ?
            ");
            $stmt->execute([$id]);
            $salary = $stmt->fetch();
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
            <h1 class="display-4 fw-bold mb-2 text-gradient">Chỉnh sửa thông tin lương</h1>
            <p class="lead text-muted mb-4">Cập nhật thông tin lương cho nhân viên</p>
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
                            <label class="form-label fw-semibold">Nhân viên</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($salary['ho_ten']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lương cơ bản <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="luong_co_ban" min="0" required value="<?php echo htmlspecialchars($salary['luong_co_ban']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lương theo đơn</label>
                            <input type="number" class="form-control" name="luong_theo_order" min="0" value="<?php echo htmlspecialchars($salary['luong_theo_order']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ngày trả lương</label>
                            <input type="date" class="form-control" name="ngay_tra" value="<?php echo htmlspecialchars($salary['ngay_tra']); ?>">
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-lg btn-gradient-primary rounded-pill shadow"><i class="bi bi-save"></i> Lưu thay đổi</button>
                            <a href="/delivery-management/admin/salaries.php" class="btn btn-lg btn-outline-secondary rounded-pill">Quay lại danh sách</a>
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
