<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Xử lý xóa nhân viên
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM NhanVien WHERE nhanvien_id = ?");
        $stmt->execute([$id]);
        $success = 'Xóa nhân viên thành công';
    } catch (PDOException $e) {
        $error = 'Lỗi khi xóa nhân viên: ' . $e->getMessage();
    }
}

// Lấy danh sách nhân viên
try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT nv.*, pt.loai, pt.bien_so 
            FROM NhanVien nv
            LEFT JOIN PhuongTien pt ON nv.phuongtien_id = pt.phuongtien_id
            WHERE nv.ho_ten LIKE ? OR nv.email LIKE ? OR nv.username LIKE ?
            ORDER BY nv.nhanvien_id DESC
        ");
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    } else {
        $stmt = $conn->query("
            SELECT nv.*, pt.loai, pt.bien_so 
            FROM NhanVien nv
            LEFT JOIN PhuongTien pt ON nv.phuongtien_id = pt.phuongtien_id
            ORDER BY nv.nhanvien_id DESC
        ");
    }
    
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy danh sách nhân viên: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container py-5" style="background: linear-gradient(135deg, #f8ffae 0%, #43c6ac 100%); min-height: 100vh;">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold mb-2 text-gradient">Quản lý nhân viên</h1>
            <p class="lead text-muted mb-4">Danh sách và thao tác quản lý nhân viên hệ thống</p>
            <a href="/delivery-management/auth/register.php" class="btn btn-lg btn-gradient-primary shadow rounded-pill px-5 mb-3">
                <i class="bi bi-person-plus"></i> Thêm nhân viên
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
                <input type="text" class="form-control border-0" name="search" placeholder="Tìm kiếm tên, email, username..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button class="btn btn-gradient-primary px-4" type="submit"><i class="bi bi-search"></i></button>
                <?php if (isset($_GET['search'])): ?>
                    <a href="/delivery-management/admin/employees.php" class="btn btn-outline-secondary px-4">Xóa bộ lọc</a>
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
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>SĐT</th>
                                    <th>Username</th>
                                    <th>Vai trò</th>
                                    <th>Phương tiện</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">Không có nhân viên nào</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td class="text-center fw-bold text-gradient">#<?php echo $employee['nhanvien_id']; ?></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($employee['ho_ten']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['sdt'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($employee['username']); ?></td>
                                            <td>
                                                <?php if ($employee['role'] === 'admin'): ?>
                                                    <span class="badge bg-danger px-3 py-2">Admin</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info px-3 py-2">Nhân viên</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($employee['phuongtien_id']): ?>
                                                    <span class="badge bg-success px-3 py-2">
                                                        <?php echo htmlspecialchars($employee['loai'] . ' - ' . $employee['bien_so']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary px-3 py-2">Chưa gán</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/delivery-management/admin/employee-edit.php?id=<?php echo $employee['nhanvien_id']; ?>" class="btn btn-gradient-primary rounded-pill px-3"><i class="bi bi-pencil"></i></a>
                                                    <a href="/delivery-management/admin/employee-view.php?id=<?php echo $employee['nhanvien_id']; ?>" class="btn btn-gradient-info rounded-pill px-3"><i class="bi bi-eye"></i></a>
                                                    <a href="/delivery-management/admin/employees.php?delete=<?php echo $employee['nhanvien_id']; ?>" class="btn btn-gradient-danger rounded-pill px-3" onclick="return confirmDelete('Bạn có chắc chắn muốn xóa nhân viên này?')"><i class="bi bi-trash"></i></a>
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
