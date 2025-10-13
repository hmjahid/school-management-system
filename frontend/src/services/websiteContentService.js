import axios from 'axios';

// Base URL for website content API
const API_BASE_URL = `${import.meta.env.VITE_API_URL || 'http://localhost:8000/api'}/website-content`;

export const websiteContentService = {
  /**
   * Get content for a specific page
   * @param {string} page - The page identifier (e.g., 'home', 'about', 'contact')
   * @returns {Promise<Object>} The page content
   */
  async getPageContent(page) {
    const endpoint = `${API_BASE_URL}/${page}`;
    console.log(`[websiteContentService] Fetching content from: ${endpoint}`);
    
    try {
      const response = await axios.get(endpoint, {
        // Don't throw on HTTP error status codes
        validateStatus: (status) => status < 500,
        // Add a timeout of 3 seconds
        timeout: 3000
      });
      
      console.log(`[websiteContentService] Response status for ${page}:`, response.status);
      
      // If we get a 404, the endpoint doesn't exist yet
      if (response.status === 404) {
        console.warn(`[websiteContentService] API endpoint for ${page} not found (404). Using default content.`);
        return null; // Will trigger fallback to initialContent
      }
      
      // Log the response data for debugging
      console.log(`[websiteContentService] Response data for ${page}:`, response.data);
      
      // If we get a successful response but no data, return null
      if (!response.data) {
        console.warn(`[websiteContentService] No data received for ${page}. Using default content.`);
        return null;
      }
      
      return response.data;
    } catch (error) {
      // Handle different types of errors
      if (error.response) {
        // The request was made and the server responded with a status code
        // that falls out of the range of 2xx
        console.warn(`[websiteContentService] Request failed with status ${error.response.status} for ${endpoint}`);
      } else if (error.request) {
        // The request was made but no response was received
        console.warn(`[websiteContentService] No response received for ${endpoint}`, error.message);
      } else if (error.code === 'ECONNABORTED') {
        console.warn(`[websiteContentService] Request timeout for ${endpoint}`);
      } else if (!navigator.onLine) {
        console.warn('[websiteContentService] No internet connection');
      } else {
        // Something happened in setting up the request that triggered an Error
        console.warn(`[websiteContentService] Error setting up request for ${endpoint}:`, error.message);
      }
      return null; // Will trigger fallback to initialContent
    }
  },

  /**
   * Update page content (admin only)
   * @param {string} page - The page identifier
   * @param {Object} content - The updated content
   * @returns {Promise<Object>} The updated page content
   */
  async updatePageContent(page, content) {
    try {
      const response = await axios.put(`${API_BASE_URL}/${page}`, content);
      return response.data;
    } catch (error) {
      console.error(`Error updating ${page} content:`, error);
      throw error;
    }
  },

  /**
   * Upload an image for a page (admin only)
   * @param {string} page - The page identifier
   * @param {File} file - The image file to upload
   * @param {string} fieldName - The field name for the image
   * @returns {Promise<Object>} The uploaded image information
   */
  async uploadImage(page, file, fieldName) {
    try {
      const formData = new FormData();
      formData.append('image', file);
      formData.append('field_name', fieldName);

      const response = await axios.post(
        `${API_BASE_URL}/${page}/upload-image`,
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        }
      );
      return response.data;
    } catch (error) {
      console.error('Error uploading image:', error);
      throw error;
    }
  },

  /**
   * Get all active pages
   * @returns {Promise<Array>} List of active pages
   */
  async getActivePages() {
    try {
      const response = await axios.get(`${API_BASE_URL}/pages`);
      return response.data;
    } catch (error) {
      console.error('Error fetching active pages:', error);
      throw error;
    }
  },
};
