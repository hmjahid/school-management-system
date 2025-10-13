import { useEffect, useState, useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import api from '../services/api';
import { AuthContext } from '../contexts/AuthContext';

export default function DashboardPage() {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const { logout } = useContext(AuthContext);
    const navigate = useNavigate();

    useEffect(() => {
        let isMounted = true;
        
        const fetchUser = async () => {
            try {
                const response = await api.get('/me', {
                    // Add timeout to prevent hanging on slow responses
                    timeout: 10000,
                    // Don't retry on 500 errors to prevent multiple failed requests
                    validateStatus: (status) => status < 500,
                    // Add cache control to prevent caching issues
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
                
                // Handle different error statuses
                if (error.code === 'ECONNABORTED' || !error.response) {
                    toast.error('Connection timeout. Using limited functionality.');
                    setUser(fallbackUser);
                } else if (error.response?.status === 401) {
                    // Unauthorized - session expired
                    toast.error('Session expired. Please log in again.');
                    navigate('/login');
                    return;
                } else if (error.response?.status === 500) {
                    // Server error - show user-friendly message
                    toast.error('Temporary server issue. Using limited functionality.');
                    setUser(fallbackUser);
                } else {
                    // For other errors
                    toast.error('Failed to load user data. Using limited functionality.');
                    setUser(fallbackUser);
                }
            } finally {
                if (isMounted) {
                    setLoading(false);
                }
            }
        };

        fetchUser();

        // Cleanup function to prevent state updates after unmount
        return () => {
            isMounted = false;
        };
    }, [navigate]);

    const handleLogout = async () => {
        try {
            // Use the correct logout endpoint
            await api.post('/logout');
            // Call the AuthContext logout to clear local state
            await logout();
            toast.success('Successfully logged out');
            navigate('/login');
        } catch (error) {
            console.error('Logout failed:', error);
            // Even if the server logout fails, we should still clear local state
            await logout();
            toast.error('Logged out locally, but server logout failed');
            navigate('/login');
        }
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-screen">
                <div className="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-100">
            <div className="py-10">
                <header>
                    <div className="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <h1 className="text-3xl font-bold leading-tight text-gray-900">Dashboard</h1>
                    </div>
                </header>
                <main>
                    <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <div className="px-4 py-8 sm:px-0">
                            <div className="p-6 bg-white border-4 border-gray-200 border-dashed rounded-lg">
                                <h2 className="text-xl font-bold">Welcome, {user?.name || 'User'}</h2>
                                <p>Your role is: {user?.roles?.length > 0 
                                    ? user.roles.map(role => {
                                        if (typeof role === 'string') return role;
                                        if (typeof role === 'object' && role !== null) {
                                            return role.name || role.role || 'unknown';
                                        }
                                        return 'unknown';
                                    }).join(', ')
                                    : user?.role || 'No role assigned'}
                                </p>
                                <button
                                    onClick={handleLogout}
                                    className="px-4 py-2 mt-4 font-bold text-white bg-red-500 rounded hover:bg-red-700"
                                >
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    );
}
