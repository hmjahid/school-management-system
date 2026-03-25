import api from './api';
import { saveAs } from 'file-saver';

/**
 * Export fees data to the specified format
 * @param {Object} filters - Filters to apply to the export
 * @param {string} format - Export format (csv, excel, pdf)
 * @param {string} [filename='fees-export'] - Base filename for the exported file
 * @returns {Promise<void>}
 */
export const exportFees = async (filters = {}, format = 'excel', filename = 'fees-export') => {
  try {
    const response = await api.get('/exports/fees', {
      params: {
        ...filters,
        format,
      },
      responseType: 'blob',
    });
    
    // Determine the file extension based on the format
    const extension = format === 'csv' ? 'csv' : format === 'pdf' ? 'pdf' : 'xlsx';
    const fullFilename = `${filename}-${new Date().toISOString().split('T')[0]}.${extension}`;
    
    // Save the file using file-saver
    saveAs(new Blob([response.data]), fullFilename);
  } catch (error) {
    console.error('Error exporting fees:', error);
    throw new Error(error.response?.data?.message || 'Failed to export fees');
  }
};

/**
 * Export payments data to the specified format
 * @param {Object} filters - Filters to apply to the export
 * @param {string} format - Export format (csv, excel, pdf)
 * @param {string} [filename='payments-export'] - Base filename for the exported file
 * @returns {Promise<void>}
 */
export const exportPayments = async (filters = {}, format = 'excel', filename = 'payments-export') => {
  try {
    const response = await api.get('/exports/payments', {
      params: {
        ...filters,
        format,
      },
      responseType: 'blob',
    });
    
    // Determine the file extension based on the format
    const extension = format === 'csv' ? 'csv' : format === 'pdf' ? 'pdf' : 'xlsx';
    const fullFilename = `${filename}-${new Date().toISOString().split('T')[0]}.${extension}`;
    
    // Save the file using file-saver
    saveAs(new Blob([response.data]), fullFilename);
  } catch (error) {
    console.error('Error exporting payments:', error);
    throw new Error(error.response?.data?.message || 'Failed to export payments');
  }
};

/**
 * Export fee details including payments to the specified format
 * @param {string} feeId - The ID of the fee to export
 * @param {string} format - Export format (csv, excel, pdf)
 * @param {string} [filename] - Base filename for the exported file
 * @returns {Promise<void>}
 */
export const exportFeeDetails = async (feeId, format = 'pdf', filename) => {
  try {
    if (!feeId) {
      throw new Error('Fee ID is required');
    }
    
    const response = await api.get(`/exports/fees/${feeId}`, {
      params: { format },
      responseType: 'blob',
    });
    
    const defaultFilename = `fee-details-${feeId}-${new Date().toISOString().split('T')[0]}`;
    const extension = format === 'csv' ? 'csv' : format === 'pdf' ? 'pdf' : 'xlsx';
    const fullFilename = `${filename || defaultFilename}.${extension}`;
    
    saveAs(new Blob([response.data]), fullFilename);
  } catch (error) {
    console.error('Error exporting fee details:', error);
    throw new Error(error.response?.data?.message || 'Failed to export fee details');
  }
};

/**
 * Export payment receipt as PDF
 * @param {string} paymentId - The ID of the payment
 * @param {string} [filename] - Custom filename for the exported file
 * @returns {Promise<void>}
 */
export const exportPaymentReceipt = async (paymentId, filename) => {
  try {
    if (!paymentId) {
      throw new Error('Payment ID is required');
    }
    
    const response = await api.get(`/exports/payments/${paymentId}/receipt`, {
      responseType: 'blob',
    });
    
    const defaultFilename = `payment-receipt-${paymentId}-${new Date().toISOString().split('T')[0]}.pdf`;
    saveAs(new Blob([response.data]), filename || defaultFilename);
  } catch (error) {
    console.error('Error exporting payment receipt:', error);
    throw new Error(error.response?.data?.message || 'Failed to export payment receipt');
  }
};
