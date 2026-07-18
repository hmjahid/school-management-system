import api from './api';

const galleryService = {
  async getGalleryItems(filters = {}) {
    try {
      const response = await api.get('/v1/website/gallery', { params: filters });
      return {
        success: true,
        data: response.data.data || [],
        error: null
      };
    } catch (error) {
      console.error('Error fetching gallery items:', error);
      const fallbackData = this.getFallbackData();
      return {
        success: false,
        data: fallbackData.items,
        error: 'Using sample data. Could not connect to server.'
      };
    }
  },

  async getGalleryCategories() {
    try {
      const response = await api.get('/v1/website/gallery/categories');
      return {
        success: true,
        data: response.data.data || [],
        error: null
      };
    } catch (error) {
      console.error('Error fetching gallery categories:', error);
      const fallbackData = this.getFallbackData();
      return {
        success: false,
        data: fallbackData.categories,
        error: 'Using sample data. Could not connect to server.'
      };
    }
  },

  getFallbackData() {
    return {
      items: [
        {
          id: 1,
          title: 'Annual Day Celebration',
          category: 'events',
          image: '/images/gallery/event1.jpg',
          date: '2023-10-10',
          featured: true
        },
      ],
      categories: [
        { id: 'all', name: 'All' },
        { id: 'events', name: 'Events' },
        { id: 'sports', name: 'Sports' },
        { id: 'academics', name: 'Academics' },
        { id: 'cultural', name: 'Cultural' },
      ]
    };
  }
};

export default galleryService;
