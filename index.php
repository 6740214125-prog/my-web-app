<?php
// ดึงค่า Environment Variables จากระบบ
function getEnvVar($key, $default = '') {
    if (!empty($_ENV[$key])) return $_ENV[$key];
    if (!empty($_SERVER[$key])) return $_SERVER[$key];
    $val = getenv($key);
    return ($val !== false && $val !== '') ? $val : $default;
}

$host = getEnvVar('MYSQLHOST', 'mysql.railway.internal');
$port = getEnvVar('MYSQLPORT', '3306');
$user = getEnvVar('MYSQLUSER', 'root');
$pass = getEnvVar('MYSQLPASSWORD', '');
$dbname = getEnvVar('MYSQLDATABASE', 'railway');
$server_name = getEnvVar('SERVER_NAME', '');

// ตรวจจับชื่อ Server จาก URL โดเมนอัตโนมัติหากไม่ได้ตั้งค่าตัวแปร
if (empty($server_name)) {
    $httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if (stripos($httpHost, 'apache') !== false) {
        $server_name = 'APACHE';
    } elseif (stripos($httpHost, 'nginx') !== false) {
        $server_name = 'NGINX';
    } else {
        $server_name = 'WEB SERVER';
    }
}

// เช็กว่ามาจาก Apache หรือไม่
$isApache = (stripos($server_name, 'apache') !== false);

// กำหนดธีมสี
if ($isApache) {
    // ธีมสีแดง/ส้ม สำหรับ APACHE
    $theme = array(
        'bg_body'      => '#fff5f5',
        'card_border'  => '#feb2b2',
        'header_color' => '#c53030',
        'badge_bg'     => '#e53e3e',
        'button_bg'    => '#dd6b20',
        'button_hover' => '#c05621',
        'th_bg'        => '#fed7d7',
        'th_text'      => '#9b2c2c'
    );
} else {
    // ธีมสีฟ้า/เขียว สำหรับ NGINX
    $theme = array(
        'bg_body'      => '#f4f6f9',
        'card_border'  => '#bee3f8',
        'header_color' => '#2b6cb0',
        'badge_bg'     => '#3182ce',
        'button_bg'    => '#38a169',
        'button_hover' => '#2f855a',
        'th_bg'        => '#ebf8ff',
        'th_text'      => '#2c5282'
    );
}

// เชื่อมต่อฐานข้อมูล MySQL
$conn = new mysqli($host, $user, $pass, $dbname, (int)$port);

if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// สร้างตารางหากยังไม่มี
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die("สร้างตาราง users ล้มเหลว: " . $conn->error);

// บันทึกข้อมูลเมื่อ Submit Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

    if (!empty($name) && !empty($email) && !empty($phone)) {
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $name, $email, $phone);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// ดึงข้อมูลผู้ใช้ทั้งหมดมาแสดงผล
$result = $conn->query("SELECT id, name, email, phone FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Contact Form - <?php echo htmlspecialchars($server_name); ?></title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: <?php echo $theme['bg_body']; ?>; 
            margin: 40px; 
        }
        .container { 
            max-width: 700px; 
            background: #fff; 
            padding: 25px; 
            border-radius: 12px; 
            border: 2px solid <?php echo $theme['card_border']; ?>;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); 
            margin: 0 auto; 
        }
        .header-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        h2 { 
            color: <?php echo $theme['header_color']; ?>; 
            margin: 0;
            font-size: 24px;
        }
        .badge { 
            background: <?php echo $theme['badge_bg']; ?>; 
            color: white; 
            padding: 6px 14px; 
            border-radius: 20px; 
            font-size: 14px; 
            font-weight: 600;
            display: inline-block;
        }
        .db-status { 
            background: #f7fafc; 
            border-left: 4px solid <?php echo $theme['badge_bg']; ?>;
            padding: 12px; 
            border-radius: 4px; 
            font-size: 14px; 
            margin-bottom: 20px; 
            color: #4a5568;
        }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #2d3748; }
        input[type="text"], input[type="email"] { 
            width: 100%; 
            padding: 10px; 
            box-sizing: border-box; 
            border: 1px solid #cbd5e0; 
            border-radius: 6px; 
            font-size: 14px;
        }
        button { 
            background: <?php echo $theme['button_bg']; ?>; 
            color: white; 
            border: none; 
            padding: 12px 20px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: bold;
            width: 100%;
            transition: background 0.2s;
        }
        button:hover { 
            background: <?php echo $theme['button_hover']; ?>; 
        }
        h3 {
            color: <?php echo $theme['header_color']; ?>;
            border-bottom: 2px solid <?php echo $theme['card_border']; ?>;
            padding-bottom: 6px;
            margin-top: 25px;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; }
        th { 
            background-color: <?php echo $theme['th_bg']; ?>; 
            color: <?php echo $theme['th_text']; ?>;
            font-weight: bold;
        }
        tr:nth-child(even) { background-color: #f7fafc; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-title">
            <h2>Contact Form</h2>
            <span class="badge">Server: <?php echo htmlspecialchars($server_name); ?></span>
        </div>

        <div class="db-status">
            <strong>Database Status:</strong> เชื่อมต่อสำเร็จ
        </div>

        <h3>เพิ่มข้อมูลผู้ใช้</h3>
        <form method="POST">
            <div class="form-group"><label>ชื่อ-นามสกุล:</label><input type="text" name="name" required></div>
            <div class="form-group"><label>Email:</label><input type="email" name="email" required></div>
            <div class="form-group"><label>เบอร์โทร:</label><input type="text" name="phone" required></div>
            <button type="submit">บันทึกข้อมูล</button>
        </form>

        <h3>ข้อมูลผู้ใช้ทั้งหมด</h3>
        <table>
            <thead><tr><th>ID</th><th>ชื่อ</th><th>Email</th><th>เบอร์โทร</th></tr></thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>