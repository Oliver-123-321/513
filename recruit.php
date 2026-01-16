<?php
declare(strict_types=1);

require __DIR__ . '/src/utils.php';
require __DIR__ . '/src/db.php';

$pageTitle = 'Snack Shop · Join Our Team';
$currentPage = 'recruit';
$message = '';
$messageType = '';

// Initialize database tables
initDatabaseTables();

// 保存申请数据到JSON文件（作为备用方案）
function saveApplicationToJson($data) {
    $filePath = __DIR__ . '/data/applications.json';
    
    // 创建数据目录（如果不存在）
    if (!file_exists(dirname($filePath))) {
        mkdir(dirname($filePath), 0777, true);
    }
    
    // 读取现有数据
    $existingData = [];
    if (file_exists($filePath)) {
        $jsonData = file_get_contents($filePath);
        if ($jsonData) {
            $existingData = json_decode($jsonData, true) ?: [];
        }
    }
    
    // 添加新申请
    $existingData[] = $data;
    
    // 保存回文件
    file_put_contents($filePath, json_encode($existingData, JSON_PRETTY_PRINT));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $position_id = trim($_POST['position'] ?? '');
        $motivation = trim($_POST['motivation'] ?? '');
        $file_path = '';
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($position_id) || empty($motivation)) {
            throw new Exception('Please fill in all required fields');
        }
        
        // Handle file upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array(strtolower($file_extension), $allowed_extensions)) {
                throw new Exception('Only PDF, JPG, JPEG, PNG, GIF or WEBP files are allowed');
            }
            
            $unique_filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
            $file_path = $upload_dir . $unique_filename;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
                $file_path = 'uploads/' . $unique_filename; // Store relative path
            } else {
                throw new Exception('File upload failed');
            }
        }
        
        // Prepare data
        $applicationData = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'position_id' => $position_id,
            'motivation' => $motivation,
            'file_path' => $file_path,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            // Try to save to database
            $conn = getDbConnection();
            $stmt = $conn->prepare("
                INSERT INTO Recruit (name, email, phone, position_id, motivation, file_path, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $result = $stmt->execute([
                $name, 
                $email, 
                $phone ?: null, 
                $position_id, 
                $motivation, 
                $file_path ?: null
            ]);
            
            if ($result) {
                $message = 'Application submitted successfully! We will contact you soon. Data saved to database.';
                $messageType = 'success';
                
                // Also save to JSON as backup
                saveApplicationToJson($applicationData);
            } else {
                // If database insert fails, use JSON as fallback
                saveApplicationToJson($applicationData);
                $message = 'Data saved to local JSON file (database insert failed)';
                $messageType = 'warning';
            }
        } catch (Exception $dbException) {
            // If database operation fails, use JSON as fallback
            saveApplicationToJson($applicationData);
            $message = 'Data saved to local JSON file (database connection issue: ' . $dbException->getMessage() . ')';
            $messageType = 'warning';
        }
        
        // Clear form
        $_POST = [];
        
    } catch (Exception $e) {
        $message = 'Submission failed: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// 招聘职位数据
$jobPositions = [
    [
        'id' => 1,
        'title' => 'Snack Chef',
        'department' => 'Production',
        'location' => 'Main Kitchen',
        'type' => 'Full-time',
        'description' => 'We are looking for a skilled snack chef to join our team. You will be responsible for creating delicious homemade snacks that delight our customers. Experience in pastry making and knowledge of healthy ingredients is preferred.',
        'requirements' => [
            '2+ years of experience in food preparation',
            'Passion for creating delicious and healthy snacks',
            'Attention to detail and good organizational skills',
            'Food handler certificate required'
        ]
    ],
    [
        'id' => 2,
        'title' => 'Customer Service Representative',
        'department' => 'Sales',
        'location' => 'Store Front',
        'type' => 'Part-time',
        'description' => 'Join our customer service team to provide excellent service to our snack shop customers. You will assist with orders, answer questions, and ensure a positive shopping experience for everyone who visits our store.',
        'requirements' => [
            'Friendly and outgoing personality',
            'Good communication skills',
            'Ability to work in a fast-paced environment',
            'Previous customer service experience a plus'
        ]
    ],
    [
        'id' => 3,
        'title' => 'Delivery Driver',
        'department' => 'Logistics',
        'location' => 'Multiple Locations',
        'type' => 'Contract',
        'description' => 'We are seeking reliable delivery drivers to ensure our delicious snacks reach our customers fresh and on time. This role involves local deliveries and maintaining a positive brand image while representing our company.',
        'requirements' => [
            'Valid driver\'s license and clean driving record',
            'Reliable vehicle',
            'Good time management skills',
            'Ability to lift up to 30 pounds'
        ]
    ]
];
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=2">
    <style>
        .recruit-hero {
            padding: 4rem 0 3rem;
            background: linear-gradient(to right, rgba(95, 143, 61, 0.1), rgba(95, 143, 61, 0.05));
        }
        
        .job-card {
            background: var(--card);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .job-header {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem 2rem;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .job-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem;
            margin: 0;
            color: var(--primary-dark);
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            color: var(--muted);
            font-size: 0.9rem;
        }
        
        .job-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .job-description {
            margin-bottom: 1.5rem;
        }
        
        .job-requirements h4 {
            margin-bottom: 0.75rem;
            color: var(--text);
        }
        
        .job-requirements ul {
            padding-left: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .job-requirements li {
            margin-bottom: 0.5rem;
        }
        
        .apply-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .company-culture {
            padding: 3rem 0;
            background: var(--accent);
            margin: 3rem 0;
        }
        
        .culture-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .culture-item {
            text-align: center;
            padding: 1.5rem;
        }
        
        .culture-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
        }
        
        .message.success {
            background-color: rgba(95, 143, 61, 0.1);
            color: #5f8f3d;
            border: 1px solid rgba(95, 143, 61, 0.3);
        }
        
        .message.error {
            background-color: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            border: 1px solid rgba(220, 38, 38, 0.3);
        }
        
        .message.warning {
            background-color: rgba(252, 211, 77, 0.1);
            color: #d97706;
            border: 1px solid rgba(252, 211, 77, 0.3);
        }
        
        /* 表单样式优化 */
        .field {
            display: block;
            margin-bottom: 1.5rem;
        }
        
        .field span {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .field input,
        .field select,
        .field textarea {
            display: block;
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Source Sans 3', sans-serif;
            font-size: 1rem;
        }
        
        .field textarea {
            min-height: 120px;
            resize: vertical;
        }
    </style>
</head>
<body class="page-recruit">
<?php include __DIR__ . '/partials/header.php'; ?>

<main>
    <!-- Hero Section -->
    <section class="recruit-hero">
        <div class="container">
            <div class="hero-inner">
                <div class="hero-copy">
                    <p class="eyebrow">Careers</p>
                    <h1>Join Our Snack Shop Team!</h1>
                    <p>
                        We're looking for passionate individuals who love delicious snacks and enjoy creating memorable experiences for our customers.
                        If you're enthusiastic, hardworking, and ready to be part of a growing team, we'd love to hear from you!
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Company Culture Section -->
    <section class="company-culture">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 2rem;">Our Company Culture</h2>
            <div class="culture-grid">
                <div class="culture-item">
                    <div class="culture-icon">🧁</div>
                    <h3>Passion for Food</h3>
                    <p>We believe in creating delicious snacks that bring joy to people's lives.</p>
                </div>
                <div class="culture-item">
                    <div class="culture-icon">🤝</div>
                    <h3>Teamwork</h3>
                    <p>We work together to achieve our goals and support each other's growth.</p>
                </div>
                <div class="culture-item">
                    <div class="culture-icon">🎯</div>
                    <h3>Customer Focus</h3>
                    <p>Our customers are at the heart of everything we do.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Job Listings Section -->
    <section class="container" style="padding: 2rem 0;">
        <h2 style="margin-bottom: 2rem;">Current Openings</h2>
        
        <?php foreach ($jobPositions as $job): ?>
            <div class="job-card">
                <div class="job-header">
                    <h3 class="job-title"><?= $job['title']; ?></h3>
                    <div class="job-meta">
                        <span>🏢 <?= $job['department']; ?></span>
                        <span>📍 <?= $job['location']; ?></span>
                        <span>⏰ <?= $job['type']; ?></span>
                    </div>
                </div>
                
                <div class="job-description">
                    <p><?= $job['description']; ?></p>
                </div>
                
                <div class="job-requirements">
                    <h4>Requirements:</h4>
                    <ul>
                        <?php foreach ($job['requirements'] as $req): ?>
                            <li><?= $req; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <a href="#apply" class="btn primary apply-button">
                    Apply Now
                </a>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- Application Form Section -->
    <section id="apply" class="container" style="padding: 2rem 0 4rem;">
        <div class="filter-card" style="max-width: 800px; margin: 0 auto; padding: 2rem;">
            <h2 style="margin-bottom: 1.5rem;">Apply for a Position</h2>
            <p>Interested in joining our team? Please fill out the form below and we'll get back to you soon!</p>
            
            <!-- 显示消息 -->
            <?php if (!empty($message)): ?>
                <div class="message <?= $messageType; ?>">
                    <?= $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" style="margin-top: 2rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <label class="field">
                        <span>Full Name *</span>
                        <input type="text" name="name" value="<?= $_POST['name'] ?? ''; ?>" required>
                    </label>
                    
                    <label class="field">
                        <span>Email *</span>
                        <input type="email" name="email" value="<?= $_POST['email'] ?? ''; ?>" required>
                    </label>
                    
                    <label class="field">
                        <span>Phone</span>
                        <input type="tel" name="phone" value="<?= $_POST['phone'] ?? ''; ?>">
                    </label>
                    
                    <label class="field">
                        <span>Position Applying For *</span>
                        <select name="position" required>
                            <option value="">Select a position</option>
                            <?php foreach ($jobPositions as $job): ?>
                                <option value="<?= $job['id']; ?>" <?= (isset($_POST['position']) && $_POST['position'] == $job['id']) ? 'selected' : ''; ?>>
                                    <?= $job['title']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
                
                <label class="field">
                    <span>Why do you want to work with us? *</span>
                    <textarea name="motivation" required><?= $_POST['motivation'] ?? ''; ?></textarea>
                </label>
                
                <label class="field">
                    <span>Upload Resume/Image (PDF/JPG/PNG/GIF/WEBP)</span>
                    <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp">
                </label>
                
                <button class="btn primary" type="submit" style="margin-top: 2rem;">
                    Submit Application
                </button>
            </form>
        </div>
    </section>
</main>

<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

<?php
// test_db.php - 小心：上线后请删除或保护此文件
$host = 'sql208.infinityfree.com';
$dbname = 'if0_39943693_wp37';
$user = 'if0_39943693';
$pass = '（你的密码）';
$port = 3306;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // 简单查询 Recruit 表前 10 行
    $stmt = $pdo->query('SELECT id, name, email, phone, position_id, created_at FROM Recruit ORDER BY id DESC LIMIT 10');
    $rows = $stmt->fetchAll();

    header('Content-Type: text/plain; charset=utf-8');
    echo "Connected — Recruit rows:\n\n";
    foreach ($rows as $r) {
        echo "{$r['id']} | {$r['name']} | {$r['email']} | {$r['phone']} | pos:{$r['position_id']} | {$r['created_at']}\n";
    }
} catch (Exception $e) {
    echo "Connection or query failed: " . $e->getMessage();
}