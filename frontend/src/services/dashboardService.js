import api from './api';

// Enhanced mock data with realistic values
const mockDashboardData = {
  stats: {
    totalStudents: 1245,
    totalTeachers: 87,
    totalClasses: 45,
    totalRevenue: 128500,
    attendanceRate: 94.2,
    pendingAssignments: 12,
    upcomingEvents: 5,
    recentActivity: [
      { id: 1, type: 'assignment', message: 'New assignment posted in Math 101', time: '2 hours ago' },
      { id: 2, type: 'announcement', message: 'School will be closed on Friday', time: '1 day ago' },
      { id: 3, type: 'grade', message: 'Your grade has been updated in Science', time: '2 days ago' },
    ]
  },
  charts: {
    monthlyRevenue: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      data: [65000, 59000, 80000, 81000, 125000, 125000, 118000, 132000, 110000, 128500, 140000, 150000]
    },
    studentPerformance: {
      labels: ['A', 'B', 'C', 'D', 'F'],
      data: [28, 38, 22, 9, 3]
    },
    attendanceTrend: {
      labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      data: [92, 94, 95, 96]
    }
  }
};

// Dashboard Statistics
export const fetchDashboardStats = async () => {
  const endpoint = '/admin/dashboard';
  const isDevelopment = import.meta.env.MODE === 'development';
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  
  try {
    if (debugMode) console.log('Fetching dashboard data from:', endpoint);
    
    // Try to fetch from the real API
    const response = await api.get(endpoint);
    
    if (debugMode) console.log('Dashboard API response:', response);
    
    // Handle different response structures
    if (response.data && response.data.data) {
      return response.data.data;
    } else if (response.data) {
      return response.data;
    }
    return response;
  } catch (error) {
    console.error('Error fetching dashboard data:', error);
    
    // In development, use mock data if the API is not available
    if (isDevelopment) {
      console.warn('Using mock dashboard data due to error');
      return mockDashboardData;
    }
    
    // In production, only use mock data for 404 errors
    if (error.response && error.response.status === 404) {
      console.warn('Using mock dashboard data (404 from server)');
      return mockDashboardData;
    } else {
      console.error('Failed to fetch dashboard data:', error);
      throw error;
    }
  }
};

// Notifications
export const fetchNotifications = async () => {
  try {
    const response = await api.get('/notifications');
    return response.data.data;
  } catch (error) {
    console.error('Error fetching notifications:', error);
    throw error;
  }
};

export const markNotificationAsRead = async (notificationId) => {
  try {
    const response = await api.patch(`/notifications/${notificationId}/read`);
    return response.data;
  } catch (error) {
    console.error('Error marking notification as read:', error);
    throw error;
  }
};

export const markAllNotificationsAsRead = async () => {
  try {
    const response = await api.patch('/notifications/mark-all-read');
    return response.data;
  } catch (error) {
    console.error('Error marking all notifications as read:', error);
    throw error;
  }
};

// Search
const searchEndpoints = {
  student: '/search/students',
  teacher: '/search/teachers',
  class: '/search/classes',
  assignment: '/search/assignments',
};

export const searchContent = async (query, type = null) => {
  try {
    const endpoint = type ? searchEndpoints[type] : '/search';
    const response = await api.get(endpoint, {
      params: { q: query }
    });
    return response.data.data || [];
  } catch (error) {
    console.error('Search error:', error);
    return [];
  }
};

// User Profile
export const fetchUserProfile = async () => {
  try {
    const response = await api.get('/profile');
    return response.data.data;
  } catch (error) {
    console.error('Error fetching user profile:', error);
    throw error;
  }
};

// Dashboard Data by Role
export const fetchAdminDashboard = async () => {
  try {
    const response = await api.get('/dashboard/admin');
    return response.data.data;
  } catch (error) {
    console.error('Error fetching admin dashboard:', error);
    throw error;
  }
};

export const fetchTeacherDashboard = async () => {
  try {
    const response = await api.get('/dashboard/teacher');
    return response.data.data;
  } catch (error) {
    console.error('Error fetching teacher dashboard:', error);
    throw error;
  }
};

export const fetchStudentDashboard = async () => {
  try {
    const response = await api.get('/dashboard/student');
    return response.data.data;
  } catch (error) {
    console.error('Error fetching student dashboard:', error);
    throw error;
  }
};

export const fetchParentDashboard = async () => {
  try {
    const response = await api.get('/dashboard/parent');
    return response.data.data;
  } catch (error) {
    console.error('Error fetching parent dashboard:', error);
    throw error;
  }
};

export const fetchStaffDashboard = async () => {
  try {
    const response = await api.get('/dashboard/staff');
    return response.data.data;
  } catch (error) {
    console.error('Error fetching staff dashboard:', error);
    throw error;
  }
};

// Data Visualization
export const fetchAttendanceData = async (params = {}) => {
  try {
    const response = await api.get('/analytics/attendance', { params });
    return response.data.data;
  } catch (error) {
    console.error('Error fetching attendance data:', error);
    throw error;
  }
};

export const fetchPerformanceData = async (params = {}) => {
  try {
    const response = await api.get('/analytics/performance', { params });
    return response.data.data;
  } catch (error) {
    console.error('Error fetching performance data:', error);
    throw error;
  }
};

export const fetchFeeCollectionData = async (params = {}) => {
  try {
    const response = await api.get('/analytics/fee-collection', { params });
    return response.data.data;
  } catch (error) {
    console.error('Error fetching fee collection data:', error);
    throw error;
  }
};

// Quick Actions
export const performQuickAction = async (action, data = {}) => {
  try {
    const response = await api.post(`/quick-actions/${action}`, data);
    return response.data;
  } catch (error) {
    console.error('Error performing quick action:', error);
    throw error;
  }
};

export default {
  fetchDashboardStats,
  fetchNotifications,
  markNotificationAsRead,
  markAllNotificationsAsRead,
  searchContent,
  fetchUserProfile,
  fetchAdminDashboard,
  fetchTeacherDashboard,
  fetchStudentDashboard,
  fetchParentDashboard,
  fetchStaffDashboard,
  fetchAttendanceData,
  fetchPerformanceData,
  fetchFeeCollectionData,
  performQuickAction,
};
