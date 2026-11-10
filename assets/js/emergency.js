// Emergency SOS System
let emergencyInterval = null;

console.log('emergency.js loaded');
console.log('safeTripJsonHeaders exists:', typeof safeTripJsonHeaders === 'function');

// Fallback headers function
function getHeaders() {
    return {
        'Content-Type': 'application/json',
        'X-CSRF-Token': window.SafeTrip?.csrfToken || ''
    };
}

// Trigger emergency alert
function triggerEmergency() {
    const btn = document.getElementById('emergencyBtn');
    const statusDiv = document.getElementById('emergencyStatus');
    
    if (confirm('⚠️ DANGER: This will send emergency alerts to police and your contacts. Continue?')) {
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> SENDING ALERT...';
        }
        
        if (statusDiv) {
            statusDiv.innerHTML = '<div class="alert alert-warning">Sending emergency alert. Please wait...</div>';
        }
        
        // Get current location
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(function(position) {
                sendEmergencyAlert(position.coords.latitude, position.coords.longitude);
            }, function(error) {
                sendEmergencyAlert(null, null);
            });
        } else {
            sendEmergencyAlert(null, null);
        }
    }
}

// Send emergency alert to server
function sendEmergencyAlert(lat, lng) {
    fetch('../api/emergency_alert.php', {
        method: 'POST',
        headers: getHeaders(),
        body: JSON.stringify({
            latitude: lat,
            longitude: lng,
            timestamp: new Date().toISOString()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showEmergencyPopup(lat, lng, data.contacts_available || 0);
        } else {
            showEmergencyError();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showEmergencyError();
    });
}

// Show emergency popup with call/sms options
function showEmergencyPopup(lat, lng, contactsCount) {
    const locationLink = lat && lng ? `https://maps.google.com/?q=${lat},${lng}` : 'Location unavailable';
    
    const popupHtml = `
        <div id="emergencyPopup" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:10000; display:flex; align-items:center; justify-content:center;">
            <div style="background:white; max-width:400px; width:90%; border-radius:20px; padding:25px; text-align:center;">
                <div style="background:#e74c3c; width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                    <i class="fas fa-exclamation-triangle" style="font-size:30px; color:white;"></i>
                </div>
                <h2 style="color:#e74c3c; margin-bottom:10px;">EMERGENCY SOS</h2>
                <p style="margin-bottom:15px;">Your alert has been recorded. ${contactsCount} trusted contacts are available for manual follow-up.</p>
                <hr style="margin:15px 0;">
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <a href="tel:100" style="background:#e74c3c; color:white; padding:12px; border-radius:10px; text-decoration:none; font-weight:bold;">
                        <i class="fas fa-phone"></i> CALL POLICE (100)
                    </a>
                    <a href="tel:112" style="background:#f39c12; color:white; padding:12px; border-radius:10px; text-decoration:none; font-weight:bold;">
                        <i class="fas fa-phone"></i> NATIONAL EMERGENCY (112)
                    </a>
                    <a href="tel:102" style="background:#27ae60; color:white; padding:12px; border-radius:10px; text-decoration:none; font-weight:bold;">
                        <i class="fas fa-ambulance"></i> AMBULANCE (102)
                    </a>
                    <a href="https://wa.me/?text=🚨 EMERGENCY! I need help. My location: ${encodeURIComponent(locationLink)}" target="_blank" style="background:#25D366; color:white; padding:12px; border-radius:10px; text-decoration:none; font-weight:bold;">
                        <i class="fab fa-whatsapp"></i> SHARE ON WHATSAPP
                    </a>
                    <button onclick="closeEmergencyPopup()" style="background:#95a5a6; color:white; padding:12px; border-radius:10px; border:none; font-weight:bold; cursor:pointer;">
                        <i class="fas fa-times"></i> CLOSE
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', popupHtml);
    
    const btn = document.getElementById('emergencyBtn');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '🚨 EMERGENCY SOS 🚨';
    }
}

// Close emergency popup
function closeEmergencyPopup() {
    const popup = document.getElementById('emergencyPopup');
    if (popup) {
        popup.remove();
    }
}

// Show emergency error
function showEmergencyError() {
    const statusDiv = document.getElementById('emergencyStatus');
    if (statusDiv) {
        statusDiv.innerHTML = '<div class="alert alert-error">Failed to send alert. Please call emergency services: 112</div>';
    }
    
    const btn = document.getElementById('emergencyBtn');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '🚨 EMERGENCY SOS 🚨';
    }
    
    // Still show call options
    showEmergencyPopup(null, null, 0);
}

// Share live location with trusted contacts
function shareLiveLocation() {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const locationLink = `https://maps.google.com/?q=${lat},${lng}`;
            
            if (confirm("Share your live location with trusted contacts?")) {
                fetch('../api/share_location.php', {
                    method: 'POST',
                    headers: safeTripJsonHeaders(),
                    body: JSON.stringify({ location_link: locationLink })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification("Location shared with your trusted contacts!", "success");
                    }
                });
            }
        });
    }
}

// Safety check-in (I am safe button)
function safetyCheckIn() {
    fetch('../api/check_safety.php', {
        method: 'POST',
        headers: safeTripJsonHeaders(),
        body: JSON.stringify({ status: 'safe' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification("Safety confirmed! Your contacts have been notified.", "success");
        }
    });
}
