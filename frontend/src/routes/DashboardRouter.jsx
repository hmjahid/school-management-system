import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import DashboardLayout from '../components/dashboard/DashboardLayout';
import AdminDashboard from '../pages/dashboard/AdminDashboard';
import StudentDashboard from '../pages/dashboard/StudentDashboard';
import TeacherDashboard from '../pages/dashboard/TeacherDashboard';
import ParentDashboard from '../pages/dashboard/ParentDashboard';
import StaffDashboard from '../pages/dashboard/StaffDashboard';
import NotFound from '../pages/NotFound';

const DashboardRouter = () => {
  const { user } = useAuth();

  // If no user is logged in, redirect to login
  if (!user) {
    return <Navigate to="/login" replace />;
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
      
      {/* 404 Not Found */}
      <Route path="*" element={<NotFound />} />
    </Routes>
  );
};

export default DashboardRouter;
