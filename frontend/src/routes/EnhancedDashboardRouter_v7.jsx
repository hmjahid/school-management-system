import { Routes, Route, Navigate, useLocation, Outlet } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { Suspense, lazy } from 'react';
import LoadingSpinner from '../components/common/LoadingSpinner';

// Use debug dashboard for now
const DebugDashboard = lazy(() => import('../pages/dashboard/DebugDashboard'));

// Lazy load other dashboard components for better performance
const DashboardLayout = lazy(() => import('../components/dashboard/DashboardLayout_debug'));
const EnhancedAdminDashboard = lazy(() => import('../pages/dashboard/EnhancedAdminDashboard'));
const StudentDashboard = lazy(() => import('../pages/dashboard/StudentDashboard'));
const TeacherDashboard = lazy(() => import('../pages/dashboard/TeacherDashboard'));
const ParentDashboard = lazy(() => import('../pages/dashboard/ParentDashboard'));
const StaffDashboard = lazy(() => import('../pages/dashboard/StaffDashboard'));
const Unauthorized = lazy(() => import('../pages/Unauthorized'));

// Helper function to check user role (case-insensitive)
const hasRole = (user, requiredRole) => {
  if (!user) {
    console.log('[hasRole] No user provided');
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
    console.log(`[hasRole] Found role in user.role: ${user.role}`);
    return true;
  }
  
  // Check roles array
  if (Array.isArray(user.roles)) {
    const hasMatchingRole = user.roles.some(role => {
      const matches = checkRole(role);
      if (matches) {
        console.log(`[hasRole] Found matching role:`, role);
      }
      return matches;
    });
    
    if (hasMatchingRole) {
      return true;
    }
  }
  
  // Debug log if no role found
  console.log('[hasRole] No matching role found:', {
    requiredRole,
    userRole: user.role,
    userRoles: user.roles,
    requiredRoleLower
  });
  
  return false;
};

// Protected route wrapper
const ProtectedRoute = ({ children, requiredRole }) => {
  const { user } = useAuth();
  const location = useLocation();

  console.log('[ProtectedRoute] Checking access:', { 
    requiredRole, 
    userRole: user?.role, 
    userRoles: user?.roles,
    path: location.pathname
  });

  if (!user) {
    console.log('[ProtectedRoute] No user, redirecting to login');
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  if (requiredRole && !hasRole(user, requiredRole)) {
    console.warn('[ProtectedRoute] Unauthorized access attempt:', { 
      requiredRole, 
      userRole: user.role, 
      userRoles: user.roles,
      path: location.pathname
    });
    return <Navigate to="/dashboard/unauthorized" replace />;
  }

  return children || <Outlet />;
};

const EnhancedDashboardRouter = () => {
  const { user } = useAuth();
  const location = useLocation();

  console.log('[EnhancedDashboardRouter] Rendering with user:', {
    hasUser: !!user,
    userRole: user?.role,
    userRoles: user?.roles,
    currentPath: location.pathname
  });

  // Render the appropriate dashboard based on user role
  const renderDashboard = () => {
    if (!user) {
      console.log('[EnhancedDashboardRouter] No user, redirecting to login');
      return <Navigate to="/login" state={{ from: location }} replace />;
    }

    // Show admin dashboard for admin users
    if (hasRole(user, 'admin')) {
      console.log('[EnhancedDashboardRouter] Rendering admin dashboard');
      return <EnhancedAdminDashboard />;
    }

    // For now, show debug dashboard for non-admin users
    console.log('[EnhancedDashboardRouter] No matching role, showing debug dashboard');
    return <DebugDashboard />;
  };

  // Render the dashboard with suspense
  return (
    <Suspense fallback={<LoadingSpinner size="lg" />}>
      {renderDashboard()}
    </Suspense>
  );

  // The following code is kept for reference but currently not used
  // as we're using the debug dashboard above
  /*
  // Determine the default dashboard based on user role
  const getDefaultDashboard = () => {
    if (hasRole(user, 'admin')) return 'admin';
    if (hasRole(user, 'teacher')) return 'teacher';
    if (hasRole(user, 'student')) return 'student';
    if (hasRole(user, 'parent')) return 'parent';
    if (hasRole(user, 'staff')) return 'staff';
    console.warn('[EnhancedDashboardRouter] No valid role found, defaulting to unauthorized');
    return 'unauthorized';
  };

  // If we're at the root dashboard path, redirect to the appropriate dashboard
  if (location.pathname === '/dashboard' || location.pathname === '/dashboard/') {
    const defaultDashboard = getDefaultDashboard();
    console.log(`[EnhancedDashboardRouter] Redirecting to default dashboard: ${defaultDashboard}`);
    return <Navigate to={`/dashboard/${defaultDashboard}`} replace />;
  }

  return (
    <Suspense fallback={<LoadingSpinner size="lg" />}>
      <Routes>
        <Route 
          path="admin/*" 
          element={
            <ProtectedRoute requiredRole="admin">
              <DashboardLayout>
                <EnhancedAdminDashboard />
              </DashboardLayout>
            </ProtectedRoute>
          } 
        />
        <Route 
          path="debug" 
          element={
            <ProtectedRoute>
              <DebugDashboard />
            </ProtectedRoute>
          } 
        />
        <Route 
          path="*" 
          element={
            <div className="p-4">
              <h2 className="text-xl font-bold text-red-600">Dashboard Not Found</h2>
              <p className="mt-2">The requested dashboard could not be found.</p>
              <pre className="mt-4 p-4 bg-gray-100 rounded text-xs overflow-x-auto">
                {JSON.stringify({
                  error: 'Dashboard not found',
                  path: location.pathname,
                  user: {
                    id: user?.id,
                    role: user?.role,
                    roles: user?.roles
                  },
                  timestamp: new Date().toISOString()
                }, null, 2)}
              </pre>
            </div>
          } 
        />
      </Routes>
    </Suspense>
  );
  */
};

export default EnhancedDashboardRouter;
