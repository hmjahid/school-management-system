import api from './api';

const galleryService = {
  /**
   * Get all gallery items with optional filtering
   * @param {Object} filters - Optional filters (e.g., { category: 'events' })
   * @returns {Promise<{success: boolean, data: Array, error: string|null}>}
   */
  async getGalleryItems(filters = {}) {
    try {
      const response = await api.get('/website/gallery', { params: filters });
      return {
        success: true,
        data: response.data.data || [],
        error: null
      };
    } catch (error) {
      console.error('Error fetching gallery items:', error);
      // Fallback to sample data if API fails
      const fallbackData = this.getFallbackData();
      return {
        success: false,
        data: fallbackData.items,
        error: 'Using sample data. Could not connect to server.'
      };
    }
  },

  /**
   * Get all available gallery categories
   * @returns {Promise<{success: boolean, data: Array, error: string|null}>}
   */
  async getGalleryCategories() {
    try {
      const response = await api.get('/website/gallery/categories');
      return {
        success: true,
        data: response.data.data || [],
        error: null
      };
    } catch (error) {
      console.error('Error fetching gallery categories:', error);
      // Fallback to sample data if API fails
      const fallbackData = this.getFallbackData();
      return {
        success: false,
        data: fallbackData.categories,
        error: 'Using sample data. Could not connect to server.'
      };
    }
  },

  // Fallback data in case API is not available
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
        // ... other fallback items
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
