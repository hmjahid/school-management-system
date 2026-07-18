import api from './api';

export const getFees = async (filters = {}) => {
  try {
    const response = await api.get('/v1/fees', { params: filters });
    return response.data;
  } catch (error) {
    console.error('Error fetching fees:', error);
    throw error;
  }
};

export const getFeeById = async (id) => {
  try {
    const response = await api.get(`/v1/fees/${id}`);
    return response.data;
  } catch (error) {
    console.error(`Error fetching fee with ID ${id}:`, error);
    throw error;
  }
};

export const createFee = async (feeData) => {
  try {
    const response = await api.post('/v1/fees', feeData);
    return response.data;
  } catch (error) {
    console.error('Error creating fee:', error);
    throw error;
  }
};

export const updateFee = async (id, feeData) => {
  try {
    const response = await api.put(`/v1/fees/${id}`, feeData);
    return response.data;
  } catch (error) {
    console.error(`Error updating fee with ID ${id}:`, error);
    throw error;
  }
};

export const deleteFee = async (id) => {
  try {
    await api.delete(`/v1/fees/${id}`);
  } catch (error) {
    console.error(`Error deleting fee with ID ${id}:`, error);
    throw error;
  }
};

export const getFeePayments = async (feeId, filters = {}) => {
  try {
    const response = await api.get(`/v1/fees/${feeId}/payments`, { params: filters });
    return response.data;
  } catch (error) {
    console.error(`Error fetching payments for fee ${feeId}:`, error);
    throw error;
  }
};

export const recordPayment = async (feeId, paymentData) => {
  try {
    const response = await api.post(`/v1/fees/${feeId}/payments`, paymentData);
    return response.data;
  } catch (error) {
    console.error(`Error recording payment for fee ${feeId}:`, error);
    throw error;
  }
};

export const getFeeStatistics = async () => {
  try {
    const response = await api.get('/v1/fees/statistics');
    return response.data;
  } catch (error) {
    console.error('Error fetching fee statistics:', error);
    throw error;
  }
};

export const getFeeTypes = async () => {
  try {
    const response = await api.get('/v1/fees/types');
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
