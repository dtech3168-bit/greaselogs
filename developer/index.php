<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isDeveloperLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pdo = db();

/* =========================
   DATABASE HEALTH
========================= */
$dbStart = microtime(true);
$dbOk = true;
try {
    $pdo->query("SELECT 1");
} catch (Exception $e) {
    $dbOk = false;
}
$dbLatency = round((microtime(true) - $dbStart) * 1000);

/* =========================
   EXTERNAL API HEALTH
========================= */
$apiTargets = [
    'AcctShop'    => 'https://acctshop.com',
    'Paystack'    => 'https://api.paystack.co',
    'Cryptomus'   => 'https://api.cryptomus.com',
    'Korapay'     => 'https://api.korapay.com',
    'NowPayments' => 'https://api.nowpayments.io',
];
$apiHealth = checkApiHealth($apiTargets);

/* =========================
   GATEWAY CONFIG STATUS
========================= */
$gatewayConfig = [];
foreach (['paystack', 'cryptomus', 'korapay', 'nowpayments'] as $gw) {
    $row = getPaymentGateway($gw) ?: [];
    $gatewayConfig[$gw] = [
        'configured' => !empty($row['secret_key']),
        'enabled'    => !empty($row['enabled']),
    ];
}

/* =========================
   SITE STATS
========================= */
$stats = [];
$stats['users']          = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['locked_users']   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE locked_until IS NOT NULL AND locked_until > NOW()")->fetchColumn();
$stats['listings_live']  = (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status='available'")->fetchColumn();
$stats['listings_sold']  = (int)$pdo->query("SELECT COUNT(*) FROM listings WHERE status='sold'")->fetchColumn();
$stats['tx_success']     = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status='success'")->fetchColumn();
$stats['tx_pending']     = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status='pending'")->fetchColumn();
$stats['tx_failed']      = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status='failed'")->fetchColumn();
$stats['revenue']        = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='deposit' AND status='success'")->fetchColumn();

/* =========================
   RECENT TRANSACTIONS
========================= */
$recentTx = $pdo->query("
    SELECT t.*, u.email FROM transactions t
    LEFT JOIN users u ON u.id = t.user_id
    ORDER BY t.transaction_date DESC LIMIT 8
")->fetchAll();

/* =========================
   RECENT ERROR LOG
========================= */
$logLines = getRecentErrorLogLines(60);

/* =========================
   SERVER / PHP INFO
========================= */
$serverInfo = [
    'PHP Version' => PHP_VERSION,
    'Server Time' => date('Y-m-d H:i:s T'),
    'Memory Usage'=> round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
    'Disk Free'   => @disk_free_space('.') ? round(disk_free_space('.') / 1024 / 1024 / 1024, 1) . ' GB' : 'N/A',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="refresh" content="60">
<title>Developer Console // Greaselogs</title>
<style>
:root {
    --neon-cyan: #00f0ff;
    --neon-purple: #a855f7;
    --neon-green: #39ff88;
    --neon-red: #ff3b5c;
    --bg-deep: #05060a;
    --panel: rgba(15, 18, 28, 0.7);
    --panel-border: rgba(0,240,255,0.18);
}
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: 'Courier New', monospace;
    background:
        radial-gradient(circle at 15% 10%, rgba(0,240,255,0.07), transparent 40%),
        radial-gradient(circle at 85% 90%, rgba(168,85,247,0.07), transparent 40%),
        var(--bg-deep);
    background-attachment: fixed;
    color: #e6f7f9;
    min-height: 100vh;
}
body::before {
    content: "";
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(0,240,255,0.035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0,240,255,0.035) 1px, transparent 1px);
    background-size: 42px 42px;
    pointer-events: none;
    z-index: 0;
}
.topbar {
    position: relative; z-index: 1;
    display: flex; justify-content: space-between; align-items: center;
    padding: 18px 30px;
    border-bottom: 1px solid var(--panel-border);
    background: rgba(5,6,10,0.8);
    backdrop-filter: blur(10px);
}
.topbar h1 {
    font-size: 16px; letter-spacing: 3px; margin: 0;
    color: var(--neon-cyan); text-shadow: 0 0 10px rgba(0,240,255,0.5);
}
.topbar .who { font-size: 12px; color: #7d93a3; }
.topbar a.logout {
    color: var(--neon-red); text-decoration: none; font-size: 12px;
    border: 1px solid rgba(255,59,92,0.4); padding: 6px 14px; border-radius: 6px;
}
.topbar a.logout:hover { background: rgba(255,59,92,0.1); }

.wrap { position: relative; z-index: 1; padding: 24px 30px 50px; max-width: 1300px; margin: 0 auto; }

.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; margin-bottom: 24px; }

.panel {
    background: var(--panel);
    border: 1px solid var(--panel-border);
    border-radius: 12px;
    padding: 18px 20px;
    backdrop-filter: blur(10px);
    box-shadow: 0 0 25px rgba(0,240,255,0.05);
}
.panel h2 {
    font-size: 11px; letter-spacing: 2px; color: var(--neon-cyan);
    margin: 0 0 14px; text-transform: uppercase;
    display: flex; align-items: center; gap: 8px;
}
.stat-value { font-size: 26px; font-weight: bold; color: #fff; }
.stat-label { font-size: 11px; color: #7d93a3; margin-top: 4px; }

.status-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 13px; border-bottom: 1px solid rgba(255,255,255,0.05); }
.status-row:last-child { border-bottom: none; }
.dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; margin-right: 8px; }
.dot.up { background: var(--neon-green); box-shadow: 0 0 8px var(--neon-green); animation: blink 2s infinite; }
.dot.down { background: var(--neon-red); box-shadow: 0 0 8px var(--neon-red); }
.dot.warn { background: #f5c542; box-shadow: 0 0 8px #f5c542; }
@keyframes blink { 0%,100%{opacity:1;} 50%{opacity:.4;} }
.latency { color: #7d93a3; font-size: 11px; }

table { width: 100%; border-collapse: collapse; font-size: 12px; }
th { text-align: left; color: var(--neon-cyan); font-weight: normal; padding: 6px 8px; border-bottom: 1px solid var(--panel-border); }
td { padding: 6px 8px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #d5e6ea; }
.badge { padding: 2px 8px; border-radius: 20px; font-size: 10px; }
.badge.success { background: rgba(57,255,136,0.15); color: var(--neon-green); }
.badge.pending { background: rgba(245,197,66,0.15); color: #f5c542; }
.badge.failed { background: rgba(255,59,92,0.15); color: var(--neon-red); }

.log-console {
    background: rgba(0,0,0,0.5);
    border: 1px solid var(--panel-border);
    border-radius: 12px;
    padding: 16px 20px;
    font-size: 11px;
    line-height: 1.6;
    color: #8fe9c9;
    max-height: 280px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-break: break-word;
}
.log-console .empty { color: #567; }
.full-width { grid-column: 1 / -1; }
</style>
</head>
<body>

<div class="topbar">
    <h1>&lt;DEVELOPER_CONSOLE/&gt;</h1>
    <div style="display:flex; align-items:center; gap:16px;">
        <span class="who">USER: <?= htmlspecialchars($_SESSION['developer_username']) ?></span>
        <a class="logout" href="logout.php">LOGOUT</a>
    </div>
</div>

<div class="wrap">

    <!-- CORE STATUS -->
    <div class="grid">
        <div class="panel">
            <h2><span class="dot <?= $dbOk ? 'up' : 'down' ?>"></span>Database</h2>
            <div class="stat-value"><?= $dbOk ? 'ONLINE' : 'DOWN' ?></div>
            <div class="stat-label">Latency: <?= $dbLatency ?>ms</div>
        </div>

        <div class="panel">
            <h2>Registered Users</h2>
            <div class="stat-value"><?= number_format($stats['users']) ?></div>
            <div class="stat-label"><?= $stats['locked_users'] ?> currently locked out</div>
        </div>

        <div class="panel">
            <h2>Listings</h2>
            <div class="stat-value"><?= $stats['listings_live'] ?></div>
            <div class="stat-label"><?= $stats['listings_sold'] ?> sold to date</div>
        </div>

        <div class="panel">
            <h2>Total Revenue</h2>
            <div class="stat-value">₦<?= number_format($stats['revenue']) ?></div>
            <div class="stat-label">from successful deposits</div>
        </div>
    </div>

    <div class="grid">
        <!-- API HEALTH -->
        <div class="panel">
            <h2>External API Reachability</h2>
            <?php foreach ($apiHealth as $label => $r): ?>
                <div class="status-row">
                    <span><span class="dot <?= $r['ok'] ? 'up' : 'down' ?>"></span><?= htmlspecialchars($label) ?></span>
                    <span class="latency"><?= $r['ok'] ? $r['latency_ms'] . 'ms' : ($r['error'] ?: 'unreachable') ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- GATEWAY CONFIG -->
        <div class="panel">
            <h2>Payment Gateways</h2>
            <?php foreach ($gatewayConfig as $gw => $cfg): ?>
                <div class="status-row">
                    <span><span class="dot <?= $cfg['enabled'] ? 'up' : ($cfg['configured'] ? 'warn' : 'down') ?>"></span><?= ucfirst($gw) ?></span>
                    <span class="latency"><?= $cfg['enabled'] ? 'Live' : ($cfg['configured'] ? 'Configured, disabled' : 'Not configured') ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- TX BREAKDOWN -->
        <div class="panel">
            <h2>Transactions</h2>
            <div class="status-row"><span><span class="dot up"></span>Success</span><span><?= $stats['tx_success'] ?></span></div>
            <div class="status-row"><span><span class="dot warn"></span>Pending</span><span><?= $stats['tx_pending'] ?></span></div>
            <div class="status-row"><span><span class="dot down"></span>Failed</span><span><?= $stats['tx_failed'] ?></span></div>
        </div>

        <!-- SERVER INFO -->
        <div class="panel">
            <h2>Server</h2>
            <?php foreach ($serverInfo as $label => $val): ?>
                <div class="status-row"><span><?= htmlspecialchars($label) ?></span><span><?= htmlspecialchars($val) ?></span></div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="grid">
        <!-- RECENT TRANSACTIONS -->
        <div class="panel full-width">
            <h2>Recent Transactions</h2>
            <table>
                <thead>
                    <tr><th>Ref</th><th>User</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php if ($recentTx): foreach ($recentTx as $tx): ?>
                        <tr>
                            <td><?= htmlspecialchars($tx['reference']) ?></td>
                            <td><?= htmlspecialchars($tx['email'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($tx['type']) ?></td>
                            <td>₦<?= number_format($tx['amount'], 2) ?></td>
                            <td><span class="badge <?= htmlspecialchars($tx['status']) ?>"><?= htmlspecialchars($tx['status']) ?></span></td>
                            <td><?= htmlspecialchars($tx['transaction_date']) ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6" style="text-align:center;">No transactions yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid">
        <!-- ERROR LOG -->
        <div class="panel full-width">
            <h2>Recent PHP Errors (live tail)</h2>
            <div class="log-console">
<?php if ($logLines): foreach ($logLines as $line): ?>
<?= htmlspecialchars($line) ?>

<?php endforeach; else: ?>
<span class="empty">No readable log entries found (or log is clean).</span>
<?php endif; ?>
            </div>
        </div>
    </div>

</div>

</body>
</html>
