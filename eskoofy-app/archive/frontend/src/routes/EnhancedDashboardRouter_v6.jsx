import { Routes, Route, Navigate, useLocation, Outlet } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { Suspense, lazy } from 'react';
import LoadingSpinner from '../components/common/LoadingSpinner';

// Lazy load dashboard components for better performance
const DashboardLayout = lazy(() => import('../components/dashboard/DashboardLayout_debug'));
const EnhancedAdminDashboard = lazy(() => import('../pages/dashboard/EnhancedAdminDashboard'));
const StudentDashboard = lazy(() => import('../pages/dashboard/StudentDashboard'));
const TeacherDashboard = lazy(() => import('../pages/dashboard/TeacherDashboard'));
const ParentDashboard = lazy(() => import('../pages/dashboard/ParentDashboard'));
const StaffDashboard = lazy(() => import('../pages/dashboard/StaffDashboard'));
const Unauthorized = lazy(() => import('../pages/Unauthorized'));

// Helper function to check user role
const hasRole = (user, requiredRole) => {
  // Check both user.roles (array) and user.role (string)
  const roles = user?.roles || [];
  const role = user?.role || '';
  const hasRole = roles.includes(requiredRole) || role === requiredRole;
  console.log(`[hasRole] Checking ${requiredRole} for user:`, { roles, role, hasRole });
  return hasRole;
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

  // If no user is logged in, redirect to login
  if (!user) {
    console.log('[EnhancedDashboardRouter] No user, redirecting to login');
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

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
        {/* Unauthorized Page */}
        <Route path="unauthorized" element={<Unauthorized />} />
        
        {/* Admin Dashboard */}
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
        
        {/* Teacher Dashboard */}
        <Route 
          path="teacher/*" 
          element={
            <ProtectedRoute requiredRole="teacher">
              <DashboardLayout>
                <TeacherDashboard />
              </DashboardLayout>
            </ProtectedRoute>
          } 
        />
        
        {/* Student Dashboard */}
        <Route 
          path="student/*" 
          element={
            <ProtectedRoute requiredRole="student">
              <DashboardLayout>
                <StudentDashboard />
              </DashboardLayout>
            </ProtectedRoute>
          } 
        />
        
        {/* Parent Dashboard */}
        <Route 
          path="parent/*" 
          element={
            <ProtectedRoute requiredRole="parent">
              <DashboardLayout>
                <ParentDashboard />
              </DashboardLayout>
            </ProtectedRoute>
          } 
        />
        
        {/* Staff Dashboard */}
        <Route 
          path="staff/*" 
          element={
            <ProtectedRoute requiredRole="staff">
              <DashboardLayout>
                <StaffDashboard />
              </DashboardLayout>
            </ProtectedRoute>
          } 
        />
        
        {/* Catch-all route for unknown dashboard paths */}
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
};

export default EnhancedDashboardRouter;
