import api from './api';

// Dashboard Statistics
export const fetchDashboardStats = async () => {
  const endpoint = '/api/admin/dashboard';
  
  // Mock data to use when the API is not available
  const mockData = {
    stats: {
      totalStudents: 1200,
      totalTeachers: 85,
      totalClasses: 42,
      totalRevenue: 125000,
      attendanceRate: 92.5,
      pendingAssignments: 15,
      upcomingEvents: 3,
      recentActivity: []
    },
    charts: {
      monthlyRevenue: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        data: [65000, 59000, 80000, 81000, 125000, 125000]
      },
      studentPerformance: {
        labels: ['A', 'B', 'C', 'D', 'F'],
        data: [25, 35, 20, 15, 5]
      }
    }
  };

  // Check if we're in development mode
  const isDevelopment = import.meta.env.MODE === 'development';
  
  // In development, we'll use mock data by default but still try the API
  if (isDevelopment) {
    try {
      const response = await api.get(endpoint);
      console.log('Successfully fetched dashboard data from API');
      return response.data.data || response.data;
    } catch (error) {
      console.warn(`Could not fetch dashboard data from ${endpoint}, using mock data.`);
      console.warn('To fix this, ensure your backend API is running and the endpoint is correct.');
      return mockData;
    }
  }
  
  // In production, try the API first, then fall back to mock data
  try {
    const response = await api.get(endpoint);
    return response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching dashboard stats:', error);
    
    if (error.response?.status === 404) {
      console.warn('Dashboard endpoint not found, using mock data');
      return mockData;
    }
    
    console.error('Failed to fetch dashboard data:', error.message);
    throw error;
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
