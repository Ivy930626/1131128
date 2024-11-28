<?php
session_start();

// 模擬的用戶進度資料
if (!isset($_SESSION['user_data'])) {
    $_SESSION['user_data'] = [];
}

// 登入邏輯
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);

    if (!empty($username)) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;

        // 如果是新用戶，初始化進度
        if (!isset($_SESSION['user_data'][$username])) {
            $_SESSION['user_data'][$username] = [
                'number' => rand(1, 100),
                'attempts' => 0,
                'guessed_numbers' => [],
            ];
        }

        $_SESSION['number'] = $_SESSION['user_data'][$username]['number'];
        $_SESSION['attempts'] = $_SESSION['user_data'][$username]['attempts'];
        $_SESSION['guessed_numbers'] = $_SESSION['user_data'][$username]['guessed_numbers'];
        
        // 記錄遊戲開始的時間
        $_SESSION['start_time'] = microtime(true);
        $_SESSION['message'] = "歡迎，{$username}！請輸入1到100間的數字！";
    } else {
        $login_error = "請輸入用戶名！";
    }
}

// 登出邏輯
if (isset($_GET['logout'])) {
    // 保存當前用戶進度
    if (isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
        $_SESSION['user_data'][$username] = [
            'number' => $_SESSION['number'],
            'attempts' => $_SESSION['attempts'],
            'guessed_numbers' => $_SESSION['guessed_numbers'],
        ];
    }

    // 清除所有登入相關的 Session
    session_unset();
    session_destroy();

    // 重定向到登入畫面
    header('Location: game.php');
    exit;
}

// 遊戲邏輯
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guess']) && isset($_SESSION['loggedin'])) {
    $guess = (int)$_POST['guess'];
    $_SESSION['attempts']++;
    $_SESSION['guessed_numbers'][] = $guess; // 將猜過的數字加入陣列

    if ($guess < $_SESSION['number']) {
        $_SESSION['message'] = "太小了！再試一次。";
    } elseif ($guess > $_SESSION['number']) {
        $_SESSION['message'] = "太大了！再試一次。";
    } else {
        $_SESSION['message'] = "恭喜 {$_SESSION['username']} 猜中了 {$_SESSION['number']} ！你共花了 {$_SESSION['attempts']} 次。";

        // 計算遊戲總時間
        $end_time = microtime(true);
        $time_taken = round($end_time - $_SESSION['start_time'], 2); // 取小數點後兩位
        $_SESSION['message'] .= " 總共花了 {$time_taken} 秒。";

        unset($_SESSION['number']); // 遊戲完成後重置
        unset($_SESSION['attempts']);
        unset($_SESSION['guessed_numbers']);
        unset($_SESSION['start_time']); // 重置遊戲開始時間
    }
}

// 重新開始遊戲邏輯
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restart'])) {
    // 重置遊戲狀態
    $username = $_SESSION['username'];
    $_SESSION['user_data'][$username] = [
        'number' => rand(1, 100),
        'attempts' => 0,
        'guessed_numbers' => [],
    ];

    $_SESSION['number'] = $_SESSION['user_data'][$username]['number'];
    $_SESSION['attempts'] = 0;
    $_SESSION['guessed_numbers'] = [];
    $_SESSION['start_time'] = microtime(true); // 記錄新的遊戲開始時間
    $_SESSION['message'] = "遊戲重新開始，開始猜數字吧！";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>猜數字遊戲 - 用戶名登入</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f0f0f0;
        }
        form {
            margin-top: 20px;
        }
        input {
            padding: 10px;
            font-size: 1em;
            margin: 5px;
        }
        #logout {
            margin-top: 20px;
            color: red;
            text-decoration: none;
        }
        #guessed-numbers {
            margin-top: 20px;
            font-size: 1.1em;
            color: #555;
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['loggedin'])): ?>
        <h1>登入猜數字遊戲</h1>
        <?php if (isset($login_error)): ?>
            <p style="color: red;"><?php echo $login_error; ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="用戶名" required>
            <input type="submit" name="login" value="登入">
        </form>
    <?php else: ?>
        <h1>猜數字遊戲</h1>
        <p><?php echo $_SESSION['message']; ?></p>

        <?php if (isset($_SESSION['number'])): ?>
            <!-- 顯示猜過的數字 -->
            <div id="guessed-numbers">
                <p><strong>已猜過的數字：</strong></p>
                <ul>
                    <?php foreach ($_SESSION['guessed_numbers'] as $guessed_number): ?>
                        <li><?php echo $guessed_number; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <form method="post">
                <input type="number" name="guess" min="1" max="100" required>
                <input type="submit" value="猜測">
            </form>
        <?php else: ?>
            <h3>遊戲結束！</h3>
            
            <!-- 遊戲結束後，移除已猜過數字的顯示 -->
            <form method="post">
                <input type="submit" name="restart" value="重新開始">
            </form>
        <?php endif; ?>
        <a id="logout" href="?logout=true">登出</a>
    <?php endif; ?>
</body>
</html>