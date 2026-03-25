import api from './api';

const cmsService = {
  // Pages
  getPages: async () => {
    try {
      const response = await api.get('/cms/pages');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  getPage: async (id) => {
    try {
      const response = await api.get(`/cms/pages/${id}`);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  createPage: async (pageData) => {
    try {
      const response = await api.post('/cms/pages', pageData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  updatePage: async (id, pageData) => {
    try {
      const response = await api.put(`/cms/pages/${id}`, pageData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  deletePage: async (id) => {
    try {
      await api.delete(`/cms/pages/${id}`);
      return true;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  // Media
  uploadMedia: async (file) => {
    const formData = new FormData();
    formData.append('file', file);

    try {
      const response = await api.post('/cms/media', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  getMedia: async () => {
    try {
      const response = await api.get('/cms/media');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  deleteMedia: async (id) => {
    try {
      await api.delete(`/cms/media/${id}`);
      return true;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  // Menus
  getMenus: async () => {
    try {
      const response = await api.get('/cms/menus');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  updateMenu: async (menuData) => {
    try {
      const response = await api.put('/cms/menus', menuData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  // Settings
  getSettings: async () => {
    try {
      const response = await api.get('/cms/settings');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  updateSettings: async (settings) => {
    try {
      const response = await api.put('/cms/settings', settings);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  // Header & Footer
  getHeader: async () => {
    try {
      const response = await api.get('/cms/header');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  updateHeader: async (headerData) => {
    try {
      const response = await api.put('/cms/header', headerData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  getFooter: async () => {
    try {
      const response = await api.get('/cms/footer');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  updateFooter: async (footerData) => {
    try {
      const response = await api.put('/cms/footer', footerData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  // Content Blocks
  getContentBlocks: async () => {
    try {
      const response = await api.get('/cms/blocks');
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  getContentBlock: async (id) => {
    try {
      const response = await api.get(`/cms/blocks/${id}`);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  createContentBlock: async (blockData) => {
    try {
      const response = await api.post('/cms/blocks', blockData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  updateContentBlock: async (id, blockData) => {
    try {
      const response = await api.put(`/cms/blocks/${id}`, blockData);
      return response.data;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },

  deleteContentBlock: async (id) => {
    try {
      await api.delete(`/cms/blocks/${id}`);
      return true;
    } catch (error) {
      throw error.response?.data || error.message;
    }
  },
};

export default cmsService;
export { cmsService };
