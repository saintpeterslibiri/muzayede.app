// =====================================================
// MAIN.JS - Core Frontend JavaScript
// =====================================================
// Common functions used across all pages:
// - API calls (fetch wrapper)
// - Authentication helpers
// - Utility functions
// =====================================================


// -----------------------------------------------------
// API Configuration
// -----------------------------------------------------

// Base URL for all API calls
// Change this if your backend runs on different port/host
const API_BASE_URL = 'http://localhost:3000/api';


// -----------------------------------------------------
// API Helper Function
// -----------------------------------------------------
// Wrapper around fetch() that handles common tasks:
// - Adds headers (Content-Type, Authorization)
// - Parses JSON response
// - Handles errors
//
// Usage:
//   const data = await api('/auctions', 'GET');
//   const data = await api('/auctions', 'POST', { title: 'My Item' });

async function api(endpoint, method = 'GET', body = null) {
    try {
        // Build request options
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        // Add auth token if exists (stored in localStorage)
        const token = localStorage.getItem('auth_token');
        if (token) {
            options.headers['Authorization'] = `Bearer ${token}`;
        }
        
        // Add body for POST/PUT requests
        if (body && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(body);
        }
        
        // Make the request
        const response = await fetch(`${API_BASE_URL}${endpoint}`, options);
        
        // Parse JSON response
        const data = await response.json();
        
        // Check if request was successful
        if (!response.ok) {
            // Throw error with message from server
            throw new Error(data.error || 'Something went wrong');
        }
        
        return data;
        
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}


// -----------------------------------------------------
// Authentication Helpers
// -----------------------------------------------------

// Check if user is logged in
function isLoggedIn() {
    return localStorage.getItem('auth_token') !== null;
}

// Get current user info from localStorage
function getCurrentUser() {
    const userJson = localStorage.getItem('current_user');
    if (userJson) {
        return JSON.parse(userJson);
    }
    return null;
}

// Save user session after login
function saveSession(token, user) {
    localStorage.setItem('auth_token', token);
    localStorage.setItem('current_user', JSON.stringify(user));
}

// Clear session on logout
function clearSession() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('current_user');
}

// Redirect to login if not authenticated
function requireAuth() {
    if (!isLoggedIn()) {
        window.location.href = 'login.html';
        return false;
    }
    return true;
}


// -----------------------------------------------------
// UI Helper Functions
// -----------------------------------------------------

// Show loading spinner
function showLoading(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Loading...</p>
            </div>
        `;
    }
}

// Show error message
function showError(containerId, message) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="error-message">
                <p>❌ ${message}</p>
            </div>
        `;
    }
}

// Show success message (toast notification)
function showToast(message, type = 'success') {
    // Remove existing toast if any
    const existingToast = document.querySelector('.toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    // Add to page
    document.body.appendChild(toast);
    
    // Show toast (with animation)
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}


// -----------------------------------------------------
// Formatting Helpers
// -----------------------------------------------------

// Format price with currency symbol
function formatPrice(price) {
    return '$' + parseFloat(price).toFixed(2);
}

// Format date for display
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Calculate and format time remaining
function formatTimeRemaining(endDateString) {
    const end = new Date(endDateString);
    const now = new Date();
    const diff = end - now;
    
    // If auction ended
    if (diff <= 0) {
        return 'Ended';
    }
    
    // Calculate days, hours, minutes, seconds
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    if (days > 0) {
        return `${days}d ${hours}h ${minutes}m`;
    } else if (hours > 0) {
        return `${hours}h ${minutes}m ${seconds}s`;
    } else if (minutes > 0) {
        return `${minutes}m ${seconds}s`;
    } else {
        return `${seconds}s`;
    }
}


// -----------------------------------------------------
// Countdown Timer
// -----------------------------------------------------
// Updates time remaining every second
// Usage: startCountdown('countdown-element-id', '2025-12-31T23:59:59');

function startCountdown(elementId, endDateString) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    function update() {
        const timeLeft = formatTimeRemaining(endDateString);
        element.textContent = timeLeft;
        
        // Add warning class if less than 1 hour
        const end = new Date(endDateString);
        const now = new Date();
        const diff = end - now;
        
        if (diff <= 0) {
            element.classList.add('ended');
            clearInterval(timer);
        } else if (diff < 3600000) { // Less than 1 hour
            element.classList.add('urgent');
        }
    }
    
    // Update immediately
    update();
    
    // Update every second
    const timer = setInterval(update, 1000);
    
    return timer;
}


// -----------------------------------------------------
// Update Navigation Based on Auth State
// -----------------------------------------------------
// Shows/hides login/logout buttons based on whether user is logged in

function updateNavigation() {
    const user = getCurrentUser();
    
    // Find nav elements
    const loginLink = document.querySelector('a[href="login.html"]');
    const registerLink = document.querySelector('a[href="register.html"]');
    const profileLink = document.querySelector('a[href="profile.html"]');
    const logoutLink = document.querySelector('#logout-link');
    
    if (user) {
        // User is logged in
        if (loginLink) loginLink.style.display = 'none';
        if (registerLink) registerLink.style.display = 'none';
        if (profileLink) {
            profileLink.style.display = 'block';
            // Update profile link text with username
            profileLink.textContent = user.username;
        }
        if (logoutLink) logoutLink.style.display = 'block';
    } else {
        // User is not logged in
        if (loginLink) loginLink.style.display = 'block';
        if (registerLink) registerLink.style.display = 'block';
        if (profileLink) profileLink.style.display = 'none';
        if (logoutLink) logoutLink.style.display = 'none';
    }
}


// -----------------------------------------------------
// Logout Function
// -----------------------------------------------------

async function logout() {
    try {
        // Call logout API (if your friend implements it)
        // await api('/auth/logout', 'POST');
        
        // Clear local session
        clearSession();
        
        // Show message
        showToast('Logged out successfully');
        
        // Redirect to home
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1000);
        
    } catch (error) {
        // Even if API fails, clear local session
        clearSession();
        window.location.href = 'index.html';
    }
}


// -----------------------------------------------------
// Initialize on page load
// -----------------------------------------------------

document.addEventListener('DOMContentLoaded', function() {
    // Update navigation based on auth state
    updateNavigation();
    
    // Add logout event listener if logout link exists
    const logoutLink = document.querySelector('#logout-link');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    }
});