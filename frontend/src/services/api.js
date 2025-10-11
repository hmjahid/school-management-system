import axios from 'axios';
import { toast } from 'react-hot-toast';

// Create axios instance with base URL and headers
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  timeout: 30000, // 30 seconds timeout
  withCredentials: true,
});

// Request interceptor to add auth token and handle common headers
api.interceptors.request.use(
  (config) => {
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
    if (response.data?.message) {
      toast.success(response.data.message);
    }
    return response;
  },
  async (error) => {
    const originalRequest = error.config;
    
    // Handle token expiration (401) with refresh token flow
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      
      try {
        // Attempt to refresh token
        const refreshToken = localStorage.getItem('refresh_token');
        if (refreshToken) {
          const response = await axios.post(
            `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'}/auth/refresh`,
            { refresh_token: refreshToken }
          );
          
          const { token, refresh_token } = response.data;
          localStorage.setItem('token', token);
          localStorage.setItem('refresh_token', refresh_token);
          
          // Update the Authorization header
          originalRequest.headers.Authorization = `Bearer ${token}`;
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

// Helper functions for common HTTP methods
const apiService = {
  get: (url, params = {}, config = {}) => 
    api.get(url, { params, ...config }),
  
  post: (url, data = {}, config = {}) => 
    api.post(url, data, config),
  
  put: (url, data = {}, config = {}) => 
    api.put(url, data, config),
  
  patch: (url, data = {}, config = {}) => 
    api.patch(url, data, config),
  
  delete: (url, config = {}) => 
    api.delete(url, config),
  
  // File upload helper
  upload: (url, file, fieldName = 'file', data = {}, config = {}) => {
    const formData = new FormData();
    formData.append(fieldName, file);
    
    // Append additional data to formData
    Object.entries(data).forEach(([key, value]) => {
      formData.append(key, value);
    });
    
    return api.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      ...config,
    });
  },
};

export default apiService;
