<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Xử lý xóa lương
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM Luong WHERE luong_id = ?");
        $stmt->execute([$id]);
        $success = 'Xóa thông tin lương thành công';
    } catch (PDOException $e) {
        $error = 'Lỗi khi xóa thông tin lương: ' . $e->getMessage();
    }
}

// Lấy danh sách lương
try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $month = isset($_GET['month']) ? $_GET['month'] : '';
    
    $query = "
        SELECT l.*, nv.ho_ten 
        FROM Luong l
        JOIN NhanVien nv ON l.nhanvien_id = nv.nhanvien_id
        WHERE 1=1
    ";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND nv.ho_ten LIKE ?";
        $params[] = "%$search%";
    }
    
    if (!empty($month)) {
        $query .= " AND l.thang = ?";
        $params[] = $month;
    }
    
    $query .= " ORDER BY l.thang DESC, nv.ho_ten";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $salaries = $stmt->fetchAll();
    
    // Lấy danh sách nhân viên
    $stmt = $conn->query("SELECT * FROM NhanVien ORDER BY ho_ten");
    $employees = $stmt->fetchAll();
    
    // Lấy danh sách tháng
    $stmt = $conn->query("SELECT DISTINCT thang FROM Luong ORDER BY thang DESC");
    $months = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy danh sách lương: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container py-5" style="background: linear-gradient(135deg, #f8ffae 0%, #43c6ac 100%); min-height: 100vh;">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold mb-2 text-gradient">Quản lý lương</h1>
            <p class="lead text-muted mb-4">Danh sách lương nhân viên và thao tác quản lý</p>
            <a href="/delivery-management/admin/salary-add.php" class="btn btn-gradient-primary rounded-pill px-5 mb-3">
                <i class="bi bi-plus"></i> Thêm thông tin lương
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
                <input type="text" class="form-control border-0" name="search" placeholder="Tìm kiếm theo tên nhân viên..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <select class="form-select border-0" name="month" onchange="this.form.submit()">
                    <option value="">Tất cả các tháng</option>
                    <?php foreach ($months as $m): ?>
                        <option value="<?php echo $m; ?>" <?php echo isset($_GET['month']) && $_GET['month'] === $m ? 'selected' : ''; ?>><?php echo $m; ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-gradient-primary px-4" type="submit"><i class="bi bi-search"></i></button>
                <?php if (isset($_GET['search']) || isset($_GET['month'])): ?>
                    <a href="/delivery-management/admin/salaries.php" class="btn btn-outline-secondary px-4">Xóa bộ lọc</a>
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
                                    <th>ID</th>
                                    <th>Nhân viên</th>
                                    <th>Tháng</th>
                                    <th>Lương cơ bản</th>
                                    <th>Lương theo đơn</th>
                                    <th>Tổng lương</th>
                                    <th>Ngày trả</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($salaries)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">Không có thông tin lương nào</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($salaries as $salary): ?>
                                        <tr>
                                            <td class="fw-bold text-gradient">#<?php echo $salary['luong_id']; ?></td>
                                            <td><?php echo htmlspecialchars($salary['ho_ten']); ?></td>
                                            <td><?php echo htmlspecialchars($salary['thang']); ?></td>
                                            <td><?php echo formatCurrency($salary['luong_co_ban']); ?></td>
                                            <td><?php echo formatCurrency($salary['luong_theo_order'] ?? 0); ?></td>
                                            <td><?php echo formatCurrency($salary['tong_luong']); ?></td>
                                            <td><?php echo $salary['ngay_tra'] ? formatDate($salary['ngay_tra']) : '<span class="text-danger">Chưa trả</span>'; ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/delivery-management/admin/salary-edit.php?id=<?php echo $salary['luong_id']; ?>" class="btn btn-gradient-primary rounded-pill px-3"><i class="bi bi-pencil"></i></a>
                                                    <a href="/delivery-management/admin/salaries.php?delete=<?php echo $salary['luong_id']; ?>" class="btn btn-gradient-danger rounded-pill px-3" onclick="return confirmDelete('Bạn có chắc chắn muốn xóa thông tin lương này?')"><i class="bi bi-trash"></i></a>
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
function confirmDelete(msg) { return confirm(msg); }
</script>
