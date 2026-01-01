<?php
// 引入公共函数
require_once '../includes/functions.php';

// 检查登录状态和权限
check_permission('admin');

// 检查考评ID是否存在
if (!isset($_GET['id'])) {
    redirect('assessment_management.php');
}

$assessment_id = (int)$_GET['id'];

// 获取考评信息
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM assessments WHERE id = ?");
$stmt->execute([$assessment_id]);
$assessment = $stmt->fetch();

if (!$assessment) {
    redirect('assessment_management.php');
}

// 搜索和筛选
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';

// 处理添加参与人员
if (isset($_POST['add_participants'])) {
    $user_ids = isset($_POST['user_ids']) ? $_POST['user_ids'] : [];
    if (!empty($user_ids)) {
        // 批量添加参与人员
        foreach ($user_ids as $user_id) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO assessment_participants (assessment_id, user_id) VALUES (?, ?)");
                $stmt->execute([$assessment_id, $user_id]);
            } catch (PDOException $e) {
                // 忽略重复插入错误
            }
        }
        redirect("manage_participants.php?id=$assessment_id&success=参与人员添加成功");
    }
}

// 处理移除参与人员
if (isset($_GET['remove_user'])) {
    $user_id = (int)$_GET['remove_user'];
    $stmt = $pdo->prepare("DELETE FROM assessment_participants WHERE assessment_id = ? AND user_id = ?");
    $stmt->execute([$assessment_id, $user_id]);
    redirect("manage_participants.php?id=$assessment_id&success=参与人员移除成功");
}

// 获取当前参与人员列表
$stmt = $pdo->prepare("SELECT u.* FROM users u 
    JOIN assessment_participants ap ON u.id = ap.user_id 
    WHERE ap.assessment_id = ? 
    ORDER BY u.realname");
$stmt->execute([$assessment_id]);
$participants = $stmt->fetchAll();

// 构建查询条件（用于搜索未参与的用户）
$where = [];
$params = [];

// 排除已参与的用户
if (!empty($participants)) {
    $participant_ids = array_column($participants, 'id');
    $placeholders = implode(',', array_fill(0, count($participant_ids), '?'));
    $where[] = "id NOT IN ($placeholders)";
    $params = array_merge($params, $participant_ids);
}

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

// 获取未参与的用户列表
$stmt = $pdo->prepare("SELECT * FROM users $where_clause ORDER BY realname LIMIT 20");
$stmt->execute($params);
$available_users = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>

    <div class="page-title">
        管理考评参与人员 - <?php echo $assessment['title']; ?>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <!-- 参与人员列表 -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">当前参与人员</h3>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="sortable-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>真实姓名</th>
                            <th>角色</th>
                            <th>部门</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($participants)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">暂无参与人员</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($participants as $participant): ?>
                                <tr>
                                    <td><?php echo $participant['id']; ?></td>
                                    <td><?php echo $participant['username']; ?></td>
                                    <td><?php echo $participant['realname']; ?></td>
                                    <td>
                                        <?php 
                                            $role_text = [
                                                'admin' => '管理员',
                                                'teacher' => '教师',
                                                'student' => '学生'
                                            ];
                                            echo $role_text[$participant['role']];
                                        ?>
                                    </td>
                                    <td><?php echo $participant['department']; ?></td>
                                    <td>
                                        <a href="manage_participants.php?id=<?php echo $assessment_id; ?>&remove_user=<?php echo $participant['id']; ?>&success=参与人员移除成功" class="btn btn-danger" onclick="return confirmDelete('确定要移除参与人员 <?php echo $participant['realname']; ?> 吗？')">移除</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 添加参与人员 -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">添加参与人员</h3>
        </div>
        <div class="card-body">
            <form action="manage_participants.php?id=<?php echo $assessment_id; ?>" method="POST">
                <div class="filter-container">
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
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <table class="sortable-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all" onclick="selectAll(this)"></th>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>真实姓名</th>
                                <th>角色</th>
                                <th>部门</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($available_users)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 20px;">暂无可用用户</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($available_users as $user): ?>
                                    <tr>
                                        <td><input type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>"></td>
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
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-actions" style="margin-top: 20px;">
                    <button type="submit" name="add_participants" class="btn btn-success" <?php echo empty($available_users) ? 'disabled' : ''; ?>>添加选中人员</button>
                    <a href="assessment_management.php" class="btn btn-default">返回</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 全选/取消全选功能
        function selectAll(checkbox) {
            const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }
    </script>

<?php include '../includes/footer.php'; ?>