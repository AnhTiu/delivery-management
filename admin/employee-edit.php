<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Lấy thông tin nhân viên
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /delivery-management/admin/employees.php");
    exit();
}

$id = $_GET['id'];

try {
    // Lấy thông tin nhân viên
    $stmt = $conn->prepare("SELECT * FROM NhanVien WHERE nhanvien_id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        header("Location: /delivery-management/admin/employees.php");
        exit();
    }
    
    // Lấy danh sách phương tiện
    $stmt = $conn->query("SELECT * FROM PhuongTien ORDER BY phuongtien_id");
    $vehicles = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Lỗi hệ thống: ' . $e->getMessage();
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ho_ten = $_POST['ho_ten'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    $email = $_POST['email'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'nhanvien';
    $phuongtien_id = !empty($_POST['phuongtien_id']) ? $_POST['phuongtien_id'] : null;
    
    if (empty($ho_ten) || empty($email) || empty($username)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } else {
        try {
            // Kiểm tra username đã tồn tại chưa (nếu thay đổi)
            if ($username !== $employee['username']) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE username = ? AND nhanvien_id != ?");
                $stmt->execute([$username, $id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Tên đăng nhập đã tồn tại';
                }
            }
            
            // Kiểm tra email đã tồn tại chưa (nếu thay đổi)
            if (empty($error) && $email !== $employee['email']) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE email = ? AND nhanvien_id != ?");
                $stmt->execute([$email, $id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Email đã tồn tại';
                }
            }
            
            if (empty($error)) {
                // Cập nhật thông tin nhân viên
                if (!empty($password)) {
                    // Nếu có cập nhật mật khẩu
                    $hashedPassword = hashPassword($password);
                    $stmt = $conn->prepare("
                        UPDATE NhanVien 
                        SET ho_ten = ?, sdt = ?, email = ?, username = ?, password = ?, role = ?, phuongtien_id = ?
                        WHERE nhanvien_id = ?
                    ");
                    $stmt->execute([$ho_ten, $sdt, $email, $username, $hashedPassword, $role, $phuongtien_id, $id]);
                } else {
                    // Không cập nhật mật khẩu
                    $stmt = $conn->prepare("
                        UPDATE NhanVien 
                        SET ho_ten = ?, sdt = ?, email = ?, username = ?, role = ?, phuongtien_id = ?
                        WHERE nhanvien_id = ?
                    ");
                    $stmt->execute([$ho_ten, $sdt, $email, $username, $role, $phuongtien_id, $id]);
                }
                
                $success = 'Cập nhật thông tin nhân viên thành công';
                
                // Cập nhật lại thông tin nhân viên
                $stmt = $conn->prepare("SELECT * FROM NhanVien WHERE nhanvien_id = ?");
                $stmt->execute([$id]);
                $employee = $stmt->fetch();
            }
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
            <h1 class="display-4 fw-bold mb-2 text-gradient">Chỉnh sửa thông tin nhân viên</h1>
            <p class="lead text-muted mb-4">Cập nhật thông tin nhân viên hệ thống</p>
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
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Họ tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ho_ten" required value="<?php echo htmlspecialchars($employee['ho_ten']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($employee['email']); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Số điện thoại</label>
                                <input type="text" class="form-control" name="sdt" value="<?php echo htmlspecialchars($employee['sdt']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="username" required value="<?php echo htmlspecialchars($employee['username']); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Vai trò</label>
                            <select class="form-select" name="role">
                                <option value="nhanvien" <?php echo $employee['role'] === 'nhanvien' ? 'selected' : ''; ?>>Nhân viên</option>
                                <option value="admin" <?php echo $employee['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phương tiện</label>
                            <select class="form-select" id="phuongtien_id" name="phuongtien_id">
                                <option value="">-- Không gán phương tiện --</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <?php
                                    $isAssigned = false;
                                    if ($vehicle['phuongtien_id'] != $employee['phuongtien_id']) {
                                        $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE phuongtien_id = ?");
                                        $stmt->execute([$vehicle['phuongtien_id']]);
                                        $isAssigned = $stmt->fetchColumn() > 0;
                                    }
                                    ?>
                                    <option value="<?php echo $vehicle['phuongtien_id']; ?>" <?php echo $vehicle['phuongtien_id'] == $employee['phuongtien_id'] ? 'selected' : ''; ?> <?php echo $isAssigned ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($vehicle['loai'] . ' - ' . $vehicle['bien_so']); ?><?php echo $isAssigned ? ' (Đã gán cho nhân viên khác)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-lg btn-gradient-primary rounded-pill shadow"><i class="bi bi-save"></i> Cập nhật</button>
                            <a href="/delivery-management/admin/employees.php" class="btn btn-lg btn-outline-secondary rounded-pill">Quay lại danh sách</a>
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
