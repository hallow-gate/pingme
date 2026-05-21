// Global variables
let currentChatUser = null;
let lastMessageId = 0;
let typingTimeout = null;
let isLoadingExplore = false;
let isLoadingFeed = false;
let exploreOffset = 0;
let feedOffset = 0;
let currentSearchQuery = '';
let pendingMessage = false; // Prevent duplicate sends

// Get CSRF token from meta tag
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
console.log('CSRF Token loaded:', CSRF_TOKEN ? 'YES' : 'NO');

// ========== MESSAGES / CHAT FUNCTIONS ==========

function loadConversations() {
    $.get('api/get_conversations.php', function(convs) {
        if (convs.error) {
            $('#conversations-list').html('<div class="empty-state">Error loading conversations</div>');
            return;
        }
        let html = '';
        if (convs.length === 0) {
            html = '<div class="empty-state"><i class="fas fa-comments"></i><p>No conversations yet</p><small>Go to Explore to start chatting!</small></div>';
        } else {
            convs.forEach(c => {
                html += `<div class="conversation-item" onclick="openChat(${c.other_user_id}, '${escapeHtml(c.full_name).replace(/'/g, "\\'")}')">
                    <div class="conv-avatar">${c.avatar}</div>
                    <div class="conv-info">
                        <div class="conv-name">${escapeHtml(c.full_name)} ${c.is_online ? '<span class="online-badge"></span>' : ''}</div>
                        <div class="conv-last-msg">${escapeHtml(c.last_message || 'Start a conversation')}</div>
                        <div class="conv-time">${c.last_active_text}</div>
                    </div>
                </div>`;
            });
        }
        $('#conversations-list').html(html);
    });
}

function openChat(userId, userName) {
    if (currentChatUser === userId) return; // Already in this chat
    
    currentChatUser = userId;
    lastMessageId = 0;
    $('#conversations-view').hide();
    $('#chat-view').show();
    $('#chat-user-name').text(userName);
    $('#chat-messages').empty();
    
    // Clear any existing interval
    if (window.chatInterval) clearInterval(window.chatInterval);
    
    // Load messages once
    loadMessages(true);
    
    // Then set up interval for new messages
    window.chatInterval = setInterval(() => {
        if (currentChatUser) loadMessages(false);
    }, 3000);
}

function loadMessages(reset = false) {
    if (!currentChatUser) return;
    
    if (reset) {
        lastMessageId = 0;
        $('#chat-messages').empty();
    }
    
    $.get(`api/get_messages.php?user_id=${currentChatUser}&last_id=${lastMessageId}`, function(data) {
        if (data.messages && data.messages.length) {
            data.messages.forEach(msg => {
                // Only add if message ID is greater than last seen
                if (msg.id > lastMessageId) {
                    appendMessage(msg);
                    lastMessageId = Math.max(lastMessageId, msg.id);
                }
            });
            scrollToBottom();
        }
        if (data.other_user_online !== undefined) {
            $('#chat-user-status').html(data.other_user_online ? '🟢 Online' : `⚫ Last seen ${data.last_active}`);
        }
    });
}

function appendMessage(msg) {
    const isMine = msg.is_mine;
    // Message is already decoded from server, just display as is
    const displayMessage = msg.message;
    
    const html = `<div class="message ${isMine ? 'message-sent' : 'message-received'}" data-message-id="${msg.id}">
        <div class="message-bubble">
            ${escapeHtmlPreserveEmoji(displayMessage)}
            <div class="message-time">${msg.time}</div>
        </div>
    </div>`;
    $('#chat-messages').append(html);
}

function sendMessage() {
    const msg = $('#messageInput').val();
    if (!msg.trim() || !currentChatUser) return;
    if (pendingMessage) {
        console.log('Message already sending, please wait');
        return;
    }
    if (!CSRF_TOKEN) { 
        alert('CSRF token missing. Refresh page.'); 
        return;
    }
    
    pendingMessage = true;
    const messageToSend = msg;
    
    // Clear input immediately for better UX
    $('#messageInput').val('');
    
    $.ajax({
        url: 'api/send_message.php',
        type: 'POST',
        data: { 
            to_user_id: currentChatUser, 
            message: messageToSend, 
            csrf_token: CSRF_TOKEN 
        },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                // Reload messages to get the new message with correct ID
                loadMessages(true);
            } else {
                alert(res.error || 'Failed to send message');
                // Restore the message if failed
                $('#messageInput').val(messageToSend);
            }
        },
        error: function(xhr) {
            console.error('Send error:', xhr);
            alert('Failed to send message. Please try again.');
            $('#messageInput').val(messageToSend);
        },
        complete: function() {
            pendingMessage = false;
        }
    });
}

function sendTyping() {
    if (!currentChatUser) return;
    if (typingTimeout) clearTimeout(typingTimeout);
    $.get(`api/typing.php?to_user=${currentChatUser}&typing=1`);
    typingTimeout = setTimeout(() => { 
        $.get(`api/typing.php?to_user=${currentChatUser}&typing=0`);
    }, 2000);
}

function closeChat() {
    currentChatUser = null;
    if (window.chatInterval) clearInterval(window.chatInterval);
    $('#chat-view').hide();
    $('#conversations-view').show();
    loadConversations();
}

function scrollToBottom() {
    const chatArea = $('#chat-messages')[0];
    if (chatArea) chatArea.scrollTop = chatArea.scrollHeight;
}

// ========== EXPLORE FUNCTIONS ==========

function loadExplore(reset = false) {
    if (reset) { exploreOffset = 0; $('#exploreGrid').empty(); }
    if (isLoadingExplore) return;
    isLoadingExplore = true;
    $('#exploreLoader').text('Loading...');
    
    let url = `api/explore_users.php?offset=${exploreOffset}`;
    if (currentSearchQuery) url += `&search=${encodeURIComponent(currentSearchQuery)}`;
    
    $.get(url, function(users) {
        if (users.error) {
            $('#exploreGrid').html('<div class="empty-state">Error loading users</div>');
            isLoadingExplore = false;
            return;
        }
        if (users.length === 0 && exploreOffset === 0) {
            $('#exploreGrid').html('<div class="empty-state"><i class="fas fa-users"></i><p>No public users found</p><small>Complete your profile and set it to public!</small></div>');
        } else if (users.length > 0) {
            users.forEach(u => {
                let actionButton = '';
                if (u.friend_status === 'none') {
                    actionButton = `<button class="btn-add" onclick="event.stopPropagation(); addFriend(${u.id})">➕ Add Friend</button>`;
                } else if (u.friend_status === 'pending') {
                    actionButton = `<button class="btn-pending" disabled>⏳ Pending</button>`;
                } else {
                    actionButton = `<button class="btn-friend" disabled>✓ Friend</button>`;
                }
                $('#exploreGrid').append(`<div class="explore-card" onclick="viewUserProfile(${u.id})">
                    <div class="explore-avatar">${u.avatar}</div>
                    <div class="explore-name" title="${escapeHtml(u.full_name_raw)}">${escapeHtml(u.full_name)}</div>
                    <div class="explore-age">${u.age || '?'} years</div>
                    <div class="explore-bio" title="${escapeHtml(u.bio_full)}">${escapeHtml(u.bio)}</div>
                    <div class="explore-actions">
                        <button class="btn-message" onclick="event.stopPropagation(); openChatFromExplore(${u.id}, '${escapeHtml(u.full_name_raw).replace(/'/g, "\\'")}')">💬 Message</button>
                        ${actionButton}
                    </div>
                </div>`);
            });
            exploreOffset += 20;
            $('#exploreLoader').text('Scroll for more');
        } else {
            $('#exploreLoader').text('No more users');
        }
        isLoadingExplore = false;
    });
}

function searchUsers() {
    currentSearchQuery = $('#searchInput').val();
    exploreOffset = 0;
    $('#exploreGrid').empty();
    loadExplore();
}

function addFriend(userId) {
    if (!CSRF_TOKEN) { alert('Security error. Refresh page.'); return; }
    $.ajax({
        url: 'api/send_friend_request.php',
        type: 'POST',
        data: { friend_id: userId, csrf_token: CSRF_TOKEN },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                alert('Friend request sent!');
                loadExplore(true);
            } else {
                alert(res.error);
            }
        },
        error: function() { alert('Failed to send friend request'); }
    });
}

function openChatFromExplore(userId, userName) {
    sessionStorage.setItem('openChatUserId', userId);
    sessionStorage.setItem('openChatUserName', userName);
    window.location.href = '?tab=messages';
}

function viewUserProfile(userId) {
    $.get(`api/get_user_profile.php?user_id=${userId}`, function(data) {
        if (data.error) { alert(data.error); return; }
        if (data.success) showUserProfileModal(data);
    });
}

function showUserProfileModal(data) {
    const user = data.user;
    const friendStatus = data.friend_status;
    const isOwnProfile = data.is_own_profile;
    
    let actionButtons = '';
    if (!isOwnProfile) {
        if (friendStatus === 'none') {
            actionButtons = `<button class="modal-btn btn-primary" onclick="addFriendFromModal(${user.id})">➕ Add Friend</button>`;
        } else if (friendStatus === 'pending') {
            actionButtons = `<button class="modal-btn btn-disabled" disabled>⏳ Pending</button>`;
        } else {
            actionButtons = `<button class="modal-btn btn-disabled" disabled>✓ Friends</button>`;
        }
        actionButtons += `<button class="modal-btn btn-message" onclick="openChatFromModal(${user.id}, '${escapeHtml(user.full_name).replace(/'/g, "\\'")}')">💬 Send Message</button>`;
    }
    
    const modalHtml = `<div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <div class="modal-avatar">${user.avatar}</div>
                <h2>${escapeHtml(user.full_name)}</h2>
                <div class="user-status">${user.is_online ? '🟢 Online' : '⚫ ' + user.last_active}</div>
            </div>
            <div class="modal-body">
                <div class="profile-stats">
                    <div class="stat"><strong>${user.post_count}</strong><span>Posts</span></div>
                    <div class="stat"><strong>${user.friend_count}</strong><span>Friends</span></div>
                    <div class="stat"><strong>${user.mutual_friends}</strong><span>Mutual</span></div>
                </div>
                <div class="profile-info">
                    <p><i class="fas fa-calendar"></i> Age: ${user.age}</p>
                    <p><i class="fas fa-venus-mars"></i> Gender: ${user.gender}</p>
                    <p><i class="fas fa-user-plus"></i> Member since: ${user.member_since}</p>
                    <div class="bio-section"><strong>About:</strong><p>${user.bio}</p></div>
                </div>
                <div class="modal-actions">${actionButtons}</div>
            </div>
        </div>
    </div>`;
    
    if ($('#profileModal').length) $('#profileModal').remove();
    $('body').append(modalHtml);
    $('#profileModal').show();
}

function closeModal() { $('#profileModal').remove(); }
function addFriendFromModal(userId) { addFriend(userId); setTimeout(() => closeModal(), 1000); }
function openChatFromModal(userId, userName) { closeModal(); sessionStorage.setItem('openChatUserId', userId); sessionStorage.setItem('openChatUserName', userName); window.location.href = '?tab=messages'; }

// ========== FEED FUNCTIONS ==========

function loadFeed(reset = false) {
    if (reset) { feedOffset = 0; $('#feedContainer').empty(); }
    if (isLoadingFeed) return;
    isLoadingFeed = true;
    $('#feedLoader').text('Loading...');
    
    $.get(`api/get_feed.php?offset=${feedOffset}`, function(posts) {
        if (posts.error) {
            $('#feedContainer').html('<div class="empty-state">Error loading feed</div>');
            isLoadingFeed = false;
            return;
        }
        if (posts.length === 0 && feedOffset === 0) {
            $('#feedContainer').html('<div class="empty-state"><i class="fas fa-newspaper"></i><p>No posts yet</p><small>Create your first post!</small></div>');
        } else if (posts.length > 0) {
            posts.forEach(post => {
                const reactionBtn = post.my_reaction ? 
                    `<button class="reaction-btn active" onclick="addReaction(${post.id}, '${post.my_reaction}')">❤️ ${post.reaction_count}</button>` :
                    `<button class="reaction-btn" onclick="addReaction(${post.id}, 'like')">❤️ ${post.reaction_count}</button>`;
                
                let commentsHtml = '';
                if (post.comments && post.comments.length) {
                    post.comments.forEach(c => {
                        commentsHtml += `<div class="comment"><strong>${escapeHtml(c.full_name)}</strong> ${escapeHtmlPreserveEmoji(c.content)}</div>`;
                    });
                }
                
                $('#feedContainer').append(`<div class="feed-post">
                    <div class="post-header">
                        <div class="post-avatar">${post.avatar}</div>
                        <div class="post-info">
                            <div class="post-author">${escapeHtml(post.full_name)}</div>
                            <div class="post-time">${post.time_ago}</div>
                        </div>
                    </div>
                    <div class="post-content">${escapeHtmlPreserveEmoji(post.content)}</div>
                    <div class="post-actions">
                        ${reactionBtn}
                        <button class="comment-btn" onclick="toggleCommentBox(${post.id})">💬 ${post.comment_count}</button>
                    </div>
                    <div class="comments-section" id="comments-${post.id}">${commentsHtml}</div>
                    <div class="comment-input-box" id="comment-input-${post.id}" style="display:none;">
                        <input type="text" id="comment-text-${post.id}" placeholder="Write a comment..." class="comment-input">
                        <button onclick="addComment(${post.id})" class="comment-submit">Post</button>
                    </div>
                </div>`);
            });
            feedOffset += 10;
            $('#feedLoader').text('Scroll for more');
        } else {
            $('#feedLoader').text('No more posts');
        }
        isLoadingFeed = false;
    });
}

function createPost() {
    const content = $('#newPost').val();
    if (!content.trim()) { alert('Please write something'); return; }
    if (!CSRF_TOKEN) { alert('CSRF token missing. Refresh page.'); return; }
    
    const postBtn = $('#postBtn');
    postBtn.prop('disabled', true).text('Posting...');
    
    $.ajax({
        url: 'api/create_post.php',
        type: 'POST',
        data: JSON.stringify({ content: content, csrf_token: CSRF_TOKEN }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $('#newPost').val('');
                loadFeed(true);
                alert('Post created!');
            } else {
                alert(res.error);
            }
        },
        error: function() { alert('Error creating post'); },
        complete: function() { postBtn.prop('disabled', false).text('Post'); }
    });
}

function addReaction(postId, type) {
    if (!CSRF_TOKEN) { alert('CSRF token missing. Refresh page.'); return; }
    $.ajax({
        url: 'api/add_reaction.php',
        type: 'POST',
        data: JSON.stringify({ post_id: postId, type: type, csrf_token: CSRF_TOKEN }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(res) { if (res.success) loadFeed(true); else alert(res.error); },
        error: function() { alert('Failed to add reaction'); }
    });
}

function addComment(postId) {
    const content = $(`#comment-text-${postId}`).val();
    if (!content.trim()) return;
    if (!CSRF_TOKEN) { alert('CSRF token missing. Refresh page.'); return; }
    
    $.ajax({
        url: 'api/add_comment.php',
        type: 'POST',
        data: JSON.stringify({ post_id: postId, content: content, csrf_token: CSRF_TOKEN }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                $(`#comment-text-${postId}`).val('');
                loadFeed(true);
            } else {
                alert(res.error);
            }
        },
        error: function() { alert('Failed to add comment'); }
    });
}

function toggleCommentBox(postId) { $(`#comment-input-${postId}`).toggle(); }

// ========== UTILITIES ==========

// Escape HTML but preserve emojis and special characters
function escapeHtmlPreserveEmoji(text) {
    if (!text) return '';
    // First, convert HTML entities back to characters
    let decoded = text;
    // Use a temporary div to decode HTML entities
    const txt = document.createElement('textarea');
    txt.innerHTML = decoded;
    decoded = txt.value;
    
    // Then escape only the characters that could break HTML
    return String(decoded)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// ========== INITIALIZATION ==========

$(document).ready(function() {
    // Check for stored chat user
    const openUserId = sessionStorage.getItem('openChatUserId');
    const openUserName = sessionStorage.getItem('openChatUserName');
    if (openUserId && openUserName) {
        sessionStorage.removeItem('openChatUserId');
        sessionStorage.removeItem('openChatUserName');
        setTimeout(() => openChat(parseInt(openUserId), openUserName), 500);
    }
    
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'messages';
    
    if (tab === 'messages') {
        loadConversations();
    } else if (tab === 'explore') {
        loadExplore();
    } else if (tab === 'feed') {
        loadFeed();
        $('#postBtn').off('click').on('click', createPost);
    } else if (tab === 'profile') {
        $('#profileForm').off('submit').on('submit', function(e) {
            e.preventDefault();
            $.post('api/update_profile.php', $(this).serialize(), function(res) {
                if (res.success) { alert('Profile updated!'); location.reload(); }
                else alert(res.error);
            }, 'json');
        });
    }
    
    // Chat handlers
    $('#sendMsgBtn').off('click').on('click', sendMessage);
    $('#messageInput').off('keypress').on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        } else if (currentChatUser) {
            sendTyping();
        }
    });
    
    // Update online status every 30 seconds
    setInterval(() => $.post('api/update_online.php'), 30000);
});