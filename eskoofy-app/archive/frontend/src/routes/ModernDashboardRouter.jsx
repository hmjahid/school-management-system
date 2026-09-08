import { Routes, Route, Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import DashboardLayout from '../components/dashboard/modern/DashboardLayout';
import ModernDashboard from '../pages/dashboard/ModernDashboard';
import ProfilePage from '../pages/ProfilePage';
import NotFoundPage from "../pages/NotFoundPage";

// Admin Routes
import UsersPage from '../pages/admin/UsersPage';
import ClassesPage from '../pages/admin/ClassesPage';
import FinancePage from '../pages/admin/FinancePage';
import ReportsPage from '../pages/admin/ReportsPage';
import SettingsPage from '../pages/admin/SettingsPage';

// Settings Subpages
import GeneralSettings from '../pages/admin/settings/GeneralSettings';
import SecuritySettings from '../pages/admin/settings/SecuritySettings';
import EmailSettings from '../pages/admin/settings/EmailSettings';
import NotificationSettings from '../pages/admin/settings/NotificationSettings';
import PaymentSettings from '../pages/admin/settings/PaymentSettings';
import BackupSettings from '../pages/admin/settings/BackupSettings';

// CMS Components
import CMS from '../pages/dashboard/cms';

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
            
            {/* User Management */}
            <Route path="users" element={<UsersPage />} />
            <Route path="users/students" element={<UsersPage type="students" />} />
            <Route path="users/teachers" element={<UsersPage type="teachers" />} />
            <Route path="users/parents" element={<UsersPage type="parents" />} />
            
            {/* Class Management */}
            <Route path="classes" element={<ClassesPage />} />
            <Route path="classes/sections" element={<ClassesPage showSections />} />
            
            {/* Finance */}
            <Route path="finance" element={<FinancePage />}>
              <Route index element={<FinancePage tab="overview" />} />
              <Route path="fees" element={<FinancePage tab="fees" />} />
              <Route path="payments" element={<FinancePage tab="payments" />} />
              <Route path="expenses" element={<FinancePage tab="expenses" />} />
            </Route>
            
            {/* Reports */}
            <Route path="reports" element={<ReportsPage />}>
              <Route index element={<ReportsPage tab="overview" />} />
              <Route path="attendance" element={<ReportsPage tab="attendance" />} />
              <Route path="exams" element={<ReportsPage tab="exams" />} />
              <Route path="finance" element={<ReportsPage tab="finance" />} />
            </Route>
            
            {/* CMS */}
            <Route path="cms/*" element={<CMS />} />
            
            {/* Settings */}
            <Route path="settings" element={<SettingsPage />}>
              <Route index element={<Navigate to="general" replace />} />
              <Route path="general" element={<GeneralSettings />} />
              <Route path="security" element={<SecuritySettings />} />
              <Route path="email" element={<EmailSettings />} />
              <Route path="notifications" element={<NotificationSettings />} />
              <Route path="payments" element={<PaymentSettings />} />
              <Route path="backup" element={<BackupSettings />} />
            </Route>
            
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
            
            {/* Student Schedule */}
            <Route path="schedule" element={<StudentSchedulePage />} />
            
            {/* Student Grades */}
            <Route path="grades" element={<StudentGradesPage />}>
              <Route index element={<StudentGradesPage tab="overview" />} />
              <Route path="subject/:subjectId" element={<StudentGradesPage tab="subject" />} />
              <Route path="term/:termId" element={<StudentGradesPage tab="term" />} />
            </Route>
            
            {/* Student Assignments */}
            <Route path="assignments" element={<AssignmentsPage />}>
              <Route index element={<AssignmentsPage tab="all" />} />
              <Route path="pending" element={<AssignmentsPage tab="pending" />} />
              <Route path="submitted" element={<AssignmentsPage tab="submitted" />} />
              <Route path="graded" element={<AssignmentsPage tab="graded" />} />
              <Route path="overdue" element={<AssignmentsPage tab="overdue" />} />
              <Route path="assignment/:assignmentId" element={<AssignmentsPage tab="details" />} />
            </Route>
            
            {/* Student Profile */}
            <Route path="profile" element={<ProfilePage />}>
              <Route index element={<ProfilePage tab="personal" />} />
              <Route path="settings" element={<ProfilePage tab="settings" />} />
              <Route path="notifications" element={<ProfilePage tab="notifications" />} />
            </Route>
            
            {/* Student Resources */}
            <Route path="resources" element={<div>Student Resources</div>}>
              <Route index element={<div>Resources Overview</div>} />
              <Route path="documents" element={<div>Documents</div>} />
              <Route path="timetable" element={<div>Class Timetable</div>} />
              <Route path="library" element={<div>Digital Library</div>} />
            </Route>
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
