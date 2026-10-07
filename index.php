<?php
// ดึงค่า Environment Variables จากระบบ
// ฟังก์ชันดึงค่า Environment Variables ให้รองรับ FrankenPHP / Railway
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
$server_name = getEnvVar('SERVER_NAME', 'UNKNOWN SERVER');
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
        if (!$stmt) {
            die("เตรียมคำสั่ง INSERT ล้มเหลว: " . $conn->error);
        }

        $stmt->bind_param("sss", $name, $email, $phone);
        if (!$stmt->execute()) {
            die("เพิ่มข้อมูลล้มเหลว: " . $stmt->error);
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// ดึงข้อมูลผู้ใช้ทั้งหมดมาแสดงผล
$result = $conn->query("SELECT id, name, email, phone FROM users ORDER BY id DESC");
if (!$result) {
    die("ดึงข้อมูลล้มเหลว: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Contact Form - <?php echo htmlspecialchars($server_name); ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 40px; }
        .container { max-width: 700px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: 0 auto; }
        h2 { color: #333; margin-top: 0; }
        .badge { background: #007bff; color: white; padding: 4px 8px; border-radius: 4px; font-size: 14px; }
        .db-status { background: #e9ecef; padding: 10px; border-radius: 4px; font-size: 13px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"] { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #dee2e6; padding: 10px; text-align: left; }
        th { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Contact Form <span class="badge">Server: <?php echo htmlspecialchars($server_name); ?></span></h2>
        <div class="db-status">
            <strong>Database Status:</strong> เชื่อมต่อ MySQL สำเร็จ (Host: <?php echo $host; ?>, Port: <?php echo $port; ?>, DB: <?php echo $dbname; ?>, Table: users)
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