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
    console.log('[AuthContext] Normalizing user data:', userData);
    
    // Extract roles from different possible locations
    let userRoles = [];
    
    // Case 1: roles is an array of role objects
    if (Array.isArray(userData.roles)) {
      userRoles = userData.roles.map(role => {
        if (typeof role === 'object' && role !== null) {
          return role.name || role.role || 'user';
        }
        return role; // in case it's already a string
      });
      console.log('[AuthContext] Extracted roles from roles array:', userRoles);
    } 
    // Case 2: roles is a single role object
    else if (userData.roles && typeof userData.roles === 'object') {
      userRoles = [userData.roles.name || userData.roles.role || 'user'];
      console.log('[AuthContext] Extracted role from roles object:', userRoles);
    }
    // Case 3: role is a direct property
    else if (userData.role) {
      userRoles = [userData.role];
      console.log('[AuthContext] Extracted role from role property:', userRoles);
    }
    // Case 4: role_id is available
    else if (userData.role_id) {
      userRoles = [`role_${userData.role_id}`];
      console.log('[AuthContext] Extracted role from role_id:', userRoles);
    }
    
    // Determine the primary role (first role in the array or 'user' as fallback)
    const userRole = userRoles[0] || 'user';
    
    console.log('[AuthContext] Final normalized data:', {
      ...userData,
      role: userRole,
      roles: userRoles
    });
    
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
        console.log('[AuthContext] Fetching user data from /me endpoint...');
        // Fetch user data using the /me endpoint
        const response = await api.get('/me').catch(err => {
          console.error('[AuthContext] User fetch error:', err);
          console.error('[AuthContext] Error response:', err.response?.data);
          throw err;
        });

        console.log('[AuthContext] Raw response from /me:', {
          status: response.status,
          data: response.data,
          headers: response.headers
        });

        if (response?.status === 200 && response?.data) {
          console.log('[AuthContext] Raw user data before normalization:', JSON.parse(JSON.stringify(response.data)));
        
        // Log all properties including non-enumerable ones
        const allProps = {};
        const userObj = response.data;
        Object.getOwnPropertyNames(userObj).forEach(prop => {
          allProps[prop] = userObj[prop];
        });
        console.log('[AuthContext] All user properties:', allProps);
        
        // Check for roles in different possible locations
        console.log('[AuthContext] Checking for roles in response...');
        console.log('user.roles:', userObj.roles);
        console.log('user.role:', userObj.role);
        console.log('user.role_id:', userObj.role_id);
        
        // Normalize user data
        const normalizedUser = normalizeUserData(response.data);
        console.log('[AuthContext] Normalized user data:', JSON.parse(JSON.stringify(normalizedUser)));
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
    console.log('[AuthContext] Login function called with email:', email);
    try {
      console.log('[AuthContext] Making login request to /auth/login');
      // Make the login request
      const response = await api.post('/auth/login', { 
        email: email.trim(),
        password: password
      }, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        withCredentials: true
      })
      .catch(error => {
        console.error('[AuthContext] Login API error:', {
          message: error.message,
          response: error.response?.data,
          status: error.response?.status,
          headers: error.response?.headers,
          config: {
            url: error.config?.url,
            method: error.config?.method,
            data: error.config?.data,
            headers: error.config?.headers
          }
        });
        throw error; // Re-throw to be caught by the outer catch
      });
      
      // Log the full response for debugging
      console.log('[AuthContext] Login response received:', {
        status: response?.status,
        data: response?.data || 'No data',  // Show full response data
        hasToken: !!(response?.data?.token || response?.data?.access_token),
        hasUser: !!response?.data?.user,
        headers: response?.headers ? Object.keys(response.headers) : 'No headers',
        responseKeys: response?.data ? Object.keys(response.data) : 'No data keys',
        userData: response?.data?.user || 'No user data in response',
        fullResponse: response  // Include full response for debugging
      });
      
      // Check if response and data exist
      if (!response || !response.data) {
        console.error('[AuthContext] No data received in response');
        throw new Error('No data received from server');
      }
      
      // Extract token from different possible response formats
      const token = response.data?.access_token || 
                  response.data?.token || 
                  response.data?.data?.access_token || 
                  response.data?.data?.token;

      console.log('[AuthContext] Extracted token:', {
        hasToken: !!token,
        fromAccessToken: !!response.data?.access_token,
        fromToken: !!response.data?.token,
        fromDataAccessToken: !!response.data?.data?.access_token,
        fromDataToken: !!response.data?.data?.token
      });
      
      // User data is at the root level of the response or in a user object
      const user = response.data?.id ? response.data : response.data?.user;
      
      console.log('[AuthContext] Extracted token and user:', {
        hasToken: !!token,
        userData: user ? {
          id: user.id,
          name: user.name,
          email: user.email,
          roles: user.roles || user.role ? [user.role].flat() : 'No roles'
        } : 'No user data'
      });
      
      if (!token) {
        console.error('[AuthContext] No authentication token found in response');
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
        console.log('[AuthContext] Normalized user data:', {
          id: normalizedUser.id,
          name: normalizedUser.name,
          roles: normalizedUser.roles,
          hasAdminRole: normalizedUser.roles?.includes('admin')
        });
        
        setUser(normalizedUser);
        
        // Redirect to dashboard - let the router handle the specific role-based path
        console.log('[AuthContext] Login successful, redirecting to /dashboard');
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
    // Debug log to surface auth state each render
    (console.log('[AuthContext] Provider render', { user, loading }),
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
    </AuthContext.Provider>)
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
