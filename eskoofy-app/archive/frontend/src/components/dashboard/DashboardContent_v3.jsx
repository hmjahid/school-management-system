import { useEffect, useState, useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import api from '../../services/api';
import { AuthContext } from '../../contexts/AuthContext';
import { useDashboardData } from '../../hooks/useDashboardData';

// Custom components for better organization
const StatCard = ({ title, value }) => (
  <div className="p-6 bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
    <div className="text-base font-medium text-gray-600 dark:text-gray-300 mb-2">
      {title}
    </div>
    <div className="text-3xl font-bold text-gray-900 dark:text-white">
      {value}
    </div>
  </div>
);

const ActivityItem = ({ title, description, timestamp }) => (
  <div className="p-5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200 rounded-lg">
    <div className="flex items-start">
      <div className="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-300 font-semibold text-lg mr-4">
        {title.charAt(0).toUpperCase()}
      </div>
      <div className="flex-1 min-w-0">
        <p className="text-base font-medium text-gray-900 dark:text-white">
          {title}
        </p>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
          {description}
        </p>
        <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
          {new Date(timestamp).toLocaleString()}
        </p>
      </div>
    </div>
  </div>
);

export default function DashboardContent() {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const { logout, user: authUser } = useContext(AuthContext);
    const navigate = useNavigate();
    
    // Use our custom dashboard hook
    const { data: dashboardData, isLoading, error } = useDashboardData();

    useEffect(() => {
        let isMounted = true;
        
        const fetchUser = async () => {
            try {
                if (authUser) {
                    if (isMounted) {
                        setUser(authUser);
                        setLoading(false);
                    }
                } else {
                    // If no authUser in context, try to fetch it
                    const response = await api.get('/user');
                    if (isMounted && response.data) {
                        setUser(response.data);
                        setLoading(false);
                    }
                }
            } catch (err) {
                console.error('Error fetching user:', err);
                if (isMounted) {
                    setLoading(false);
                    
                    // If we get a 401, redirect to login
                    if (err.response?.status === 401) {
                        toast.error('Your session has expired. Please log in again.');
                        logout();
                        navigate('/login');
                    } else {
                        // For other errors, use a fallback user
                        setUser({
                            name: 'Guest User',
                            email: 'guest@example.com',
                            role: 'guest',
                            roles: [{ name: 'guest' }]
                        });
                    }
                }
            }
        };

        fetchUser();

        return () => {
            isMounted = false;
        };
    }, [authUser, logout, navigate]);

    // Handle loading state
    if (isLoading || loading) {
        return (
            <div className="flex justify-center items-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                <span className="ml-4 text-gray-700 dark:text-gray-300">Loading dashboard data...</span>
            </div>
        );
    }

    // Handle error state
    if (error) {
        console.error('Dashboard error:', error);
        return (
            <div className="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <h3 className="text-lg font-medium text-red-800 dark:text-red-200">Error Loading Dashboard</h3>
                <p className="mt-2 text-red-700 dark:text-red-300">
                    {error.message || 'Failed to load dashboard data. Please try again later.'}
                </p>
                <button
                    onClick={() => window.location.reload()}
                    className="mt-4 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    Retry
                </button>
            </div>
        );
    }


    return (
        <div className="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
            {/* Welcome Section */}
            <div className="mb-10">
                <h1 className="text-3xl font-bold text-gray-800 dark:text-white">
                    Welcome back, {user?.name || 'User'}! 👋
                </h1>
                <p className="mt-2 text-base text-gray-600 dark:text-gray-300">
                    Here's what's happening with your school today.
                </p>
            </div>

            {/* Stats Grid */}
            {dashboardData?.stats && (
                <div className="mb-12">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-white mb-6">
                        Overview
                    </h2>
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {Object.entries(dashboardData.stats).map(([key, value]) => (
                            <StatCard 
                                key={key}
                                title={key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')}
                                value={value}
                            />
                        ))}
                    </div>
                </div>
            )}

            {/* Recent Activity */}
            {dashboardData?.recentActivity && dashboardData.recentActivity.length > 0 && (
                <div className="mb-12">
                    <div className="flex items-center justify-between mb-6">
                        <h2 className="text-xl font-semibold text-gray-800 dark:text-white">
                            Recent Activity
                        </h2>
                        <button className="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                            View All
                        </button>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                        <ul className="divide-y divide-gray-100 dark:divide-gray-700">
                            {dashboardData.recentActivity.map((activity, index) => (
                                <li key={index}>
                                    <ActivityItem 
                                        title={activity.title}
                                        description={activity.description}
                                        timestamp={activity.timestamp}
                                    />
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            )}

            {/* Quick Actions */}
            <div>
                <h2 className="text-xl font-semibold text-gray-800 dark:text-white mb-6">
                    Quick Actions
                </h2>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <button className="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-100 dark:border-gray-700 text-left">
                        <div className="text-blue-600 dark:text-blue-400 mb-2">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <h3 className="font-medium text-gray-900 dark:text-white">Add New Student</h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">Register a new student</p>
                    </button>
                    
                    <button className="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-100 dark:border-gray-700 text-left">
                        <div className="text-green-600 dark:text-green-400 mb-2">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h3 className="font-medium text-gray-900 dark:text-white">Create Report</h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">Generate student reports</p>
                    </button>
                    
                    <button className="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-100 dark:border-gray-700 text-left">
                        <div className="text-purple-600 dark:text-purple-400 mb-2">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 className="font-medium text-gray-900 dark:text-white">View Calendar</h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">Check school events</p>
                    </button>
                    
                    <button className="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-100 dark:border-gray-700 text-left">
                        <div className="text-yellow-600 dark:text-yellow-400 mb-2">
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 className="font-medium text-gray-900 dark:text-white">Attendance</h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">Mark attendance</p>
                    </button>
                </div>
            </div>
        </div>
    );
}
