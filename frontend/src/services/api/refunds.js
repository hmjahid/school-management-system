import api from './api';

/**
 * Get a list of refunds with optional filters and pagination
 * @param {Object} params - Query parameters for filtering and pagination
 * @param {string} [params.status] - Filter by status (pending, processing, completed, failed, cancelled)
 * @param {string} [params.payment_id] - Filter by payment ID
 * @param {string} [params.user_id] - Filter by user ID
 * @param {string} [params.date_from] - Filter by start date (YYYY-MM-DD)
 * @param {string} [params.date_to] - Filter by end date (YYYY-MM-DD)
 * @param {string} [params.search] - Search query
 * @param {string} [params.sort_by] - Field to sort by
 * @param {string} [params.sort_order] - Sort order (asc, desc)
 * @param {number} [params.page=1] - Page number
 * @param {number} [params.per_page=10] - Items per page
 * @returns {Promise<Object>} - Returns the list of refunds and pagination info
 */
export const getRefunds = async (params = {}) => {
  const response = await api.get('/api/refunds', { params });
  return response.data;
};

/**
 * Get a single refund by ID
 * @param {string|number} id - The refund ID
 * @returns {Promise<Object>} - Returns the refund details
 */
export const getRefund = async (id) => {
  const response = await api.get(`/api/refunds/${id}`);
  return response.data.data;
};

/**
 * Request a new refund
 * @param {string|number} paymentId - The payment ID to refund
 * @param {Object} data - Refund data
 * @param {number} data.amount - Amount to refund
 * @param {string} data.reason - Reason for the refund
 * @param {Object} [data.metadata] - Additional metadata
 * @returns {Promise<Object>} - Returns the created refund
 */
export const requestRefund = async (paymentId, { amount, reason, metadata }) => {
  const response = await api.post(`/api/payments/${paymentId}/refunds`, {
    amount,
    reason,
    metadata,
  });
  return response.data.data;
};

/**
 * Process a pending refund
 * @param {string|number} refundId - The refund ID to process
 * @param {Object} data - Processing data
 * @param {string} data.transaction_id - Transaction ID from payment gateway
 * @param {Object} [data.metadata] - Additional metadata
 * @returns {Promise<Object>} - Returns the updated refund
 */
export const processRefund = async (refundId, { transaction_id, metadata }) => {
  const response = await api.post(`/api/refunds/${refundId}/process`, {
    transaction_id,
    metadata,
  });
  return response.data.data;
};

/**
 * Cancel a pending or processing refund
 * @param {string|number} refundId - The refund ID to cancel
 * @param {Object} data - Cancellation data
 * @param {string} data.reason - Reason for cancellation
 * @param {Object} [data.metadata] - Additional metadata
 * @returns {Promise<Object>} - Returns the updated refund
 */
export const cancelRefund = async (refundId, { reason, metadata }) => {
  const response = await api.post(`/api/refunds/${refundId}/cancel`, {
    reason,
    metadata,
  });
  return response.data.data;
};

/**
 * Get refund statistics
 * @param {Object} [params] - Query parameters for filtering
 * @param {string} [params.group_by] - Group by field (day, week, month, year, status, payment_method)
 * @param {string} [params.date_from] - Filter by start date (YYYY-MM-DD)
 * @param {string} [params.date_to] - Filter by end date (YYYY-MM-DD)
 * @returns {Promise<Object>} - Returns the refund statistics
 */
export const getRefundStats = async (params = {}) => {
  const response = await api.get('/api/refunds/stats', { params });
  return response.data;
};

/**
 * Export refunds to a file
 * @param {Object} [params] - Query parameters for filtering
 * @param {string} [params.format=csv] - Export format (csv, xlsx, pdf)
 * @param {string} [params.fields] - Comma-separated list of fields to include
 * @returns {Promise<Blob>} - Returns the exported file as a Blob
 */
export const exportRefunds = async (params = {}) => {
  const response = await api.get('/api/refunds/export', {
    params: {
      format: 'csv', // Default format
      ...params,
    },
    responseType: 'blob',
  });
  return response.data;
};

/**
 * Export a refund receipt
 * @param {string|number} refundId - The refund ID
 * @param {string} [format=pdf] - Export format (pdf, csv)
 * @returns {Promise<Blob>} - Returns the receipt file as a Blob
 */
export const exportRefundReceipt = async (refundId, format = 'pdf') => {
  const response = await api.get(`/api/refunds/${refundId}/receipt`, {
    params: { format },
    responseType: 'blob',
  });
  return response.data;
};

/**
 * Get refund reasons (for dropdowns)
 * @returns {Promise<Array>} - Returns the list of common refund reasons
 */
export const getRefundReasons = async () => {
  const response = await api.get('/api/refunds/reasons');
  return response.data;
};

/**
 * Get refund settings
 * @returns {Promise<Object>} - Returns the refund settings
 */
export const getRefundSettings = async () => {
  const response = await api.get('/api/settings/refunds');
  return response.data;
};

/**
 * Update refund settings
 * @param {Object} settings - Settings to update
 * @returns {Promise<Object>} - Returns the updated settings
 */
export const updateRefundSettings = async (settings) => {
  const response = await api.put('/api/settings/refunds', settings);
  return response.data;
};

/**
 * Process multiple refunds in bulk
 * @param {Array} refunds - Array of refund IDs to process
 * @param {Object} data - Processing data
 * @param {string} data.transaction_id - Base transaction ID (will be appended with index)
 * @param {Object} [data.metadata] - Additional metadata
 * @returns {Promise<Object>} - Returns the processing results
 */
export const processBulkRefunds = async (refunds, { transaction_id, metadata }) => {
  const response = await api.post('/api/refunds/bulk-process', {
    refunds,
    transaction_id,
    metadata,
  });
  return response.data;
};

export default {
  getRefunds,
  getRefund,
  requestRefund,
  processRefund,
  cancelRefund,
  getRefundStats,
  exportRefunds,
  exportRefundReceipt,
  getRefundReasons,
  getRefundSettings,
  updateRefundSettings,
  processBulkRefunds,
};
