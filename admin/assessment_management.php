<?php
// 引入公共函数
require_once '../includes/functions.php';

// 检查登录状态和权限
check_permission('admin');

// 获取当前用户信息
$user = get_logged_in_user();

// 搜索和筛选
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// 分页
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 构建查询条件
$where = [];
$params = [];

if ($search) {
    $where[] = "title LIKE ?";
    $params[] = "%$search%";
}

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
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

// 获取总考评分页数
global $pdo;
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM assessments $where_clause");
$stmt->execute($params); // $params 此时仅含 WHERE 参数
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $limit);

// 边界检查
if ($page < 1) {
    $page = 1;
} elseif ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;

// 获取考评列表
$stmt = $pdo->prepare("SELECT * FROM assessments $where_clause ORDER BY $sort $order LIMIT ? OFFSET ?");
// 重建参数
$select_params = $params;
$select_params[] = $limit;
$select_params[] = $offset;
$stmt->execute($select_params);
$assessments = $stmt->fetchAll();

// 获取创建者信息
$creators = [];
if (!empty($assessments)) {
    $creator_ids = array_unique(array_column($assessments, 'created_by'));
    $placeholders = implode(',', array_fill(0, count($creator_ids), '?'));
    $stmt = $pdo->prepare("SELECT id, realname FROM users WHERE id IN ($placeholders)");
    $stmt->execute($creator_ids);
    $creator_data = $stmt->fetchAll();
    foreach ($creator_data as $creator) {
        $creators[$creator['id']] = $creator['realname'];
    }
}

// 获取每个考评的参与人数
$participant_counts = [];
if (!empty($assessments)) {
    $assessment_ids = array_column($assessments, 'id');
    $placeholders = implode(',', array_fill(0, count($assessment_ids), '?'));
    $stmt = $pdo->prepare("SELECT assessment_id, COUNT(*) AS count FROM assessment_participants WHERE assessment_id IN ($placeholders) GROUP BY assessment_id");
    $stmt->execute($assessment_ids);
    $participant_data = $stmt->fetchAll();
    foreach ($participant_data as $participant) {
        $participant_counts[$participant['assessment_id']] = $participant['count'];
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="page-title">
    考评管理
</div>

<!-- 筛选器 -->
<div class="filter-container">
    <form method="GET" action="assessment_management.php">
        <div class="filter-row">
            <div class="filter-item">
                <label for="search">搜索：</label>
                <input type="text" id="search" name="search" value="<?php echo $search; ?>">
            </div>
            <div class="filter-item">
                <label for="status">状态：</label>
                <select id="status" name="status">
                    <option value="">全部</option>
                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>草稿</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>进行中</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>已完成</option>
                </select>
            </div>
            <div class="filter-item">
                <button type="submit" class="btn btn-primary">搜索</button>
                <a href="assessment_management.php" class="btn btn-default">重置</a>
                <a href="add_assessment.php" class="btn btn-success">新增考评</a>
            </div>
        </div>
    </form>
</div>

<!-- 考评列表 -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">考评列表</h3>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="sortable-table">
                <thead>
                    <tr>
                        <th>
                            <a href="?search=<?php echo $search; ?>&status=<?php echo $status; ?>&sort=id&order=<?php echo $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="sort-link <?php echo $sort === 'id' ? 'active' : ''; ?>">
                                ID
                                <span class="sort-arrow"><?php echo $sort === 'id' && $order === 'ASC' ? '↑' : '↓'; ?></span>
                            </a>
                        </th>
                        <th>标题</th>
                        <th>开始时间</th>
                        <th>结束时间</th>
                        <th>状态</th>
                        <th>创建者</th>
                        <th>参与人数</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assessments)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 20px;">暂无考评数据</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($assessments as $assessment): ?>
                            <tr>
                                <td><?php echo $assessment['id']; ?></td>
                                <td><?php echo $assessment['title']; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($assessment['start_time'])); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($assessment['end_time'])); ?></td>
                                <td>
                                    <?php
                                    $status_text = [
                                        'draft' => '草稿',
                                        'active' => '进行中',
                                        'completed' => '已完成'
                                    ];
                                    echo $status_text[$assessment['status']];
                                    ?>
                                </td>
                                <td><?php echo $creators[$assessment['created_by']]; ?></td>
                                <td><?php echo isset($participant_counts[$assessment['id']]) ? $participant_counts[$assessment['id']] : 0; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($assessment['created_at'])); ?></td>
                                <td>
                                    <a href="edit_assessment.php?id=<?php echo $assessment['id']; ?>" class="btn btn-primary">编辑</a>
                                    <a href="manage_participants.php?id=<?php echo $assessment['id']; ?>" class="btn btn-success">参与人员</a>
                                    <a href="delete_assessment.php?id=<?php echo $assessment['id']; ?>" class="btn btn-danger" onclick="return confirmDelete('确定要删除考评 <?php echo $assessment['title']; ?> 吗？')">删除</a>
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
                <a href="<?php echo $page > 1 ? "?page=1&search=$search&status=$status&sort=$sort&order=$order" : 'javascript:void(0);'; ?>" class="pagination-btn" <?php echo $page === 1 ? 'disabled' : ''; ?>>首页</a>
                <a href="<?php echo $page > 1 ? "?page=" . ($page - 1) . "&search=$search&status=$status&sort=$sort&order=$order" : 'javascript:void(0);'; ?>" class="pagination-btn" <?php echo $page === 1 ? 'disabled' : ''; ?>>上一页</a>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&status=<?php echo $status; ?>&sort=<?php echo $sort; ?>&order=<?php echo $order; ?>" class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <a href="<?php echo $page < $total_pages ? "?page=" . ($page + 1) . "&search=$search&status=$status&sort=$sort&order=$order" : 'javascript:void(0);'; ?>" class="pagination-btn" <?php echo $page === $total_pages ? 'disabled' : ''; ?>>下一页</a>
                <a href="<?php echo $page < $total_pages ? "?page=$total_pages&search=$search&status=$status&sort=$sort&order=$order" : 'javascript:void(0);'; ?>" class="pagination-btn" <?php echo $page === $total_pages ? 'disabled' : ''; ?>>末页</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>