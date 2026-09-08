import { Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useEffect, useState, Suspense } from 'react';
import Sidebar from './Sidebar';
import Header from './Header';
import { Toaster } from 'react-hot-toast';
import { useAuth } from '../../contexts/AuthContext';
import LoadingSpinner from '../common/LoadingSpinner';

const DashboardLayout = ({ children }) => {
  const { user, loading } = useAuth();
  const location = useLocation();
  const navigate = useNavigate();
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);

  useEffect(() => {
    console.log('[DashboardLayout] Rendering with user:', {
      hasUser: !!user,
      userRole: user?.role,
      userRoles: user?.roles,
      currentPath: location.pathname,
      isLoading: loading
    });

    // Redirect to login if no user and not loading
    if (!loading && !user) {
      console.log('[DashboardLayout] No user and not loading, redirecting to login');
      navigate('/login', { replace: true });
    }
  }, [user, location, loading, navigate]);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gray-50">
        <LoadingSpinner size="large" />
      </div>
    );
  }

  if (!user) {
    console.log('[DashboardLayout] No user, not rendering dashboard');
    return null;
  }

  return (
    <div className="flex h-screen bg-gray-50">
      <Sidebar />
      <div className="flex-1 flex flex-col overflow-hidden">
        <Header toggleSidebar={() => setIsSidebarOpen(!isSidebarOpen)} />
        <main className="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 md:p-6 transition-all duration-300 ml-0 md:ml-64">
          <Toaster position="top-right" />
          
          {/* Debug Panel - Only show in development */}
          {import.meta.env.MODE === 'development' && (
            <div className="p-4 border border-blue-200 rounded-lg bg-blue-50 mb-4">
              <div className="flex justify-between items-center">
                <h3 className="font-bold text-blue-800">Debug Information</h3>
                <button 
                  onClick={() => {
                    console.log('Current user data:', user);
                    console.log('Current route:', location.pathname);
                  }}
                  className="text-xs bg-blue-100 hover:bg-blue-200 text-blue-800 px-2 py-1 rounded"
                >
                  Log to Console
                </button>
              </div>
              <div className="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                  <h4 className="font-semibold">User Info</h4>
                  <pre className="text-blue-700 overflow-x-auto">
                    {JSON.stringify({
                      id: user?.id,
                      name: user?.name,
                      email: user?.email,
                      role: user?.role,
                      rolesCount: user?.roles?.length || 0
                    }, null, 2)}
                  </pre>
                </div>
                <div>
                  <h4 className="font-semibold">Page Info</h4>
                  <pre className="text-blue-700 overflow-x-auto">
                    {JSON.stringify({
                      path: location.pathname,
                      search: location.search,
                      timestamp: new Date().toLocaleString(),
                      env: import.meta.env.MODE
                    }, null, 2)}
                  </pre>
                </div>
              </div>
            </div>
          )}
          
          {/* Main Content */}
          <div className="bg-white rounded-lg shadow p-6">
            <Suspense fallback={
              <div className="flex justify-center items-center p-8">
                <LoadingSpinner />
                <span className="ml-2">Loading dashboard content...</span>
              </div>
            }>
              {children}
            </Suspense>
          </div>
        </main>
      </div>
    </div>
  );
};

export default DashboardLayout;
