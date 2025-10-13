import React, { Suspense, useEffect } from 'react';
import { Routes, Route, Navigate, useRoutes, Link, useLocation } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { WebSocketProvider } from './contexts/WebSocketContext';
import { AuthProvider } from './contexts/AuthContext_debug';

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
import LoginPage from './pages/LoginPageV3';
import RegisterPage from './pages/RegisterPage';
import ForgotPasswordPage from './pages/ForgotPasswordPage';

// Dashboard and Admin Pages
import EnhancedDashboardRouter from './routes/EnhancedDashboardRouter_v7'; // Using debug router
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

// Debug component to log router changes
const DebugRouter = ({ children }) => {
  const location = useLocation();
  
  useEffect(() => {
    console.log('[Router] Current path:', location.pathname);
  }, [location]);
  
  return children;
};

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
          <DebugRouter>
            <Layout />
          </DebugRouter>
        </PrivateRoute>
      ),
      children: [
        { 
          path: '/dashboard/*', 
          element: <EnhancedDashboardRouter />
        },
        { path: '/admin/website/content', element: <WebsiteContentPage /> },
        { path: '/admin/website/about', element: <AboutContentPage /> },
        { path: '/admin/website/settings', element: <WebsiteSettingsPage /> },
        ...feeRoutes,
      ],
    },
    
    // Error boundary route
    {
      path: '/error',
      element: (
        <div className="min-h-screen flex items-center justify-center bg-gray-50">
          <div className="bg-white p-8 rounded-lg shadow-lg max-w-md w-full text-center">
            <h1 className="text-2xl font-bold text-red-600 mb-4">Oops! Something went wrong</h1>
            <p className="text-gray-700 mb-6">We're having trouble loading the page. Please try again later.</p>
            <Link 
              to="/" 
              className="inline-block bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors"
            >
              Return Home
            </Link>
          </div>
        </div>
      ),
    },
    
    // Redirect any unknown routes to home
    { path: '*', element: <Navigate to="/" replace /> },
  ]);

  return routes;
};

// Error boundary component
class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { 
      hasError: false, 
      error: null, 
      errorInfo: null,
      showDetails: false
    };
  }

  static getDerivedStateFromError(error) {
    console.error('Error caught by error boundary:', error);
    return { 
      hasError: true,
      error: error
    };
  }

  componentDidCatch(error, errorInfo) {
    console.error('Error caught by error boundary:', error, errorInfo);
    this.setState({
      error: error,
      errorInfo: errorInfo
    });
  }

  toggleDetails = () => {
    this.setState(prevState => ({
      showDetails: !prevState.showDetails
    }));
  };

  render() {
    if (this.state.hasError) {
      return (
        <div className="min-h-screen bg-gray-100 p-4 flex items-center justify-center">
          <div className="bg-white p-8 rounded-lg shadow-lg max-w-2xl w-full">
            <div className="text-center">
              <div className="text-6xl mb-4">⚠️</div>
              <h1 className="text-2xl font-bold text-red-600 mb-2">Oops! Something went wrong</h1>
              <p className="text-gray-700 mb-6">
                We're having trouble loading the page. Please try again later.
              </p>
              
              <button
                onClick={this.toggleDetails}
                className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-4"
              >
                {this.state.showDetails ? 'Hide details' : 'Show details'}
              </button>
              
              {this.state.showDetails && (
                <div className="bg-gray-50 p-4 rounded-md text-left text-sm text-gray-600 font-mono overflow-auto max-h-64">
                  <p className="font-semibold mb-2">Error details:</p>
                  <pre className="whitespace-pre-wrap">
                    {this.state.error && this.state.error.toString()}
                  </pre>
                  
                  {this.state.errorInfo && this.state.errorInfo.componentStack && (
                    <>
                      <p className="font-semibold mt-4 mb-2">Component stack:</p>
                      <pre className="whitespace-pre-wrap text-xs">
                        {this.state.errorInfo.componentStack}
                      </pre>
                    </>
                  )}
                </div>
              )}
              
              <div className="mt-6">
                <button
                  onClick={() => window.location.reload()}
                  className="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors"
                >
                  Reload Page
                </button>
              </div>
            </div>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

// Main App component
function App() {
  return (
    <ErrorBoundary>
      <AuthProvider>
        <div className="min-h-screen bg-white dark:bg-gray-900">
          <Suspense fallback={
            <div className="flex items-center justify-center min-h-screen">
              <LoadingSpinner size="lg" />
            </div>
          }>
            <AppRoutes />
          </Suspense>
          <Toaster position="top-right" />
        </div>
      </AuthProvider>
    </ErrorBoundary>
  );
}

export default App;
