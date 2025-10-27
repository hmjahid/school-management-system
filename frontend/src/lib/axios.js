import Axios from 'axios';
import { getAuthToken, setAuthToken, getRefreshToken, setRefreshToken } from '../utils/auth';

const axios = Axios.create({
    baseURL: 'http://localhost:8080/api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
    withCredentials: true,
});

// Add a request interceptor to add auth token to requests
axios.interceptors.request.use(
    (config) => {
        const token = getAuthToken();
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Add a response interceptor to handle token refresh
axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;
        
        // If the error status is 401 and we haven't already tried to refresh the token
        if (error.response.status === 401 && !originalRequest._retry) {
            originalRequest._retry = true;
            
            try {
                const refreshToken = getRefreshToken();
                if (!refreshToken) {
                    // No refresh token available, redirect to login
                    window.location.href = '/login';
                    return Promise.reject(error);
                }
                
                // Try to refresh the token
                const response = await Axios.post('http://localhost:8080/api/refresh-token', 
                    { refresh_token: refreshToken },
                    { withCredentials: true }
                );
                
                const { token, refresh_token: newRefreshToken } = response.data;
                
                // Store the new tokens
                setAuthToken(token);
                if (newRefreshToken) {
                    setRefreshToken(newRefreshToken);
                }
                
                // Update the Authorization header
                originalRequest.headers.Authorization = `Bearer ${token}`;
                
                // Retry the original request
                return axios(originalRequest);
            } catch (error) {
                // If refresh token fails, clear tokens and redirect to login
                console.error('Failed to refresh token:', error);
                window.location.href = '/login';
                return Promise.reject(error);
            }
        }
        
        return Promise.reject(error);
    }
);

export default axios;
