import React from 'react';
import { Routes, Route, Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { Suspense, lazy, useEffect } from 'react';
import LoadingSpinner from '../components/common/LoadingSpinner';
import { Toaster } from 'react-hot-toast';

// Lazy load the main dashboard component with error boundary
const DashboardPageV2 = lazy(() => 
  import('../pages/DashboardPageV2').catch(error => {
    console.error('Failed to load DashboardPageV2:', error);
    return { default: () => <div className="text-red-500 p-4">Failed to load dashboard. Please refresh the page.</div> };
  })
);

// Error boundary component
class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error('Dashboard Error:', error, errorInfo);
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="min-h-screen flex items-center justify-center p-4">
          <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg max-w-md w-full">
            <h2 className="text-xl font-bold text-red-600 dark:text-red-400 mb-4">Something went wrong</h2>
            <p className="text-gray-700 dark:text-gray-300 mb-4">
              We're having trouble loading the dashboard. Please try refreshing the page.
            </p>
            <button
              onClick={() => window.location.reload()}
              className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
            >
              Refresh Page
            </button>
            <p className="mt-4 text-xs text-gray-500 dark:text-gray-400">
              Error: {this.state.error?.message || 'Unknown error'}
            </p>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}

// Protected route wrapper
const ProtectedRoute = ({ children }) => {
  const { user, isAuthenticated, loading } = useAuth();
  const location = useLocation();

  // Debug logging
  useEffect(() => {
    console.group('ProtectedRoute Debug');
    console.log('Auth State:', { isAuthenticated, loading, user });
    console.log('Current Path:', location.pathname);
    console.groupEnd();
  }, [isAuthenticated, loading, user, location.pathname]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <LoadingSpinner size="lg" text="Checking authentication..." />
      </div>
    );
  }

  if (!isAuthenticated) {
    console.log('[ProtectedRoute] Not authenticated, redirecting to login');
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  return <ErrorBoundary>{children}</ErrorBoundary>;
}

const EnhancedDashboardRouter = () => {
  console.log('Rendering EnhancedDashboardRouter');
  
  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <Toaster position="top-right" />
      <ErrorBoundary>
        <Suspense
          fallback={
            <div className="flex items-center justify-center min-h-screen">
              <LoadingSpinner size="lg" text="Loading dashboard..." />
            </div>
          }
        >
          <Routes>
            <Route
              path="/*"
              element={
                <ProtectedRoute>
                  <DashboardPageV2 />
                </ProtectedRoute>
              }
            />
          </Routes>
        </Suspense>
      </ErrorBoundary>
    </div>
  );
};

export default EnhancedDashboardRouter;