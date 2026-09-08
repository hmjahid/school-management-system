import React, { createContext, useState, useEffect, useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import api from '../services/api';

const AuthContext = createContext({});

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  // Check for existing session on initial load
  useEffect(() => {
    const checkAuth = async () => {
      const token = localStorage.getItem('token');
      
      if (!token) {
        setUser(null);
        setLoading(false);
        return;
      }

      // Safely set auth header
      try {
        if (api?.defaults?.headers?.common) {
          api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }
      } catch (headerError) {
        console.error('Error setting auth header:', headerError);
      }

      try {
        // Fetch user data using the /me endpoint
        const response = await api.get('/me').catch(err => {
          console.error('User fetch error:', err);
          throw err;
        });

        if (response?.status === 200 && response?.data) {
          setUser(response.data);
        } else {
          throw new Error('Invalid user data received');
        }
      } catch (error) {
        console.error('Auth check failed:', error);
        
        // Clear invalid token and auth header
        localStorage.removeItem('token');
        try {
          if (api?.defaults?.headers?.common?.['Authorization']) {
            delete api.defaults.headers.common['Authorization'];
          }
        } catch (headerError) {
          console.error('Error clearing auth header:', headerError);
        }
        
        setUser(null);
        
        // Only redirect if this is a 401 error
        if (error.response?.status === 401) {
          navigate('/login');
        }
      } finally {
        setLoading(false);
      }
    };

    checkAuth();
  }, []);

  // Login function
  const login = async (email, password) => {
    try {
      // Make the login request
      const response = await api.post('/login', { email, password });
      
      // Log the full response for debugging
      console.log('Login response:', {
        status: response?.status,
        data: response?.data,
        headers: response?.headers ? Object.keys(response.headers) : 'No headers'
      });
      
      // Check if response and data exist
      if (!response || !response.data) {
        throw new Error('No data received from server');
      }
      
      // Extract token and user data safely
      const token = response.data?.token || response.data?.access_token;
      const user = response.data?.user || response.data;
      
      if (!token) {
        throw new Error('No authentication token found in response');
      }
      
      // Store token and set auth header
      localStorage.setItem('token', token);
      
      // Set the authorization header safely
      if (api && api.defaults && api.defaults.headers && api.defaults.headers.common) {
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      } else {
        console.warn('Could not set auth header - api defaults not properly initialized');
      }
      
      // Update user state
      if (user) {
        setUser(user);
        
        // Redirect to the dashboard - let the router handle the specific role-based path
        console.log('Login successful, redirecting to /dashboard');
        navigate('/dashboard');
      } else {
        // If no user data, try to fetch it
        try {
          const userResponse = await api.get('/user');
          if (userResponse?.data) {
            setUser(userResponse.data);
            navigate('/dashboard');
          } else {
            throw new Error('Failed to fetch user data');
          }
        } catch (userError) {
          console.error('Error fetching user data:', userError);
          throw new Error('Login successful but could not load user profile');
        }
      }
      
      return user;
    } catch (error) {
      console.error('Login failed:', error);
      const message = error.response?.data?.message || 'Login failed. Please try again.';
      toast.error(message);
      throw error;
    }
  };

  // Logout function
  const logout = () => {
    // Clear auth state
    localStorage.removeItem('token');
    delete api.defaults.headers.common['Authorization'];
    setUser(null);
    
    // Redirect to login
    navigate('/login');
    toast.success('Logged out successfully');
  };

  // Update user data
  const updateUser = (userData) => {
    setUser(prev => ({
      ...prev,
      ...userData
    }));
  };

  // Check if user has required role
  const hasRole = (requiredRole) => {
    if (!user) return false;
    // Check both user.roles (array) and user.role (string)
    const roles = user.roles || [];
    const role = user.role || '';
    return roles.includes(requiredRole) || role === requiredRole;
  };

  // Check if user has any of the required roles
  const hasAnyRole = (requiredRoles = []) => {
    if (!user) return false;
    // Check both user.roles (array) and user.role (string)
    const roles = user.roles || [];
    const role = user.role || '';
    return requiredRoles.some(r => roles.includes(r) || role === r);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        isAuthenticated: !!user,
        login,
        logout,
        updateUser,
        hasRole,
        hasAnyRole,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

// Custom hook to use the auth context
export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export default AuthContext;
