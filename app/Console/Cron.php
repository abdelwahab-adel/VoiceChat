<?php
/**
 * VoiceChat — Cron entry point
 * 
 * Add to crontab:
 *   * * * * * php /path/to/voicechat/cron.php >> /path/to/storage/logs/cron.log 2>&1
 * 
 * Runs maintenance tasks:
 *  - Update online status
 *  - Clean expired bans
 *  - Clean old password resets
 *  - Daily bonus
 *  - Log activity cleanup
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;
use App\Services\LoggerService;

$app = Application::bootstrap(__DIR__);
$db  = $app->getDb();
$logger = $app->getService('logger');

$logger->info('Cron run started');

$tasks = [
    'expired_bans' => function() use ($db, $logger) {
        $rows = $db->query(
            'UPDATE bans SET is_active = 0 WHERE is_active = 1 AND expires_at IS NOT NULL AND expires_at < NOW()'
        );
        // Unban affected users
        $db->query('UPDATE users u JOIN bans b ON b.user_id = u.id SET u.status = "active" WHERE b.is_active = 0 AND u.status = "banned" AND b.expires_at < NOW()');
        $logger->info('Expired bans cleaned', ['rows' => $rows->rowCount()]);
    },
    'expired_password_resets' => function() use ($db, $logger) {
        $rows = $db->delete('password_resets', 'expires_at < NOW() - INTERVAL 1 DAY');
        $logger->info('Expired password resets cleaned', ['rows' => $rows]);
    },
    'old_login_history' => function() use ($db, $logger) {
        $rows = $db->query('DELETE FROM login_history WHERE created_at < NOW() - INTERVAL 90 DAY');
        $logger->info('Old login history cleaned', ['rows' => $rows->rowCount()]);
    },
    'old_activity_logs' => function() use ($db, $logger) {
        $rows = $db->query('DELETE FROM activity_logs WHERE created_at < NOW() - INTERVAL 30 DAY');
        $logger->info('Old activity logs cleaned', ['rows' => $rows->rowCount()]);
    },
    'delivered_ws_events' => function() use ($db, $logger) {
        $rows = $db->query('DELETE FROM ws_events WHERE delivered = 1 AND delivered_at < NOW() - INTERVAL 1 HOUR');
        $logger->info('Old WS events cleaned', ['rows' => $rows->rowCount()]);
    },
    'offline_users' => function() use ($db, $logger) {
        $rows = $db->query('UPDATE users SET online_status = "offline" WHERE online_status != "offline" AND last_seen_at < NOW() - INTERVAL 5 MINUTE');
        $logger->info('Stale online users marked offline', ['rows' => $rows->rowCount()]);
    },
    'daily_bonus' => function() use ($db, $logger) {
        // Give daily bonus once per day
        $today = date('Y-m-d');
        $rows = $db->query(
            'SELECT u.id FROM users u
             WHERE u.status = "active"
               AND u.id NOT IN (SELECT user_id FROM coin_transactions WHERE type = "daily_bonus" AND DATE(created_at) = :d)
             LIMIT 500',
            ['d' => $today]
        );
        $count = 0;
        while ($u = $rows->fetch()) {
            $db->insert('coin_transactions', [
                'user_id' => $u['id'], 'type' => 'daily_bonus',
                'amount' => (int) ($_ENV['DAILY_BONUS_COINS'] ?? 20),
                'balance_after' => 0, 'description' => 'Daily login bonus',
            ]);
            $db->query('UPDATE users SET coins = coins + :c WHERE id = :id', ['c' => (int) ($_ENV['DAILY_BONUS_COINS'] ?? 20), 'id' => $u['id']]);
            $count++;
        }
        $logger->info('Daily bonus distributed', ['count' => $count]);
    },
];

foreach ($tasks as $name => $fn) {
    try {
        $fn();
    } catch (\Throwable $e) {
        $logger->error("Task $name failed: " . $e->getMessage());
    }
}

$logger->info('Cron run finished');
echo "Cron completed at " . date('Y-m-d H:i:s') . PHP_EOL;
