import { useEffect, useState, useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import api from '../../services/api';
import { AuthContext } from '../../contexts/AuthContext';
import { useDashboardData } from '../../hooks/useDashboardData';

export default function DashboardContent() {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const { logout } = useContext(AuthContext);
    const navigate = useNavigate();
    
    // Use our custom dashboard hook with the correct name
    const { data: dashboardData, isLoading, error } = useDashboardData();

    useEffect(() => {
        let isMounted = true;
        
        const fetchUser = async () => {
            try {
                const response = await api.get('/me', {
                    timeout: 10000,
                    validateStatus: (status) => status < 500,
                    headers: {
                        'Cache-Control': 'no-cache, no-store, must-revalidate',
                        'Pragma': 'no-cache',
                        'Expires': '0'
                    }
                });

                if (!isMounted) return;

                if (response?.data) {
                    setUser(response.data);
                } else {
                    throw new Error('No user data received');
                }
            } catch (error) {
                if (!isMounted) return;
                console.error('Failed to fetch user:', error);
                
                // Default fallback user object
                const fallbackUser = { 
                    name: 'Guest User', 
                    role: 'user',
                    roles: [{ name: 'user' }],
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
            } finally {
                if (isMounted) {
                    setLoading(false);
                }
            }
        };

        fetchUser();

        return () => {
            isMounted = false;
        };
    }, [logout, navigate]);

    if (loading || isLoading) {
        return (
            <div className="flex items-center justify-center min-h-[50vh]">
                <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="bg-red-50 border-l-4 border-red-500 p-4">
                <div className="flex">
                    <div className="flex-shrink-0">
                        <svg className="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                        </svg>
                    </div>
                    <div className="ml-3">
                        <p className="text-sm text-red-700">
                            Error loading dashboard data. {error.message}
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="p-6">
            <div className="mb-8">
                <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    Welcome back, {user?.name || 'User'}!
                </h1>
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Here's what's happening with your school today.
                </p>
            </div>

            {/* Dashboard Stats */}
            {dashboardData?.stats && (
                <div className="grid grid-cols-1 gap-5 mt-6 sm:grid-cols-2 lg:grid-cols-4">
                    {Object.entries(dashboardData.stats).map(([key, value]) => (
                        <div key={key} className="p-5 bg-white rounded-lg shadow dark:bg-gray-800">
                            <div className="text-sm font-medium text-gray-500 truncate dark:text-gray-400">
                                {key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')}
                            </div>
                            <div className="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">
                                {value}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Recent Activity */}
            {dashboardData?.recentActivity && dashboardData.recentActivity.length > 0 && (
                <div className="mt-8">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Recent Activity</h2>
                    <div className="bg-white shadow overflow-hidden sm:rounded-md dark:bg-gray-800">
                        <ul className="divide-y divide-gray-200 dark:divide-gray-700">
                            {dashboardData.recentActivity.map((activity, index) => (
                                <li key={index} className="p-4">
                                    <div className="flex items-center">
                                        <div className="min-w-0 flex-1 flex items-center">
                                            <div className="min-w-0 flex-1 px-4">
                                                <div>
                                                    <p className="text-sm font-medium text-blue-600 dark:text-blue-400 truncate">
                                                        {activity.title}
                                                    </p>
                                                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                        {activity.description}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="ml-5 flex-shrink-0">
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                {new Date(activity.timestamp).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            )}
        </div>
    );
}
