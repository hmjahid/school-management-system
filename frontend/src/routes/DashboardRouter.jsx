import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import DashboardLayout from '../components/dashboard/DashboardLayout';
import AdminDashboard from '../pages/dashboard/AdminDashboard';
import StudentDashboard from '../pages/dashboard/StudentDashboard';
import TeacherDashboard from '../pages/dashboard/TeacherDashboard';
import ParentDashboard from '../pages/dashboard/ParentDashboard';
import StaffDashboard from '../pages/dashboard/StaffDashboard';
import CMS from '../pages/dashboard/cms';
import NotFound from '../pages/NotFound';

const DashboardRouter = () => {
  const { user } = useAuth();
  
  // Simple log to check if component is rendered
  console.log('DashboardRouter rendered');
  console.log('[DashboardRouter] Current user:', user);
  console.log('[DashboardRouter] User roles:', user?.roles);

  // If no user is logged in, redirect to login
  if (!user) {
    console.log('[DashboardRouter] No user found, redirecting to login');
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
    console.log('[DashboardRouter] Determining dashboard for user:', user);
    console.log('[DashboardRouter] User roles:', user.roles);
    
    if (hasRole('admin')) {
      console.log('[DashboardRouter] User has admin role');
      return 'admin';
    }
    if (hasRole('teacher')) {
      console.log('[DashboardRouter] User has teacher role');
      return 'teacher';
    }
    if (hasRole('student')) {
      console.log('[DashboardRouter] User has student role');
      return 'student';
    }
    if (hasRole('parent')) {
      console.log('[DashboardRouter] User has parent role');
      return 'parent';
    }
    if (hasRole('staff')) {
      console.log('[DashboardRouter] User has staff role');
      return 'staff';
    }
    return 'not-found';
  };

  return (
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
          user.roles?.includes('admin') ? (
            <DashboardLayout>
              <AdminDashboard />
            </DashboardLayout>
          ) : (
            <Navigate to="/dashboard/unauthorized" replace />
          )
        } 
      />
      
      {/* Student Dashboard */}
      <Route 
        path="student/*" 
        element={
          user.roles?.includes('student') ? (
            <DashboardLayout>
              <StudentDashboard />
            </DashboardLayout>
          ) : (
            <Navigate to="/dashboard/unauthorized" replace />
          )
        } 
      />
      
      {/* Teacher Dashboard */}
      <Route 
        path="teacher/*" 
        element={
          user.roles?.includes('teacher') ? (
            <DashboardLayout>
              <TeacherDashboard />
            </DashboardLayout>
          ) : (
            <Navigate to="/dashboard/unauthorized" replace />
          )
        } 
      />
      
      {/* Parent Dashboard */}
      <Route 
        path="parent/*" 
        element={
          user.roles?.includes('parent') ? (
            <DashboardLayout>
              <ParentDashboard />
            </DashboardLayout>
          ) : (
            <Navigate to="/dashboard/unauthorized" replace />
          )
        } 
      />
      
      {/* Staff Dashboard */}
      <Route 
        path="staff/*" 
        element={
          user.roles?.includes('staff') ? (
            <DashboardLayout>
              <StaffDashboard />
            </DashboardLayout>
          ) : (
            <Navigate to="/dashboard/unauthorized" replace />
          )
        } 
      />
      
      {/* Unauthorized route */}
      <Route 
        path="unauthorized" 
        element={
          <div className="flex items-center justify-center min-h-screen bg-gray-50">
            <div className="p-8 bg-white rounded-lg shadow-md text-center">
              <h2 className="text-2xl font-bold text-red-600 mb-4">Access Denied</h2>
              <p className="text-gray-600 mb-6">You don't have permission to access this dashboard.</p>
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
      
      {/* Admin Routes */}
      {hasRole('admin') && (
        <>
          <Route path="admin" element={<AdminDashboard />} />
          <Route path="cms/*" element={<CMS />} />
        </>
      )}
      
      {/* 404 Not Found */}
      <Route path="*" element={<NotFound />} />
    </Routes>
  );
};

export default DashboardRouter;
