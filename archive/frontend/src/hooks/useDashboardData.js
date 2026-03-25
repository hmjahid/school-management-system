import { useQuery, useQueryClient, useMutation } from '@tanstack/react-query';
import { 
  fetchDashboardStats, 
  fetchNotifications, 
  markNotificationAsRead, 
  markAllNotificationsAsRead,
  fetchAdminDashboard,
  fetchTeacherDashboard,
  fetchStudentDashboard,
  fetchParentDashboard,
  fetchStaffDashboard,
  fetchAttendanceData,
  fetchPerformanceData,
  fetchFeeCollectionData,
} from '../services/dashboardService';
import { useAuth } from '../contexts/AuthContext';

export const useDashboardData = () => {
  const { user } = useAuth();
  const queryClient = useQueryClient();

  // Common query options
  const queryOptions = {
    staleTime: 5 * 60 * 1000, // 5 minutes
    refetchOnWindowFocus: true,
    retry: 1,
  };

  // Dashboard stats
  const dashboardStatsQuery = useQuery({
    queryKey: ['dashboardStats'],
    queryFn: fetchDashboardStats,
    ...queryOptions,
  });

  // Notifications
  const notificationsQuery = useQuery({
    queryKey: ['notifications'],
    queryFn: fetchNotifications,
    ...queryOptions,
  });

  // Mark notification as read
  const markAsReadMutation = useMutation({
    mutationFn: markNotificationAsRead,
    onSuccess: () => {
      // Invalidate and refetch notifications
      queryClient.invalidateQueries(['notifications']);
      queryClient.invalidateQueries(['dashboardStats']);
    },
  });

  // Mark all notifications as read
  const markAllAsReadMutation = useMutation({
    mutationFn: markAllNotificationsAsRead,
    onSuccess: () => {
      // Invalidate and refetch notifications
      queryClient.invalidateQueries(['notifications']);
      queryClient.invalidateQueries(['dashboardStats']);
    },
  });

  // Role-based dashboard data
  const roleBasedDashboardQuery = useQuery({
    queryKey: ['roleDashboard', user?.roles?.[0]],
    queryFn: async () => {
      if (!user?.roles?.length) return null;
      
      const role = user.roles[0];
      switch (role) {
        case 'admin':
          return await fetchAdminDashboard();
        case 'teacher':
          return await fetchTeacherDashboard();
        case 'student':
          return await fetchStudentDashboard();
        case 'parent':
          return await fetchParentDashboard();
        case 'staff':
          return await fetchStaffDashboard();
        default:
          return null;
      }
    },
    ...queryOptions,
    enabled: !!user?.roles?.length,
  });

  // Analytics data
  const attendanceDataQuery = useQuery({
    queryKey: ['attendanceData'],
    queryFn: () => fetchAttendanceData({ period: 'monthly' }),
    ...queryOptions,
  });

  const performanceDataQuery = useQuery({
    queryKey: ['performanceData'],
    queryFn: () => fetchPerformanceData({ period: 'monthly' }),
    ...queryOptions,
  });

  const feeCollectionDataQuery = useQuery({
    queryKey: ['feeCollectionData'],
    queryFn: () => fetchFeeCollectionData({ period: 'monthly' }),
    ...queryOptions,
  });

  // Helper function to get loading and error states
  const getStatus = (query) => ({
    isLoading: query.isLoading,
    isError: query.isError,
    error: query.error,
    refetch: query.refetch,
  });

  return {
    // Stats
    stats: {
      data: dashboardStatsQuery.data,
      ...getStatus(dashboardStatsQuery),
    },
    
    // Notifications
    notifications: {
      data: notificationsQuery.data || [],
      unreadCount: notificationsQuery.data?.filter(n => !n.read).length || 0,
      markAsRead: markAsReadMutation.mutate,
      markAllAsRead: markAllAsReadMutation.mutate,
      ...getStatus(notificationsQuery),
    },
    
    // Role-based dashboard
    dashboard: {
      data: roleBasedDashboardQuery.data,
      ...getStatus(roleBasedDashboardQuery),
    },
    
    // Analytics
    analytics: {
      attendance: {
        data: attendanceDataQuery.data,
        ...getStatus(attendanceDataQuery),
      },
      performance: {
        data: performanceDataQuery.data,
        ...getStatus(performanceDataQuery),
      },
      feeCollection: {
        data: feeCollectionDataQuery.data,
        ...getStatus(feeCollectionDataQuery),
      },
    },
    
    // Refresh all data
    refreshAll: () => {
      queryClient.invalidateQueries({
        predicate: query => 
          query.queryKey[0] === 'dashboardStats' ||
          query.queryKey[0] === 'notifications' ||
          query.queryKey[0] === 'roleDashboard' ||
          query.queryKey[0] === 'attendanceData' ||
          query.queryKey[0] === 'performanceData' ||
          query.queryKey[0] === 'feeCollectionData'
      });
    },
  };
};

export default useDashboardData;
