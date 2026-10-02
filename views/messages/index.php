<?php $layout = 'layouts/main'; ?>
<?php $extraScripts = ['messenger.js']; ?>
<div data-current-user-id="<?= (int)$user['id'] ?>"></div>
<div class="page-header">
    <div>
        <h1 class="page-title">Messages</h1>
        <p class="page-subtitle">Chat with other users in real-time</p>
    </div>
</div>

<div class="messenger-container">
    <!-- Conversation List (Left Panel) -->
    <div class="messenger-sidebar">
        <div class="messenger-sidebar-header">
            <span class="messenger-sidebar-title">Conversations</span>
            <span class="messenger-sidebar-count" id="conversationCount"><?= count($conversations) ?></span>
        </div>
        <div class="messenger-search">
            <i class="fas fa-search"></i>
            <input type="text" id="userSearch" placeholder="Search users..." autocomplete="off">
        </div>
        <div class="user-search-results" id="userSearchResults" style="display:none;"></div>
        <div class="conversation-list" id="conversationList">
            <?php if (empty($conversations)): ?>
                <div class="conversation-empty">
                    <i class="fas fa-comments"></i>
                    <p>No conversations yet</p>
                    <p class="small text-muted">Search for a user above to start chatting</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                <div class="conversation-item" data-user-id="<?= $conv['user_id'] ?>" onclick="Messenger.openConversation(<?= $conv['user_id'] ?>)">
                    <div class="conversation-avatar">
                        <?php if ($conv['avatar']): ?>
                            <img src="<?= UPLOADS_URL ?>/avatars/<?= htmlspecialchars($conv['avatar']) ?>" alt="">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                        <?php if ($conv['is_online'] ?? false): ?>
                            <span class="avatar-status online"></span>
                        <?php else: ?>
                            <span class="avatar-status offline"></span>
                        <?php endif; ?>
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-name">
                            <?= htmlspecialchars($conv['full_name']) ?>
                            <?php if (!$conv['is_active']): ?>
                                <span class="text-muted smaller">(inactive)</span>
                            <?php endif; ?>
                        </div>
                        <div class="conversation-preview">
                            <?php if ($conv['last_message']): ?>
                                <?= htmlspecialchars(\App\Helpers\Format::truncate($conv['last_message'], 50)) ?>
                            <?php elseif ($conv['is_online'] ?? false): ?>
                                <span class="text-muted">Online</span>
                            <?php else: ?>
                                <span class="text-muted">Offline</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="conversation-meta">
                        <?php if ($conv['last_message_time']): ?>
                            <span class="conversation-time"><?= \App\Helpers\Format::relativeTime($conv['last_message_time']) ?></span>
                        <?php endif; ?>
                        <?php if ((int) $conv['unread_count'] > 0): ?>
                            <span class="conversation-badge"><?= (int) $conv['unread_count'] ?></span>
                        <?php endif; ?>
                        <?php if ($conv['role']): ?>
                            <span class="badge badge-sm badge-<?= $conv['role'] === 'admin' ? 'danger' : ($conv['role'] === 'user' ? 'primary' : 'secondary') ?>"><?= ucfirst($conv['role']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chat Window (Right Panel) -->
    <div class="messenger-chat" id="messengerChat">
        <div class="chat-placeholder" id="chatPlaceholder">
            <div class="chat-placeholder-icon">
                <i class="fas fa-comment-dots"></i>
            </div>
            <h3>Select a conversation</h3>
            <p class="text-muted">Choose a user from the left to start chatting</p>
        </div>

        <div class="chat-header" id="chatHeader" style="display:none;">
            <div class="chat-header-user">
                <div class="chat-header-avatar" id="chatHeaderAvatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div>
                    <span class="chat-header-name" id="chatOtherName"></span>
                    <span class="chat-header-status" id="chatOtherStatus"></span>
                </div>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages" style="display:none;">
            <!-- Messages will be dynamically inserted here -->
        </div>

        <div class="chat-composer" id="chatComposer" style="display:none;">
            <form id="messageForm" onsubmit="return Messenger.sendMessage(event)">
                <?= \App\Helpers\Security::csrfField() ?>
                <input type="hidden" name="receiver_id" id="chatReceiverId" value="">
                <div class="composer-input-wrapper">
                    <input type="text" name="message" id="chatMessageInput" class="form-input" placeholder="Type a message..." autocomplete="off" required>
                    <button type="submit" class="btn btn-primary composer-send" title="Send message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>