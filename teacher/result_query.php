<?php
// 引入公共函数
require_once '../includes/functions.php';

// 检查登录状态和权限
check_permission('teacher');

// 获取当前用户信息
$user = get_logged_in_user();

// 获取当前教师可参与的考评列表
$stmt = $pdo->prepare("SELECT a.* FROM assessments a 
    JOIN assessment_participants ap ON a.id = ap.assessment_id 
    WHERE ap.user_id = ? 
    ORDER BY a.end_time DESC");
$stmt->execute([$user['id']]);
$assessments = $stmt->fetchAll();

// 搜索和筛选
$selected_assessment_id = isset($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';

// 获取考评项目列表
$items = [];
if ($selected_assessment_id) {
    $stmt = $pdo->prepare("SELECT * FROM assessment_items ORDER BY weight DESC");
    $stmt->execute();
    $items = $stmt->fetchAll();
}

// 处理成绩导出
if (isset($_GET['export']) && $selected_assessment_id) {
    // 获取所有参与人员的成绩
    $where = [];
    $params = [];
    $where[] = "ap.assessment_id = ?";
    $params[] = $selected_assessment_id;

    if ($search) {
        $where[] = "(u.username LIKE ? OR u.realname LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($department) {
        $where[] = "u.department = ?";
        $params[] = $department;
    }

    $where_clause = implode(" AND ", $where);

    // 获取参与人员列表
    $stmt = $pdo->prepare("SELECT DISTINCT u.* FROM users u 
        JOIN assessment_participants ap ON u.id = ap.user_id 
        WHERE $where_clause 
        ORDER BY u.realname");
    $stmt->execute($params);
    $participants = $stmt->fetchAll();

    // 准备导出数据
    $export_data = [];
    foreach ($participants as $participant) {
        // 获取该用户的平均成绩
        $stmt = $pdo->prepare("SELECT AVG(s.score * ai.weight / 100) as total_score 
            FROM scores s 
            JOIN assessment_items ai ON s.item_id = ai.id 
            WHERE s.assessment_id = ? AND s.evaluatee_id = ?");
        $stmt->execute([$selected_assessment_id, $participant['id']]);
        $result = $stmt->fetch();
        $total_score = $result['total_score'] ? round($result['total_score'], 2) : 0;

        $export_data[] = [
            '用户名' => $participant['username'],
            '真实姓名' => $participant['realname'],
            '部门' => $participant['department'],
            '总分' => $total_score
        ];
    }

    // 生成CSV文件
    generate_csv($export_data, "考评成绩_" . date('Ymd') . ".csv");
}

// 获取参与人员的成绩数据
$participants = [];
$departments = [];
if ($selected_assessment_id) {
    // 获取所有部门列表
    $stmt = $pdo->prepare("SELECT DISTINCT department FROM users WHERE department != '' ORDER BY department");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 构建查询条件
    $where = [];
    $params = [];
    $where[] = "ap.assessment_id = ?";
    $params[] = $selected_assessment_id;

    if ($search) {
        $where[] = "(u.username LIKE ? OR u.realname LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($department) {
        $where[] = "u.department = ?";
        $params[] = $department;
    }

    $where_clause = implode(" AND ", $where);

    // 获取参与人员列表
    $stmt = $pdo->prepare("SELECT DISTINCT u.* FROM users u 
        JOIN assessment_participants ap ON u.id = ap.user_id 
        WHERE $where_clause 
        ORDER BY u.realname");
    $stmt->execute($params);
    $participants = $stmt->fetchAll();

    // 获取每个参与者的成绩
    foreach ($participants as &$participant) {
        // 获取该用户的平均成绩
        $stmt = $pdo->prepare("SELECT AVG(s.score * ai.weight / 100) as total_score 
            FROM scores s 
            JOIN assessment_items ai ON s.item_id = ai.id 
            WHERE s.assessment_id = ? AND s.evaluatee_id = ?");
        $stmt->execute([$selected_assessment_id, $participant['id']]);
        $result = $stmt->fetch();
        $participant['total_score'] = $result['total_score'] ? round($result['total_score'], 2) : 0;

        // 获取评分人数
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT s.evaluator_id) as evaluator_count 
            FROM scores s 
            WHERE s.assessment_id = ? AND s.evaluatee_id = ?");
        $stmt->execute([$selected_assessment_id, $participant['id']]);
        $result = $stmt->fetch();
        $participant['evaluator_count'] = $result['evaluator_count'];
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="page-title">
    成绩查询
</div>

<?php if (!empty($assessments)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">选择考评</h3>
        </div>
        <div class="card-body">
            <div class="assessment-selector">
                <?php foreach ($assessments as $assessment): ?>
                    <a href="result_query.php?assessment_id=<?php echo $assessment['id']; ?>"
                        class="btn btn-primary <?php echo $selected_assessment_id == $assessment['id'] ? 'active' : ''; ?>">
                        <?php echo $assessment['title']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ($selected_assessment_id): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">筛选条件</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="result_query.php">
                    <input type="hidden" name="assessment_id" value="<?php echo $selected_assessment_id; ?>">
                    <div class="filter-row">
                        <div class="filter-item">
                            <label for="search">搜索（姓名/用户名）：</label>
                            <input type="text" id="search" name="search" value="<?php echo $search; ?>">
                        </div>
                        <div class="filter-item">
                            <label for="department">部门：</label>
                            <select id="department" name="department">
                                <option value="">全部</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept; ?>" <?php echo $department == $dept ? 'selected' : ''; ?>>
                                        <?php echo $dept; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-item">
                            <button type="submit" class="btn btn-primary">搜索</button>
                            <a href="result_query.php?assessment_id=<?php echo $selected_assessment_id; ?>" class="btn btn-default">重置</a>
                            <a href="result_query.php?assessment_id=<?php echo $selected_assessment_id; ?>&search=<?php echo $search; ?>&department=<?php echo $department; ?>&export=1" class="btn btn-success">导出成绩</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">成绩列表</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="sortable-table">
                        <thead>
                            <tr>
                                <th>序号</th>
                                <th>姓名</th>
                                <th>用户名</th>
                                <th>部门</th>
                                <th>总分</th>
                                <th>评分人数</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($participants)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 20px;">暂无成绩数据</td>
                                </tr>
                            <?php else: ?>
                                <?php $i = 1;
                                foreach ($participants as $participant): ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo $participant['realname']; ?></td>
                                        <td><?php echo $participant['username']; ?></td>
                                        <td><?php echo $participant['department']; ?></td>
                                        <td><?php echo $participant['total_score']; ?></td>
                                        <td><?php echo $participant['evaluator_count']; ?></td>
                                        <td>
                                            <a href="score_detail.php?assessment_id=<?php echo $selected_assessment_id; ?>&user_id=<?php echo $participant['id']; ?>&search=<?php echo $search; ?>&department=<?php echo $department; ?>&export=1" class="btn btn-primary">查看详情</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <p style="text-align: center; padding: 40px; color: #666;">暂无可用的考评成绩，请联系管理员。</p>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>