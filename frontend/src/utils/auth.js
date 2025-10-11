/**
 * Authentication utility functions
 * Provides helper functions for managing authentication tokens and user sessions
 */

/**
 * Get the current authentication token from localStorage
 * @returns {string|null} The authentication token or null if not found
 */
export const getAuthToken = () => {
  return localStorage.getItem('token');
};

/**
 * Get the refresh token from localStorage
 * @returns {string|null} The refresh token or null if not found
 */
export const getRefreshToken = () => {
  return localStorage.getItem('refresh_token');
};

/**
 * Set the authentication token in localStorage
 * @param {string} token - The JWT token to store
 */
export const setAuthToken = (token) => {
  if (token) {
    localStorage.setItem('token', token);
  } else {
    localStorage.removeItem('token');
  }
};

/**
 * Set the refresh token in localStorage
 * @param {string} refreshToken - The refresh token to store
 */
export const setRefreshToken = (refreshToken) => {
  if (refreshToken) {
    localStorage.setItem('refresh_token', refreshToken);
  } else {
    localStorage.removeItem('refresh_token');
  }
};

/**
 * Remove all authentication tokens from localStorage
 */
export const clearAuthTokens = () => {
  localStorage.removeItem('token');
  localStorage.removeItem('refresh_token');
};

/**
 * Check if the user is currently authenticated
 * @returns {boolean} True if a valid token exists
 */
export const isAuthenticated = () => {
  const token = getAuthToken();
  if (!token) return false;
  
  // Check if token is expired (JWT format: header.payload.signature)
  try {
    const payload = JSON.parse(atob(token.split('.')[1]));
    return payload.exp * 1000 > Date.now();
  } catch (e) {
    console.error('Error parsing token:', e);
    return false;
  }
};

/**
 * Get the current user's information from the token
 * @returns {Object|null} User information or null if not available
 */
export const getCurrentUser = () => {
  const token = getAuthToken();
  if (!token) return null;
  
  try {
    const payload = JSON.parse(atob(token.split('.')[1]));
    return {
      id: payload.sub,
      email: payload.email,
      roles: payload.roles || [],
      name: payload.name,
      // Add any other user properties you store in the token
    };
  } catch (e) {
    console.error('Error parsing user from token:', e);
    return null;
  }
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
