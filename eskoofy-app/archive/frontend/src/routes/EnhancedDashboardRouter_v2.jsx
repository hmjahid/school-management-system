import { Routes, Route, Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { Suspense, lazy, useEffect } from 'react';
import LoadingSpinner from '../components/common/LoadingSpinner';

// Lazy load dashboard components for better performance
const DashboardLayout = lazy(() => import('../components/dashboard/DashboardLayout'));
const EnhancedAdminDashboard = lazy(() => import('../pages/dashboard/EnhancedAdminDashboard'));
const StudentDashboard = lazy(() => import('../pages/dashboard/StudentDashboard'));
const TeacherDashboard = lazy(() => import('../pages/dashboard/TeacherDashboard'));
const ParentDashboard = lazy(() => import('../pages/dashboard/ParentDashboard'));
const StaffDashboard = lazy(() => import('../pages/dashboard/StaffDashboard'));
const NotFound = lazy(() => import('../pages/NotFound'));

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
    return 'not-found';
  };

  // Check if the current path is just /dashboard
  const isRootDashboard = location.pathname === '/dashboard' || location.pathname === '/dashboard/';

  // If we're at the root dashboard path, redirect to the appropriate dashboard
  if (isRootDashboard) {
    return <Navigate to={`/dashboard/${getDefaultDashboard()}`} replace />;
  }

  return (
    <Suspense fallback={<LoadingSpinner size="lg" />}>
      <Routes>
        {/* Admin Dashboard */}
        <Route 
          path="admin/*" 
          element={
            user.roles?.includes('admin') ? (
              <DashboardLayout>
                <EnhancedAdminDashboard user={user} />
              </DashboardLayout>
            ) : (
              <Navigate to="/unauthorized" replace />
            )
          } 
        />
        
        {/* Teacher Dashboard */}
        <Route 
          path="teacher/*" 
          element={
            user.roles?.includes('teacher') ? (
              <DashboardLayout>
                <TeacherDashboard user={user} />
              </DashboardLayout>
            ) : (
              <Navigate to="/unauthorized" replace />
            )
          } 
        />
        
        {/* Student Dashboard */}
        <Route 
          path="student/*" 
          element={
            user.roles?.includes('student') ? (
              <DashboardLayout>
                <StudentDashboard user={user} />
              </DashboardLayout>
            ) : (
              <Navigate to="/unauthorized" replace />
            )
          } 
        />
        
        {/* Parent Dashboard */}
        <Route 
          path="parent/*" 
          element={
            user.roles?.includes('parent') ? (
              <DashboardLayout>
                <ParentDashboard user={user} />
              </DashboardLayout>
            ) : (
              <Navigate to="/unauthorized" replace />
            )
          } 
        />
        
        {/* Staff Dashboard */}
        <Route 
          path="staff/*" 
          element={
            user.roles?.includes('staff') ? (
              <DashboardLayout>
                <StaffDashboard user={user} />
              </DashboardLayout>
            ) : (
              <Navigate to="/unauthorized" replace />
            )
          } 
        />
        
        {/* Redirect any unknown dashboard routes to the default dashboard */}
        <Route 
          path="*" 
          element={<Navigate to={getDefaultDashboard()} replace />} 
        />
      </Routes>
    </Suspense>
  );
};

export default EnhancedDashboardRouter;
