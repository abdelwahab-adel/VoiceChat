<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Services\NotificationService;
use Ramsey\Uuid\Uuid;

class Gift extends Model
{
    protected string $table = 'gifts';
    protected array $fillable = [
        'name','slug','description','image','animation','category','price_coins','rarity',
        'is_animated','is_active','sort_order',
    ];
    protected array $casts = [
        'is_animated' => 'bool',
        'is_active'   => 'bool',
        'price_coins' => 'int',
        'sort_order'  => 'int',
    ];

    public function __construct(
        \App\Core\Database $db,
        private readonly NotificationService $notifications
    ) {
        parent::__construct($db);
    }

    public function active(int $limit = 60): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM gifts WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT ' . (int) $limit
        );
    }

    public function send(int $giftId, int $senderId, int $receiverId, int $quantity = 1, ?int $roomId = null, ?int $agencyId = null, ?string $message = null, bool $anonymous = false): array
    {
        $gift = $this->find($giftId);
        if (!$gift || !$gift['is_active']) {
            throw new \InvalidArgumentException('Gift not available');
        }
        $quantity = max(1, $quantity);
        $totalCoins = (int) $gift['price_coins'] * $quantity;

        $sender = $this->db->fetchOne('SELECT coins FROM users WHERE id = :id', ['id' => $senderId]);
        if (!$sender) throw new \RuntimeException('Sender not found');
        if ((int) $sender['coins'] < $totalCoins) {
            throw new \RuntimeException('Insufficient coins');
        }

        $txId = $this->db->transaction(function ($db) use ($gift, $senderId, $receiverId, $quantity, $totalCoins, $roomId, $agencyId, $message, $anonymous) {
            $newSenderBalance = (int) $sender['coins'] - $totalCoins;
            $db->update('users', ['coins' => $newSenderBalance], 'id = :id', ['id' => $senderId]);
            $db->insert('coin_transactions', [
                'user_id' => $senderId, 'type' => 'gift_sent', 'amount' => -$totalCoins,
                'balance_after' => $newSenderBalance, 'description' => "Sent {$quantity}x {$gift['name']}",
            ]);

            $receiver = $db->fetchOne('SELECT coins FROM users WHERE id = :id', ['id' => $receiverId]);
            if ($receiver) {
                $newReceiverBalance = (int) $receiver['coins'] + $totalCoins;
                $db->update('users', ['coins' => $newReceiverBalance], 'id = :id', ['id' => $receiverId]);
                $db->insert('coin_transactions', [
                    'user_id' => $receiverId, 'type' => 'gift_received', 'amount' => $totalCoins,
                    'balance_after' => $newReceiverBalance, 'description' => "Received {$quantity}x {$gift['name']}",
                ]);
            }
            return $db->insert('gift_transactions', [
                'gift_id'      => $gift['id'],
                'sender_id'    => $senderId,
                'receiver_id'  => $receiverId,
                'room_id'      => $roomId,
                'agency_id'    => $agencyId,
                'quantity'     => $quantity,
                'coins_total'  => $totalCoins,
                'message'      => $message,
                'is_anonymous' => $anonymous ? 1 : 0,
            ]);
        });

        // Notify receiver
        $this->notifications->create(
            $receiverId,
            'gift_received',
            $anonymous ? 'You received a gift' : 'You received a gift from ' . ($this->db->fetchValue('SELECT username FROM users WHERE id = :id', ['id' => $senderId]) ?? 'someone'),
            "You received {$quantity}x {$gift['name']}",
            ['gift_id' => $gift['id'], 'sender_id' => $senderId, 'room_id' => $roomId, 'agency_id' => $agencyId, 'coins' => $totalCoins],
            $roomId ? url('rooms/' . $roomId) : url('profile/' . $senderId)
        );

        return [
            'transaction_id' => (int) $txId,
            'coins_spent'    => $totalCoins,
            'sender_balance' => (int) $this->db->fetchValue('SELECT coins FROM users WHERE id = :id', ['id' => $senderId]),
        ];
    }

    public function history(int $userId, string $direction = 'received', int $limit = 30, int $offset = 0): array
    {
        $col = $direction === 'sent' ? 'sender_id' : 'receiver_id';
        $sql = "SELECT gt.*, g.name as gift_name, g.image as gift_image, g.rarity,
                       sender.username as sender_username, sender.display_name as sender_display_name, sender.avatar as sender_avatar,
                       receiver.username as receiver_username, receiver.display_name as receiver_display_name, receiver.avatar as receiver_avatar
                FROM gift_transactions gt
                JOIN gifts g ON g.id = gt.gift_id
                JOIN users sender ON sender.id = gt.sender_id
                JOIN users receiver ON receiver.id = gt.receiver_id
                WHERE gt.{$col} = :u
                ORDER BY gt.id DESC LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset;
        return $this->db->fetchAll($sql, ['u' => $userId]);
    }

    public function top(int $limit = 20, string $by = 'received'): array
    {
        $col = $by === 'sent' ? 'sender_id' : 'receiver_id';
        $rows = $this->db->fetchAll(
            "SELECT u.id, u.username, u.display_name, u.avatar, u.level,
                    COALESCE(SUM(gt.coins_total), 0) as total_coins,
                    COUNT(gt.id) as total_gifts
             FROM users u
             JOIN gift_transactions gt ON gt.{$col} = u.id
             GROUP BY u.id
             ORDER BY total_coins DESC
             LIMIT " . (int) $limit
        );
        return $rows;
    }
}
