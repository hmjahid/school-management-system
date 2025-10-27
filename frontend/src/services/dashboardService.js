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

// Mock notifications data
const mockNotifications = [
  { id: 1, title: 'New Message', message: 'You have a new message from John Doe', read: false, createdAt: '2025-10-27T10:30:00Z' },
  { id: 2, title: 'Assignment Graded', message: 'Your Math assignment has been graded', read: false, createdAt: '2025-10-26T14:15:00Z' },
  { id: 3, title: 'Payment Received', message: 'Payment of $500 received for October fees', read: true, createdAt: '2025-10-25T09:45:00Z' }
];

// Mock user profile data
const mockUserProfile = {
  id: 1,
  name: 'Admin User',
  email: 'admin@example.com',
  role: 'admin',
  avatar: '/images/avatars/admin-avatar.png',
  lastLogin: '2025-10-27T08:30:00Z'
};

// Dashboard Statistics
export const fetchDashboardStats = async () => {
  const endpoint = '/admin/dashboard';
  const isDevelopment = import.meta.env.MODE === 'development';
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  
  // For now, always use mock data until backend endpoints are ready
  const useMockData = true; // Set to false to try real API
  
  if (useMockData) {
    if (debugMode) console.log('Using mock dashboard data');
    return Promise.resolve(mockDashboardData);
  }
  
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
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  const useMockData = true; // Set to false to try real API
  
  if (useMockData) {
    if (debugMode) console.log('Using mock notifications data');
    return Promise.resolve(mockNotifications);
  }
  
  try {
    const response = await api.get('/notifications');
    return response.data.data || [];
  } catch (error) {
    console.error('Error fetching notifications, using mock data instead:', error);
    return mockNotifications; // Return mock data on error
  }
};

export const markNotificationAsRead = async (notificationId) => {
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  
  if (debugMode) console.log(`Marking notification ${notificationId} as read (mock)`);
  
  // In a real implementation, this would update the backend
  // For now, just return success
  return { success: true, message: 'Notification marked as read' };
};

export const markAllNotificationsAsRead = async () => {
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  
  if (debugMode) console.log('Marking all notifications as read (mock)');
  
  // In a real implementation, this would update the backend
  // For now, just return success
  return { success: true, message: 'All notifications marked as read' };
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
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  const useMockData = true; // Set to false to try real API
  
  if (useMockData) {
    if (debugMode) console.log('Using mock user profile data');
    return Promise.resolve(mockUserProfile);
  }
  
  try {
    const response = await api.get('/user/profile');
    return response.data.data || mockUserProfile;
  } catch (error) {
    console.error('Error fetching user profile, using mock data instead:', error);
    return mockUserProfile; // Return mock data on error
  }
};

// Dashboard Data by Role
export const fetchAdminDashboard = async () => {
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  
  if (debugMode) console.log('Using mock admin dashboard data');
  return Promise.resolve({
    ...mockDashboardData,
    role: 'admin',
    quickActions: [
      { id: 'add-student', label: 'Add Student', icon: 'user-plus' },
      { id: 'add-teacher', label: 'Add Teacher', icon: 'chalkboard-teacher' },
      { id: 'create-class', label: 'Create Class', icon: 'chalkboard' },
      { id: 'generate-report', label: 'Generate Report', icon: 'file-export' }
    ]
  });
};

export const fetchTeacherDashboard = async () => {
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  
  if (debugMode) console.log('Using mock teacher dashboard data');
  return Promise.resolve({
    ...mockDashboardData,
    role: 'teacher',
    quickActions: [
      { id: 'create-assignment', label: 'Create Assignment', icon: 'tasks' },
      { id: 'take-attendance', label: 'Take Attendance', icon: 'clipboard-check' },
      { id: 'post-announcement', label: 'Post Announcement', icon: 'bullhorn' }
    ]
  });
};

export const fetchStudentDashboard = async () => {
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  
  if (debugMode) console.log('Using mock student dashboard data');
  return Promise.resolve({
    ...mockDashboardData,
    role: 'student',
    quickActions: [
      { id: 'view-timetable', label: 'View Timetable', icon: 'calendar-alt' },
      { id: 'submit-assignment', label: 'Submit Assignment', icon: 'upload' },
      { id: 'view-grades', label: 'View Grades', icon: 'chart-line' }
    ]
  });
};

export const fetchParentDashboard = async () => {
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  
  if (debugMode) console.log('Using mock parent dashboard data');
  return Promise.resolve({
    ...mockDashboardData,
    role: 'parent',
    quickActions: [
      { id: 'view-child-progress', label: 'Child Progress', icon: 'user-graduate' },
      { id: 'view-attendance', label: 'Attendance', icon: 'calendar-check' },
      { id: 'make-payment', label: 'Make Payment', icon: 'credit-card' }
    ]
  });
};

export const fetchStaffDashboard = async () => {
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  
  if (debugMode) console.log('Using mock staff dashboard data');
  return Promise.resolve({
    ...mockDashboardData,
    role: 'staff',
    quickActions: [
      { id: 'manage-inventory', label: 'Manage Inventory', icon: 'box' },
      { id: 'process-payments', label: 'Process Payments', icon: 'money-bill-wave' },
      { id: 'view-reports', label: 'View Reports', icon: 'file-alt' }
    ]
  });
};

// Mock data for data visualization
const mockAttendanceData = {
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  datasets: [
    {
      label: 'Present',
      data: [92, 91, 93, 94, 95, 96, 95, 96, 97, 98, 97, 98],
      borderColor: 'rgba(75, 192, 192, 1)',
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      tension: 0.4
    },
    {
      label: 'Absent',
      data: [8, 9, 7, 6, 5, 4, 5, 4, 3, 2, 3, 2],
      borderColor: 'rgba(255, 99, 132, 1)',
      backgroundColor: 'rgba(255, 99, 132, 0.2)',
      tension: 0.4
    }
  ]
};

const mockPerformanceData = {
  labels: ['Math', 'Science', 'English', 'History', 'Art', 'PE'],
  datasets: [
    {
      label: 'Class Average',
      data: [85, 78, 82, 88, 92, 95],
      backgroundColor: 'rgba(54, 162, 235, 0.5)',
      borderColor: 'rgba(54, 162, 235, 1)',
      borderWidth: 1
    },
    {
      label: 'Student Score',
      data: [92, 85, 88, 90, 94, 98],
      backgroundColor: 'rgba(75, 192, 192, 0.5)',
      borderColor: 'rgba(75, 192, 192, 1)',
      borderWidth: 1
    }
  ]
};

const mockFeeCollectionData = {
  labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
  datasets: [
    {
      label: 'Collected',
      data: [12500, 11800, 13500, 14200, 12800, 13200, 14500, 13800, 15200, 14800, 15500, 16000],
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      borderColor: 'rgba(75, 192, 192, 1)',
      tension: 0.4,
      fill: true
    },
    {
      label: 'Pending',
      data: [2500, 2200, 2100, 1800, 2200, 2000, 1900, 2100, 1800, 1700, 1600, 1500],
      backgroundColor: 'rgba(255, 206, 86, 0.2)',
      borderColor: 'rgba(255, 206, 86, 1)',
      tension: 0.4,
      fill: true
    }
  ]
};

// Data Visualization
export const fetchAttendanceData = async (params = {}) => {
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  
  if (debugMode) console.log('Using mock attendance data');
  
  // Simulate API delay
  await new Promise(resolve => setTimeout(resolve, 300));
  
  // Return mock data
  return {
    data: mockAttendanceData,
    summary: {
      present: 96.5,
      absent: 3.5,
      late: 2.1,
      excused: 1.2
    },
    lastUpdated: new Date().toISOString()
  };
};

export const fetchPerformanceData = async (params = {}) => {
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  
  if (debugMode) console.log('Using mock performance data');
  
  // Simulate API delay
  await new Promise(resolve => setTimeout(resolve, 300));
  
  // Return mock data
  return {
    data: mockPerformanceData,
    summary: {
      averageGrade: 'A-',
      totalAssignments: 24,
      completed: 22,
      pending: 2,
      classRank: '5/120'
    },
    lastUpdated: new Date().toISOString()
  };
};

export const fetchFeeCollectionData = async (params = {}) => {
  const debugMode = import.meta.env.VITE_DEBUG_API === 'true';
  
  if (debugMode) console.log('Using mock fee collection data');
  
  // Simulate API delay
  await new Promise(resolve => setTimeout(resolve, 300));
  
  // Return mock data
  return {
    data: mockFeeCollectionData,
    summary: {
      totalCollected: 158700,
      totalPending: 23400,
      collectionRate: 87.1,
      lastPaymentDate: '2025-10-26T14:30:00Z'
    },
    lastUpdated: new Date().toISOString()
  };
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
