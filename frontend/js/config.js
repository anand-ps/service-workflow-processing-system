// API Configuration
// Automatically detects base URL path for Apache / XAMPP hosting

function getApiBaseUrl() {
    // If running under Apache XAMPP, determine relative backend path
    const path = window.location.pathname;
    
    // Check if hosted under service-workflow-processing-system or directly
    if (path.includes('/service-workflow-processing-system/frontend/')) {
        return window.location.origin + '/service-workflow-processing-system/backend/api';
    } else if (path.includes('/frontend/')) {
        const root = path.substring(0, path.indexOf('/frontend/'));
        return window.location.origin + root + '/backend/api';
    } else {
        // Fallback standard relative URL
        return '../backend/api';
    }
}

const API_BASE_URL = getApiBaseUrl();

// Centralized Fetch wrapper with credentials for session management
async function apiRequest(endpoint, method = 'GET', data = null) {
    const url = `${API_BASE_URL}/${endpoint}`;
    
    const options = {
        method: method,
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        const result = await response.json();
        return { ok: response.ok, status: response.status, data: result };
    } catch (err) {
        console.error('API Request Failed:', err);
        return { ok: false, status: 0, data: { status: 'error', message: 'Unable to reach backend API. Ensure Apache & MySQL are running in XAMPP.' } };
    }
}
