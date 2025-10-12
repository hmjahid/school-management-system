import axios from 'axios';

const API_BASE_URL = '/api/website';

export const websiteContentService = {
  /**
   * Get content for a specific page
   * @param {string} page - The page identifier (e.g., 'home', 'about', 'contact')
   * @returns {Promise<Object>} The page content
   */
  async getPageContent(page) {
    try {
      const response = await axios.get(`${API_BASE_URL}/${page}`, {
        // Add timeout to prevent hanging
        timeout: 5000,
        // Don't throw on HTTP error status codes
        validateStatus: (status) => status < 500
      });
      
      // If we get a 404, the endpoint doesn't exist yet
      if (response.status === 404) {
        console.warn(`API endpoint for ${page} not found (404). Using default content.`);
        return null; // Will trigger fallback to initialContent
      }
      
      // If we get a successful response but no data, return null
      if (!response.data) {
        console.warn(`No data received for ${page}. Using default content.`);
        return null;
      }
      
      return response.data;
    } catch (error) {
      // Network errors, timeouts, etc.
      if (error.code === 'ECONNABORTED') {
        console.warn(`Request timeout for ${page}. Using default content.`);
      } else if (!navigator.onLine) {
        console.warn('No internet connection. Using default content.');
      } else {
        console.warn(`Error fetching ${page}:`, error.message);
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
