import { Routes, Route, Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import DashboardLayout from '../components/dashboard/modern/DashboardLayout';
import ModernDashboard from '../pages/dashboard/ModernDashboard';
import ProfilePage from '../pages/ProfilePage';
import NotFoundPage from '../pages/NotFoundPage';

// Admin Routes
import UsersPage from '../pages/admin/UsersPage';
import ClassesPage from '../pages/admin/ClassesPage';
import FinancePage from '../pages/admin/FinancePage';
import ReportsPage from '../pages/admin/ReportsPage';
import SettingsPage from '../pages/admin/SettingsPage';

// Teacher Routes
import TeacherClassesPage from '../pages/teacher/ClassesPage';
import AttendancePage from '../pages/teacher/AttendancePage';
import GradesPage from '../pages/teacher/GradesPage';

// Student Routes
import StudentSchedulePage from '../pages/student/SchedulePage';
import StudentGradesPage from '../pages/student/GradesPage';
import AssignmentsPage from '../pages/student/AssignmentsPage';

// Parent Routes
import ChildrenPage from '../pages/parent/ChildrenPage';
import ParentAttendancePage from '../pages/parent/AttendancePage';
import PaymentsPage from '../pages/parent/PaymentsPage';

const ModernDashboardRouter = () => {
  const { user } = useAuth();
  const location = useLocation();

  // Redirect to login if not authenticated
  if (!user) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  // Role-based routes
  const getRoleBasedRoutes = () => {
    switch (user.role) {
      case 'admin':
        return (
          <>
            <Route index element={<ModernDashboard />} />
            <Route path="dashboard" element={<ModernDashboard />} />
            <Route path="users/*" element={<UsersPage />} />
            <Route path="classes/*" element={<ClassesPage />} />
            <Route path="finance/*" element={<FinancePage />} />
            <Route path="reports/*" element={<ReportsPage />} />
            <Route path="settings/*" element={<SettingsPage />} />
            <Route path="profile" element={<ProfilePage />} />
          </>
        );
      case 'teacher':
        return (
          <>
            <Route index element={<ModernDashboard />} />
            <Route path="dashboard" element={<ModernDashboard />} />
            <Route path="classes/*" element={<TeacherClassesPage />} />
            <Route path="attendance/*" element={<AttendancePage />} />
            <Route path="grades/*" element={<GradesPage />} />
            <Route path="profile" element={<ProfilePage />} />
          </>
        );
      case 'student':
        return (
          <>
            <Route index element={<ModernDashboard />} />
            <Route path="dashboard" element={<ModernDashboard />} />
            <Route path="schedule" element={<StudentSchedulePage />} />
            <Route path="grades" element={<StudentGradesPage />} />
            <Route path="assignments" element={<AssignmentsPage />} />
            <Route path="profile" element={<ProfilePage />} />
          </>
        );
      case 'parent':
        return (
          <>
            <Route index element={<ModernDashboard />} />
            <Route path="dashboard" element={<ModernDashboard />} />
            <Route path="children" element={<ChildrenPage />} />
            <Route path="attendance" element={<ParentAttendancePage />} />
            <Route path="payments" element={<PaymentsPage />} />
            <Route path="profile" element={<ProfilePage />} />
          </>
        );
      default:
        return (
          <>
            <Route index element={<ModernDashboard />} />
            <Route path="dashboard" element={<ModernDashboard />} />
            <Route path="profile" element={<ProfilePage />} />
          </>
        );
    }
  };

  return (
    <Routes>
      <Route element={<DashboardLayout />}>
        {getRoleBasedRoutes()}
        <Route path="*" element={<NotFoundPage />} />
      </Route>
    </Routes>
  );
};

export default ModernDashboardRouter;
