import { Outlet, useLocation } from 'react-router-dom';
import { useEffect } from 'react';
import Sidebar from './Sidebar';
import Header from './Header';
import { Toaster } from 'react-hot-toast';
import { useAuth } from '../../contexts/AuthContext';

const DashboardLayout = () => {
  const { user } = useAuth();
  const location = useLocation();

  useEffect(() => {
    console.log('[DashboardLayout] Rendering with user:', {
      hasUser: !!user,
      userRole: user?.role,
      userRoles: user?.roles,
      currentPath: location.pathname
    });
  }, [user, location]);

  if (!user) {
    console.log('[DashboardLayout] No user, not rendering dashboard');
    return null;
  }

  return (
    <div className="flex h-screen bg-gray-50">
      <Sidebar />
      <div className="flex-1 flex flex-col overflow-hidden">
        <Header />
        <main className="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 md:p-6">
          <Toaster position="top-right" />
          <div className="p-4 border border-blue-200 rounded-lg bg-blue-50 mb-4">
            <h3 className="font-bold text-blue-800">DashboardLayout Debug Info</h3>
            <pre className="text-xs text-blue-700 mt-2 overflow-x-auto">
              {JSON.stringify({
                user: {
                  id: user?.id,
                  name: user?.name,
                  email: user?.email,
                  role: user?.role,
                  roles: user?.roles
                },
                path: location.pathname,
                timestamp: new Date().toISOString()
              }, null, 2)}
            </pre>
          </div>
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default DashboardLayout;
