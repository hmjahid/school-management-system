import { Routes, Route, Navigate } from 'react-router-dom';
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
        
        {/* Admin Dashboard */}
        <Route 
          path="admin/*" 
          element={
            hasRole('admin') ? (
              <EnhancedAdminDashboard user={user} />
            ) : (
              <Navigate to="/dashboard/unauthorized" replace />
            )
          } 
        />
        
        {/* Student Dashboard */}
        <Route 
          path="student/*" 
          element={
            hasRole('student') ? (
              <StudentDashboard user={user} />
            ) : (
              <Navigate to="/dashboard/unauthorized" replace />
            )
          } 
        />
        
        {/* Teacher Dashboard */}
        <Route 
          path="teacher/*" 
          element={
            hasRole('teacher') ? (
              <TeacherDashboard user={user} />
            ) : (
              <Navigate to="/dashboard/unauthorized" replace />
            )
          } 
        />
        
        {/* Parent Dashboard */}
        <Route 
          path="parent/*" 
          element={
            hasRole('parent') ? (
              <ParentDashboard user={user} />
            ) : (
              <Navigate to="/dashboard/unauthorized" replace />
            )
          } 
        />
        
        {/* Staff Dashboard */}
        <Route 
          path="staff/*" 
          element={
            hasRole('staff') ? (
              <StaffDashboard user={user} />
            ) : (
              <Navigate to="/dashboard/unauthorized" replace />
            )
          } 
        />
        
        {/* Unauthorized route */}
        <Route 
          path="unauthorized" 
          element={
            <div className="flex items-center justify-center min-h-screen bg-gray-50 dark:bg-gray-900">
              <div className="p-8 bg-white dark:bg-gray-800 rounded-lg shadow-md text-center">
                <h2 className="text-2xl font-bold text-red-600 dark:text-red-400 mb-4">Access Denied</h2>
                <p className="text-gray-600 dark:text-gray-300 mb-6">You don't have permission to access this dashboard.</p>
                <button 
                  onClick={() => window.history.back()}
                  className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                  Go Back
                </button>
              </div>
            </div>
          } 
        />
        
        {/* 404 Not Found */}
        <Route 
          path="*" 
          element={
            <Suspense fallback={<LoadingSpinner size="lg" />}>
              <NotFound />
            </Suspense>
          } 
        />
      </Routes>
    </Suspense>
  );
};

export default EnhancedDashboardRouter;
