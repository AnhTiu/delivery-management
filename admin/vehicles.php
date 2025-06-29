<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$error = '';
$success = '';

// Xử lý thêm phương tiện mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $loai = $_POST['loai'] ?? '';
    $bien_so = $_POST['bien_so'] ?? '';
    
    if (empty($loai) || empty($bien_so)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        try {
            // Kiểm tra biển số đã tồn tại chưa
            $stmt = $conn->prepare("SELECT COUNT(*) FROM PhuongTien WHERE bien_so = ?");
            $stmt->execute([$bien_so]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Biển số đã tồn tại';
            } else {
                // Thêm phương tiện mới
                $stmt = $conn->prepare("INSERT INTO PhuongTien (loai, bien_so) VALUES (?, ?)");
                $stmt->execute([$loai, $bien_so]);
                $success = 'Thêm phương tiện thành công';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// Xử lý xóa phương tiện
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Kiểm tra xem phương tiện có đang được sử dụng không
        $stmt = $conn->prepare("SELECT COUNT(*) FROM NhanVien WHERE phuongtien_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Không thể xóa phương tiện đang được sử dụng';
        } else {
            $stmt = $conn->prepare("DELETE FROM PhuongTien WHERE phuongtien_id = ?");
            $stmt->execute([$id]);
            $success = 'Xóa phương tiện thành công';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi khi xóa phương tiện: ' . $e->getMessage();
    }
}

// Lấy danh sách phương tiện
try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $filter = isset($_GET['filter']) ? $_GET['filter'] : '';
    
    $query = "
        SELECT pt.*, nv.nhanvien_id, nv.ho_ten 
        FROM PhuongTien pt
        LEFT JOIN NhanVien nv ON pt.phuongtien_id = nv.phuongtien_id
        WHERE 1=1
    ";
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (pt.loai LIKE ? OR pt.bien_so LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($filter === 'available') {
        $query .= " AND nv.nhanvien_id IS NULL";
    } elseif ($filter === 'assigned') {
        $query .= " AND nv.nhanvien_id IS NOT NULL";
    }
    
    $query .= " ORDER BY pt.phuongtien_id DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $vehicles = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Lỗi khi lấy danh sách phương tiện: ' . $e->getMessage();
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="container py-5" style="background: linear-gradient(135deg, #f8ffae 0%, #43c6ac 100%); min-height: 100vh;">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-4 fw-bold mb-2 text-gradient">Quản lý phương tiện</h1>
            <p class="lead text-muted mb-4">Danh sách và thao tác quản lý phương tiện hệ thống</p>
            <button type="button" class="btn btn-lg btn-gradient-primary shadow rounded-pill px-5 mb-3" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                <i class="bi bi-truck"></i> Thêm phương tiện
            </button>
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
                <input type="text" class="form-control border-0" name="search" placeholder="Tìm kiếm loại, biển số..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <select class="form-select border-0" name="filter" onchange="this.form.submit()" style="max-width:180px;">
                    <option value="" <?php echo !isset($_GET['filter']) || $_GET['filter'] === '' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="available" <?php echo isset($_GET['filter']) && $_GET['filter'] === 'available' ? 'selected' : ''; ?>>Chưa gán</option>
                    <option value="assigned" <?php echo isset($_GET['filter']) && $_GET['filter'] === 'assigned' ? 'selected' : ''; ?>>Đã gán</option>
                </select>
                <button class="btn btn-gradient-primary px-4" type="submit"><i class="bi bi-search"></i></button>
                <?php if (isset($_GET['search']) || isset($_GET['filter'])): ?>
                    <a href="/delivery-management/admin/vehicles.php" class="btn btn-outline-secondary px-4">Xóa bộ lọc</a>
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
                                    <th>Loại</th>
                                    <th>Biển số</th>
                                    <th>Trạng thái</th>
                                    <th>Nhân viên sử dụng</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($vehicles)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">Không có phương tiện nào</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <tr>
                                            <td class="text-center fw-bold text-gradient">#<?php echo $vehicle['phuongtien_id']; ?></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($vehicle['loai']); ?></td>
                                            <td><?php echo htmlspecialchars($vehicle['bien_so']); ?></td>
                                            <td>
                                                <?php if ($vehicle['nhanvien_id']): ?>
                                                    <span class="badge bg-warning px-3 py-2">Đang sử dụng</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success px-3 py-2">Sẵn sàng</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($vehicle['nhanvien_id']): ?>
                                                    <a href="/delivery-management/admin/employee-view.php?id=<?php echo $vehicle['nhanvien_id']; ?>">
                                                        <?php echo htmlspecialchars($vehicle['ho_ten']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Chưa gán</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="/delivery-management/admin/vehicle-edit.php?id=<?php echo $vehicle['phuongtien_id']; ?>" class="btn btn-gradient-primary rounded-pill px-3"><i class="bi bi-pencil"></i></a>
                                                    <?php if (!$vehicle['nhanvien_id']): ?>
                                                        <a href="/delivery-management/admin/vehicles.php?delete=<?php echo $vehicle['phuongtien_id']; ?>" class="btn btn-gradient-danger rounded-pill px-3" onclick="return confirmDelete('Bạn có chắc chắn muốn xóa phương tiện này?')"><i class="bi bi-trash"></i></a>
                                                        <a href="/delivery-management/admin/vehicle-assign.php?phuongtien_id=<?php echo $vehicle['phuongtien_id']; ?>" class="btn btn-gradient-info rounded-pill px-3"><i class="bi bi-person-plus"></i></a>
                                                    <?php else: ?>
                                                        <form method="POST" action="/delivery-management/admin/vehicle-assign.php" style="display: inline;">
                                                            <input type="hidden" name="nhanvien_id" value="<?php echo $vehicle['nhanvien_id']; ?>">
                                                            <input type="hidden" name="action" value="remove">
                                                            <button type="submit" class="btn btn-warning rounded-pill px-3"><i class="bi bi-person-dash"></i></button>
                                                        </form>
                                                    <?php endif; ?>
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

<!-- Modal thêm phương tiện -->
<div class="modal fade" id="addVehicleModal" tabindex="-1" aria-labelledby="addVehicleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title" id="addVehicleModalLabel">Thêm phương tiện mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="loai" class="form-label">Loại phương tiện <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="loai" name="loai" required>
                    </div>
                    <div class="mb-3">
                        <label for="bien_so" class="form-label">Biển số <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bien_so" name="bien_so" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-gradient-primary">Thêm</button>
                </div>
            </form>
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
