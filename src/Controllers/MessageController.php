<?php
/**
 * Message Controller - handles user-to-user private messaging
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Message;
use App\Models\User;
use App\Services\AuditService;

class MessageController extends Controller
{
    private AuditService $auditService;

    public function __construct()
    {
        parent::__construct();
        $this->auditService = new AuditService();
    }

    /**
     * Show the messenger page
     */
public function index(): void
    {
        $role = $this->currentUser['role'] ?? null;
        $conversations = Message::conversations($this->currentUser['id'], $role);
        $users = Message::getChatUsers($this->currentUser['id'], '', $role);

        // Add is_online flag to each conversation
        $conversations = array_map(function ($conv) {
            $conv['is_online'] = $this->isOnline($conv);
            return $conv;
        }, $conversations);

        $this->render('messages/index', [
            'title' => 'Messages',
            'conversations' => $conversations,
            'users' => $users,
        ]);
    }

    /**
     * AJAX: Search users to start a new conversation
     */
public function users(): void
    {
        $query = $this->request->query('q', '');
        $role = $this->currentUser['role'] ?? null;
        $users = Message::getChatUsers($this->currentUser['id'], $query, $role);

        $result = array_map(function ($user) {
            return [
                'id' => (int) $user['id'],
                'full_name' => $user['full_name'],
                'username' => $user['username'],
                'avatar' => $user['avatar'],
                'role' => $user['role'],
            ];
        }, $users);

        $this->json(['users' => $result]);
    }

/**
     * AJAX: Get full conversation with a user (marks as read)
     */
    public function conversation(int $id): void
    {
$otherUserId = $id;
        $otherUser = User::find($otherUserId);
        if (!$otherUser) {
            $this->json(['error' => 'User not found'], 404);
            return;
        }

        // Operators may only converse with admin accounts
        if (($this->currentUser['role'] ?? null) === 'user' && $otherUser['role'] !== 'admin') {
            $this->json(['error' => 'You can only communicate with admin accounts.'], 403);
            return;
        }

        // Mark incoming messages as read
        Message::markAsRead($otherUserId, $this->currentUser['id']);

        // Get conversation
        $messages = Message::conversation($this->currentUser['id'], $otherUserId);

    $result = array_map(function ($msg) {
            return [
                'id' => (int) $msg['id'],
                'sender_id' => (int) $msg['sender_id'],
                'receiver_id' => (int) $msg['receiver_id'],
                'message' => $msg['message'],
                'sender_name' => $msg['sender_name'],
                'sender_avatar' => $msg['sender_avatar'],
                'created_at' => $msg['created_at'],
                'is_mine' => (int) $msg['sender_id'] === $this->currentUser['id'],
            ];
        }, $messages);

        $this->json([
            'messages' => $result,
            'other_user' => [
                'id' => (int) $otherUser['id'],
                'full_name' => $otherUser['full_name'],
                'username' => $otherUser['username'],
                'avatar' => $otherUser['avatar'],
                'is_active' => (bool) $otherUser['is_active'],
                'is_online' => $this->isOnline($otherUser),
            ],
        ]);
    }

    /**
     * Determine if a user is currently online (active within last 5 minutes)
     */
    private function isOnline(array $user): bool
    {
        if (!$user['is_active']) return false;
        if (empty($user['last_seen'])) return false;
        return (time() - strtotime($user['last_seen'])) < 300; // 5 minutes
    }

    /**
     * AJAX: Send a message
     */
    public function send(): void
    {
        $receiverId = (int) $this->request->input('receiver_id');
        $message = trim($this->request->input('message', ''));

        if (!$receiverId || !$message) {
            $this->json(['error' => 'Missing receiver or message'], 400);
            return;
        }

        // Reject absurdly large messages (message column is TEXT, 64KB max).
        if (mb_strlen($message) > 10000) {
            $this->json(['error' => 'Message is too long (10000 characters max).'], 422);
            return;
        }

        if ($receiverId === $this->currentUser['id']) {
            $this->json(['error' => 'Cannot send message to yourself'], 400);
            return;
        }

// Verify receiver exists and is active
        $receiver = User::find($receiverId);
        if (!$receiver || !$receiver['is_active']) {
            $this->json(['error' => 'User not found or inactive'], 404);
            return;
        }

        // Operators may only send messages to admin accounts
        if (($this->currentUser['role'] ?? null) === 'user' && $receiver['role'] !== 'admin') {
            $this->json(['error' => 'You can only communicate with admin accounts.'], 403);
            return;
        }

        $messageId = Message::send($this->currentUser['id'], $receiverId, $message);

        // Audit: conversation reference only — never the message body (privacy).
        $conversationRef = min((int) $this->currentUser['id'], $receiverId) . '-' . max((int) $this->currentUser['id'], $receiverId);
        $this->auditService->log('message_sent', 'message', $messageId, $conversationRef, null, null, null, 'Message sent');

        $this->json([
            'success' => true,
            'message_id' => $messageId,
            'message' => [
                'id' => $messageId,
                'sender_id' => $this->currentUser['id'],
                'receiver_id' => $receiverId,
                'message' => $message,
                'sender_name' => $this->currentUser['full_name'],
                'sender_avatar' => $this->currentUser['avatar'],
                'created_at' => date('Y-m-d H:i:s'),
                'is_mine' => true,
            ],
        ]);
    }

/**
     * AJAX: Poll for new messages since a given message ID
     */
public function poll(int $id): void
    {
        $otherUserId = $id;
        $sinceId = (int) $this->request->query('since_id', 0);

        // Operators may only converse with admin accounts
        if (($this->currentUser['role'] ?? null) === 'user') {
            $otherUser = User::find($otherUserId);
            if (!$otherUser || $otherUser['role'] !== 'admin') {
                $this->json(['messages' => []]);
                return;
            }
        }

        $messages = Message::conversation($this->currentUser['id'], $otherUserId, $sinceId);

        $result = array_map(function ($msg) {
            return [
                'id' => (int) $msg['id'],
                'sender_id' => (int) $msg['sender_id'],
                'receiver_id' => (int) $msg['receiver_id'],
                'message' => $msg['message'],
                'sender_name' => $msg['sender_name'],
                'sender_avatar' => $msg['sender_avatar'],
                'created_at' => $msg['created_at'],
                'is_mine' => (int) $msg['sender_id'] === $this->currentUser['id'],
            ];
        }, $messages);

        // Mark incoming messages as read
        if (!empty($result)) {
            Message::markAsRead($otherUserId, $this->currentUser['id']);
        }

        $this->json(['messages' => $result]);
    }

    /**
     * AJAX: Get unread message count
     */
    public function unread(): void
    {
        $count = Message::unreadCount($this->currentUser['id']);
        $this->json(['unread' => $count]);
    }

    /**
     * AJAX: Get conversations list (for sidebar refresh)
     */
public function conversations(): void
    {
        $role = $this->currentUser['role'] ?? null;
        $conversations = Message::conversations($this->currentUser['id'], $role);

        // Add is_online flag to each conversation
        $conversations = array_map(function ($conv) {
            $conv['is_online'] = $this->isOnline($conv);
            return $conv;
        }, $conversations);

        $this->json(['conversations' => $conversations]);
    }
}
