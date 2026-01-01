-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS assessment_system DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE assessment_system;

-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    realname VARCHAR(50) NOT NULL,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    department VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 考评项目表
CREATE TABLE IF NOT EXISTS assessment_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    weight DECIMAL(5,2) NOT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- 考评表
CREATE TABLE IF NOT EXISTS assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('draft', 'active', 'completed') NOT NULL DEFAULT 'draft',
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- 考评参与表
CREATE TABLE IF NOT EXISTS assessment_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_participant (assessment_id, user_id)
);

-- 评分表
CREATE TABLE IF NOT EXISTS scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT NOT NULL,
    item_id INT NOT NULL,
    evaluator_id INT NOT NULL,
    evaluatee_id INT NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES assessment_items(id),
    FOREIGN KEY (evaluator_id) REFERENCES users(id),
    FOREIGN KEY (evaluatee_id) REFERENCES users(id),
    UNIQUE KEY unique_score (assessment_id, item_id, evaluator_id, evaluatee_id)
);

-- 插入默认管理员用户
INSERT INTO users (username, password, realname, role, department) VALUES 
('admin', md5('admin123'), '管理员', 'admin', '管理部');

-- 插入默认考评项目
INSERT INTO assessment_items (name, description, weight, created_by) VALUES 
('工作态度', '包括责任心、主动性、团队合作等', 25.00, 1),
('工作能力', '包括专业技能、学习能力、解决问题能力等', 30.00, 1),
('工作业绩', '包括工作完成质量、效率、成果等', 35.00, 1),
('综合素质', '包括沟通能力、创新能力、职业道德等', 10.00, 1);

-- 插入默认考评
INSERT INTO assessments (title, start_time, end_time, status, created_by) VALUES 
('2025年度综合考评', '2025-01-01 00:00:00', '2025-12-31 23:59:59', 'active', 1);
