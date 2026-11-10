<?php

require_once '../config/database.php';
require_once '../includes/session.php';
redirectIfNotLoggedIn();

$page_title = 'Travel Groups';
require_once '../includes/user_header.php';

$user_id = getUserId();
$trip_id = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;
$group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

$group = null;
$trip = null;

if ($group_id > 0) {
    $group_query = $conn->query("SELECT tg.*, t.from_location, t.to_location, t.travel_date FROM travel_groups tg JOIN trips t ON tg.trip_id = t.id WHERE tg.id = $group_id");
    if ($group_query && $group_query->num_rows > 0) {
        $group = $group_query->fetch_assoc();
        $trip_id = $group['trip_id'];
    }
}

if ($trip_id > 0) {
    $trip_query = $conn->query("SELECT * FROM trips WHERE id = $trip_id");
    if ($trip_query && $trip_query->num_rows > 0) {
        $trip = $trip_query->fetch_assoc();
    }
}

if ($trip_id > 0 && !$group_id) {
    $group_query = $conn->query("SELECT tg.* FROM travel_groups tg WHERE tg.trip_id = $trip_id");
    if ($group_query && $group_query->num_rows == 0) {
        $group_code = generateGroupCode();
        $conn->query("INSERT INTO travel_groups (trip_id, group_code, created_by) VALUES ($trip_id, '$group_code', $user_id)");
        $new_group_id = $conn->insert_id;
        $conn->query("INSERT INTO group_members (group_id, user_id) VALUES ($new_group_id, $user_id)");
        $group_query = $conn->query("SELECT tg.* FROM travel_groups tg WHERE tg.id = $new_group_id");
    }
    if ($group_query && $group_query->num_rows > 0) {
        $group = $group_query->fetch_assoc();
        $group_id = $group['id'];
    }
}

if ($group_id > 0) {
    $check_member = $conn->query("SELECT * FROM group_members WHERE group_id = $group_id AND user_id = $user_id");
    if ($check_member && $check_member->num_rows == 0) {
        $conn->query("INSERT INTO group_members (group_id, user_id) VALUES ($group_id, $user_id)");
    }
}

$members = $conn->query("
    SELECT u.id, u.full_name, u.trust_score, u.verification_status 
    FROM group_members gm 
    JOIN users u ON gm.user_id = u.id 
    WHERE gm.group_id = $group_id
");
?>

<!-- Page Header -->
<div class="page-header-gradient">
    <div class="page-header-kicker">
        <span>Social Features</span>
    </div>
    <h1><i class="fas fa-users"></i> Travel Group</h1>
    <p>Manage your travel group and invite companions.</p>
</div>

<?php if ($trip): ?>
<div class="st-alert st-alert-info" style="margin-bottom: 20px;">
    <i class="fas fa-route"></i> Trip: <?php echo htmlspecialchars($trip['from_location']); ?> → <?php echo htmlspecialchars($trip['to_location']); ?> on <?php echo date('d M Y', strtotime($trip['travel_date'])); ?>
    <?php if($group && isset($group['group_code'])): ?>
        <br><i class="fas fa-code"></i> Group Code: <strong><?php echo $group['group_code']; ?></strong>
    <?php endif; ?>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 300px 1fr; gap: 25px;" class="st-grid-responsive">
    <div class="st-card">
        <div class="st-card-header">
            <h3><i class="fas fa-user-friends"></i> Group Members</h3>
        </div>
        <div class="st-card-body">
            <?php if ($group && isset($group['group_code'])): ?>
            <div style="background: #f0fdf4; padding: 12px; border-radius: 8px; text-align: center; margin-bottom: 15px; border: 1px solid #bbf7d0;">
                <i class="fas fa-code"></i> <strong>Group Code:</strong> <?php echo $group['group_code']; ?>
                <button onclick="copyToClipboard('<?php echo $group['group_code']; ?>')" class="st-btn st-btn-sm st-btn-secondary" style="margin-left: 8px;">Copy</button>
            </div>
            <?php endif; ?>
            
            <div style="max-height: 400px; overflow-y: auto;">
                <?php if ($members && $members->num_rows > 0): ?>
                    <?php while($member = $members->fetch_assoc()): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-bottom: 1px solid #e5e7eb;">
                        <div>
                            <strong><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($member['full_name']); ?></strong>
                            <?php if($member['verification_status'] == 'verified'): ?>
                                <span style="background: #0f766e; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 5px;">✓</span>
                            <?php endif; ?>
                            <div style="margin-top: 5px;">
                                <span style="background: #fef3c7; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;">⭐ <?php echo $member['trust_score']; ?></span>
                            </div>
                        </div>
                        <?php if($member['id'] != $user_id): ?>
                            <button onclick="reportUser(<?php echo $member['id']; ?>)" class="st-btn st-btn-sm st-btn-danger">Report</button>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #667085; padding: 20px;">No members yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="st-card">
        <div class="st-card-header">
            <h3><i class="fas fa-comments"></i> Group Chat</h3>
        </div>
        <div class="st-card-body" style="padding: 0;">
            <div id="chatMessages" style="height: 400px; overflow-y: auto; padding: 20px; background: #f8f9fa;">
                <div style="text-align: center; color: #667085;">Loading messages...</div>
            </div>
            <div style="padding: 15px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; background: white;">
                <input type="text" id="messageInput" placeholder="Type your message..." style="flex: 1; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 25px; outline: none;">
                <button onclick="sendMessage()" class="st-btn-primary" style="padding: 12px 20px; border-radius: 25px; border: none; background: #0f766e; color: white; cursor: pointer;">
                    <i class="fas fa-paper-plane"></i>
                </button>
                <button onclick="shareLocation()" class="location-btn" title="Share your location">
                    <i class="fas fa-location-crosshairs"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>

<script>
    const groupId = <?php echo $group_id ?: 0; ?>;
    const userId = <?php echo $user_id; ?>;
    const csrfToken = window.SafeTrip?.csrfToken || '';
    
    function makeLinksClickable(text) {
        const urlRegex = /(https?:\/\/[^\s<]+)/g;
        return text.replace(urlRegex, function(url) {
            let displayText = url;
            if (url.includes('maps.google.com') || url.includes('maps.app.goo.gl')) {
                displayText = '<i class="fas fa-map-marker-alt" style="color: #ef4444;"></i> View Location on Map';
            }
            return '<a href="' + url + '" target="_blank" style="color: #0f766e; text-decoration: underline;">' + displayText + '</a>';
        });
    }
    
    function loadMessages() {
        if (groupId === 0) return;
        fetch(`../api/get_messages.php?group_id=${groupId}`)
            .then(response => response.json())
            .then(messages => {
                const chatDiv = document.getElementById('chatMessages');
                if (messages.length === 0) {
                    chatDiv.innerHTML = '<div style="text-align: center; color: #667085; padding: 40px;">No messages yet. Start the conversation!</div>';
                } else {
                    chatDiv.innerHTML = '';
                    messages.forEach(msg => {
                        const messageDiv = document.createElement('div');
                        const isMyMessage = msg.user_id == userId;
                        messageDiv.style.cssText = isMyMessage 
                            ? 'background: linear-gradient(135deg, #0f766e, #14b8a6); color: white; margin-left: auto; max-width: 75%; padding: 12px 16px; border-radius: 18px 18px 4px 18px; margin-bottom: 10px; box-shadow: 0 2px 8px rgba(15,118,110,0.2);' 
                            : 'background: white; max-width: 75%; padding: 12px 16px; border-radius: 18px 18px 18px 4px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);';
                        messageDiv.innerHTML = `
                            <div style="font-size: 0.75rem; font-weight: 600; color: ${isMyMessage ? 'rgba(255,255,255,0.9)' : '#0f766e'}; margin-bottom: 4px;">${escapeHtml(msg.user_name)}</div>
                            <div style="line-height: 1.5;">${makeLinksClickable(escapeHtml(msg.message))}</div>
                            <div style="font-size: 0.65rem; margin-top: 4px; opacity: 0.7;">${new Date(msg.created_at).toLocaleTimeString()}</div>
                        `;
                        chatDiv.appendChild(messageDiv);
                    });
                }
                chatDiv.scrollTop = chatDiv.scrollHeight;
            })
            .catch(error => console.error('Error loading messages:', error));
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function sendMessage() {
        if (groupId === 0) { alert('No group selected'); return; }
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        if (message) {
            fetch('../api/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ group_id: groupId, message: message })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) { input.value = ''; loadMessages(); }
                else alert('Failed: ' + (data.message || 'Unknown error'));
            })
            .catch(err => console.error('Send error:', err));
        }
    }
    
    function shareLocation() {
        if (navigator.geolocation) {
            const btn = document.querySelector('.location-btn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            navigator.geolocation.getCurrentPosition(position => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const locationLink = `https://maps.google.com/?q=${lat},${lng}`;
                const message = `📍 My current location: ${locationLink}`;
                
                fetch('../api/send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ group_id: groupId, message: message })
                }).then(() => {
                    btn.innerHTML = '<i class="fas fa-check"></i>';
                    loadMessages();
                    setTimeout(() => {
                        btn.innerHTML = '<i class="fas fa-location-crosshairs"></i>';
                    }, 2000);
                });
            }, () => {
                alert('Unable to get your location. Please enable location services.');
                btn.innerHTML = '<i class="fas fa-location-crosshairs"></i>';
            });
        } else {
            alert('Location is not supported by your browser.');
        }
    }
    
    function reportUser(reportedUserId) {
        const reason = prompt('Please provide reason for reporting this user:');
        if (reason && reason.trim()) {
            fetch('../api/report_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ reported_user_id: reportedUserId, reason: reason })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    return { success: false, message: 'Server returned an invalid response. Please refresh and try again.' };
                }
            })
            .then(data => {
                if (data.success) alert(data.message || 'Report submitted successfully.');
                else alert('Failed: ' + (data.message || 'Unable to submit report.'));
            })
            .catch(() => alert('Could not submit report. Please check your connection and try again.'));
        }
    }
    
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text);
        alert('Copied!');
    }
    
    document.getElementById('messageInput')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') sendMessage();
    });
    
    if (groupId > 0) { loadMessages(); setInterval(loadMessages, 3000); }
</script>

<style>
.location-btn {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    border: 2px solid #e5e7eb;
    background: white;
    color: #0f766e;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.location-btn:hover {
    background: #0f766e;
    color: white;
    border-color: #0f766e;
}

[data-theme="dark"] .location-btn {
    background: #334155;
    border-color: #475569;
    color: #14b8a6;
}

[data-theme="dark"] .location-btn:hover {
    background: #14b8a6;
    color: white;
}
</style>
