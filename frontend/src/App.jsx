import { Routes, Route, Navigate, useRoutes, Link } from 'react-router-dom';
import { Suspense } from 'react';
import { Toaster } from 'react-hot-toast';
import { WebSocketProvider } from './contexts/WebSocketContext';
import { AuthProvider } from './contexts/AuthContext';

// Layouts
import WebsiteLayout from './components/website/WebsiteLayout';

// Public Pages
import HomePage from './pages/HomePage';
import AboutPage from './pages/AboutPage';
import AcademicsPage from './pages/AcademicsPage';
import CurriculumPage from './pages/academics/CurriculumPage';
import ProgramsPage from './pages/academics/ProgramsPage';
import FacultyPage from './pages/academics/FacultyPage';
import AdmissionsPage from './pages/AdmissionsPage';
import NewsEventsPage from './pages/NewsEventsPage';
import GalleryPage from './pages/GalleryPage';
import ContactPage from './pages/ContactPage';
import TermsOfServicePage from './pages/TermsOfServicePage';
import PrivacyPolicyPage from './pages/PrivacyPolicyPage';
import SitemapPage from './pages/SitemapPage';
import CareerPage from './pages/CareerPage';

// Auth Pages
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import ForgotPasswordPage from './pages/ForgotPasswordPage';

// Admin Pages
import DashboardPage from './pages/DashboardPage';
import WebsiteContentPage from './pages/admin/WebsiteContentPage';
import AboutContentPage from './pages/admin/AboutContentPage';
import WebsiteSettingsPage from './pages/admin/WebsiteSettingsPage';
import Layout from './components/layout/Layout';
import LoadingSpinner from './components/common/LoadingSpinner';
import PrivateRoute from './routes/PrivateRoute';
import feeRoutes from './routes/feeRoutes';

// Layout for authentication pages (login, register, etc.)
const AuthLayout = ({ children }) => (
  <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4 w-full">
    <div className="w-full">
      {children}
    </div>
  </div>
);

// Main app routes with WebSocket connection
const AppRoutes = () => {
  const routes = useRoutes([
    // Public routes with WebsiteLayout
    {
      element: <WebsiteLayout />,
      children: [
        { path: '/', element: <HomePage /> },
        { path: '/about', element: <AboutPage /> },
        { path: '/academics', element: <AcademicsPage /> },
        { path: '/academics/curriculum', element: <CurriculumPage /> },
        { path: '/academics/programs', element: <ProgramsPage /> },
        { path: '/academics/faculty', element: <FacultyPage /> },
        { path: '/admissions', element: <AdmissionsPage /> },
        { path: '/news', element: <NewsEventsPage /> },
        { path: '/gallery', element: <GalleryPage /> },
        { path: '/contact', element: <ContactPage /> },
        { path: '/terms', element: <TermsOfServicePage /> },
        { path: '/privacy', element: <PrivacyPolicyPage /> },
        { path: '/sitemap', element: <SitemapPage /> },
        { path: '/careers', element: <CareerPage /> },
      ],
    },
    {
      path: '/login',
      element: (
        <AuthLayout>
          <LoginPage />
        </AuthLayout>
      ),
    },
    {
      path: '/register',
      element: (
        <AuthLayout>
          <RegisterPage />
        </AuthLayout>
      ),
    },
    {
      path: '/forgot-password',
      element: (
        <AuthLayout>
          <ForgotPasswordPage />
        </AuthLayout>
      ),
    },
    
    // Protected admin routes
    {
      element: (
        <PrivateRoute>
          <Layout />
        </PrivateRoute>
      ),
      children: [
        {
          path: '/dashboard',
          element: <DashboardPage />,
        },
        {
          path: '/admin/website',
          element: <WebsiteContentPage />,
        },
        {
          path: '/admin/about',
          element: <AboutContentPage />,
        },
        {
          path: '/admin/settings',
          element: <WebsiteSettingsPage />,
        },
        ...feeRoutes,
      ],
    },
    
    // Catch-all route for 404s - redirect to home
    {
      path: '*',
      element: <Navigate to="/" replace />,
    },
  ]);

  return routes;
};

function App() {
  return (
    <WebSocketProvider>
      <AuthProvider>
        <Suspense fallback={<LoadingSpinner fullScreen />}>
          <Toaster 
            position="top-right"
            toastOptions={{
              duration: 4000,
              style: {
                background: '#363636',
                color: '#fff',
              },
            }}
          />
          <AppRoutes />
        </Suspense>
      </AuthProvider>
    </WebSocketProvider>
  );
}

export default App;