import api from './api';

/**
 * Get all fees with optional filters
 * @param {Object} filters - Filter criteria
 * @returns {Promise<Array>} - List of fees
 */
export const getFees = async (filters = {}) => {
  try {
    const response = await api.get('/fees', { params: filters });
    return response.data;
  } catch (error) {
    console.error('Error fetching fees:', error);
    throw error;
  }
};

/**
 * Get a single fee by ID
 * @param {number|string} id - Fee ID
 * @returns {Promise<Object>} - Fee details
 */
export const getFeeById = async (id) => {
  try {
    const response = await api.get(`/fees/${id}`);
    return response.data;
  } catch (error) {
    console.error(`Error fetching fee with ID ${id}:`, error);
    throw error;
  }
};

/**
 * Create a new fee
 * @param {Object} feeData - Fee data
 * @returns {Promise<Object>} - Created fee
 */
export const createFee = async (feeData) => {
  try {
    const response = await api.post('/fees', feeData);
    return response.data;
  } catch (error) {
    console.error('Error creating fee:', error);
    throw error;
  }
};

/**
 * Update an existing fee
 * @param {number|string} id - Fee ID
 * @param {Object} feeData - Updated fee data
 * @returns {Promise<Object>} - Updated fee
 */
export const updateFee = async (id, feeData) => {
  try {
    const response = await api.put(`/fees/${id}`, feeData);
    return response.data;
  } catch (error) {
    console.error(`Error updating fee with ID ${id}:`, error);
    throw error;
  }
};

/**
 * Delete a fee
 * @param {number|string} id - Fee ID
 * @returns {Promise<void>}
 */
export const deleteFee = async (id) => {
  try {
    await api.delete(`/fees/${id}`);
  } catch (error) {
    console.error(`Error deleting fee with ID ${id}:`, error);
    throw error;
  }
};

/**
 * Get payments for a specific fee
 * @param {number|string} feeId - Fee ID
 * @param {Object} filters - Additional filters
 * @returns {Promise<Array>} - List of payments
 */
export const getFeePayments = async (feeId, filters = {}) => {
  try {
    const response = await api.get(`/fees/${feeId}/payments`, { params: filters });
    return response.data;
  } catch (error) {
    console.error(`Error fetching payments for fee ${feeId}:`, error);
    throw error;
  }
};

/**
 * Record a new payment for a fee
 * @param {number|string} feeId - Fee ID
 * @param {Object} paymentData - Payment data
 * @returns {Promise<Object>} - Created payment
 */
export const recordPayment = async (feeId, paymentData) => {
  try {
    const response = await api.post(`/fees/${feeId}/payments`, paymentData);
    return response.data;
  } catch (error) {
    console.error(`Error recording payment for fee ${feeId}:`, error);
    throw error;
  }
};

/**
 * Get fee statistics
 * @returns {Promise<Object>} - Fee statistics
 */
export const getFeeStatistics = async () => {
  try {
    const response = await api.get('/fees/statistics');
    return response.data;
  } catch (error) {
    console.error('Error fetching fee statistics:', error);
    throw error;
  }
};

/**
 * Get fee types
 * @returns {Promise<Array>} - List of fee types
 */
export const getFeeTypes = async () => {
  try {
    const response = await api.get('/fees/types');
    return response.data;
  } catch (error) {
    console.error('Error fetching fee types:', error);
    throw error;
  }
};

export default {
  getFees,
  getFeeById,
  createFee,
  updateFee,
  deleteFee,
  getFeePayments,
  recordPayment,
  getFeeStatistics,
  getFeeTypes,
};
