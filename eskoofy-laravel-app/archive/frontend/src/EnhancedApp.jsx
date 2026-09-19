import { Routes, Route, Navigate, useRoutes, Link } from 'react-router-dom';
import { Suspense, lazy } from 'react';
import { Toaster } from 'react-hot-toast';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { WebSocketProvider } from './contexts/WebSocketContext';
import { AuthProvider } from './contexts/AuthContext';
import { EnhancedDashboardProvider } from './contexts/EnhancedDashboardContext';

// Layouts
import WebsiteLayout from './components/website/WebsiteLayout';
import EnhancedDashboardLayout from './components/dashboard/EnhancedDashboardLayout';

// Public Pages
const HomePage = lazy(() => import('./pages/HomePage'));
const AboutPage = lazy(() => import('./pages/AboutPage'));
const AcademicsPage = lazy(() => import('./pages/AcademicsPage'));
const CurriculumPage = lazy(() => import('./pages/academics/CurriculumPage'));
const ProgramsPage = lazy(() => import('./pages/academics/ProgramsPage'));
const FacultyPage = lazy(() => import('./pages/academics/FacultyPage'));
const AdmissionsPage = lazy(() => import('./pages/AdmissionsPage'));
const NewsEventsPage = lazy(() => import('./pages/NewsEventsPage'));
const GalleryPage = lazy(() => import('./pages/GalleryPage'));
const ContactPage = lazy(() => import('./pages/ContactPage'));
const TermsOfServicePage = lazy(() => import('./pages/TermsOfServicePage'));
const PrivacyPolicyPage = lazy(() => import('./pages/PrivacyPolicyPage'));
const SitemapPage = lazy(() => import('./pages/SitemapPage'));
const CareerPage = lazy(() => import('./pages/CareerPage'));

// Auth Pages
const LoginPage = lazy(() => import('./pages/LoginPage'));
const RegisterPage = lazy(() => import('./pages/RegisterPage'));
const ForgotPasswordPage = lazy(() => import('./pages/ForgotPasswordPage'));

// Admin Pages
const DashboardPage = lazy(() => import('./pages/DashboardPage'));
const WebsiteContentPage = lazy(() => import('./pages/admin/WebsiteContentPage'));
const AboutContentPage = lazy(() => import('./pages/admin/AboutContentPage'));
const WebsiteSettingsPage = lazy(() => import('./pages/admin/WebsiteSettingsPage'));
import LoadingSpinner from './components/common/LoadingSpinner';
import PrivateRoute from './routes/PrivateRoute';
import feeRoutes from './routes/feeRoutes';

// Create a client
const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // 5 minutes
      refetchOnWindowFocus: true,
      retry: 1,
    },
  },
});

// Layout for authentication pages (login, register, etc.)
const AuthLayout = ({ children }) => (
  <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center p-4 w-full">
    <div className="w-full max-w-md">
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
        { path: '/', element: <Suspense fallback={<LoadingSpinner fullScreen />}><HomePage /></Suspense> },
        { path: '/about', element: <Suspense fallback={<LoadingSpinner fullScreen />}><AboutPage /></Suspense> },
        { path: '/academics', element: <Suspense fallback={<LoadingSpinner fullScreen />}><AcademicsPage /></Suspense> },
        { path: '/academics/curriculum', element: <Suspense fallback={<LoadingSpinner fullScreen />}><CurriculumPage /></Suspense> },
        { path: '/academics/programs', element: <Suspense fallback={<LoadingSpinner fullScreen />}><ProgramsPage /></Suspense> },
        { path: '/academics/faculty', element: <Suspense fallback={<LoadingSpinner fullScreen />}><FacultyPage /></Suspense> },
        { path: '/admissions', element: <Suspense fallback={<LoadingSpinner fullScreen />}><AdmissionsPage /></Suspense> },
        { path: '/news', element: <Suspense fallback={<LoadingSpinner fullScreen />}><NewsEventsPage /></Suspense> },
        { path: '/gallery', element: <Suspense fallback={<LoadingSpinner fullScreen />}><GalleryPage /></Suspense> },
        { path: '/contact', element: <Suspense fallback={<LoadingSpinner fullScreen />}><ContactPage /></Suspense> },
        { path: '/terms', element: <Suspense fallback={<LoadingSpinner fullScreen />}><TermsOfServicePage /></Suspense> },
        { path: '/privacy', element: <Suspense fallback={<LoadingSpinner fullScreen />}><PrivacyPolicyPage /></Suspense> },
        { path: '/sitemap', element: <Suspense fallback={<LoadingSpinner fullScreen />}><SitemapPage /></Suspense> },
        { path: '/careers', element: <Suspense fallback={<LoadingSpinner fullScreen />}><CareerPage /></Suspense> },
      ],
    },
    {
      path: '/login',
      element: (
        <AuthLayout>
          <Suspense fallback={<LoadingSpinner fullScreen />}>
            <LoginPage />
          </Suspense>
        </AuthLayout>
      ),
    },
    {
      path: '/register',
      element: (
        <AuthLayout>
          <Suspense fallback={<LoadingSpinner fullScreen />}>
            <RegisterPage />
          </Suspense>
        </AuthLayout>
      ),
    },
    {
      path: '/forgot-password',
      element: (
        <AuthLayout>
          <Suspense fallback={<LoadingSpinner fullScreen />}>
            <ForgotPasswordPage />
          </Suspense>
        </AuthLayout>
      ),
    },
    
    // Protected admin routes with EnhancedDashboardLayout
    {
      element: (
        <PrivateRoute>
          <EnhancedDashboardLayout />
        </PrivateRoute>
      ),
      children: [
        {
          path: '/dashboard/*',
          element: (
            <Suspense fallback={<LoadingSpinner fullScreen />}>
              <DashboardPage />
            </Suspense>
          ),
        },
        {
          path: '/admin/website/*',
          element: (
            <Suspense fallback={<LoadingSpinner fullScreen />}>
              <WebsiteContentPage />
            </Suspense>
          ),
        },
        {
          path: '/admin/about/*',
          element: (
            <Suspense fallback={<LoadingSpinner fullScreen />}>
              <AboutContentPage />
            </Suspense>
          ),
        },
        {
          path: '/admin/settings/*',
          element: (
            <Suspense fallback={<LoadingSpinner fullScreen />}>
              <WebsiteSettingsPage />
            </Suspense>
          ),
        },
        ...feeRoutes.map(route => ({
          ...route,
          element: <Suspense fallback={<LoadingSpinner fullScreen />}>{route.element}</Suspense>,
        })),
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
    <QueryClientProvider client={queryClient}>
      <WebSocketProvider>
        <AuthProvider>
          <EnhancedDashboardProvider>
            <Suspense fallback={<LoadingSpinner fullScreen />}>
              <Toaster 
                position="top-right"
                toastOptions={{
                  duration: 4000,
                  style: {
                    background: 'var(--color-bg-elevated)',
                    color: 'var(--color-text)',
                    border: '1px solid var(--color-border)',
                  },
                }}
              />
              <AppRoutes />
              {import.meta.env.DEV && <ReactQueryDevtools initialIsOpen={false} />}
            </Suspense>
          </EnhancedDashboardProvider>
        </AuthProvider>
      </WebSocketProvider>
    </QueryClientProvider>
  );
}

export default App;
