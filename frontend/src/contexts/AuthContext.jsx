import React, { createContext, useState, useEffect, useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import api from '../services/api';

const AuthContext = createContext({});

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  // Helper function to normalize user data
  const normalizeUserData = (userData) => {
    // Check for role in different possible locations
    let userRole = userData.role || 
                  (userData.roles && userData.roles[0]) || 
                  (userData.roles && userData.roles.name) ||
                  (userData.role_id && `role_${userData.role_id}`) ||
                  'user';
    
    // Ensure roles is an array
    let userRoles = [];
    if (Array.isArray(userData.roles)) {
      userRoles = [...userData.roles];
    } else if (userData.roles && typeof userData.roles === 'object') {
      // Handle case where roles might be an object with role data
      userRoles = [userData.roles.name || 'user'];
    } else if (userData.role) {
      userRoles = [userData.role];
    } else if (userData.role_id) {
      userRoles = [`role_${userData.role_id}`];
    }
    
    // Return normalized user data
    return {
      ...userData,
      role: userRole,
      roles: userRoles
    };
  };

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
          // Normalize user data
          const normalizedUser = normalizeUserData(response.data);
          setUser(normalizedUser);
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
      const response = await api.post('/auth/login', { email, password });
      
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
        // Normalize user data
        const normalizedUser = normalizeUserData(user);
        setUser(normalizedUser);
        
        // Redirect to dashboard - let the router handle the specific role-based path
        console.log('Redirecting to:', '/dashboard');
        navigate('/dashboard');
      } else {
        // If no user data, try to fetch it
        try {
          const userResponse = await api.get('/user');
          if (userResponse?.data) {
            // Normalize user data
            const normalizedUser = normalizeUserData(userResponse.data);
            setUser(normalizedUser);
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

  // Check if user has required role (case-insensitive)
  const hasRole = (requiredRole) => {
    if (!user) return false;
    
    // Convert required role to lowercase for case-insensitive comparison
    const requiredRoleLower = String(requiredRole).toLowerCase().trim();
    
    // Check if user has the required role (case-insensitive)
    const checkRole = (role) => {
      if (!role) return false;
      
      // Handle role as string
      if (typeof role === 'string') {
        return role.toLowerCase() === requiredRoleLower;
      }
      
      // Handle role as object with name or role property
      if (role && typeof role === 'object') {
        const roleName = (role.name || role.role || '').toLowerCase();
        return roleName === requiredRoleLower;
      }
      
      return false;
    };
    
    // Check legacy role property (string)
    if (user.role && checkRole(user.role)) {
      return true;
    }
    
    // Check roles array
    if (Array.isArray(user.roles)) {
      return user.roles.some(role => checkRole(role));
    }
    
    return false;
  };

  // Check if user has any of the required roles
  const hasAnyRole = (requiredRoles = []) => {
    if (!user) return false;
    return requiredRoles.some(role => hasRole(role));
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

// Export the context and provider
export { AuthContext };
export default AuthProvider;
