import axios from 'axios';
import { toast } from 'react-hot-toast';

// Create axios instance with base URL and headers
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8001/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  timeout: 30000, // 30 seconds timeout
  withCredentials: true,
});

// Initialize defaults if they don't exist
api.defaults = api.defaults || {};
api.defaults.headers = api.defaults.headers || {};
api.defaults.headers.common = api.defaults.headers.common || {};

// Request interceptor to add auth token and handle common headers
api.interceptors.request.use(
  (config) => {
    // Ensure headers exist
    config.headers = config.headers || {};
    
    // Add auth token if available
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    // Add cache-busting parameter for GET requests
    if (config.method === 'get') {
      config.params = {
        ...config.params,
        _t: Date.now(),
      };
    }

    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle common errors and messages
api.interceptors.response.use(
  (response) => {
    // Handle success messages if present in response
    if (response?.data?.message) {
      toast.success(response.data.message);
    }
    return response;
  },
  async (error) => {
    // Handle case where error is undefined or doesn't have config
    if (!error) {
      const errorMessage = 'No response from server. Please check your internet connection.';
      toast.error(errorMessage);
      return Promise.reject(new Error(errorMessage));
    }

    // Handle case where error.response is undefined
    if (!error.response) {
      const errorMessage = error.message || 'Network error. Please check your connection and try again.';
      toast.error(errorMessage);
      return Promise.reject(new Error(errorMessage));
    }
    if (!error || !error.config) {
      toast.error('Network error. Please check your connection.');
      return Promise.reject(error || new Error('Network error'));
    }

    const originalRequest = error.config;
    
    // Handle token expiration (401) with refresh token flow
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      
      try {
        // Check if we have a refresh token
        const refreshToken = localStorage.getItem('refresh_token');
        
        if (!refreshToken) {
          // No refresh token available, redirect to login
          window.location.href = '/login';
          return Promise.reject(new Error('No refresh token available'));
        }

        // Attempt to refresh token
        const response = await axios({
          method: 'post',
          url: `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8001/api'}/auth/refresh`,
          data: { refresh_token: refreshToken },
          skipAuthRefresh: true // Prevent infinite loop
        });
        
        if (response.data?.token) {
          const { token, refresh_token: newRefreshToken } = response.data;
          
          // Store the new tokens
          localStorage.setItem('token', token);
          if (newRefreshToken) {
            localStorage.setItem('refresh_token', newRefreshToken);
          }
          
          // Update the Authorization header for the original request
          if (originalRequest?.headers) {
            originalRequest.headers.Authorization = `Bearer ${token}`;
          }
          
          // Retry the original request with new token
          return api(originalRequest);
        }
      } catch (refreshError) {
        console.error('Token refresh failed:', refreshError);
      }
      
      // If we get here, token refresh failed or no refresh token
      localStorage.removeItem('token');
      localStorage.removeItem('refresh_token');
      window.location.href = '/login';
      return Promise.reject(error);
    }

    // Handle other error statuses
    const errorMessage = error.response?.data?.message || 
                        error.message || 
                        'An error occurred. Please try again.';
    
    // Don't show 404 errors as toasts
    if (error.response?.status !== 404) {
      toast.error(errorMessage);
    }

    return Promise.reject(error);
  }
);

// Helper functions

// Export the axios instance directly
export default api;
