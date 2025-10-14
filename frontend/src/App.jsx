import React, { Suspense, lazy } from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate, useLocation } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { WebSocketProvider } from './contexts/WebSocketContext';
import { AuthProvider } from './contexts/AuthContext';
import LoadingSpinner from './components/common/LoadingSpinner';

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
import { useAuth } from './contexts/AuthContext';

// Component to handle public routes (login, register, etc.)
const PublicRoute = ({ children }) => {
  const { user, loading } = useAuth();
  const location = useLocation();

  if (loading) {
    return <LoadingSpinner fullScreen />;
  }

  if (user) {
    // Redirect to dashboard if user is already logged in
    return <Navigate to="/dashboard" state={{ from: location }} replace />;
  }

  return children;
};

// Dashboard and Admin Pages
import EnhancedDashboardRouter from './routes/EnhancedDashboardRouter';
import WebsiteContentPage from './pages/admin/WebsiteContentPage';
import AboutContentPage from './pages/admin/AboutContentPage';
import WebsiteSettingsPage from './pages/admin/WebsiteSettingsPage';
import Layout from './components/layout/Layout';
import PrivateRoute from './routes/PrivateRoute';
import feeRoutes from './routes/feeRoutes';

// Layout for authentication pages (login, register, etc.)
const AuthLayout = ({ children }) => (
  <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4 w-full">
    <div className="w-full max-w-md">
      {children}
    </div>
  </div>
);

// Main app routes with WebSocket connection
const AppRoutes = () => {
  return (
    <Routes>
      {/* Public routes with WebsiteLayout */}
      <Route element={<WebsiteLayout />}>
        <Route path="/" element={<HomePage />} />
        <Route path="/about" element={<AboutPage />} />
        <Route path="/academics" element={<AcademicsPage />} />
        <Route path="/academics/curriculum" element={<CurriculumPage />} />
        <Route path="/academics/programs" element={<ProgramsPage />} />
        <Route path="/academics/faculty" element={<FacultyPage />} />
        <Route path="/admissions" element={<AdmissionsPage />} />
        <Route path="/news" element={<NewsEventsPage />} />
        <Route path="/gallery" element={<GalleryPage />} />
        <Route path="/contact" element={<ContactPage />} />
        <Route path="/terms" element={<TermsOfServicePage />} />
        <Route path="/privacy" element={<PrivacyPolicyPage />} />
        <Route path="/sitemap" element={<SitemapPage />} />
        <Route path="/careers" element={<CareerPage />} />
      </Route>

      {/* Auth routes */}
      <Route
        path="/login"
        element={
          <PublicRoute>
            <AuthLayout>
              <LoginPage />
            </AuthLayout>
          </PublicRoute>
        }
      />
      <Route
        path="/register"
        element={
          <PublicRoute>
            <AuthLayout>
              <RegisterPage />
            </AuthLayout>
          </PublicRoute>
        }
      />
      <Route
        path="/forgot-password"
        element={
          <PublicRoute>
            <AuthLayout>
              <ForgotPasswordPage />
            </AuthLayout>
          </PublicRoute>
        }
      />

      {/* Dashboard routes - All dashboard routes are handled by EnhancedDashboardRouter */}
      <Route
        path="/dashboard/*"
        element={
          <Suspense fallback={
            <div className="min-h-screen flex items-center justify-center">
              <LoadingSpinner size="lg" />
            </div>
          }>
            <EnhancedDashboardRouter />
          </Suspense>
        }
      />

      {/* Redirect root to home */}
      <Route path="/" element={<Navigate to="/" replace />} />
      
      {/* 404 - Keep this at the end */}
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
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
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error('Error caught by error boundary:', error, errorInfo);
    this.setState({ errorInfo });
  }

  toggleDetails = () => {
    this.setState(prevState => ({
      showDetails: !prevState.showDetails
    }));
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
          <div className="max-w-2xl w-full bg-white rounded-xl shadow-lg p-6">
            <div className="text-center">
              <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg
                  className="h-6 w-6 text-red-600"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                  />
                </svg>
              </div>
              <h2 className="mt-3 text-2xl font-bold text-gray-900">
                Something went wrong!
              </h2>
              <p className="mt-2 text-gray-600">
                We're sorry, but an unexpected error occurred. Our team has been notified.
              </p>
              
              <div className="mt-6">
                <button
                  onClick={() => window.location.reload()}
                  className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  Reload Page
                </button>
                
                <button
                  onClick={this.toggleDetails}
                  className="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  {this.state.showDetails ? 'Hide Details' : 'Show Details'}
                </button>
                
                {this.state.showDetails && (
                  <div className="mt-4 p-4 bg-gray-50 rounded-md overflow-auto max-h-64">
                    <p className="text-sm font-medium text-gray-900 mb-2">Error details:</p>
                    <pre className="text-xs text-red-600">
                      {this.state.error && this.state.error.toString()}
                      {this.state.errorInfo && this.state.errorInfo.componentStack}
                    </pre>
                  </div>
                )}
              </div>
              
              <div className="mt-8">
                <p className="text-sm text-gray-500">
                  If the problem persists, please contact support.
                </p>
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
const App = () => {
  return (
    <ErrorBoundary>
      <WebSocketProvider>
        <AuthProvider>
          <Toaster 
            position="top-right"
            toastOptions={{
              duration: 5000,
              style: {
                borderRadius: '0.5rem',
                background: '#fff',
                color: '#1f2937',
                boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
              },
              success: {
                iconTheme: {
                  primary: '#10B981',
                  secondary: '#fff',
                },
              },
              error: {
                iconTheme: {
                  primary: '#EF4444',
                  secondary: '#fff',
                },
              },
            }}
          />
          <AppRoutes />
        </AuthProvider>
      </WebSocketProvider>
    </ErrorBoundary>
  );
};

export default App;
