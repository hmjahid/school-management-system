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
import AdmissionsPage from './pages/AdmissionsPage';
import NewsEventsPage from './pages/NewsEventsPage';
import GalleryPage from './pages/GalleryPage';
import ContactPage from './pages/ContactPage';

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

// Using WebsiteLayout for all pages including auth to maintain consistent header
const AuthLayout = ({ children }) => (
  <WebsiteLayout>
    <div className="min-h-[60vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <div className="w-full max-w-md space-y-8">
        <div className="text-center">
          <h2 className="mt-6 text-3xl font-extrabold text-gray-900">
            {window.location.pathname.includes('login') ? 'Sign in to your account' : 'Create a new account'}
          </h2>
        </div>
        {children}
      </div>
    </div>
  </WebsiteLayout>
);

// Main app routes with WebSocket connection
const AppRoutes = () => {
  const routes = useRoutes([
    // Public routes with WebsiteLayout
    {
      element: <WebsiteLayout />,
      children: [
        {
          path: '/',
          element: <HomePage />,
        },
        {
          path: '/about',
          element: <AboutPage />,
        },
        {
          path: '/academics',
          element: <AcademicsPage />,
        },
        {
          path: '/admissions',
          element: <AdmissionsPage />,
        },
        {
          path: '/news',
          element: <NewsEventsPage />,
        },
        {
          path: '/gallery',
          element: <GalleryPage />,
        },
        {
          path: '/contact',
          element: <ContactPage />,
        },
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