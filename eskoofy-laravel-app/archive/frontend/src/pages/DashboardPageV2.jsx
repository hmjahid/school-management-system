import { useEffect, useState, useContext, Suspense, useCallback } from 'react';
import { useNavigate, Outlet } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { motion, AnimatePresence } from 'framer-motion';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { 
  PlusIcon, 
  Cog6ToothIcon, 
  ArrowsPointingOutIcon,
  XMarkIcon,
  ArrowPathIcon
} from '@heroicons/react/24/outline';

import api from '../services/api';
import { AuthContext } from '../contexts/AuthContext';
import { useWidgets } from '../contexts/WidgetContext';
import LoadingSpinner from '../components/common/LoadingSpinner';
import WidgetSettingsModal from '../components/dashboard/WidgetSettingsModal';
import WidgetContainer from '../components/dashboard/WidgetContainer';

// Widget components
import QuickStatsWidget from '../components/widgets/QuickStatsWidget';
import RevenueChartWidget from '../components/widgets/RevenueChartWidget';
import RecentActivityWidget from '../components/widgets/RecentActivityWidget';
import UpcomingEventsWidget from '../components/widgets/UpcomingEventsWidget';
import ClassDistributionWidget from '../components/widgets/ClassDistributionWidget';
import PerformanceMetricsWidget from '../components/widgets/PerformanceMetricsWidget';

// Widget component mapping
const widgetComponents = {
  quick_stats: QuickStatsWidget,
  revenue_chart: RevenueChartWidget,
  recent_activity: RecentActivityWidget,
  upcoming_events: UpcomingEventsWidget,
  class_distribution: ClassDistributionWidget,
  performance_metrics: PerformanceMetricsWidget,
};

// Get the appropriate widget component based on widget type
const getWidgetComponent = (widgetId) => {
  return widgetComponents[widgetId] || (() => (
    <div className="p-4 text-center text-gray-500">
      Widget "{widgetId}" not found
    </div>
  ));
};

export default function DashboardPageV2() {
    const [user, setUser] = useState(null);
    const [isSettingsOpen, setIsSettingsOpen] = useState(false);
    const [isEditing, setIsEditing] = useState(false);
    const [isFullscreen, setIsFullscreen] = useState(false);
    const { logout } = useContext(AuthContext);
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const { 
      widgets, 
      enabledWidgets, 
      allWidgets, 
      isLoading: isLoadingWidgets, 
      error: widgetsError, 
      saveWidgets, 
      resetWidgets, 
      updateWidget, 
      toggleWidget, 
      reorderWidgets 
    } = useWidgets();

    // Fetch user data using React Query for better data management
    console.log('Starting to fetch user data...');
    const { data: userData, isLoading, error } = useQuery({
        queryKey: ['currentUser'],
        queryFn: async () => {
            try {
                console.log('Making request to /me endpoint...');
                const response = await api.get('/me', {
                    timeout: 10000,
                    validateStatus: (status) => status < 500,
                    headers: {
                        'Cache-Control': 'no-cache, no-store, must-revalidate',
                        'Pragma': 'no-cache',
                        'Expires': '0',
                        'Accept': 'application/json'
                    }
                });
                
                console.log('Response from /me:', response);
                
                if (!response?.data) {
                    console.error('No user data in response:', response);
                    throw new Error('No user data received');
                }
                
                // Ensure the user has a roles array
                const userData = response.data;
                if (!userData.roles && userData.role) {
                    userData.roles = [userData.role];
                }
                
                console.log('User data processed:', userData);
                return userData;
            } catch (error) {
                console.error('Error in /me request:', error);
                if (error.response) {
                    console.error('Response data:', error.response.data);
                    console.error('Response status:', error.response.status);
                    console.error('Response headers:', error.response.headers);
                } else if (error.request) {
                    console.error('No response received:', error.request);
                } else {
                    console.error('Error setting up request:', error.message);
                }
                throw error;
            }
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

    // Toggle edit mode
    const toggleEditMode = () => {
      setIsEditing(!isEditing);
    };

    // Toggle fullscreen mode
    const toggleFullscreen = () => {
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
          console.error(`Error attempting to enable fullscreen: ${err.message}`);
        });
      } else {
        if (document.exitFullscreen) {
          document.exitFullscreen();
        }
      }
      setIsFullscreen(!isFullscreen);
    };

    // Handle widget reordering
    const handleDragEnd = (result) => {
      if (!result.destination) return;
      
      const items = Array.from(enabledWidgets);
      const [reorderedItem] = items.splice(result.source.index, 1);
      items.splice(result.destination.index, 0, reorderedItem);
      
      // Update widget positions
      const updatedWidgets = { ...widgets };
      items.forEach((widget, index) => {
        updatedWidgets[widget.id] = {
          ...updatedWidgets[widget.id],
          position: index + 1
        };
      });
      
      saveWidgets(updatedWidgets);
    };

    // Refresh dashboard data
    const refreshDashboard = async () => {
      await queryClient.invalidateQueries(['dashboardData']);
      toast.success('Dashboard refreshed');
    };

    // Debug loading and error states
    console.log('Loading state:', isLoading, 'Error:', error);
    
    // Show loading state
    if (isLoading || isLoadingWidgets) {
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
    if ((error || widgetsError) && !user) {
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

    // Debug user data and roles with detailed inspection
    console.log('=== DEBUG: Current User Data ===');
    console.log('Full user object:', JSON.parse(JSON.stringify(user || {})));
    console.log('User roles (direct):', user?.roles);
    console.log('User role (direct):', user?.role);
    
    // Log all properties of the user object
    if (user) {
        console.log('User object properties:', Object.keys(user));
        if (user.roles && Array.isArray(user.roles)) {
            console.log('Roles array:', user.roles);
            console.log('First role (if array):', user.roles[0]);
            console.log('First role type:', typeof user.roles[0]);
            if (user.roles[0]) {
                console.log('First role properties:', Object.keys(user.roles[0]));
                console.log('First role name:', user.roles[0].name);
            } else {
                console.log('No roles in the roles array');
            }
        }
    }

    // Render the widget grid
    const renderWidgets = () => {
      if (!enabledWidgets || enabledWidgets.length === 0) {
        return (
          <div className="col-span-full text-center py-12">
            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-700">
              <PlusIcon className="h-6 w-6 text-gray-400" />
            </div>
            <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">No widgets</h3>
            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Get started by adding some widgets to your dashboard.
            </p>
            <div className="mt-6">
              <button
                type="button"
                onClick={() => setIsSettingsOpen(true)}
                className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <PlusIcon className="-ml-1 mr-2 h-5 w-5" />
                Add Widgets
              </button>
            </div>
          </div>
        );
      }

      return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {enabledWidgets.map((widget) => {
            const WidgetComponent = getWidgetComponent(widget.id);
            return (
              <WidgetContainer
                key={widget.id}
                widget={widget}
                isEditing={isEditing}
                onEdit={() => {
                  // Handle edit click
                  console.log('Edit widget:', widget.id);
                }}
                onRemove={async (widgetId) => {
                  await toggleWidget(widgetId);
                }}
              >
                <WidgetComponent widget={widget} />
              </WidgetContainer>
            );
          })}
        </div>
      );
    };

    // Render the dashboard header
    const renderHeader = () => {
      return (
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
              {user?.name ? `${user.name}'s Dashboard` : 'Dashboard'}
            </h1>
            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Welcome back! Here's what's happening with your school.
            </p>
          </div>
          <div className="mt-4 sm:mt-0 flex space-x-3">
            <button
              type="button"
              onClick={refreshDashboard}
              className="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              title="Refresh dashboard"
            >
              <ArrowPathIcon className="h-4 w-4 mr-1.5" />
              <span className="hidden sm:inline">Refresh</span>
            </button>
            
            <button
              type="button"
              onClick={toggleEditMode}
              className={`inline-flex items-center px-3 py-1.5 border ${
                isEditing 
                  ? 'border-indigo-500 text-indigo-700 dark:text-indigo-200 bg-indigo-50 dark:bg-indigo-900/30' 
                  : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600'
              } shadow-sm text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500`}
            >
              <Cog6ToothIcon className="h-4 w-4 mr-1.5" />
              <span className="hidden sm:inline">{isEditing ? 'Done' : 'Edit'}</span>
            </button>
            
            <button
              type="button"
              onClick={toggleFullscreen}
              className="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              title={isFullscreen ? 'Exit fullscreen' : 'Fullscreen'}
            >
              <ArrowsPointingOutIcon className="h-4 w-4" />
              <span className="sr-only">{isFullscreen ? 'Exit fullscreen' : 'Fullscreen'}</span>
            </button>
            
            <button
              type="button"
              onClick={() => setIsSettingsOpen(true)}
              className="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <PlusIcon className="h-4 w-4 mr-1.5" />
              <span className="hidden sm:inline">Add Widget</span>
            </button>
          </div>
        </div>
      );
    };

    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200 p-4 sm:p-6">
        <div className="max-w-7xl mx-auto">
          {renderHeader()}
          {renderWidgets()}
          
          {/* Widget Settings Modal */}
          <WidgetSettingsModal
            isOpen={isSettingsOpen}
            onClose={() => setIsSettingsOpen(false)}
            widgets={widgets}
            onSave={saveWidgets}
            onReset={resetWidgets}
          />
          
          {/* Debug info - only show in development */}
          {import.meta.env.DEV && (
            <div className="mt-8 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg text-xs text-gray-600 dark:text-gray-400">
              <div className="font-medium mb-2">Debug Info:</div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <div className="font-medium">User:</div>
                  <pre className="mt-1 p-2 bg-white dark:bg-gray-700 rounded overflow-auto max-h-40">
                    {JSON.stringify(user, null, 2)}
                  </pre>
                </div>
                <div>
                  <div className="font-medium">Widgets:</div>
                  <pre className="mt-1 p-2 bg-white dark:bg-gray-700 rounded overflow-auto max-h-40">
                    {JSON.stringify(enabledWidgets, null, 2)}
                  </pre>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    );
}
