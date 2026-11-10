// Map Integration with OpenStreetMap
let map = null;
let marker = null;
let watchId = null;

// Initialize map
function initMap(lat, lng, fromLoc, toLoc) {
    const mapContainer = document.getElementById('routeMap');
    if (!mapContainer) return;
    
    // Use OpenStreetMap iframe as simple solution
    const zoom = 10;
    const url = `https://www.openstreetmap.org/export/embed.html?bbox=${lng-0.5},${lat-0.5},${lng+0.5},${lat+0.5}&layer=mapnik&marker=${lat},${lng}`;
    
    mapContainer.innerHTML = `<iframe width="100%" height="100%" frameborder="0" style="border:0; border-radius:10px;" src="${url}"></iframe>`;
}

// Calculate distance between two coordinates (Haversine formula)
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// Get current location
function getCurrentLocation() {
    return new Promise((resolve, reject) => {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                position => resolve({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                }),
                error => reject(error)
            );
        } else {
            reject(new Error("Geolocation not supported"));
        }
    });
}

// Start live tracking
function startLiveTracking(tripId) {
    if ("geolocation" in navigator) {
        watchId = navigator.geolocation.watchPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                updateLocationOnServer(lat, lng, tripId);
                updateMapDisplay(lat, lng);
            },
            function(error) {
                console.error("Geolocation error:", error);
            },
            {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            }
        );
        showNotification("Live tracking started", "success");
    }
}

// Stop live tracking
function stopLiveTracking() {
    if (watchId) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
        showNotification("Live tracking stopped", "info");
    }
}

// Update location on server
function updateLocationOnServer(lat, lng, tripId) {
    fetch('../api/update_location.php', {
        method: 'POST',
        headers: safeTripJsonHeaders(),
        body: JSON.stringify({
            latitude: lat,
            longitude: lng,
            trip_id: tripId
        })
    })
    .then(response => response.json())
    .catch(error => console.error('Error:', error));
}

// Update map display
function updateMapDisplay(lat, lng) {
    const mapElement = document.getElementById('liveMap');
    if (mapElement) {
        const zoom = 15;
        mapElement.innerHTML = `<iframe width="100%" height="300" frameborder="0" style="border:0; border-radius:10px;" src="https://www.openstreetmap.org/export/embed.html?bbox=${lng-0.05},${lat-0.05},${lng+0.05},${lat+0.05}&layer=mapnik&marker=${lat},${lng}"></iframe>`;
    }
}

// Get route between two cities (using OSRM API - free)
async function getRoute(from, to) {
    try {
        // Geocode first (using Nominatim - free)
        const fromCoords = await geocode(from);
        const toCoords = await geocode(to);
        
        if (fromCoords && toCoords) {
            const url = `https://router.project-osrm.org/route/v1/driving/${fromCoords.lng},${fromCoords.lat};${toCoords.lng},${toCoords.lat}?overview=full&geometries=geojson`;
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.routes && data.routes[0]) {
                const distance = (data.routes[0].distance / 1000).toFixed(1);
                const duration = (data.routes[0].duration / 3600).toFixed(1);
                return { distance, duration, route: data.routes[0].geometry };
            }
        }
        return null;
    } catch (error) {
        console.error('Route error:', error);
        return null;
    }
}

// Geocode location name to coordinates
async function geocode(location) {
    try {
        const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(location)}&format=json&limit=1`;
        const response = await fetch(url);
        const data = await response.json();
        
        if (data && data[0]) {
            return {
                lat: parseFloat(data[0].lat),
                lng: parseFloat(data[0].lon)
            };
        }
        return null;
    } catch (error) {
        console.error('Geocode error:', error);
        return null;
    }
}

// Show nearby police stations
async function showNearbyPolice(lat, lng) {
    try {
        const url = `https://nominatim.openstreetmap.org/search?q=police+station&format=json&limit=5&bounded=1&viewbox=${lng-0.1},${lat-0.1},${lng+0.1},${lat+0.1}`;
        const response = await fetch(url);
        const data = await response.json();
        
        const policeList = document.getElementById('nearbyPolice');
        if (policeList) {
            if (data.length > 0) {
                policeList.innerHTML = data.map(p => `<li><i class="fas fa-shield-alt"></i> ${p.display_name.substring(0, 50)}</li>`).join('');
            } else {
                policeList.innerHTML = '<li>No nearby police stations found</li>';
            }
        }
    } catch (error) {
        console.error('Police search error:', error);
    }
}
