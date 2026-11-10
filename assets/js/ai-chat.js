// ==========================================
// AI Chat Widget Functions
// ==========================================

window.toggleAIChat = function() {
    var chatBox = document.getElementById('aiChatBox');
    var toggle = document.getElementById('aiChatToggle');
    if(!chatBox || !toggle) return;
    
    chatBox.classList.toggle('active');
    toggle.classList.toggle('active');
    if(chatBox.classList.contains('active')) {
        var input = document.getElementById('aiChatInput');
        if(input) input.focus();
    }
};

window.sendAIMessage = function() {
    var input = document.getElementById('aiChatInput');
    if(!input) return;
    
    var message = input.value.trim();
    if(!message) return;
    
    addMessage(message, 'user');
    input.value = '';
    
    var sendBtn = document.getElementById('aiChatSendBtn');
    if(sendBtn) sendBtn.disabled = true;
    
    // Show typing indicator first
    var typingDiv = document.createElement('div');
    typingDiv.className = 'ai-chat-message bot';
    typingDiv.id = 'aiTyping';
    typingDiv.innerHTML = '<div class="ai-chat-message-icon"><i class="fas fa-robot"></i></div><div class="ai-chat-message-content"><div class="ai-chat-typing"><span></span><span></span><span></span></div></div>';
    
    var messagesDiv = document.getElementById('aiChatMessages');
    if(messagesDiv) {
        messagesDiv.appendChild(typingDiv);
        scrollToBottom();
    }
    
    requestAIResponse(message, false)
    .then(function(data) {
        // Remove typing indicator
        var typingEl = document.getElementById('aiTyping');
        if(typingEl) typingEl.remove();
        
        if(data.success) {
            addMessage(data.response, 'bot');
            
            // If response asks to register, open register modal
            if(data.response.includes('Sign Up Free') || data.response.includes('account') || data.response.includes('register')) {
                setTimeout(function() {
                    if(window.openRegisterModal) {
                        window.openRegisterModal();
                    }
                }, 1000);
            }
        } else {
            addMessage(data.response || getSafeTripFallback(message), 'bot');
        }
        
        if(sendBtn) sendBtn.disabled = false;
    })
    .catch(function() {
        var typingEl = document.getElementById('aiTyping');
        if(typingEl) typingEl.remove();
        
        addMessage(getSafeTripFallback(message), 'bot');
        if(sendBtn) sendBtn.disabled = false;
    });
};

function requestAIResponse(message, retried) {
    var externalEndpoint = window.SafeTripAI && window.SafeTripAI.endpoint;

    if (externalEndpoint && /^https?:\/\//.test(externalEndpoint)) {
        return fetch(externalEndpoint, {
            method: 'POST',
            cache: 'no-store',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
        .then(function(res) { return res.json(); });
    }

    var formData = new FormData();
    formData.append('message', message);

    return fetch('api/ai_chat.php?t=' + Date.now(), {
        method: 'POST',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { 'Accept': 'application/json' },
        body: formData
    })
    .then(function(res) { return res.text(); })
    .then(function(text) {
        try {
            return JSON.parse(text);
        } catch (error) {
            if (!retried) {
                return fetch('api/ai_chat.php?warmup=1&t=' + Date.now(), {
                    credentials: 'same-origin',
                    cache: 'no-store'
                }).then(function() {
                    return new Promise(function(resolve) {
                        setTimeout(resolve, 500);
                    });
                }).then(function() {
                    return requestAIResponse(message, true);
                });
            }
            return {
                success: true,
                response: getSafeTripFallback(message)
            };
        }
    });
}

function getSafeTripFallback(message) {
    var text = String(message || '').toLowerCase();

    if (text.includes('create') || text.includes('trip')) {
        return 'To create a trip, sign up or log in, open your dashboard, choose Create Trip, add from/to locations, date, time, seats, and save it. Verified travelers can then find or join your route.';
    }

    if (text.includes('emergency') || text.includes('sos') || text.includes('help')) {
        return 'For Emergency SOS, keep trusted contacts updated in your profile. In an emergency, use the SOS option from your dashboard to alert contacts with your location. For immediate danger, call local emergency services first.';
    }

    if (text.includes('privacy') || text.includes('safe') || text.includes('number') || text.includes('location')) {
        return 'Privacy tips: do not share your phone number publicly, travel only with verified users, share live trip links only with trusted people, and avoid posting your exact location in public chats.';
    }

    if (text.includes('solo') || text.includes('safety') || text.includes('tips')) {
        return 'Safety tips: travel with verified companions, share your route with a trusted contact, keep check-ins enabled, meet in public places, and trust your instincts if something feels wrong.';
    }

    return 'I can help with SafeTrip basics: safety tips, creating trips, emergency SOS, privacy, trusted contacts, and verified traveler guidance. Ask me one of those topics.';
}

function addMessage(content, type) {
    var messagesDiv = document.getElementById('aiChatMessages');
    if(!messagesDiv) return;
    
    var welcome = messagesDiv.querySelector('.ai-chat-welcome');
    if(welcome) welcome.remove();
    
    var messageDiv = document.createElement('div');
    messageDiv.className = 'ai-chat-message ' + type;
    var icon = type === 'bot' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
    messageDiv.innerHTML = '<div class="ai-chat-message-icon">' + icon + '</div><div class="ai-chat-message-content">' + String(content).replace(/\n/g, '<br>') + '</div>';
    
    messagesDiv.appendChild(messageDiv);
    scrollToBottom();
}

window.sendQuickMessage = function(message) {
    var input = document.getElementById('aiChatInput');
    if(input) {
        input.value = message;
        sendAIMessage();
    }
};

function scrollToBottom() {
    var messagesDiv = document.getElementById('aiChatMessages');
    if(messagesDiv) messagesDiv.scrollTop = messagesDiv.scrollHeight;
}
