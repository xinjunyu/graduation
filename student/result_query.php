<?php
// 引入公共函数
require_once '../includes/functions.php';

// 检查登录状态和权限
check_permission('student');

// 获取当前用户信息
$user = get_logged_in_user();

// 获取当前学生参与的考评列表
$stmt = $pdo->prepare("SELECT a.* FROM assessments a 
    JOIN assessment_participants ap ON a.id = ap.assessment_id 
    WHERE ap.user_id = ? 
    ORDER BY a.end_time DESC");
$stmt->execute([$user['id']]);
$assessments = $stmt->fetchAll();

// 搜索和筛选
$selected_assessment_id = isset($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : 0;

// 获取考评项目列表
$items = [];
if ($selected_assessment_id) {
    $stmt = $pdo->prepare("SELECT * FROM assessment_items ORDER BY weight DESC");
    $stmt->execute();
    $items = $stmt->fetchAll();
}

// 获取评分记录
$scores_by_evaluator = [];
$item_avg_scores = [];
$total_avg_score = 0;
$evaluator_count = 0;
if ($selected_assessment_id) {
    // 获取评分记录
    $stmt = $pdo->prepare("SELECT s.*, u.realname as evaluator_name, ai.name as item_name, ai.weight 
        FROM scores s 
        JOIN users u ON s.evaluator_id = u.id 
        JOIN assessment_items ai ON s.item_id = ai.id 
        WHERE s.assessment_id = ? AND s.evaluatee_id = ?
        ORDER BY u.realname, ai.weight DESC");
    $stmt->execute([$selected_assessment_id, $user['id']]);
    $score_records = $stmt->fetchAll();
    
    // 按评分人分组
    foreach ($score_records as $record) {
        $evaluator_id = $record['evaluator_id'];
        if (!isset($scores_by_evaluator[$evaluator_id])) {
            $scores_by_evaluator[$evaluator_id] = [
                'evaluator_name' => $record['evaluator_name'],
                'scores' => [],
                'comment' => $record['comment']
            ];
        }
        $scores_by_evaluator[$evaluator_id]['scores'][$record['item_id']] = [
            'score' => $record['score'],
            'item_name' => $record['item_name'],
            'weight' => $record['weight']
        ];
    }
    
    // 计算每个考评项目的平均分和总分
    $total_weight = 0;
    
    // 计算总分权重
    foreach ($items as $item) {
        $total_weight += $item['weight'];
    }
    
    // 计算每个项目的平均分
    foreach ($items as $item) {
        $item_scores = [];
        foreach ($scores_by_evaluator as $evaluator_scores) {
            if (isset($evaluator_scores['scores'][$item['id']])) {
                $item_scores[] = $evaluator_scores['scores'][$item['id']]['score'];
            }
        }
        $avg_score = !empty($item_scores) ? round(array_sum($item_scores) / count($item_scores), 2) : 0;
        $item_avg_scores[$item['id']] = $avg_score;
        $total_avg_score += $avg_score * $item['weight'] / $total_weight;
    }
    
    $total_avg_score = round($total_avg_score, 2);
    $evaluator_count = count($scores_by_evaluator);
}
?>
<?php include '../includes/header.php'; ?>

    <div class="page-title">
        我的成绩
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
            <!-- 基本信息 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">基本信息</h3>
                </div>
                <div class="card-body">
                    <div class="basic-info">
                        <div class="info-row">
                            <div class="info-label">姓名：</div>
                            <div class="info-value"><?php echo $user['realname']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">部门：</div>
                            <div class="info-value"><?php echo $user['department']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">总分：</div>
                            <div class="info-value" style="font-weight: bold; font-size: 18px; color: #1890ff;">
                                <?php echo $total_avg_score; ?>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">评分人数：</div>
                            <div class="info-value"><?php echo $evaluator_count; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 各项目平均分 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">各项目平均分</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="sortable-table">
                            <thead>
                                <tr>
                                    <th>考评项目</th>
                                    <th>权重</th>
                                    <th>平均分</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; padding: 20px;">暂无考评项目</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?php echo $item['name']; ?></td>
                                            <td><?php echo $item['weight']; ?>%</td>
                                            <td><?php echo $item_avg_scores[$item['id']]; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 详细评分记录 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">详细评分记录</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($scores_by_evaluator)): ?>
                        <p style="text-align: center; padding: 20px; color: #666;">暂无评分记录</p>
                    <?php else: ?>
                        <div class="score-records">
                            <?php foreach ($scores_by_evaluator as $evaluator_id => $evaluator_scores): ?>
                                <div class="evaluator-section">
                                    <h4 class="evaluator-name">评分人：<?php echo $evaluator_scores['evaluator_name']; ?></h4>
                                    <div class="table-container">
                                        <table class="sortable-table">
                                            <thead>
                                                <tr>
                                                    <th>考评项目</th>
                                                    <th>权重</th>
                                                    <th>得分</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td><?php echo $item['name']; ?></td>
                                                        <td><?php echo $item['weight']; ?>%</td>
                                                        <td>
                                                            <?php echo isset($evaluator_scores['scores'][$item['id']]) ? $evaluator_scores['scores'][$item['id']]['score'] : '未评分'; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if ($evaluator_scores['comment']): ?>
                                        <div class="comment-section">
                                            <strong>评语：</strong>
                                            <p><?php echo $evaluator_scores['comment']; ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <p style="text-align: center; padding: 40px; color: #666;">您尚未参与任何考评，请联系管理员。</p>
            </div>
        </div>
    <?php endif; ?>

    <style>
        .basic-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            align-items: center;
        }
        
        .info-label {
            font-weight: bold;
            margin-right: 10px;
            width: 80px;
        }
        
        .evaluator-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #fafafa;
            border-radius: 8px;
        }
        
        .evaluator-name {
            margin-bottom: 15px;
            color: #1890ff;
            font-size: 16px;
        }
        
        .comment-section {
            margin-top: 15px;
            padding: 15px;
            background-color: #f0f9ff;
            border-left: 4px solid #1890ff;
            border-radius: 4px;
        }
        
        .comment-section p {
            margin: 0;
            color: #333;
        }
    </style>

<?php include '../includes/footer.php'; ?>