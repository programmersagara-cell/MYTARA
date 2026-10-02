/**
 * Messenger - Real-time user-to-user messaging with AJAX polling
 */
var Messenger = (function() {
    'use strict';

    var currentUserId = null;
    var currentOtherUserId = null;
    var lastMessageId = 0;
    var pollInterval = null;
    var POLL_DELAY = 2500; // 2.5 seconds
    var isSending = false;

    /**
     * Initialize the messenger
     */
    function init() {
        // Get current user ID from the page
        var userEl = document.querySelector('[data-current-user-id]');
        if (userEl) {
            currentUserId = parseInt(userEl.dataset.currentUserId, 10);
        }

        // User search
        var searchInput = document.getElementById('userSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                var query = this.value.trim();
                if (query.length < 1) {
                    document.getElementById('userSearchResults').style.display = 'none';
                    return;
                }
                searchUsers(query);
            });
        }

        // Start unread count polling
        startUnreadPolling();
    }

    /**
     * Search users for new conversation
     */
    function searchUsers(query) {
        var url = BASE_PATH + '/messages/users?q=' + encodeURIComponent(query);
        var resultsEl = document.getElementById('userSearchResults');

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.users || data.users.length === 0) {
                    resultsEl.innerHTML = '<div class="search-result-item disabled">No users found</div>';
                    resultsEl.style.display = 'block';
                    return;
                }
                var html = '';
                data.users.forEach(function(user) {
                    var avatarHtml = user.avatar
                        ? '<img src="' + UPLOADS_URL + '/avatars/' + encodeURIComponent(user.avatar) + '" alt="">'
                        : '<i class="fas fa-user-circle"></i>';
                    html += '<div class="search-result-item" onclick="Messenger.openConversation(' + user.id + ')">'
                        + '<div class="search-result-avatar">' + avatarHtml + '</div>'
                        + '<div class="search-result-info">'
                        + '<strong>' + escHtml(user.full_name) + '</strong>'
                        + '<span class="text-muted small">@' + escHtml(user.username) + '</span>'
                        + '</div>'
                        + '</div>';
                });
                resultsEl.innerHTML = html;
                resultsEl.style.display = 'block';
            })
            .catch(function() {
                resultsEl.innerHTML = '<div class="search-result-item disabled">Search failed</div>';
                resultsEl.style.display = 'block';
            });
    }

    /**
     * Open a conversation with a user
     */
    function openConversation(userId) {
        // Hide search results
        document.getElementById('userSearchResults').style.display = 'none';
        document.getElementById('userSearch').value = '';

        // Highlight active conversation
        document.querySelectorAll('.conversation-item').forEach(function(el) {
            el.classList.remove('active');
        });
        var convItem = document.querySelector('.conversation-item[data-user-id="' + userId + '"]');
        if (convItem) {
            convItem.classList.add('active');
            // Update chat header avatar from the conversation item
            var convAvatar = convItem.querySelector('.conversation-avatar');
            var headerAvatar = document.getElementById('chatHeaderAvatar');
            if (convAvatar && headerAvatar) {
                headerAvatar.innerHTML = convAvatar.innerHTML;
            }
        }

        // Update state
        currentOtherUserId = userId;
        lastMessageId = 0;

        // Show chat UI
        document.getElementById('chatPlaceholder').style.display = 'none';
        document.getElementById('chatHeader').style.display = 'flex';
        document.getElementById('chatMessages').style.display = 'flex';
        document.getElementById('chatComposer').style.display = 'block';
        document.getElementById('chatReceiverId').value = userId;

        // Fetch conversation
        fetchConversation(userId);

        // Start polling
        startPolling(userId);
    }

    /**
     * Fetch full conversation
     */
    function fetchConversation(otherUserId) {
        var url = BASE_PATH + '/messages/conversation/' + otherUserId;
        var messagesEl = document.getElementById('chatMessages');

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.error) {
                    messagesEl.innerHTML = '<div class="chat-error">' + escHtml(data.error) + '</div>';
                    return;
                }

                // Update header
                var other = data.other_user;
                document.getElementById('chatOtherName').textContent = other.full_name;
                var statusEl = document.getElementById('chatOtherStatus');
                if (other.is_online) {
                    statusEl.innerHTML = '<span class="online-dot"></span> Online';
                } else {
                    statusEl.innerHTML = '<span class="offline-dot"></span> Offline';
                }

                // Update header avatar
                var headerAvatar = document.getElementById('chatHeaderAvatar');
                if (headerAvatar) {
                    if (other.avatar) {
                        headerAvatar.innerHTML = '<img src="' + UPLOADS_URL + '/avatars/' + encodeURIComponent(other.avatar) + '" alt="">';
                    } else {
                        headerAvatar.innerHTML = '<i class="fas fa-user-circle"></i>';
                    }
                }

                // Render messages
                renderMessages(data.messages);

                // Update last message ID
                if (data.messages.length > 0) {
                    lastMessageId = data.messages[data.messages.length - 1].id;
                }

                // Clear unread badge on this conversation
                var convItem = document.querySelector('.conversation-item[data-user-id="' + otherUserId + '"]');
                if (convItem) {
                    var badge = convItem.querySelector('.conversation-badge');
                    if (badge) badge.remove();
                }

                // Messages were just marked read — refresh the global nav badge now
                if (typeof window.refreshMessagesUnreadCount === 'function') {
                    window.refreshMessagesUnreadCount();
                }
            })
            .catch(function() {
                messagesEl.innerHTML = '<div class="chat-error">Failed to load conversation</div>';
            });
    }

    /**
     * Render messages in the chat window
     */
    function renderMessages(messages) {
        var messagesEl = document.getElementById('chatMessages');
        messagesEl.innerHTML = '';

        if (messages.length === 0) {
            messagesEl.innerHTML = '<div class="chat-empty">No messages yet. Say hello!</div>';
            return;
        }

        messages.forEach(function(msg) {
            var div = document.createElement('div');
            div.className = 'chat-message' + (msg.is_mine ? ' message-mine' : ' message-theirs');

            var time = formatTime(msg.created_at);
            var avatarHtml = msg.sender_avatar
                ? '<img src="' + UPLOADS_URL + '/avatars/' + encodeURIComponent(msg.sender_avatar) + '" alt="">'
                : '<i class="fas fa-user-circle"></i>';

            div.innerHTML = '<div class="message-avatar">' + avatarHtml + '</div>'
                + '<div class="message-bubble">'
                + '<div class="message-text">' + escHtml(msg.message) + '</div>'
                + '<div class="message-time">' + time + '</div>'
                + '</div>';

            messagesEl.appendChild(div);
        });

        // Scroll to bottom
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    /**
     * Send a message
     */
    function sendMessage(event) {
        event.preventDefault();
        if (isSending) return false;

        var input = document.getElementById('chatMessageInput');
        var message = input.value.trim();
        if (!message) return false;

        var receiverId = document.getElementById('chatReceiverId').value;
        if (!receiverId) return false;

        isSending = true;

        var formData = new FormData();
        formData.append('receiver_id', receiverId);
        formData.append('message', message);
        formData.append('_csrf_token', getCsrfToken());

        var url = BASE_PATH + '/messages/send';

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            isSending = false;
            if (data.success) {
                input.value = '';
                // Append the sent message to the chat
                appendMessage(data.message);
                // Update last message ID
                if (data.message_id > lastMessageId) {
                    lastMessageId = data.message_id;
                }
                // Update conversation list
                refreshConversations();
            } else {
                alert(data.error || 'Failed to send message');
            }
        })
        .catch(function() {
            isSending = false;
            alert('Failed to send message');
        });

        return false;
    }

    /**
     * Append a single message to the chat window
     */
    function appendMessage(msg) {
        var messagesEl = document.getElementById('chatMessages');

        // Remove empty state if present
        var emptyEl = messagesEl.querySelector('.chat-empty');
        if (emptyEl) emptyEl.remove();

        var div = document.createElement('div');
        div.className = 'chat-message' + (msg.is_mine ? ' message-mine' : ' message-theirs');

        var time = formatTime(msg.created_at);
        var avatarHtml = msg.sender_avatar
            ? '<img src="' + UPLOADS_URL + '/avatars/' + encodeURIComponent(msg.sender_avatar) + '" alt="">'
            : '<i class="fas fa-user-circle"></i>';

        div.innerHTML = '<div class="message-avatar">' + avatarHtml + '</div>'
            + '<div class="message-bubble">'
            + '<div class="message-text">' + escHtml(msg.message) + '</div>'
            + '<div class="message-time">' + time + '</div>'
            + '</div>';

        messagesEl.appendChild(div);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    /**
     * Poll for new messages
     */
    function startPolling(otherUserId) {
        stopPolling();
        pollInterval = setInterval(function() {
            pollConversation(otherUserId);
        }, POLL_DELAY);
    }

    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    function pollConversation(otherUserId) {
        var url = BASE_PATH + '/messages/poll/' + otherUserId + '?since_id=' + lastMessageId;

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(function(msg) {
                        appendMessage(msg);
                        if (msg.id > lastMessageId) {
                            lastMessageId = msg.id;
                        }
                    });
                    // Refresh conversation list to update previews
                    refreshConversations();
                }
            })
            .catch(function() {
                // Silently fail on poll
            });
    }

/**
     * Refresh the conversation list sidebar (shows ALL users)
     */
    function refreshConversations() {
        var url = BASE_PATH + '/messages/conversations';
        var listEl = document.getElementById('conversationList');

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.conversations || data.conversations.length === 0) {
                    listEl.innerHTML = '<div class="conversation-empty">'
                        + '<i class="fas fa-comments"></i><p>No users found</p>'
                        + '<p class="small text-muted">Search for a user above to start chatting</p>'
                        + '</div>';
                    return;
                }

                var html = '';
                data.conversations.forEach(function(conv) {
                    var avatarHtml = conv.avatar
                        ? '<img src="' + UPLOADS_URL + '/avatars/' + encodeURIComponent(conv.avatar) + '" alt="">'
                        : '<i class="fas fa-user-circle"></i>';

                    var activeClass = (parseInt(conv.user_id, 10) === currentOtherUserId) ? ' active' : '';
                    var unreadBadge = parseInt(conv.unread_count, 10) > 0
                        ? '<span class="conversation-badge">' + conv.unread_count + '</span>'
                        : '';

                    // Role badge color
                    var roleBadge = '';
                    if (conv.role) {
                        var badgeClass = conv.role === 'admin' ? 'danger' : (conv.role === 'user' ? 'primary' : 'secondary');
                        roleBadge = '<span class="badge badge-sm badge-' + badgeClass + '">' + conv.role.charAt(0).toUpperCase() + conv.role.slice(1) + '</span>';
                    }

                    // Avatar status indicator (based on real online status)
                    var avatarStatus = conv.is_online
                        ? '<span class="avatar-status online"></span>'
                        : '<span class="avatar-status offline"></span>';

                    // Preview text
                    var preview = '';
                    if (conv.last_message) {
                        preview = escHtml(conv.last_message.substring(0, 50));
                    } else if (conv.is_online) {
                        preview = '<span class="text-muted">Online</span>';
                    } else {
                        preview = '<span class="text-muted">Offline</span>';
                    }

                    html += '<div class="conversation-item' + activeClass + '" data-user-id="' + conv.user_id + '" onclick="Messenger.openConversation(' + conv.user_id + ')">'
                        + '<div class="conversation-avatar">' + avatarHtml + avatarStatus + '</div>'
                        + '<div class="conversation-info">'
                        + '<div class="conversation-name">' + escHtml(conv.full_name) + '</div>'
                        + '<div class="conversation-preview">' + preview + '</div>'
                        + '</div>'
                        + '<div class="conversation-meta">'
                        + (conv.last_message_time ? '<span class="conversation-time">' + relativeTime(conv.last_message_time) + '</span>' : '')
                        + unreadBadge
                        + roleBadge
                        + '</div>'
                        + '</div>';
                });
                listEl.innerHTML = html;

                // Update conversation count
                var countEl = document.getElementById('conversationCount');
                if (countEl) {
                    countEl.textContent = data.conversations.length;
                }
            })
            .catch(function() {});
    }

    /**
     * Poll for unread count (for nav badge)
     * The shared layout (layouts/main.php) runs a global unread updater on
     * every page — only ONE poller is ever allowed.
     */
    function startUnreadPolling() {
        if (window.__msgUnreadPollStarted) {
            return;
        }
        window.__msgUnreadPollStarted = true;
        setInterval(function() {
            var url = BASE_PATH + '/messages/unread';
            fetch(url)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    updateNavBadge(data.unread || 0);
                })
                .catch(function() {});
        }, 10000); // Every 10 seconds
    }

    function updateNavBadge(count) {
        var badge = document.getElementById('messagesBadge');
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('empty');
            badge.style.display = 'inline-flex';
        } else {
            badge.textContent = '0';
            badge.classList.add('empty');
            badge.style.display = 'none';
        }
    }

    // ─── Utility Functions ───

    function formatTime(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr.replace(' ', 'T') + (dateStr.indexOf('Z') === -1 ? 'Z' : ''));
        if (isNaN(d.getTime())) return '';
        var timeStr = d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        var dateStrFull = d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
        return dateStrFull + ', ' + timeStr;
    }

    function relativeTime(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr.replace(' ', 'T') + (dateStr.indexOf('Z') === -1 ? 'Z' : ''));
        if (isNaN(d.getTime())) return '';
        var now = new Date();
        var isToday = d.toDateString() === now.toDateString();
        var timeStr = d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        var dateStrFull = d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });

        if (isToday) {
            return 'Today, ' + timeStr;
        }
        var yesterday = new Date(now);
        yesterday.setDate(now.getDate() - 1);
        if (d.toDateString() === yesterday.toDateString()) {
            return 'Yesterday, ' + timeStr;
        }
        return dateStrFull + ', ' + timeStr;
    }

    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // Public API
    return {
        init: init,
        openConversation: openConversation,
        sendMessage: sendMessage,
        refreshConversations: refreshConversations,
    };
})();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    Messenger.init();
});
