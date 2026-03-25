import React from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

// Lazy load components for better performance
const RefundList = React.lazy(() => import('../pages/RefundListPage'));
const RefundDetail = React.lazy(() => import('../pages/RefundDetailPage'));
const RequestRefund = React.lazy(() => import('../pages/RequestRefundPage'));
const RefundSettings = React.lazy(() => import('../pages/RefundSettingsPage'));

// Loading component
const Loading = () => (
  <div className="flex justify-center items-center h-64">
    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
  </div>
);

// Auth wrapper component
const RequireAuth = ({ children, permissions = [] }) => {
  const { isAuthenticated, hasPermission } = useAuth();
  
  if (!isAuthenticated) {
    return <Navigate to="/login" replace state={{ from: window.location.pathname }} />;
  }
  
  // Check if user has all required permissions
  if (permissions.length > 0 && !permissions.every(p => hasPermission(p))) {
    return <Navigate to="/unauthorized" replace />;
  }
  
  return children;
};

const RefundRoutes = () => {
  return (
    <React.Suspense fallback={<Loading />}>
      <Routes>
        {/* Refund List - Requires refunds.view permission */}
        <Route
          path="/"
          element={
            <RequireAuth permissions={['refunds.view']}>
              <RefundList />
            </RequireAuth>
          }
        />
        
        {/* Request Refund - Requires refunds.request permission */}
        <Route
          path="/request"
          element={
            <RequireAuth permissions={['refunds.request']}>
              <RequestRefund />
            </RequireAuth>
          }
        />
        
        {/* Request Refund for Specific Payment - Requires refunds.request permission */}
        <Route
          path="/request/payment/:paymentId"
          element={
            <RequireAuth permissions={['refunds.request']}>
              <RequestRefund />
            </RequireAuth>
          }
        />
        
        {/* Refund Detail - Requires refunds.view permission */}
        <Route
          path="/:id"
          element={
            <RequireAuth permissions={['refunds.view']}>
              <RefundDetail />
            </RequireAuth>
          }
        />
        
        {/* Refund Settings - Requires refunds.settings permission */}
        <Route
          path="/settings"
          element={
            <RequireAuth permissions={['refunds.settings']}>
              <RefundSettings />
            </RequireAuth>
          }
        />
        
        {/* Redirect invalid routes to refunds list */}
        <Route path="*" element={<Navigate to="/refunds" replace />} />
      </Routes>
    </React.Suspense>
  );
};

export default RefundRoutes;
