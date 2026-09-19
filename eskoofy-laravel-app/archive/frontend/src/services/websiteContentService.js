import api from './api';

export const websiteContentService = {
  async getPageContent(page) {
    try {
      const response = await api.get(`/v1/website-content/${page}`, {
        validateStatus: (status) => status < 500,
        timeout: 5000
      });

      if (response.status === 404) {
        console.warn(`[websiteContentService] Content for ${page} not found (404).`);
        return null;
      }

      if (!response.data) {
        return null;
      }

      return response.data;
    } catch (error) {
      console.warn(`[websiteContentService] Error fetching ${page}:`, error.message);
      return null;
    }
  },

  async updatePageContent(page, content) {
    try {
      const response = await api.put(`/v1/admin/website-content/${page}`, content);
      return response.data;
    } catch (error) {
      console.error(`Error updating ${page} content:`, error);
      throw error;
    }
  },

  async uploadImage(page, file, fieldName) {
    try {
      const formData = new FormData();
      formData.append('image', file);
      formData.append('field_name', fieldName);

      const response = await api.post(
        `/v1/admin/website-content/${page}/upload-image`,
        formData,
        { headers: { 'Content-Type': 'multipart/form-data' } }
      );
      return response.data;
    } catch (error) {
      console.error('Error uploading image:', error);
      throw error;
    }
  },

  async getActivePages() {
    try {
      const response = await api.get('/v1/website-content/pages');
      return response.data;
    } catch (error) {
      console.error('Error fetching active pages:', error);
      throw error;
    }
  },
};
