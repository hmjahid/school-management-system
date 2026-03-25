import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import LoadingSpinner from '../components/common/LoadingSpinner';

/**
 * A wrapper for routes that require authentication
 */
const PrivateRoute = ({ children }) => {
  const { user, loading } = useAuth();

  if (loading) {
    return <LoadingSpinner fullScreen />;
  }

  // If not authenticated, redirect to login
  if (!user) return <Navigate to="/login" replace />;

  // If children are provided directly, render them (used in App.jsx), otherwise render nested routes via Outlet
  return children ? children : <Outlet />;
};

export default PrivateRoute;
