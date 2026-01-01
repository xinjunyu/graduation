<?php
// 引入公共函数
require_once '../includes/functions.php';

// 检查登录状态和权限
check_permission('teacher');

// 获取当前用户信息
$user = get_logged_in_user();

// 验证参数
$assessment_id = isset($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : 0;
$evaluatee_id = isset($_GET['evaluatee_id']) ? (int)$_GET['evaluatee_id'] : 0;

if (!$assessment_id || !$evaluatee_id) {
    redirect('scoring.php?error=参数错误');
}

// 验证该考评是否包含当前教师
$stmt = $pdo->prepare("SELECT * FROM assessments a 
    JOIN assessment_participants ap ON a.id = ap.assessment_id 
    WHERE a.id = ? AND ap.user_id = ? AND a.status = 'active'");
$stmt->execute([$assessment_id, $user['id']]);
$assessment = $stmt->fetch();

if (!$assessment) {
    redirect('scoring.php?error=您没有权限参与该考评');
}

// 验证被评分人是否参与了该考评
$stmt = $pdo->prepare("SELECT * FROM users u 
    JOIN assessment_participants ap ON u.id = ap.user_id 
    WHERE u.id = ? AND ap.assessment_id = ?");
$stmt->execute([$evaluatee_id, $assessment_id]);
$evaluatee = $stmt->fetch();

if (!$evaluatee) {
    redirect('scoring.php?error=该用户未参与此考评');
}

// 获取考评项目列表
$stmt = $pdo->prepare("SELECT * FROM assessment_items ORDER BY weight DESC");
$stmt->execute();
$items = $stmt->fetchAll();

// 获取已有的评分记录
$existing_scores = [];
$existing_comment = '';
$stmt = $pdo->prepare("SELECT s.* FROM scores s WHERE s.assessment_id = ? AND s.evaluator_id = ? AND s.evaluatee_id = ?");
$stmt->execute([$assessment_id, $user['id'], $evaluatee_id]);
$scores = $stmt->fetchAll();

if (!empty($scores)) {
    foreach ($scores as $score) {
        $existing_scores[$score['item_id']] = $score['score'];
        $existing_comment = $score['comment'];
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = validate_form($_POST['comment']);
    
    // 开始事务
    $pdo->beginTransaction();
    
    try {
        // 删除已有的评分记录
        $stmt = $pdo->prepare("DELETE FROM scores WHERE assessment_id = ? AND evaluator_id = ? AND evaluatee_id = ?");
        $stmt->execute([$assessment_id, $user['id'], $evaluatee_id]);
        
        // 插入新的评分记录
        foreach ($items as $item) {
            $score = floatval($_POST['scores'][$item['id']]);
            $stmt = $pdo->prepare("INSERT INTO scores (assessment_id, item_id, evaluator_id, evaluatee_id, score, comment) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$assessment_id, $item['id'], $user['id'], $evaluatee_id, $score, $comment]);
        }
        
        // 提交事务
        $pdo->commit();
        
        // 重定向到评分列表
        redirect("scoring.php?assessment_id=$assessment_id&success=评分成功");
    } catch (Exception $e) {
        // 回滚事务
        $pdo->rollBack();
        redirect("score.php?assessment_id=$assessment_id&evaluatee_id=$evaluatee_id&error=评分失败: " . $e->getMessage());
    }
}
?>
<?php include '../includes/header.php'; ?>

    <div class="page-title">
        评分 - <?php echo $assessment['title']; ?> - <?php echo $evaluatee['realname']; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">评分信息</h3>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <form action="score.php?assessment_id=<?php echo $assessment_id; ?>&evaluatee_id=<?php echo $evaluatee_id; ?>" method="POST" onsubmit="return validateForm(this);">
                <div class="form-container">
                    <!-- 评分项目 -->
                    <?php foreach ($items as $item): ?>
                        <div class="form-row">
                            <div class="form-col">
                                <label for="score_<?php echo $item['id']; ?>">
                                    <?php echo $item['name']; ?> (权重: <?php echo $item['weight']; ?>%) *
                                </label>
                                <div class="rating-container">
                                    <div class="rating-stars" data-item-id="<?php echo $item['id']; ?>">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="rating-star <?php echo isset($existing_scores[$item['id']]) && $existing_scores[$item['id']] >= $i ? 'active' : ''; ?>" 
                                                  data-score="<?php echo $i; ?>">
                                                ★
                                            </span>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="rating-value">
                                        <input type="number" name="scores[<?php echo $item['id']; ?>]" 
                                               id="score_<?php echo $item['id']; ?>" 
                                               value="<?php echo isset($existing_scores[$item['id']]) ? $existing_scores[$item['id']] : 0; ?>" 
                                               min="0" max="5" step="0.1" required>
                                    </div>
                                </div>
                                <p class="item-description" style="margin-top: 5px; font-size: 14px; color: #666;">
                                    <?php echo $item['description']; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- 评语 -->
                    <div class="form-row">
                        <div class="form-col">
                            <label for="comment">评语</label>
                            <textarea id="comment" name="comment" rows="5"><?php echo $existing_comment; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">提交评分</button>
                        <a href="scoring.php?assessment_id=<?php echo $assessment_id; ?>" class="btn btn-default">返回</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 评分星级交互
        document.addEventListener('DOMContentLoaded', function() {
            const starContainers = document.querySelectorAll('.rating-stars');
            
            starContainers.forEach(container => {
                const stars = container.querySelectorAll('.rating-star');
                const itemId = container.dataset.itemId;
                const input = document.getElementById(`score_${itemId}`);
                
                // 更新星级显示
                function updateStars(score) {
                    stars.forEach((star, index) => {
                        if (parseFloat(score) >= index + 1) {
                            star.classList.add('active');
                        } else {
                            star.classList.remove('active');
                        }
                    });
                }
                
                // 点击星级评分
                stars.forEach(star => {
                    star.addEventListener('click', () => {
                        const score = star.dataset.score;
                        input.value = score;
                        updateStars(score);
                    });
                });
                
                // 悬停效果
                stars.forEach(star => {
                    star.addEventListener('mouseenter', () => {
                        const score = star.dataset.score;
                        stars.forEach((s, index) => {
                            if (index < parseFloat(score)) {
                                s.style.color = '#faad14';
                            }
                        });
                    });
                    
                    star.addEventListener('mouseleave', () => {
                        stars.forEach(s => {
                            s.style.color = '';
                        });
                        updateStars(input.value);
                    });
                });
                
                // 输入框变化时更新星级
                input.addEventListener('input', () => {
                    updateStars(input.value);
                });
                
                // 初始化星级显示
                updateStars(input.value);
            });
        });
    </script>

<?php include '../includes/footer.php'; ?>