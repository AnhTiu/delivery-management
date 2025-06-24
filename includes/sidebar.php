<div class="container-fluid px-0">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded-bottom-4 mb-4">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarNav" aria-controls="sidebarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="sidebarNav">
                <ul class="navbar-nav mx-auto gap-2">
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 rounded-3 px-3 py-2 fw-semibold <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="/delivery-management/admin/index.php">
                                <i class="bi bi-speedometer2"></i> Tổng quan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 rounded-3 px-3 py-2 fw-semibold <?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : ''; ?>" href="/delivery-management/admin/employees.php">
                                <i class="bi bi-people"></i> Quản lý nhân viên
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 rounded-3 px-3 py-2 fw-semibold <?php echo basename($_SERVER['PHP_SELF']) == 'vehicles.php' ? 'active' : ''; ?>" href="/delivery-management/admin/vehicles.php">
                                <i class="bi bi-truck"></i> Quản lý phương tiện
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 rounded-3 px-3 py-2 fw-semibold <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="/delivery-management/admin/orders.php">
                                <i class="bi bi-box-seam"></i> Quản lý đơn hàng
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 rounded-3 px-3 py-2 fw-semibold <?php echo basename($_SERVER['PHP_SELF']) == 'salaries.php' ? 'active' : ''; ?>" href="/delivery-management/admin/salaries.php">
                                <i class="bi bi-cash-stack"></i> Quản lý lương
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 rounded-3 px-3 py-2 fw-semibold <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="/delivery-management/admin/reports.php">
                                <i class="bi bi-bar-chart"></i> Báo cáo & Thống kê
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 rounded-3 px-3 py-2 fw-semibold <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="/delivery-management/employee/index.php">
                                <i class="bi bi-speedometer2"></i> Tổng quan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 rounded-3 px-3 py-2 fw-semibold <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="/delivery-management/employee/profile.php">
                                <i class="bi bi-person"></i> Hồ sơ cá nhân
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 rounded-3 px-3 py-2 fw-semibold <?php echo basename($_SERVER['PHP_SELF']) == 'vehicles.php' ? 'active' : ''; ?>" href="/delivery-management/employee/vehicles.php">
                                <i class="bi bi-truck"></i> Phương tiện
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center gap-2 rounded-3 px-3 py-2 fw-semibold <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="/delivery-management/employee/orders.php">
                                <i class="bi bi-box-seam"></i> Đơn hàng
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="content">
