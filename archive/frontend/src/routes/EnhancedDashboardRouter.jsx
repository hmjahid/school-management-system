import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { Suspense, lazy } from 'react';
import LoadingSpinner from '../components/common/LoadingSpinner';

// Import ModernDashboard directly instead of using lazy loading
import ModernDashboard from '../pages/dashboard/ModernDashboard';

// Lazy load other components
const DashboardLayout = lazy(() => import('../components/dashboard/DashboardLayout'));
const NotFound = lazy(() => import('../pages/NotFound'));

const EnhancedDashboardRouter = () => {
  console.log('[EnhancedDashboardRouter] Component mounted');
  const { user } = useAuth();
  
  console.log('[EnhancedDashboardRouter] User from useAuth:', user);

  // If no user is logged in, redirect to login
  if (!user) {
    console.log('[EnhancedDashboardRouter] No user found, redirecting to login');
    console.log('[EnhancedDashboardRouter] Dashboard router called without user');
    console.log('[EnhancedDashboardRouter] Dashboard router called');
    return <Navigate to="/login" replace />;
  }

  // Helper function to check if user has a specific role
  const hasRole = (roleName) => {
    if (!user?.roles) return false;
    
    // Handle both array of roles and direct role property
    const roles = Array.isArray(user.roles) ? user.roles : [user.role];
    
    return roles.some(role => {
      // Handle role as object with name property
      if (role && typeof role === 'object') {
        return (role.name || '').toLowerCase() === roleName.toLowerCase();
      }
      // Handle role as string
      if (typeof role === 'string') {
        return role.toLowerCase() === roleName.toLowerCase();
      }
      return false;
    });
  };

  // Determine the default dashboard based on user role
  const getDefaultDashboard = () => {
    console.log('[EnhancedDashboardRouter] Determining dashboard for user:', user);
    console.log('[EnhancedDashboardRouter] User roles:', user.roles);
    console.log('[EnhancedDashboardRouter] Dashboard router called for user:', user);
    console.log('[EnhancedDashboardRouter] Dashboard router called');
    
    if (hasRole('admin')) {
      console.log('[EnhancedDashboardRouter] User has admin role');
      return 'admin';
    }
    if (hasRole('teacher')) {
      console.log('[EnhancedDashboardRouter] User has teacher role');
      return 'teacher';
    }
    if (hasRole('student')) {
      console.log('[EnhancedDashboardRouter] User has student role');
      return 'student';
    }
    if (hasRole('parent')) {
      console.log('[EnhancedDashboardRouter] User has parent role');
      return 'parent';
    }
    if (hasRole('staff')) {
      console.log('[EnhancedDashboardRouter] User has staff role');
      return 'staff';
    }
    console.log('[EnhancedDashboardRouter] No matching role found');
    return 'not-found';
  };

  return (
    <Suspense fallback={<LoadingSpinner size="lg" />}>
      <Routes>
        {/* Main dashboard route redirects to role-specific dashboard */}
        <Route 
          path="/" 
          element={<Navigate to={`/dashboard/${getDefaultDashboard()}`} replace />} 
        />
        
        {/* Modern Dashboard for all roles */}
        <Route 
          path=":role/*" 
          element={
            user ? (
              <ModernDashboard user={user} />
            ) : (
              <Navigate to="/login" replace />
            )
          } 
        />
        
        {/* Unauthorized route */}
        <Route 
          path="unauthorized" 
          element={
            <div className="flex items-center justify-center min-h-screen bg-gray-50 p-4">
              <div className="text-center">
                <h2 className="text-2xl font-bold text-red-600 mb-2">Access Denied</h2>
                <p className="text-gray-600">You don't have permission to view this page.</p>
                <button 
                  onClick={() => window.history.back()} 
                  className="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                >
                  Go Back
                </button>
              </div>
            </div>
          } 
        />
        
        {/* 404 - Keep this at the end */}
        <Route path="*" element={<Navigate to="/404" replace />} />
      </Routes>
    </Suspense>
  );
};

export default EnhancedDashboardRouter;
