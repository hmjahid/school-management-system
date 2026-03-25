import { Routes, Route, Navigate, useLocation, Outlet } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { Suspense, lazy } from 'react';
import LoadingSpinner from '../components/common/LoadingSpinner';

// Lazy load dashboard components for better performance
const DashboardLayout = lazy(() => import('../components/dashboard/DashboardLayout'));
const EnhancedAdminDashboard = lazy(() => import('../pages/dashboard/EnhancedAdminDashboard'));
const StudentDashboard = lazy(() => import('../pages/dashboard/StudentDashboard'));
const TeacherDashboard = lazy(() => import('../pages/dashboard/TeacherDashboard'));
const ParentDashboard = lazy(() => import('../pages/dashboard/ParentDashboard'));
const StaffDashboard = lazy(() => import('../pages/dashboard/StaffDashboard'));

// Protected route wrapper
const ProtectedRoute = ({ children, requiredRole }) => {
  const { user } = useAuth();
  const location = useLocation();

  if (!user) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  if (requiredRole && !user.roles?.includes(requiredRole)) {
    return <Navigate to="/unauthorized" replace />;
  }

  return children || <Outlet />;
};

const EnhancedDashboardRouter = () => {
  const { user } = useAuth();
  const location = useLocation();

  // If no user is logged in, redirect to login
  if (!user) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  // Determine the default dashboard based on user role
  const getDefaultDashboard = () => {
    if (user.roles?.includes('admin')) return 'admin';
    if (user.roles?.includes('teacher')) return 'teacher';
    if (user.roles?.includes('student')) return 'student';
    if (user.roles?.includes('parent')) return 'parent';
    if (user.roles?.includes('staff')) return 'staff';
    return 'unauthorized';
  };

  // If we're at the root dashboard path, redirect to the appropriate dashboard
  if (location.pathname === '/dashboard' || location.pathname === '/dashboard/') {
    const defaultDashboard = getDefaultDashboard();
    return <Navigate to={`/dashboard/${defaultDashboard}`} replace />;
  }

  return (
    <Suspense fallback={<LoadingSpinner size="lg" />}>
      <Routes>
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
          element={<Navigate to={`/dashboard/${getDefaultDashboard()}`} replace />} 
        />
      </Routes>
    </Suspense>
  );
};

export default EnhancedDashboardRouter;
