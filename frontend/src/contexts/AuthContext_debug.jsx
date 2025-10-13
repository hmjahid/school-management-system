import React, { createContext, useState, useEffect, useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import api from '../services/api';

const AuthContext = createContext({});

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  // Debug function to log user data
  const debugUser = (context, userData) => {
    console.log(`[AuthContext] ${context}:`, {
      hasUser: !!userData,
      userRole: userData?.role,
      userRoles: userData?.roles,
      userData: userData
    });
  };

  // Check for existing session on initial load
  useEffect(() => {
    const checkAuth = async () => {
      const token = localStorage.getItem('token');
      
      if (!token) {
        console.log('[AuthContext] No token found');
        setUser(null);
        setLoading(false);
        return;
      }

      // Safely set auth header
      try {
        if (api?.defaults?.headers?.common) {
          api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
          console.log('[AuthContext] Set auth header with token');
        }
      } catch (headerError) {
        console.error('[AuthContext] Error setting auth header:', headerError);
      }

      try {
        console.log('[AuthContext] Fetching user data...');
        const response = await api.get('/me').catch(err => {
          console.error('[AuthContext] User fetch error:', err);
          throw err;
        });

        if (response?.status === 200 && response?.data) {
          console.log('[AuthContext] User data received:', response.data);
          setUser(response.data);
          debugUser('User set from /me', response.data);
        } else {
          throw new Error('Invalid user data received');
        }
      } catch (error) {
        console.error('[AuthContext] Auth check failed:', error);
        
        // Clear invalid token and auth header
        localStorage.removeItem('token');
        try {
          if (api?.defaults?.headers?.common?.['Authorization']) {
            delete api.defaults.headers.common['Authorization'];
          }
        } catch (headerError) {
          console.error('[AuthContext] Error clearing auth header:', headerError);
        }
        
        setUser(null);
        
        if (error.response?.status === 401) {
          console.log('[AuthContext] 401 Unauthorized, redirecting to login');
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
    console.log('[AuthContext] Login attempt with email:', email);
    try {
      const response = await api.post('/login', { email, password });
      
      console.log('[AuthContext] Login response:', {
        status: response?.status,
        data: response?.data,
        hasUser: !!response?.data?.user,
        hasToken: !!(response?.data?.token || response?.data?.access_token)
      });
      
      if (!response || !response.data) {
        throw new Error('No data received from server');
      }
      
      const token = response.data?.token || response.data?.access_token;
      const user = response.data?.user || response.data;
      
      if (!token) {
        throw new Error('No authentication token found in response');
      }
      
      localStorage.setItem('token', token);
      
      if (api && api.defaults && api.defaults.headers && api.defaults.headers.common) {
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        console.log('[AuthContext] Set auth header after login');
      } else {
        console.warn('[AuthContext] Could not set auth header - api defaults not properly initialized');
      }
      
      if (user) {
        console.log('[AuthContext] Setting user data:', user);
        setUser(user);
        debugUser('User set after login', user);
        
        // Redirect to dashboard - let the router handle the specific role-based path
        console.log('[AuthContext] Login successful, redirecting to /dashboard');
        navigate('/dashboard');
      } else {
        console.log('[AuthContext] No user data in response, trying to fetch user...');
        try {
          const userResponse = await api.get('/user');
          if (userResponse?.data) {
            console.log('[AuthContext] Fetched user data:', userResponse.data);
            setUser(userResponse.data);
            debugUser('User set after fetch', userResponse.data);
            navigate('/dashboard');
          } else {
            throw new Error('Failed to fetch user data');
          }
        } catch (userError) {
          console.error('[AuthContext] Error fetching user data:', userError);
          throw new Error('Login successful but could not load user profile');
        }
      }
      
      return user;
    } catch (error) {
      console.error('[AuthContext] Login failed:', error);
      const message = error.response?.data?.message || 'Login failed. Please try again.';
      toast.error(message);
      throw error;
    }
  };

  // Logout function
  const logout = () => {
    console.log('[AuthContext] Logging out user');
    localStorage.removeItem('token');
    if (api?.defaults?.headers?.common?.['Authorization']) {
      delete api.defaults.headers.common['Authorization'];
    }
    setUser(null);
    navigate('/login');
    toast.success('Logged out successfully');
  };

  // Check if user has required role (case-insensitive)
  const hasRole = (requiredRole) => {
    if (!user) {
      console.log('[AuthContext] hasRole: No user');
      return false;
    }
    
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
      console.log(`[AuthContext] Found role in user.role: ${user.role}`);
      return true;
    }
    
    // Check roles array
    if (Array.isArray(user.roles)) {
      const hasMatchingRole = user.roles.some(role => {
        const matches = checkRole(role);
        if (matches) {
          console.log(`[AuthContext] Found matching role:`, role);
        }
        return matches;
      });
      
      if (hasMatchingRole) {
        return true;
      }
    }
    
    // Debug log if no role found
    console.log('[AuthContext] No matching role found:', {
      requiredRole,
      userRole: user.role,
      userRoles: user.roles,
      requiredRoleLower
    });
    
    return false;
  };

  // Check if user has any of the required roles
  const hasAnyRole = (requiredRoles = []) => {
    if (!user) {
      console.log('[AuthContext] hasAnyRole: No user');
      return false;
    }
    const hasAny = requiredRoles.some(role => 
      (user.roles && user.roles.includes(role)) || user.role === role
    );
    console.log(`[AuthContext] Checking any of roles ${requiredRoles.join(', ')}:`, {
      hasAny,
      userRole: user.role,
      userRoles: user.roles
    });
    return hasAny;
  };

  // Debug the current user state
  useEffect(() => {
    console.log('[AuthContext] User state updated:', {
      hasUser: !!user,
      userRole: user?.role,
      userRoles: user?.roles,
      loading
    });
  }, [user, loading]);

  return (
    <AuthContext.Provider
      value={{
        user,
        loading,
        isAuthenticated: !!user,
        login,
        logout,
        updateUser: (data) => {
          console.log('[AuthContext] Updating user data:', data);
          setUser(prev => ({
            ...prev,
            ...data
          }));
        },
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
