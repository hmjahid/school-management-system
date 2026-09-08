import axios from 'axios';
import { toast } from 'react-hot-toast';

// Create axios instance with default config
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  timeout: 30000,
  withCredentials: true, // Important for cookies, authorization headers with HTTPS
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
});

// Helper function to log request details
const logRequest = (config) => {
  if (process.env.NODE_ENV === 'development') {
    console.groupCollapsed(`%c ${config.method?.toUpperCase()} ${config.url}`, 'color: #4CAF50; font-weight: bold');
    console.log('Request:', {
      url: config.url,
      method: config.method,
      headers: config.headers,
      params: config.params,
      data: config.data,
    });
    console.groupEnd();
  }
  return config;
};

// Helper function to log response details
const logResponse = (response) => {
  if (process.env.NODE_ENV === 'development') {
    console.groupCollapsed(`%c ${response.status} ${response.config.method?.toUpperCase()} ${response.config.url}`, 
      `color: ${response.status >= 200 && response.status < 300 ? '#4CAF50' : '#FF5722'}; font-weight: bold`
    );
    console.log('Response:', {
      status: response.status,
      statusText: response.statusText,
      headers: response.headers,
      data: response.data,
      config: response.config,
    });
    console.groupEnd();
  }
  return response;
};

// Helper function to log error details
const logError = (error) => {
  if (process.env.NODE_ENV === 'development') {
    console.groupCollapsed(`%c ERROR ${error.config?.method?.toUpperCase()} ${error.config?.url}`, 
      'color: #FF0000; font-weight: bold'
    );
    
    if (error.response) {
      // The request was made and the server responded with a status code
      // that falls out of the range of 2xx
      console.error('Response Error:', {
        status: error.response.status,
        statusText: error.response.statusText,
        headers: error.response.headers,
        data: error.response.data,
        config: error.config,
      });
    } else if (error.request) {
      // The request was made but no response was received
      console.error('Request Error:', {
        message: error.message,
        request: error.request,
        config: error.config,
      });
    } else {
      // Something happened in setting up the request that triggered an Error
      console.error('Error:', {
        message: error.message,
        stack: error.stack,
        config: error.config,
      });
    }
    
    console.groupEnd();
  }
  return Promise.reject(error);
};

// Ensure defaults exist
api.defaults = api.defaults || {};
api.defaults.headers = api.defaults.headers || {};
api.defaults.headers.common = api.defaults.headers.common || {};

// Add request interceptor for logging
api.interceptors.request.use(
  config => logRequest(config),
  error => {
    console.error('Request interceptor error:', error);
    return Promise.reject(error);
  }
);

// Add response interceptor for logging
api.interceptors.response.use(
  response => logResponse(response),
  error => logError(error)
);

api.interceptors.request.use(
  (config) => {
    // Skip logging for token refresh requests to prevent token leakage in logs
    const isAuthRequest = config.url?.includes('auth/');
    
    if (!isAuthRequest && import.meta.env.DEV) {
      console.log('[API] Request:', {
        url: config.url,
        method: config.method,
        data: config.data,
        headers: config.headers,
        params: config.params
      });
    }
    
    // Skip auth header for login/register/refresh-token endpoints
    if (config.url?.includes('/auth/') || config.skipAuthRefresh) {
      return config;
    }

    config.headers = config.headers || {};
    // Check multiple possible keys for stored auth token
    const token = localStorage.getItem('token') || localStorage.getItem('access_token') || localStorage.getItem('accessToken');
    
    if (token) {
      // Ensure the token is properly formatted (remove any quotes if present)
      const cleanToken = token.replace(/^['"]|['"]$/g, '');
      config.headers.Authorization = `Bearer ${cleanToken}`;
      
      if (!isAuthRequest && import.meta.env.DEV) {
        console.log('[API] Added auth token to request headers');
      }
    } else {
      if (import.meta.env.DEV) {
        console.debug('[API] No auth token found in localStorage (checked: token, access_token, accessToken)');
      }
      // Don't throw here, let the server handle unauthorized requests
    }

    if (config.method === 'get') {
      config.params = { ...config.params, _t: Date.now() };
    }

    return config;
  },
  (error) => {
    if (import.meta.env.DEV) console.error('[API] Request error:', error);
    return Promise.reject(error);
  }
);

api.interceptors.response.use(
  (response) => {
    if (import.meta.env.DEV) {
      console.log('[API] Response:', {
        url: response.config.url,
        status: response.status,
        data: response.data,
        headers: response.headers
      });
    }
    
    if (response?.data?.message) {
      toast.success(response.data.message);
    }
    return response;
  },
  async (error) => {
    if (import.meta.env.DEV) console.error(error);
    // keep original handler behavior
    if (!error) {
      toast.error('No response from server. Please check your internet connection.');
      return Promise.reject(new Error('No response from server'));
    }

    if (!error.response) {
      const errorMessage = error.message || 'Network error. Please check your connection.';
      toast.error(errorMessage);
      return Promise.reject(new Error(errorMessage));
    }

    const originalRequest = error.config;
    
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      
      try {
        const refreshToken = localStorage.getItem('refresh_token');
        if (!refreshToken) {
          window.location.href = '/login';
          return Promise.reject(new Error('No refresh token available'));
        }

        const response = await axios.post(
          `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8001/api'}/auth/refresh-token`,
          { refresh_token: refreshToken },
          { skipAuthRefresh: true }
        );
        
        if (response.data?.token) {
          const { token, refresh_token: newRefreshToken } = response.data;
          localStorage.setItem('token', token);
          if (newRefreshToken) {
            localStorage.setItem('refresh_token', newRefreshToken);
          }
          
          if (originalRequest?.headers) {
            originalRequest.headers.Authorization = `Bearer ${token}`;
          }
          
          return api(originalRequest);
        }
      } catch (refreshError) {
        if (import.meta.env.DEV) console.error('Token refresh failed:', refreshError);
      }
      
      localStorage.removeItem('token');
      localStorage.removeItem('refresh_token');
      window.location.href = '/login';
      return Promise.reject(error);
    }

    const errorMessage = error.response?.data?.message || 
                        error.message || 
                        'An error occurred. Please try again.';
    
    if (error.response?.status !== 404) {
      toast.error(errorMessage);
    }

    return Promise.reject(error);
  }
);

export default api;
