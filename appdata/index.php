<?php
/**
 * PWA Install Statistics Dashboard
 * Password Protected Admin Page
 */

session_start();

// Hard coded password
define('ADMIN_PASSWORD', 'pizzahub0987');

// Get base URL dynamically
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

// Check if logging out
if (isset($_GET['logout'])) {
    unset($_SESSION['appdata_auth']);
    session_destroy();
    header('Location: ' . $baseUrl . '/');
    exit();
}

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['appdata_auth'] = true;
        // No redirect, just continue to show dashboard
    } else {
        $error = 'Incorrect password';
    }
}

// If not authenticated, show login form
if (!isset($_SESSION['appdata_auth']) || $_SESSION['appdata_auth'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Access | Pizza Hub</title>
        <meta name="robots" content="noindex, nofollow">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-box {
                background: #fff;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                width: 100%;
                max-width: 400px;
                margin: 20px;
            }
            .login-box h1 {
                color: #333;
                margin-bottom: 30px;
                text-align: center;
                font-size: 24px;
            }
            .login-box input[type="password"] {
                width: 100%;
                padding: 15px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 16px;
                margin-bottom: 20px;
                transition: border-color 0.3s;
            }
            .login-box input[type="password"]:focus {
                outline: none;
                border-color: #FFD700;
            }
            .login-box button {
                width: 100%;
                padding: 15px;
                background: #FFD700;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: bold;
                cursor: pointer;
                transition: background 0.3s;
            }
            .login-box button:hover {
                background: #e6c200;
            }
            .error {
                background: #ffebee;
                color: #c62828;
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 20px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>🔒 Admin Access</h1>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter password" required autofocus>
                <button type="submit">Access Dashboard</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// User is authenticated - Show dashboard
require_once '../api/db_config.php';

$conn = getDBConnection();
$stats = [
    'today' => 0,
    'yesterday' => 0,
    'this_week' => 0,
    'this_month' => 0,
    'this_year' => 0,
    'total' => 0
];
$dailyData = [];
$monthlyData = [];
$deviceData = [];
$osData = [];
$browserData = [];

if ($conn) {
    // Today
    $stmt = $conn->query("SELECT COUNT(*) as count FROM pwa_installs WHERE DATE(installed_at) = CURDATE()");
    $stats['today'] = $stmt->fetch()['count'];

    // Yesterday
    $stmt = $conn->query("SELECT COUNT(*) as count FROM pwa_installs WHERE DATE(installed_at) = CURDATE() - INTERVAL 1 DAY");
    $stats['yesterday'] = $stmt->fetch()['count'];

    // This week
    $stmt = $conn->query("SELECT COUNT(*) as count FROM pwa_installs WHERE installed_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stats['this_week'] = $stmt->fetch()['count'];

    // This month
    $stmt = $conn->query("SELECT COUNT(*) as count FROM pwa_installs WHERE MONTH(installed_at) = MONTH(CURDATE()) AND YEAR(installed_at) = YEAR(CURDATE())");
    $stats['this_month'] = $stmt->fetch()['count'];

    // This year
    $stmt = $conn->query("SELECT COUNT(*) as count FROM pwa_installs WHERE YEAR(installed_at) = YEAR(CURDATE())");
    $stats['this_year'] = $stmt->fetch()['count'];

    // Total
    $stmt = $conn->query("SELECT COUNT(*) as count FROM pwa_installs");
    $stats['total'] = $stmt->fetch()['count'];

    // Daily data for last 30 days
    $stmt = $conn->query("
        SELECT DATE(installed_at) as date, COUNT(*) as count
        FROM pwa_installs
        WHERE installed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(installed_at)
        ORDER BY date ASC
    ");
    $dailyData = $stmt->fetchAll();

    // Monthly data for last 12 months
    $stmt = $conn->query("
        SELECT DATE_FORMAT(installed_at, '%Y-%m') as month, COUNT(*) as count
        FROM pwa_installs
        WHERE installed_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(installed_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $monthlyData = $stmt->fetchAll();

    // Device breakdown
    $stmt = $conn->query("SELECT device_type, COUNT(*) as count FROM pwa_installs GROUP BY device_type ORDER BY count DESC");
    $deviceData = $stmt->fetchAll();

    // OS breakdown
    $stmt = $conn->query("SELECT os, COUNT(*) as count FROM pwa_installs GROUP BY os ORDER BY count DESC");
    $osData = $stmt->fetchAll();

    // Browser breakdown
    $stmt = $conn->query("SELECT browser, COUNT(*) as count FROM pwa_installs GROUP BY browser ORDER BY count DESC");
    $browserData = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWA Install Stats | Pizza Hub Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h1 {
            color: #333;
            font-size: 28px;
        }
        .logout-btn {
            background: #ff4444;
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s;
        }
        .logout-btn:hover { background: #cc0000; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            text-align: center;
        }
        .stat-card.highlight {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        }
        .stat-card.highlight .stat-value,
        .stat-card.highlight .stat-label { color: #333; }
        .stat-value {
            font-size: 42px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        @media (max-width: 500px) {
            .charts-section {
                grid-template-columns: 1fr;
            }
        }
        .chart-card {
            background: #fff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .chart-card h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }

        .breakdown-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }
        .breakdown-card {
            background: #fff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .breakdown-card h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .breakdown-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .breakdown-item:last-child { border-bottom: none; }
        .breakdown-name { color: #333; font-weight: 500; }
        .breakdown-count {
            background: #FFD700;
            color: #333;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }

        .no-data {
            text-align: center;
            color: #999;
            padding: 40px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 PWA Install Statistics</h1>
        <a href="?logout=1" class="logout-btn">Logout</a>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['today']); ?></div>
            <div class="stat-label">Today</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['yesterday']); ?></div>
            <div class="stat-label">Yesterday</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['this_week']); ?></div>
            <div class="stat-label">This Week</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['this_month']); ?></div>
            <div class="stat-label">This Month</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['this_year']); ?></div>
            <div class="stat-label">This Year</div>
        </div>
        <div class="stat-card highlight">
            <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
            <div class="stat-label">Total Installs</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-section">
        <div class="chart-card">
            <h3>📈 Daily Installs (Last 30 Days)</h3>
            <div class="chart-container">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h3>📊 Monthly Installs (Last 12 Months)</h3>
            <div class="chart-container">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Breakdowns -->
    <div class="breakdown-section">
        <div class="breakdown-card">
            <h3>📱 Device Type</h3>
            <?php if (empty($deviceData)): ?>
                <div class="no-data">No data yet</div>
            <?php else: ?>
                <?php foreach ($deviceData as $item): ?>
                    <div class="breakdown-item">
                        <span class="breakdown-name"><?php echo htmlspecialchars($item['device_type'] ?: 'Unknown'); ?></span>
                        <span class="breakdown-count"><?php echo number_format($item['count']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="breakdown-card">
            <h3>💻 Operating System</h3>
            <?php if (empty($osData)): ?>
                <div class="no-data">No data yet</div>
            <?php else: ?>
                <?php foreach ($osData as $item): ?>
                    <div class="breakdown-item">
                        <span class="breakdown-name"><?php echo htmlspecialchars($item['os'] ?: 'Unknown'); ?></span>
                        <span class="breakdown-count"><?php echo number_format($item['count']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="breakdown-card">
            <h3>🌐 Browser</h3>
            <?php if (empty($browserData)): ?>
                <div class="no-data">No data yet</div>
            <?php else: ?>
                <?php foreach ($browserData as $item): ?>
                    <div class="breakdown-item">
                        <span class="breakdown-name"><?php echo htmlspecialchars($item['browser'] ?: 'Unknown'); ?></span>
                        <span class="breakdown-count"><?php echo number_format($item['count']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Daily Chart Data
        const dailyLabels = <?php echo json_encode(array_column($dailyData, 'date')); ?>;
        const dailyCounts = <?php echo json_encode(array_map('intval', array_column($dailyData, 'count'))); ?>;

        // Monthly Chart Data
        const monthlyLabels = <?php echo json_encode(array_column($monthlyData, 'month')); ?>;
        const monthlyCounts = <?php echo json_encode(array_map('intval', array_column($monthlyData, 'count'))); ?>;

        // Daily Chart
        new Chart(document.getElementById('dailyChart'), {
            type: 'line',
            data: {
                labels: dailyLabels,
                datasets: [{
                    label: 'Installs',
                    data: dailyCounts,
                    borderColor: '#FFD700',
                    backgroundColor: 'rgba(255, 215, 0, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#FFD700',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    },
                    x: {
                        ticks: {
                            maxTicksLimit: 10
                        }
                    }
                }
            }
        });

        // Monthly Chart
        new Chart(document.getElementById('monthlyChart'), {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Installs',
                    data: monthlyCounts,
                    backgroundColor: '#FFD700',
                    borderColor: '#e6c200',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
</body>
</html>
