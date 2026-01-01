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
    WHERE ap.user_id = ? AND a.status = 'active' 
    ORDER BY a.end_time");
$stmt->execute([$user['id']]);
$assessments = $stmt->fetchAll();

// 获取考评项目列表
$stmt = $pdo->prepare("SELECT * FROM assessment_items ORDER BY weight DESC");
$stmt->execute();
$items = $stmt->fetchAll();

// 如果选择了某个考评，获取该考评下的参与人员
$selected_assessment = null;
$participants = [];
if (isset($_GET['assessment_id'])) {
    $assessment_id = (int)$_GET['assessment_id'];
    // 验证该考评是否包含当前教师
    $stmt = $pdo->prepare("SELECT * FROM assessments a 
        JOIN assessment_participants ap ON a.id = ap.assessment_id 
        WHERE a.id = ? AND ap.user_id = ? AND a.status = 'active'");
    $stmt->execute([$assessment_id, $user['id']]);
    $selected_assessment = $stmt->fetch();
    
    if ($selected_assessment) {
        // 获取该考评下的所有参与人员
        $stmt = $pdo->prepare("SELECT u.*, 
            (SELECT COUNT(*) FROM scores s WHERE s.assessment_id = ? AND s.evaluator_id = ? AND s.evaluatee_id = u.id) as scored
            FROM users u 
            JOIN assessment_participants ap ON u.id = ap.user_id 
            WHERE ap.assessment_id = ? 
            ORDER BY u.realname");
        $stmt->execute([$assessment_id, $user['id'], $assessment_id]);
        $participants = $stmt->fetchAll();
    }
}
?>
<?php include '../includes/header.php'; ?>

    <div class="page-title">
        评分管理
    </div>

    <?php if (!empty($assessments)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">选择考评</h3>
            </div>
            <div class="card-body">
                <div class="assessment-selector">
                    <?php foreach ($assessments as $assessment): ?>
                        <a href="scoring.php?assessment_id=<?php echo $assessment['id']; ?>" 
                           class="btn btn-primary <?php echo $selected_assessment && $selected_assessment['id'] == $assessment['id'] ? 'active' : ''; ?>">
                            <?php echo $assessment['title']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if ($selected_assessment): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php echo $selected_assessment['title']; ?> - 评分人员列表
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="sortable-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>真实姓名</th>
                                    <th>用户名</th>
                                    <th>部门</th>
                                    <th>角色</th>
                                    <th>评分状态</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($participants)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 20px;">该考评暂无参与人员</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($participants as $participant): ?>
                                        <tr>
                                            <td><?php echo $participant['id']; ?></td>
                                            <td><?php echo $participant['realname']; ?></td>
                                            <td><?php echo $participant['username']; ?></td>
                                            <td><?php echo $participant['department']; ?></td>
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
                                            <td>
                                                <?php if ($participant['scored'] > 0): ?>
                                                    <span style="color: green;">已评分</span>
                                                <?php else: ?>
                                                    <span style="color: red;">未评分</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="score.php?assessment_id=<?php echo $selected_assessment['id']; ?>&evaluatee_id=<?php echo $participant['id']; ?>"
                                                   class="btn btn-primary">
                                                    评分
                                                </a>
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
                <p style="text-align: center; padding: 40px; color: #666;">暂无可用的考评，请联系管理员添加您到相关考评中。</p>
            </div>
        </div>
    <?php endif; ?>

<?php include '../includes/footer.php'; ?>