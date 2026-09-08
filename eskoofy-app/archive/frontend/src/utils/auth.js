/**
 * Authentication utility functions
 * Provides helper functions for managing authentication tokens and user sessions
 */

// Token storage keys
const AUTH_TOKEN_KEY = 'auth_token';
const REFRESH_TOKEN_KEY = 'refresh_token';
const TOKEN_EXPIRY_KEY = 'token_expiry';
const USER_DATA_KEY = 'user_data';

// Session timeout (30 minutes of inactivity)
const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutes in milliseconds
let sessionTimer = null;

/**
 * Get the current authentication token from localStorage
 * @returns {string|null} The authentication token or null if not found
 */
export const getAuthToken = () => {
  return localStorage.getItem(AUTH_TOKEN_KEY);
};

/**
 * Get the refresh token from localStorage
 * @returns {string|null} The refresh token or null if not found
 */
export const getRefreshToken = () => {
  return localStorage.getItem(REFRESH_TOKEN_KEY);
};

/**
 * Get the token expiry timestamp
 * @returns {number|null} The token expiry timestamp or null if not found
 */
const getTokenExpiry = () => {
  const expiry = localStorage.getItem(TOKEN_EXPIRY_KEY);
  return expiry ? parseInt(expiry, 10) : null;
};

/**
 * Set the authentication token and its expiry in localStorage
 * @param {string} token - The JWT token to store
 * @param {number} expiresIn - Token expiration time in seconds
 */
export const setAuthToken = (token, expiresIn = 3600) => {
  if (token) {
    localStorage.setItem(AUTH_TOKEN_KEY, token);
    // Calculate expiry time (current time + expiresIn seconds)
    const expiryTime = Date.now() + (expiresIn * 1000);
    localStorage.setItem(TOKEN_EXPIRY_KEY, expiryTime.toString());
    
    // Reset the session timer
    resetSessionTimer();
  } else {
    localStorage.removeItem(AUTH_TOKEN_KEY);
    localStorage.removeItem(TOKEN_EXPIRY_KEY);
  }
};

/**
 * Set the refresh token in localStorage
 * @param {string} refreshToken - The refresh token to store
 */
export const setRefreshToken = (refreshToken) => {
  if (refreshToken) {
    localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);
  } else {
    localStorage.removeItem(REFRESH_TOKEN_KEY);
  }
};

/**
 * Store user data in localStorage
 * @param {Object} userData - The user data to store
 */
export const setUserData = (userData) => {
  if (userData) {
    localStorage.setItem(USER_DATA_KEY, JSON.stringify(userData));
  } else {
    localStorage.removeItem(USER_DATA_KEY);
  }
};

/**
 * Get user data from localStorage
 * @returns {Object|null} The stored user data or null if not found
 */
export const getUserData = () => {
  const userData = localStorage.getItem(USER_DATA_KEY);
  return userData ? JSON.parse(userData) : null;
};

/**
 * Remove all authentication data from localStorage
 */
export const clearAuthData = () => {
  localStorage.removeItem(AUTH_TOKEN_KEY);
  localStorage.removeItem(REFRESH_TOKEN_KEY);
  localStorage.removeItem(TOKEN_EXPIRY_KEY);
  localStorage.removeItem(USER_DATA_KEY);
  
  // Clear any active session timer
  if (sessionTimer) {
    clearTimeout(sessionTimer);
    sessionTimer = null;
  }
};

/**
 * Reset the session timer
 */
const resetSessionTimer = () => {
  if (sessionTimer) {
    clearTimeout(sessionTimer);
  }
  
  // Set a new timer that will trigger when the session expires
  sessionTimer = setTimeout(() => {
    // When session times out, clear auth data and redirect to login
    clearAuthData();
    
    // Only redirect if we're not already on the login page
    if (!window.location.pathname.includes('/login')) {
      window.location.href = '/login?session_expired=true';
    }
  }, SESSION_TIMEOUT);
};

/**
 * Check if the user is currently authenticated
 * @returns {boolean} True if a valid token exists and is not expired
 */
export const isAuthenticated = () => {
  const token = getAuthToken();
  if (!token) return false;
  
  // Check if token is expired
  const expiry = getTokenExpiry();
  if (!expiry || expiry <= Date.now()) {
    return false;
  }
  
  // Reset the session timer on activity
  resetSessionTimer();
  
  return true;
};

/**
 * Get the current user's information
 * @returns {Object|null} User information or null if not available
 */
export const getCurrentUser = () => {
  // First try to get from localStorage
  let userData = getUserData();
  
  // If not in localStorage but we have a token, try to extract from token
  if (!userData) {
    const token = getAuthToken();
    if (token) {
      try {
        const payload = JSON.parse(atob(token.split('.')[1]));
        userData = {
          id: payload.sub,
          email: payload.email,
          roles: payload.roles || [],
          name: payload.name,
        };
        // Cache the user data
        setUserData(userData);
      } catch (e) {
        console.error('Error parsing user from token:', e);
      }
    }
  }
  
  return userData;
};

/**
 * Check if the current user has a specific role
 * @param {string|string[]} roles - Role or array of roles to check against
 * @returns {boolean} True if the user has at least one of the required roles
 */
export const hasRole = (roles) => {
  const user = getCurrentUser();
  if (!user || !user.roles) return false;
  
  const requiredRoles = Array.isArray(roles) ? roles : [roles];
  return requiredRoles.some(role => user.roles.includes(role));
};
