<?php
// 引入公共函数
require_once '../includes/functions.php';

// 检查登录状态和权限
check_permission('admin');

// 获取当前用户信息
$user = get_logged_in_user();

// 搜索和筛选
$search = isset($_GET['search']) ? $_GET['search'] : '';

// 分页
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 构建查询条件
$where = [];
$params = [];

if ($search) {
    $where[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// 获取考评项目列表
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM assessment_items $where_clause ORDER BY id DESC LIMIT ? OFFSET ?");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$items = $stmt->fetchAll();

// 获取总项目数
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM assessment_items $where_clause");
$stmt->execute(array_slice($params, 0, -2));
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// 获取项目创建者信息
$creators = [];
if (!empty($items)) {
    $creator_ids = array_unique(array_column($items, 'created_by'));
    $placeholders = implode(',', array_fill(0, count($creator_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, realname FROM users WHERE id IN ($placeholders)");
    $stmt->execute($creator_ids);
    $creator_data = $stmt->fetchAll();
    foreach ($creator_data as $creator) {
        $creators[$creator['id']] = $creator['realname'];
    }
}

// 获取总权重
$total_weight = get_total_weight();
?>
<?php include '../includes/header.php'; ?>

    <div class="page-title">
        考评项目管理
    </div>

    <!-- 筛选器 -->
    <div class="filter-container">
        <form method="GET" action="item_management.php">
            <div class="filter-row">
                <div class="filter-item">
                    <label for="search">搜索：</label>
                    <input type="text" id="search" name="search" value="<?php echo $search; ?>">
                </div>
                <div class="filter-item">
                    <button type="submit" class="btn btn-primary">筛选</button>
                    <a href="item_management.php" class="btn btn-default">重置</a>
                    <a href="add_item.php" class="btn btn-success">新增考评项目</a>
                </div>
            </div>
        </form>
    </div>

    <!-- 总权重提示 -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">总权重信息</h3>
        </div>
        <div class="card-body">
            <p>当前所有考评项目的总权重为：<strong><?php echo $total_weight; ?></strong>，建议总权重设置为 100.00。</p>
        </div>
    </div>

    <!-- 考评项目列表 -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">考评项目列表</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="sortable-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>项目名称</th>
                            <th>描述</th>
                            <th>权重</th>
                            <th>创建者</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px;">暂无考评项目数据</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo $item['id']; ?></td>
                                    <td><?php echo $item['name']; ?></td>
                                    <td><?php echo $item['description']; ?></td>
                                    <td><?php echo $item['weight']; ?></td>
                                    <td><?php echo $creators[$item['created_by']]; ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($item['created_at'])); ?></td>
                                    <td>
                                        <a href="edit_item.php?id=<?php echo $item['id']; ?>" class="btn btn-primary">编辑</a>
                                        <a href="delete_item.php?id=<?php echo $item['id']; ?>" class="btn btn-danger" onclick="return confirmDelete('确定要删除考评项目 <?php echo $item['name']; ?> 吗？')">删除</a>
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
                    <a href="?page=1&search=<?php echo $search; ?>" class="pagination-btn" <?php echo $page === 1 ? 'disabled' : ''; ?>>首页</a>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>" class="pagination-btn" <?php echo $page === 1 ? 'disabled' : ''; ?>>上一页</a>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>" class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>" class="pagination-btn" <?php echo $page === $total_pages ? 'disabled' : ''; ?>>下一页</a>
                    <a href="?page=<?php echo $total_pages; ?>&search=<?php echo $search; ?>" class="pagination-btn" <?php echo $page === $total_pages ? 'disabled' : ''; ?>>末页</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>