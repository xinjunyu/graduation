<?php
// 引入公共函数
require_once '../includes/functions.php';

// 检查登录状态和权限
check_permission('admin');

// 获取当前用户信息
$user = get_logged_in_user();

// 搜索和筛选
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';

// 分页
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 构建查询条件
$where = [];
$params = [];

if ($search) {
    $where[] = "(username LIKE ? OR realname LIKE ? OR department LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role) {
    $where[] = "role = ?";
    $params[] = $role;
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// 排序
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// 允许排序的字段
$allowed_sorts = ['id'];
if (!in_array($sort, $allowed_sorts)) {
    $sort = 'id';
}
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// 获取总用户数
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM users $where_clause");
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// 边界检查：确保 page 在有效范围内
if ($page < 1) {
    $page = 1;
} elseif ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;

// 获取用户列表
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM users $where_clause ORDER BY $sort $order LIMIT ? OFFSET ?");
$params[] = $limit;
$params[] = $offset; // 注意：params 之前已经被用于 count 查询，需要重置或分别处理
// 由于 params 在 count 查询中被绑定了，我们需要重新构建用于 select 的 params
// 最简单的方法是重新构建 params 数组
$select_params = $params; // 这里的 $params 包含了搜索条件的参数
// 但是 wait, sql parameters logic needs care.
// Let's rewrite the parameter logic to be safe.
$select_params = [];
if ($search) {
    $select_params[] = "%$search%";
    $select_params[] = "%$search%";
}
if ($role) {
    $select_params[] = $role;
}
$select_params[] = $limit;
$select_params[] = $offset;

$stmt->execute($select_params);
$users = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>

<div class="page-title">
    用户管理
</div>

<!-- 筛选器 -->
<div class="filter-container">
    <form method="GET" action="user_management.php">
        <div class="filter-row">
            <div class="filter-item">
                <label for="search">搜索：</label>
                <input type="text" id="search" name="search" value="<?php echo $search; ?>">
            </div>
            <div class="filter-item">
                <label for="role">角色：</label>
                <select id="role" name="role">
                    <option value="">全部</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>管理员</option>
                    <option value="teacher" <?php echo $role === 'teacher' ? 'selected' : ''; ?>>教师</option>
                    <option value="student" <?php echo $role === 'student' ? 'selected' : ''; ?>>学生</option>
                </select>
            </div>
            <div class="filter-item">
                <button type="submit" class="btn btn-primary">搜索</button>
                <a href="user_management.php" class="btn btn-default">重置</a>
                <a href="add_user.php" class="btn btn-success">新增用户</a>
            </div>
        </div>
    </form>
</div>

<!-- 用户列表 -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">用户列表</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="sortable-table">
                <thead>
                    <tr>
                        <th>
                            <a href="?search=<?php echo $search; ?>&role=<?php echo $role; ?>&sort=id&order=<?php echo $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link <?php echo $sort === 'id' ? 'active' : ''; ?>">
                                ID
                                <span class="sort-arrow"><?php echo $sort === 'id' && $order === 'ASC' ? '↑' : '↓'; ?></span>
                            </a>
                        </th>
                        <th>用户名</th>
                        <th>真实姓名</th>
                        <th>角色</th>
                        <th>部门</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px;">暂无用户数据</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['realname']; ?></td>
                                <td>
                                    <?php
                                    $role_text = [
                                        'admin' => '管理员',
                                        'teacher' => '教师',
                                        'student' => '学生'
                                    ];
                                    echo $role_text[$user['role']];
                                    ?>
                                </td>
                                <td><?php echo $user['department']; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-primary">编辑</a>
                                    <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-danger" onclick="return confirmDelete('确定要删除用户 <?php echo $user['realname']; ?> 吗？')">删除</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 分页 -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="<?php echo $page > 1 ? "?page=1&search=$search&role=$role&sort=$sort&order=$order" : 'javascript:void(0);'; ?>" class="pagination-btn" <?php echo $page === 1 ? 'disabled' : ''; ?>>首页</a>
                <a href="<?php echo $page > 1 ? "?page=" . ($page - 1) . "&search=$search&role=$role&sort=$sort&order=$order" : 'javascript:void(0);'; ?>" class="pagination-btn" <?php echo $page === 1 ? 'disabled' : ''; ?>>上一页</a>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&role=<?php echo $role; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <a href="<?php echo $page < $total_pages ? "?page=" . ($page + 1) . "&search=$search&role=$role&sort=$sort&order=$order" : 'javascript:void(0);'; ?>" class="pagination-btn" <?php echo $page === $total_pages ? 'disabled' : ''; ?>>下一页</a>
                <a href="<?php echo $page < $total_pages ? "?page=$total_pages&search=$search&role=$role&sort=$sort&order=$order" : 'javascript:void(0);'; ?>" class="pagination-btn" <?php echo $page === $total_pages ? 'disabled' : ''; ?>>末页</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>