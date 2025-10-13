import { useEffect, useState, useContext, Suspense } from 'react';
import { useNavigate, Outlet } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import api from '../services/api';
import { AuthContext } from '../contexts/AuthContext';
import LoadingSpinner from '../components/common/LoadingSpinner';
import EnhancedAdminDashboard from './dashboard/EnhancedAdminDashboard';

// Lazy load other dashboard components to improve initial load performance
const TeacherDashboard = () => (
  <div className="p-6 max-w-6xl mx-auto">
    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-gray-800 dark:text-white">Teacher Dashboard</h2>
        <p className="text-gray-600 dark:text-gray-300">Welcome back!</p>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {[1, 2, 3].map((item) => (
          <div key={item} className="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
            <div className="h-4 bg-gray-200 dark:bg-gray-600 rounded w-3/4 mb-3"></div>
            <div className="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/2"></div>
          </div>
        ))}
      </div>
    </div>
  </div>
);

const StudentDashboard = () => (
  <div className="p-6 max-w-6xl mx-auto">
    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-gray-800 dark:text-white">Student Dashboard</h2>
        <p className="text-gray-600 dark:text-gray-300">Welcome back!</p>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-lg border border-blue-100 dark:border-blue-900/30">
          <h3 className="font-medium text-blue-800 dark:text-blue-200 mb-2">My Classes</h3>
          <p className="text-blue-600 dark:text-blue-300 text-3xl font-bold">5</p>
        </div>
        <div className="bg-green-50 dark:bg-green-900/20 p-6 rounded-lg border border-green-100 dark:border-green-900/30">
          <h3 className="font-medium text-green-800 dark:text-green-200 mb-2">Assignments Due</h3>
          <p className="text-green-600 dark:text-green-300 text-3xl font-bold">3</p>
        </div>
      </div>
    </div>
  </div>
);

const ParentDashboard = () => (
  <div className="p-6 max-w-6xl mx-auto">
    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-gray-800 dark:text-white">Parent Dashboard</h2>
        <p className="text-gray-600 dark:text-gray-300">Welcome back!</p>
      </div>
      <div className="bg-yellow-50 dark:bg-yellow-900/20 p-6 rounded-lg border border-yellow-100 dark:border-yellow-900/30">
        <h3 className="font-medium text-yellow-800 dark:text-yellow-200 mb-4">My Children</h3>
        <div className="space-y-4">
          {[1, 2].map((child) => (
            <div key={child} className="flex items-center p-3 bg-white dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
              <div className="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-600 mr-3"></div>
              <div>
                <p className="font-medium text-gray-800 dark:text-gray-200">Student {child} Name</p>
                <p className="text-sm text-gray-500 dark:text-gray-400">Grade {5 + child}</p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  </div>
);

export default function DashboardPageV2() {
    const [user, setUser] = useState(null);
    const { logout } = useContext(AuthContext);
    const navigate = useNavigate();

    // Fetch user data using React Query for better data management
    const { data: userData, isLoading, error } = useQuery({
        queryKey: ['currentUser'],
        queryFn: async () => {
            const response = await api.get('/me', {
                timeout: 10000,
                validateStatus: (status) => status < 500,
                headers: {
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache',
                    'Expires': '0'
                }
            });
            
            if (!response?.data) {
                throw new Error('No user data received');
            }
            
            return response.data;
        },
        retry: 1,
        staleTime: 5 * 60 * 1000, // 5 minutes
        onError: (error) => {
            console.error('Failed to fetch user:', error);
            
            // Set a fallback user for better UX
            const fallbackUser = { 
                name: 'Guest User', 
                role: 'user',
                roles: [],
                email: 'guest@example.com'
            };
            
            setUser(fallbackUser);
            
            if (error.response?.status === 401) {
                toast.error('Your session has expired. Please log in again.');
                logout();
                navigate('/login');
            } else {
                toast.error('Failed to load user data. Using limited functionality.');
            }
        },
        onSuccess: (data) => {
            setUser(data);
        }
    });

    const handleLogout = async () => {
        try {
            await api.post('/logout');
            await logout();
            toast.success('Successfully logged out');
            navigate('/login');
        } catch (error) {
            console.error('Logout failed:', error);
            await logout();
            toast.error('Logged out locally, but server logout failed');
            navigate('/login');
        }
    };

    // Show loading state
    if (isLoading) {
        return (
            <div className="flex items-center justify-center min-h-screen bg-gray-50 dark:bg-gray-900">
                <div className="text-center">
                    <LoadingSpinner size="lg" />
                    <p className="mt-4 text-gray-600 dark:text-gray-400">Loading dashboard...</p>
                </div>
            </div>
        );
    }

    // Error state
    if (error && !user) {
        return (
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
                <div className="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-red-200 dark:border-red-900/30">
                    <div className="flex items-start">
                        <div className="flex-shrink-0">
                            <svg className="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                            </svg>
                        </div>
                        <div className="ml-3">
                            <h3 className="text-lg font-medium text-red-800 dark:text-red-200">Error loading dashboard</h3>
                            <div className="mt-2 text-sm text-red-700 dark:text-red-300">
                                <p>We couldn't load your dashboard due to an error. Please try refreshing the page or contact support if the problem persists.</p>
                                <p className="mt-2 font-mono text-xs bg-red-50 dark:bg-red-900/30 p-2 rounded">
                                    {error.message || 'Unknown error occurred'}
                                </p>
                            </div>
                            <div className="mt-4">
                                <button
                                    type="button"
                                    onClick={() => window.location.reload()}
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                >
                                    Refresh Page
                                </button>
                                <button
                                    type="button"
                                    onClick={handleLogout}
                                    className="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    // Determine user role and render appropriate dashboard
    const isAdmin = user?.roles?.some(role => role.name === 'admin');
    const isTeacher = user?.roles?.some(role => role.name === 'teacher');
    const isStudent = user?.roles?.some(role => role.name === 'student');
    const isParent = user?.roles?.some(role => role.name === 'parent');

    // Render the appropriate dashboard based on user role
    const renderDashboard = () => {
        if (isAdmin) {
            return <EnhancedAdminDashboard user={user} />;
        } else if (isTeacher) {
            return <TeacherDashboard />;
        } else if (isStudent) {
            return <StudentDashboard />;
        } else if (isParent) {
            return <ParentDashboard />;
        }

        // Default dashboard for users with no specific role
        return (
            <motion.div 
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="p-6 max-w-4xl mx-auto"
            >
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
                    <h2 className="text-2xl font-bold text-gray-800 dark:text-white mb-4">Welcome, {user?.name || 'User'}!</h2>
                    <p className="text-gray-600 dark:text-gray-300 mb-4">
                        You don't have any specific role assigned. Please contact the administrator to get the appropriate access.
                    </p>
                    <div className="bg-yellow-50 dark:bg-yellow-900/10 border-l-4 border-yellow-400 dark:border-yellow-500 p-4 rounded-r">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <svg className="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <p className="text-sm text-yellow-700 dark:text-yellow-200">
                                    <span className="font-medium">Notice:</span> Your account doesn't have any assigned roles. Some features may be limited.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </motion.div>
        );
    };

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
            <Suspense fallback={
                <div className="flex items-center justify-center min-h-screen">
                    <LoadingSpinner size="lg" />
                </div>
            }>
                {renderDashboard()}
                <Outlet />
            </Suspense>
        </div>
    );
}
