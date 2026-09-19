import api from '../api';

const transformDashboardData = (apiData) => {
  const totals = apiData?.totals ?? {};
  const monthlyData = apiData?.monthly_data ?? [];
  const recentActivity = apiData?.recent_activity ?? [];
  const classDistribution = apiData?.class_distribution ?? {};
  const performanceMetrics = apiData?.performance_metrics ?? {};

  const months = monthlyData.map(d => d.month);
  const revenueData = monthlyData.map(d => d.revenue);
  const newStudentsData = monthlyData.map(d => d.new_students);
  const attendanceRates = monthlyData.map(d => d.attendance_rate);

  const studentPerformance = {
    labels: ['A', 'B', 'C', 'D', 'F'],
    data: [
      performanceMetrics?.current?.enrollments || 0,
      Math.round((totals?.students || 0) * 0.3),
      Math.round((totals?.students || 0) * 0.2),
      Math.round((totals?.students || 0) * 0.1),
      Math.round((totals?.students || 0) * 0.05),
    ],
    title: 'Student Performance',
    type: 'doughnut',
  };

  return {
    stats: {
      totalStudents: totals?.students ?? 0,
      totalTeachers: totals?.teachers ?? 0,
      totalClasses: totals?.classes ?? 0,
      totalRevenue: revenueData.reduce((a, b) => a + b, 0),
      totalStaff: totals?.staff ?? 0,
      attendanceRate: attendanceRates.length > 0 ? attendanceRates[attendanceRates.length - 1] : 0,
      pendingAssignments: apiData?.pending_assignments?.length ?? 0,
      upcomingEvents: apiData?.upcoming_events?.length ?? 0,
      studentsTrend: newStudentsData.length > 1
        ? Math.round(((newStudentsData[newStudentsData.length - 1] - newStudentsData[newStudentsData.length - 2]) / Math.max(newStudentsData[newStudentsData.length - 2], 1)) * 100)
        : 0,
      teachersTrend: 0,
      classesTrend: 0,
      revenueGrowth: performanceMetrics?.growth?.revenue ?? 0,
    },
    charts: {
      monthlyRevenue: {
        title: 'Monthly Revenue',
        labels: months,
        data: revenueData,
        type: 'line',
        color: 'primary',
      },
      studentPerformance,
      classDistribution: {
        title: 'Students by Class',
        labels: Object.keys(classDistribution),
        data: Object.values(classDistribution),
        type: 'doughnut',
        color: 'warning',
      },
      attendanceTrend: {
        title: 'Attendance Rate',
        labels: months,
        data: attendanceRates,
        type: 'line',
        color: 'info',
        suffix: '%',
      },
      newStudents: {
        title: 'New Students',
        labels: months,
        data: newStudentsData,
        type: 'bar',
        color: 'success',
      },
    },
    recentActivity: recentActivity.map(a => ({
      id: a.id,
      type: a.type || 'activity',
      message: a.message || a.title || 'No details',
      time: a.time || '',
      icon: a.icon || 'bell',
    })),
    quickActions: [
      { id: 'add-student', title: 'Add Student', icon: 'user-plus', url: '/students/create', color: 'indigo' },
      { id: 'add-teacher', title: 'Add Teacher', icon: 'chalkboard-teacher', url: '/teachers/create', color: 'green' },
      { id: 'create-class', title: 'Create Class', icon: 'layer-group', url: '/classes/create', color: 'blue' },
      { id: 'generate-report', title: 'Generate Report', icon: 'file-export', url: '/reports', color: 'purple' },
    ],
    lastUpdated: apiData?.meta?.last_updated || new Date().toISOString(),
  };
};

export const fetchDashboardData = async () => {
  try {
    const response = await api.get('/v1/admin/dashboard');
    const raw = response.data?.data ?? response.data;
    return transformDashboardData(raw);
  } catch (error) {
    console.error('Error fetching dashboard data:', error);
    throw error;
  }
};

export const fetchAnalyticsOverview = async () => {
  try {
    const response = await api.get('/v1/admin/analytics/overview');
    return response.data?.data ?? response.data;
  } catch (error) {
    console.error('Error fetching analytics data:', error);
    throw error;
  }
};

export const fetchRecentActivity = async () => {
  try {
    const response = await api.get('/v1/admin/activity');
    return response.data?.data ?? response.data;
  } catch (error) {
    console.error('Error fetching recent activity:', error);
    throw error;
  }
};

export const performQuickAction = async (action, data = {}) => {
  try {
    const response = await api.post('/v1/admin/quick-actions', { action, ...data });
    return response.data;
  } catch (error) {
    console.error('Error performing quick action:', error);
    throw error;
  }
};

export default {
  fetchDashboardData,
  fetchAnalyticsOverview,
  fetchRecentActivity,
  performQuickAction,
};
