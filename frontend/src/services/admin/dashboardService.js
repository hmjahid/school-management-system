import api from '../api';

/**
 * Fetches dashboard data from the API
 * @returns {Promise<Object>} Dashboard data
 */
export const fetchDashboardData = async () => {
  try {
    const response = await api.get('/api/admin/dashboard');
    console.log('Dashboard API Response:', response.data);
    return response.data;
  } catch (error) {
    console.error('Error fetching dashboard data:', error);
    console.error('Error details:', error.response?.data || error.message);
    throw error;
  }
};

/**
 * Fetches analytics overview data
 * @returns {Promise<Object>} Analytics data
 */
export const fetchAnalyticsOverview = async () => {
  try {
    const response = await api.get('/admin/analytics/overview');
    return response.data;
  } catch (error) {
    console.error('Error fetching analytics data:', error);
    throw error;
  }
};

/**
 * Fetches recent activity
 * @returns {Promise<Array>} List of recent activities
 */
export const fetchRecentActivity = async () => {
  try {
    const response = await api.get('/admin/activity');
    return response.data;
  } catch (error) {
    console.error('Error fetching recent activity:', error);
    throw error;
  }
};

/**
 * Performs a quick action
 * @param {string} action - Action to perform
 * @param {Object} data - Data for the action
 * @returns {Promise<Object>} Action result
 */
export const performQuickAction = async (action, data = {}) => {
  try {
    const response = await api.post('/admin/quick-actions', { action, ...data });
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
