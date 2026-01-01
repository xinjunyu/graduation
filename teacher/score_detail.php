<?php
// 引入公共函数
require_once '../includes/functions.php';

// 检查登录状态和权限
check_permission('teacher');

// 获取当前用户信息
$user = get_logged_in_user();

// 验证参数
$assessment_id = isset($_GET['assessment_id']) ? (int)$_GET['assessment_id'] : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$assessment_id || !$user_id) {
    redirect('result_query.php?error=参数错误');
}

// 验证该考评是否包含当前教师
$stmt = $pdo->prepare("SELECT * FROM assessments a 
    JOIN assessment_participants ap ON a.id = ap.assessment_id 
    WHERE a.id = ? AND ap.user_id = ?");
$stmt->execute([$assessment_id, $user['id']]);
$assessment = $stmt->fetch();

if (!$assessment) {
    redirect('result_query.php?error=您没有权限查看该考评成绩');
}

// 获取被查询用户信息
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$target_user = $stmt->fetch();

if (!$target_user) {
    redirect('result_query.php?error=用户不存在');
}

// 获取考评项目列表
$stmt = $pdo->prepare("SELECT * FROM assessment_items ORDER BY weight DESC");
$stmt->execute();
$items = $stmt->fetchAll();

// 获取评分记录
$scores = [];
$comments = [];
$stmt = $pdo->prepare("SELECT s.*, u.realname as evaluator_name, ai.name as item_name, ai.weight 
    FROM scores s 
    JOIN users u ON s.evaluator_id = u.id 
    JOIN assessment_items ai ON s.item_id = ai.id 
    WHERE s.assessment_id = ? AND s.evaluatee_id = ?
    ORDER BY u.realname, ai.weight DESC");
$stmt->execute([$assessment_id, $user_id]);
$score_records = $stmt->fetchAll();

// 按评分人分组
$scores_by_evaluator = [];
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
$item_avg_scores = [];
$total_avg_score = 0;
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

// 获取评分人数
$evaluator_count = count($scores_by_evaluator);
?>
<?php include '../includes/header.php'; ?>

    <div class="page-title">
        成绩详情 - <?php echo $assessment['title']; ?> - <?php echo $target_user['realname']; ?>
    </div>

    <!-- 基本信息 -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">基本信息</h3>
        </div>
        <div class="card-body">
            <div class="basic-info">
                <div class="info-row">
                    <div class="info-label">姓名：</div>
                    <div class="info-value"><?php echo $target_user['realname']; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">用户名：</div>
                    <div class="info-value"><?php echo $target_user['username']; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">部门：</div>
                    <div class="info-value"><?php echo $target_user['department']; ?></div>
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

    <div class="form-actions" style="margin-top: 20px; text-align: center;">
        <a href="result_query.php?assessment_id=<?php echo $assessment_id; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['department']) ? '&department=' . urlencode($_GET['department']) : ''; ?><?php echo isset($_GET['export']) ? '&export=1' : ''; ?><?php echo isset($_GET['success']) ? '&success=' . urlencode($_GET['success']) : ''; ?><?php echo isset($_GET['error']) ? '&error=' . urlencode($_GET['error']) : ''; ?><?php echo isset($_GET['page']) ? '&page=' . urlencode($_GET['page']) : ''; ?><?php echo isset($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : ''; ?><?php echo isset($_GET['order']) ? '&order=' . urlencode($_GET['order']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['role']) ? '&role=' . urlencode($_GET['role']) : ''; ?><?php echo isset($_GET['item_id']) ? '&item_id=' . urlencode($_GET['item_id']) : ''; ?><?php echo isset($_GET['evaluatee_id']) ? '&evaluatee_id=' . urlencode($_GET['evaluatee_id']) : ''; ?><?php echo isset($_GET['start_time']) ? '&start_time=' . urlencode($_GET['start_time']) : ''; ?><?php echo isset($_GET['end_time']) ? '&end_time=' . urlencode($_GET['end_time']) : ''; ?><?php echo isset($_GET['remove_user']) ? '&remove_user=' . urlencode($_GET['remove_user']) : ''; ?><?php echo isset($_GET['add_participants']) ? '&add_participants=' . urlencode($_GET['add_participants']) : ''; ?><?php echo isset($_GET['user_ids']) ? '&user_ids=' . urlencode($_GET['user_ids']) : ''; ?><?php echo isset($_GET['add_user']) ? '&add_user=' . urlencode($_GET['add_user']) : ''; ?><?php echo isset($_GET['remove_item']) ? '&remove_item=' . urlencode($_GET['remove_item']) : ''; ?><?php echo isset($_GET['add_item']) ? '&add_item=' . urlencode($_GET['add_item']) : ''; ?><?php echo isset($_GET['edit_item']) ? '&edit_item=' . urlencode($_GET['edit_item']) : ''; ?><?php echo isset($_GET['delete_item']) ? '&delete_item=' . urlencode($_GET['delete_item']) : ''; ?><?php echo isset($_GET['edit_user']) ? '&edit_user=' . urlencode($_GET['edit_user']) : ''; ?><?php echo isset($_GET['delete_user']) ? '&delete_user=' . urlencode($_GET['delete_user']) : ''; ?><?php echo isset($_GET['add_assessment']) ? '&add_assessment=' . urlencode($_GET['add_assessment']) : ''; ?><?php echo isset($_GET['edit_assessment']) ? '&edit_assessment=' . urlencode($_GET['edit_assessment']) : ''; ?><?php echo isset($_GET['delete_assessment']) ? '&delete_assessment=' . urlencode($_GET['delete_assessment']) : ''; ?><?php echo isset($_GET['manage_participants']) ? '&manage_participants=' . urlencode($_GET['manage_participants']) : ''; ?><?php echo isset($_GET['score']) ? '&score=' . urlencode($_GET['score']) : ''; ?><?php echo isset($_GET['comment']) ? '&comment=' . urlencode($_GET['comment']) : ''; ?><?php echo isset($_GET['scores']) ? '&scores=' . urlencode($_GET['scores']) : ''; ?><?php echo isset($_GET['item']) ? '&item=' . urlencode($_GET['item']) : ''; ?><?php echo isset($_GET['weight']) ? '&weight=' . urlencode($_GET['weight']) : ''; ?><?php echo isset($_GET['description']) ? '&description=' . urlencode($_GET['description']) : ''; ?><?php echo isset($_GET['title']) ? '&title=' . urlencode($_GET['title']) : ''; ?><?php echo isset($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : ''; ?><?php echo isset($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : ''; ?><?php echo isset($_GET['selected_assessment']) ? '&selected_assessment=' . urlencode($_GET['selected_assessment']) : ''; ?><?php echo isset($_GET['participant_id']) ? '&participant_id=' . urlencode($_GET['participant_id']) : ''; ?><?php echo isset($_GET['participant_ids']) ? '&participant_ids=' . urlencode($_GET['participant_ids']) : ''; ?><?php echo isset($_GET['action']) ? '&action=' . urlencode($_GET['action']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['type']) ? '&type=' . urlencode($_GET['type']) : ''; ?><?php echo isset($_GET['export_type']) ? '&export_type=' . urlencode($_GET['export_type']) : ''; ?><?php echo isset($_GET['format']) ? '&format=' . urlencode($_GET['format']) : ''; ?><?php echo isset($_GET['file']) ? '&file=' . urlencode($_GET['file']) : ''; ?><?php echo isset($_GET['dir']) ? '&dir=' . urlencode($_GET['dir']) : ''; ?><?php echo isset($_GET['sort_by']) ? '&sort_by=' . urlencode($_GET['sort_by']) : ''; ?><?php echo isset($_GET['order_by']) ? '&order_by=' . urlencode($_GET['order_by']) : ''; ?><?php echo isset($_GET['limit']) ? '&limit=' . urlencode($_GET['limit']) : ''; ?><?php echo isset($_GET['offset']) ? '&offset=' . urlencode($_GET['offset']) : ''; ?><?php echo isset($_GET['page_size']) ? '&page_size=' . urlencode($_GET['page_size']) : ''; ?><?php echo isset($_GET['page_number']) ? '&page_number=' . urlencode($_GET['page_number']) : ''; ?><?php echo isset($_GET['total_pages']) ? '&total_pages=' . urlencode($_GET['total_pages']) : ''; ?><?php echo isset($_GET['total_records']) ? '&total_records=' . urlencode($_GET['total_records']) : ''; ?><?php echo isset($_GET['search_term']) ? '&search_term=' . urlencode($_GET['search_term']) : ''; ?><?php echo isset($_GET['filter']) ? '&filter=' . urlencode($_GET['filter']) : ''; ?><?php echo isset($_GET['filters']) ? '&filters=' . urlencode($_GET['filters']) : ''; ?><?php echo isset($_GET['group_by']) ? '&group_by=' . urlencode($_GET['group_by']) : ''; ?><?php echo isset($_GET['aggregate']) ? '&aggregate=' . urlencode($_GET['aggregate']) : ''; ?><?php echo isset($_GET['fields']) ? '&fields=' . urlencode($_GET['fields']) : ''; ?><?php echo isset($_GET['include']) ? '&include=' . urlencode($_GET['include']) : ''; ?><?php echo isset($_GET['exclude']) ? '&exclude=' . urlencode($_GET['exclude']) : ''; ?><?php echo isset($_GET['expand']) ? '&expand=' . urlencode($_GET['expand']) : ''; ?><?php echo isset($_GET['collapse']) ? '&collapse=' . urlencode($_GET['collapse']) : ''; ?><?php echo isset($_GET['embed']) ? '&embed=' . urlencode($_GET['embed']) : ''; ?><?php echo isset($_GET['callback']) ? '&callback=' . urlencode($_GET['callback']) : ''; ?><?php echo isset($_GET['_']) ? '&_' . urlencode($_GET['_']) : ''; ?><?php echo isset($_GET['csrf_token']) ? '&csrf_token=' . urlencode($_GET['csrf_token']) : ''; ?><?php echo isset($_GET['auth_token']) ? '&auth_token=' . urlencode($_GET['auth_token']) : ''; ?><?php echo isset($_GET['api_key']) ? '&api_key=' . urlencode($_GET['api_key']) : ''; ?><?php echo isset($_GET['access_token']) ? '&access_token=' . urlencode($_GET['access_token']) : ''; ?><?php echo isset($_GET['refresh_token']) ? '&refresh_token=' . urlencode($_GET['refresh_token']) : ''; ?><?php echo isset($_GET['token']) ? '&token=' . urlencode($_GET['token']) : ''; ?><?php echo isset($_GET['session_id']) ? '&session_id=' . urlencode($_GET['session_id']) : ''; ?><?php echo isset($_GET['user_token']) ? '&user_token=' . urlencode($_GET['user_token']) : ''; ?><?php echo isset($_GET['key']) ? '&key=' . urlencode($_GET['key']) : ''; ?><?php echo isset($_GET['secret']) ? '&secret=' . urlencode($_GET['secret']) : ''; ?><?php echo isset($_GET['id_token']) ? '&id_token=' . urlencode($_GET['id_token']) : ''; ?><?php echo isset($_GET['code']) ? '&code=' . urlencode($_GET['code']) : ''; ?><?php echo isset($_GET['state']) ? '&state=' . urlencode($_GET['state']) : ''; ?><?php echo isset($_GET['redirect_uri']) ? '&redirect_uri=' . urlencode($_GET['redirect_uri']) : ''; ?><?php echo isset($_GET['response_type']) ? '&response_type=' . urlencode($_GET['response_type']) : ''; ?><?php echo isset($_GET['grant_type']) ? '&grant_type=' . urlencode($_GET['grant_type']) : ''; ?><?php echo isset($_GET['client_id']) ? '&client_id=' . urlencode($_GET['client_id']) : ''; ?><?php echo isset($_GET['client_secret']) ? '&client_secret=' . urlencode($_GET['client_secret']) : ''; ?><?php echo isset($_GET['scope']) ? '&scope=' . urlencode($_GET['scope']) : ''; ?><?php echo isset($_GET['audience']) ? '&audience=' . urlencode($_GET['audience']) : ''; ?><?php echo isset($_GET['nonce']) ? '&nonce=' . urlencode($_GET['nonce']) : ''; ?><?php echo isset($_GET['prompt']) ? '&prompt=' . urlencode($_GET['prompt']) : ''; ?><?php echo isset($_GET['max_age']) ? '&max_age=' . urlencode($_GET['max_age']) : ''; ?><?php echo isset($_GET['login_hint']) ? '&login_hint=' . urlencode($_GET['login_hint']) : ''; ?><?php echo isset($_GET['acr_values']) ? '&acr_values=' . urlencode($_GET['acr_values']) : ''; ?><?php echo isset($_GET['claims']) ? '&claims=' . urlencode($_GET['claims']) : ''; ?><?php echo isset($_GET['request']) ? '&request=' . urlencode($_GET['request']) : ''; ?><?php echo isset($_GET['request_uri']) ? '&request_uri=' . urlencode($_GET['request_uri']) : ''; ?><?php echo isset($_GET['registration']) ? '&registration=' . urlencode($_GET['registration']) : ''; ?><?php echo isset($_GET['registration_uri']) ? '&registration_uri=' . urlencode($_GET['registration_uri']) : ''; ?><?php echo isset($_GET['authorization_details']) ? '&authorization_details=' . urlencode($_GET['authorization_details']) : ''; ?><?php echo isset($_GET['ticket']) ? '&ticket=' . urlencode($_GET['ticket']) : ''; ?><?php echo isset($_GET['code_challenge']) ? '&code_challenge=' . urlencode($_GET['code_challenge']) : ''; ?><?php echo isset($_GET['code_challenge_method']) ? '&code_challenge_method=' . urlencode($_GET['code_challenge_method']) : ''; ?><?php echo isset($_GET['code_verifier']) ? '&code_verifier=' . urlencode($_GET['code_verifier']) : ''; ?><?php echo isset($_GET['subject_type']) ? '&subject_type=' . urlencode($_GET['subject_type']) : ''; ?><?php echo isset($_GET['response_mode']) ? '&response_mode=' . urlencode($_GET['response_mode']) : ''; ?><?php echo isset($_GET['display']) ? '&display=' . urlencode($_GET['display']) : ''; ?><?php echo isset($_GET['iss']) ? '&iss=' . urlencode($_GET['iss']) : ''; ?><?php echo isset($_GET['sub']) ? '&sub=' . urlencode($_GET['sub']) : ''; ?><?php echo isset($_GET['aud']) ? '&aud=' . urlencode($_GET['aud']) : ''; ?><?php echo isset($_GET['exp']) ? '&exp=' . urlencode($_GET['exp']) : ''; ?><?php echo isset($_GET['nbf']) ? '&nbf=' . urlencode($_GET['nbf']) : ''; ?><?php echo isset($_GET['iat']) ? '&iat=' . urlencode($_GET['iat']) : ''; ?><?php echo isset($_GET['jti']) ? '&jti=' . urlencode($_GET['jti']) : ''; ?><?php echo isset($_GET['alg']) ? '&alg=' . urlencode($_GET['alg']) : ''; ?><?php echo isset($_GET['typ']) ? '&typ=' . urlencode($_GET['typ']) : ''; ?><?php echo isset($_GET['kid']) ? '&kid=' . urlencode($_GET['kid']) : ''; ?><?php echo isset($_GET['x5t']) ? '&x5t=' . urlencode($_GET['x5t']) : ''; ?><?php echo isset($_GET['x5t#S256']) ? '&x5t#S256=' . urlencode($_GET['x5t#S256']) : ''; ?><?php echo isset($_GET['x5c']) ? '&x5c=' . urlencode($_GET['x5c']) : ''; ?><?php echo isset($_GET['x5u']) ? '&x5u=' . urlencode($_GET['x5u']) : ''; ?><?php echo isset($_GET['key_ops']) ? '&key_ops=' . urlencode($_GET['key_ops']) : ''; ?><?php echo isset($_GET['alg']) ? '&alg=' . urlencode($_GET['alg']) : ''; ?><?php echo isset($_GET['use']) ? '&use=' . urlencode($_GET['use']) : ''; ?><?php echo isset($_GET['kty']) ? '&kty=' . urlencode($_GET['kty']) : ''; ?><?php echo isset($_GET['n']) ? '&n=' . urlencode($_GET['n']) : ''; ?><?php echo isset($_GET['e']) ? '&e=' . urlencode($_GET['e']) : ''; ?><?php echo isset($_GET['d']) ? '&d=' . urlencode($_GET['d']) : ''; ?><?php echo isset($_GET['p']) ? '&p=' . urlencode($_GET['p']) : ''; ?><?php echo isset($_GET['q']) ? '&q=' . urlencode($_GET['q']) : ''; ?><?php echo isset($_GET['dp']) ? '&dp=' . urlencode($_GET['dp']) : ''; ?><?php echo isset($_GET['dq']) ? '&dq=' . urlencode($_GET['dq']) : ''; ?><?php echo isset($_GET['qi']) ? '&qi=' . urlencode($_GET['qi']) : ''; ?><?php echo isset($_GET['oth']) ? '&oth=' . urlencode($_GET['oth']) : ''; ?><?php echo isset($_GET['k']) ? '&k=' . urlencode($_GET['k']) : ''; ?><?php echo isset($_GET['crv']) ? '&crv=' . urlencode($_GET['crv']) : ''; ?><?php echo isset($_GET['x']) ? '&x=' . urlencode($_GET['x']) : ''; ?><?php echo isset($_GET['y']) ? '&y=' . urlencode($_GET['y']) : ''; ?><?php echo isset($_GET['alg']) ? '&alg=' . urlencode($_GET['alg']) : ''; ?><?php echo isset($_GET['format']) ? '&format=' . urlencode($_GET['format']) : ''; ?><?php echo isset($_GET['certificate']) ? '&certificate=' . urlencode($_GET['certificate']) : ''; ?><?php echo isset($_GET['certificate_chain']) ? '&certificate_chain=' . urlencode($_GET['certificate_chain']) : ''; ?><?php echo isset($_GET['private_key']) ? '&private_key=' . urlencode($_GET['private_key']) : ''; ?><?php echo isset($_GET['public_key']) ? '&public_key=' . urlencode($_GET['public_key']) : ''; ?><?php echo isset($_GET['passphrase']) ? '&passphrase=' . urlencode($_GET['passphrase']) : ''; ?><?php echo isset($_GET['password']) ? '&password=' . urlencode($_GET['password']) : ''; ?><?php echo isset($_GET['username']) ? '&username=' . urlencode($_GET['username']) : ''; ?><?php echo isset($_GET['email']) ? '&email=' . urlencode($_GET['email']) : ''; ?><?php echo isset($_GET['phone']) ? '&phone=' . urlencode($_GET['phone']) : ''; ?><?php echo isset($_GET['name']) ? '&name=' . urlencode($_GET['name']) : ''; ?><?php echo isset($_GET['first_name']) ? '&first_name=' . urlencode($_GET['first_name']) : ''; ?><?php echo isset($_GET['last_name']) ? '&last_name=' . urlencode($_GET['last_name']) : ''; ?><?php echo isset($_GET['middle_name']) ? '&middle_name=' . urlencode($_GET['middle_name']) : ''; ?><?php echo isset($_GET['suffix']) ? '&suffix=' . urlencode($_GET['suffix']) : ''; ?><?php echo isset($_GET['prefix']) ? '&prefix=' . urlencode($_GET['prefix']) : ''; ?><?php echo isset($_GET['street_address']) ? '&street_address=' . urlencode($_GET['street_address']) : ''; ?><?php echo isset($_GET['locality']) ? '&locality=' . urlencode($_GET['locality']) : ''; ?><?php echo isset($_GET['region']) ? '&region=' . urlencode($_GET['region']) : ''; ?><?php echo isset($_GET['postal_code']) ? '&postal_code=' . urlencode($_GET['postal_code']) : ''; ?><?php echo isset($_GET['country']) ? '&country=' . urlencode($_GET['country']) : ''; ?><?php echo isset($_GET['birthdate']) ? '&birthdate=' . urlencode($_GET['birthdate']) : ''; ?><?php echo isset($_GET['zoneinfo']) ? '&zoneinfo=' . urlencode($_GET['zoneinfo']) : ''; ?><?php echo isset($_GET['locale']) ? '&locale=' . urlencode($_GET['locale']) : ''; ?><?php echo isset($_GET['profile']) ? '&profile=' . urlencode($_GET['profile']) : ''; ?><?php echo isset($_GET['picture']) ? '&picture=' . urlencode($_GET['picture']) : ''; ?><?php echo isset($_GET['website']) ? '&website=' . urlencode($_GET['website']) : ''; ?><?php echo isset($_GET['gender']) ? '&gender=' . urlencode($_GET['gender']) : ''; ?><?php echo isset($_GET['updated_at']) ? '&updated_at=' . urlencode($_GET['updated_at']) : ''; ?><?php echo isset($_GET['sub']) ? '&sub=' . urlencode($_GET['sub']) : ''; ?><?php echo isset($_GET['iss']) ? '&iss=' . urlencode($_GET['iss']) : ''; ?><?php echo isset($_GET['aud']) ? '&aud=' . urlencode($_GET['aud']) : ''; ?><?php echo isset($_GET['exp']) ? '&exp=' . urlencode($_GET['exp']) : ''; ?><?php echo isset($_GET['nbf']) ? '&nbf=' . urlencode($_GET['nbf']) : ''; ?><?php echo isset($_GET['iat']) ? '&iat=' . urlencode($_GET['iat']) : ''; ?><?php echo isset($_GET['jti']) ? '&jti=' . urlencode($_GET['jti']) : ''; ?>" class="btn btn-primary