import api from './api';

const cmsService = {
  getPages: async () => {
    try {
      const response = await api.get('/v1/admin/cms/pages');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  getPage: async (id) => {
    try {
      const response = await api.get(`/v1/admin/cms/pages/${id}`);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  createPage: async (pageData) => {
    try {
      const response = await api.post('/v1/admin/cms/pages', pageData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  updatePage: async (id, pageData) => {
    try {
      const response = await api.put(`/v1/admin/cms/pages/${id}`, pageData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  deletePage: async (id) => {
    try {
      await api.delete(`/v1/admin/cms/pages/${id}`);
      return true;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  uploadMedia: async (file) => {
    const formData = new FormData();
    formData.append('file', file);

    try {
      const response = await api.post('/v1/admin/cms/media', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  getMedia: async () => {
    try {
      const response = await api.get('/v1/admin/cms/media');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  deleteMedia: async (id) => {
    try {
      await api.delete(`/v1/admin/cms/media/${id}`);
      return true;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  getMenus: async () => {
    try {
      const response = await api.get('/v1/admin/cms/menus');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  updateMenu: async (menuData) => {
    try {
      const response = await api.put('/v1/admin/cms/menus', menuData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  getSettings: async () => {
    try {
      const response = await api.get('/v1/admin/cms/settings');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  updateSettings: async (settings) => {
    try {
      const response = await api.put('/v1/admin/cms/settings', settings);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  getHeader: async () => {
    try {
      const response = await api.get('/v1/admin/cms/header');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  updateHeader: async (headerData) => {
    try {
      const response = await api.put('/v1/admin/cms/header', headerData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  getFooter: async () => {
    try {
      const response = await api.get('/v1/admin/cms/footer');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  updateFooter: async (footerData) => {
    try {
      const response = await api.put('/v1/admin/cms/footer', footerData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  getContentBlocks: async () => {
    try {
      const response = await api.get('/v1/admin/cms/blocks');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  getContentBlock: async (id) => {
    try {
      const response = await api.get(`/v1/admin/cms/blocks/${id}`);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  createContentBlock: async (blockData) => {
    try {
      const response = await api.post('/v1/admin/cms/blocks', blockData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  updateContentBlock: async (id, blockData) => {
    try {
      const response = await api.put(`/v1/admin/cms/blocks/${id}`, blockData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  deleteContentBlock: async (id) => {
    try {
      await api.delete(`/v1/admin/cms/blocks/${id}`);
      return true;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },
};

export default cmsService;
export { cmsService };
