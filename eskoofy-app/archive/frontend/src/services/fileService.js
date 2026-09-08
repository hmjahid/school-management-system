import api from './api';

/**
 * Uploads a file to the server
 * @param {File} file - The file to upload
 * @param {string} [folder='receipts'] - The folder to upload the file to
 * @param {Object} [additionalData={}] - Additional form data to include in the request
 * @returns {Promise<Object>} The response data from the server
 */
export const uploadFile = async (file, folder = 'receipts', additionalData = {}) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('folder', folder);
  
  // Append additional data to form data
  Object.entries(additionalData).forEach(([key, value]) => {
    if (value !== undefined && value !== null) {
      formData.append(key, value);
    }
  });
  
  try {
    const response = await api.post('/uploads', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    
    return response.data;
  } catch (error) {
    console.error('Error uploading file:', error);
    throw new Error(error.response?.data?.message || 'Failed to upload file');
  }
};

/**
 * Uploads multiple files to the server
 * @param {File[]} files - Array of files to upload
 * @param {string} [folder='receipts'] - The folder to upload the files to
 * @param {Object} [additionalData={}] - Additional form data to include in the request
 * @returns {Promise<Array>} Array of uploaded file data
 */
export const uploadFiles = async (files, folder = 'receipts', additionalData = {}) => {
  const uploadPromises = files.map(file => 
    uploadFile(file, folder, additionalData)
  );
  
  try {
    const results = await Promise.all(uploadPromises);
    return results;
  } catch (error) {
    console.error('Error uploading files:', error);
    throw error;
  }
};

/**
 * Deletes a file from the server
 * @param {string} filePath - The path of the file to delete
 * @returns {Promise<Object>} The response data from the server
 */
export const deleteFile = async (filePath) => {
  try {
    const response = await api.delete('/uploads', { 
      data: { filePath },
      headers: {
        'Content-Type': 'application/json',
      },
    });
    
    return response.data;
  } catch (error) {
    console.error('Error deleting file:', error);
    throw new Error(error.response?.data?.message || 'Failed to delete file');
  }
};

/**
 * Gets the full URL for a file path
 * @param {string} filePath - The file path
 * @returns {string} The full URL to the file
 */
export const getFileUrl = (filePath) => {
  if (!filePath) return '';
  
  // If it's already a full URL, return as is
  if (filePath.startsWith('http')) {
    return filePath;
  }
  
  // Otherwise, construct the URL using the API base URL
  const baseUrl = import.meta.env.VITE_API_BASE_URL || '';
  return `${baseUrl.replace(/\/+$/, '')}/${filePath.replace(/^\/+/, '')}`;
};
