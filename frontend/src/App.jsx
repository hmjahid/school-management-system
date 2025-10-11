import { Routes, Route, Navigate, useRoutes } from 'react-router-dom';
import { Suspense } from 'react';
import { Toaster } from 'react-hot-toast';
import { WebSocketProvider } from './contexts/WebSocketContext';
import { AuthProvider } from './contexts/AuthContext';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import PrivateRoute from './routes/PrivateRoute';
import RegisterPage from './pages/RegisterPage';
import Layout from './components/layout/Layout';
import LoadingSpinner from './components/common/LoadingSpinner';
import feeRoutes from './routes/feeRoutes';

// Layout for authentication pages (login, register, etc.)
const AuthLayout = ({ children }) => (
  <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div className="w-full max-w-md">
      {children}
    </div>
  </div>
);

// Main app routes with WebSocket connection
const AppRoutes = () => {
  // WebSocket connection is managed by the WebSocketProvider
  // All child components can use the useWebSocket hook to interact with the WebSocket
  const routes = useRoutes([
    {
      path: '/',
      element: <Navigate to="/dashboard" replace />,
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
      path: '*',
      element: <Navigate to="/" replace />,
    },
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
        ...feeRoutes,
      ],
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