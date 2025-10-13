import axios from 'axios';

// Use the environment variable if it exists, otherwise default to local development server
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

// Sample data to use when the backend is not available
const SAMPLE_NEWS = [
  {
    id: '1',
    title: 'Welcome to Our New Website',
    excerpt: 'We are excited to launch our new school website with improved features and better user experience.',
    content: 'Our new website offers a modern design, easy navigation, and up-to-date information about our school. Explore the site to learn more about our programs, faculty, and upcoming events. The new platform includes interactive features, a mobile-friendly interface, and easy access to important resources for students, parents, and staff.',
    imageUrl: '/images/news/website-launch.jpg',
    category: 'Announcement',
    date: new Date().toISOString(),
    author: 'School Administration',
    readTime: '2 min read'
  },
  {
    id: '2',
    title: 'Annual Science Fair 2023',
    excerpt: 'Students showcase innovative science projects in our annual science fair.',
    content: 'The annual science fair was a great success with over 50 student projects on display. Topics ranged from renewable energy solutions to artificial intelligence applications. The event was judged by a panel of local scientists and educators, with prizes awarded in various categories. Special thanks to all the teachers, students, and volunteers who made this event possible.',
    imageUrl: '/images/news/science-fair.jpg',
    category: 'Events',
    date: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString(),
    author: 'Science Department',
    readTime: '3 min read'
  },
  {
    id: '3',
    title: 'New Sports Facilities Now Open',
    excerpt: 'State-of-the-art sports complex now available for student use.',
    content: 'We are proud to announce the opening of our new sports complex, featuring a full-size soccer field, basketball courts, and a modern fitness center. These facilities will support our physical education program and after-school sports activities. The grand opening ceremony will include demonstrations by our school teams and a community open house this weekend.',
    imageUrl: '/images/news/sports-complex.jpg',
    category: 'Facilities',
    date: new Date(Date.now() - 10 * 24 * 60 * 60 * 1000).toISOString(),
    author: 'Athletics Department',
    readTime: '2 min read'
  }
];

const SAMPLE_EVENTS = [
  {
    id: 'e1',
    title: 'Parent-Teacher Conference',
    description: 'Annual parent-teacher conference to discuss student progress and academic performance.',
    date: new Date(Date.now() + 5 * 24 * 60 * 60 * 1000).toISOString(),
    endDate: new Date(Date.now() + 5 * 24 * 60 * 60 * 1000 + 2 * 60 * 60 * 1000).toISOString(),
    location: 'School Auditorium',
    imageUrl: '/images/events/parent-teacher.jpg',
    category: 'Academic',
    registrationRequired: true,
    registrationDeadline: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString()
  },
  {
    id: 'e2',
    title: 'School Talent Show',
    description: 'An evening of performances showcasing our students\' diverse talents in music, dance, and drama.',
    date: new Date(Date.now() + 10 * 24 * 60 * 60 * 1000).toISOString(),
    endDate: new Date(Date.now() + 10 * 24 * 60 * 60 * 1000 + 3 * 60 * 60 * 1000).toISOString(),
    location: 'School Auditorium',
    imageUrl: '/images/events/talent-show.jpg',
    category: 'Performing Arts',
    registrationRequired: false
  },
  {
    id: 'e3',
    title: 'College Fair',
    description: 'Meet representatives from top universities and learn about higher education opportunities.',
    date: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString(),
    endDate: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000 + 5 * 60 * 60 * 1000).toISOString(),
    location: 'School Gymnasium',
    imageUrl: '/images/events/college-fair.jpg',
    category: 'Academic',
    registrationRequired: true,
    registrationDeadline: new Date(Date.now() + 12 * 24 * 60 * 60 * 1000).toISOString()
  },
  {
    id: 'e4',
    title: 'Sports Day',
    description: 'Annual inter-house sports competition featuring various athletic events and team challenges.',
    date: new Date(Date.now() + 21 * 24 * 60 * 60 * 1000).toISOString(),
    endDate: new Date(Date.now() + 21 * 24 * 60 * 60 * 1000 + 8 * 60 * 60 * 1000).toISOString(),
    location: 'School Sports Field',
    imageUrl: '/images/events/sports-day.jpg',
    category: 'Sports',
    registrationRequired: true,
    registrationDeadline: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString()
  }
];

/**
 * Fetch all news articles from the backend
 * @param {Object} options - Optional query parameters
 * @param {number} options.limit - Maximum number of news items to return
 * @param {string} options.category - Filter news by category
 * @returns {Promise<Object>} Object containing success status, data, and error message if any
 */
export const getNews = async ({ limit, category } = {}) => {
  try {
    const params = new URLSearchParams();
    if (limit) params.append('limit', limit);
    if (category) params.append('category', category);
    
    const response = await axios.get(`${API_URL}/news?${params.toString()}`, {
      // Don't throw an error for 404 responses
      validateStatus: (status) => status >= 200 && status < 300 || status === 404
    });

    // If the endpoint returns 404, return sample data
    if (response.status === 404) {
      console.warn('News endpoint not found, using sample data');
      return {
        success: true,
        data: limit ? SAMPLE_NEWS.slice(0, limit) : SAMPLE_NEWS,
        error: null
      };
    }

    return {
      success: true,
      data: response.data,
      error: null
    };
  } catch (error) {
    console.error('Error fetching news:', error);
    // Return sample data on error
    return {
      success: true,
      data: limit ? SAMPLE_NEWS.slice(0, limit) : SAMPLE_NEWS,
      error: 'Using sample data as the news service is temporarily unavailable.'
    };
  }
};

/**
 * Fetch a single news article by ID
 * @param {string} id - The ID of the news article to fetch
 * @returns {Promise<Object>} Object containing success status, data, and error message if any
 */
export const getNewsById = async (id) => {
  try {
    const response = await axios.get(`${API_URL}/news/${id}`, {
      validateStatus: (status) => status >= 200 && status < 300 || status === 404
    });

    // If the endpoint returns 404, try to find in sample data
    if (response.status === 404) {
      console.warn(`News item ${id} not found, checking sample data`);
      const sampleNews = SAMPLE_NEWS.find(item => item.id === id);
      if (sampleNews) {
        return {
          success: true,
          data: sampleNews,
          error: null
        };
      }
      throw new Error('News article not found');
    }

    return {
      success: true,
      data: response.data,
      error: null
    };
  } catch (error) {
    console.error(`Error fetching news item ${id}:`, error);
    // Try to find in sample data as fallback
    const sampleNews = SAMPLE_NEWS.find(item => item.id === id);
    if (sampleNews) {
      return {
        success: true,
        data: sampleNews,
        error: 'Using sample data as the news service is temporarily unavailable.'
      };
    }
    
    return {
      success: false,
      data: null,
      error: 'News article not found. It may have been removed or is temporarily unavailable.'
    };
  }
};

/**
 * Fetch upcoming events from the backend
 * @param {Object} options - Optional query parameters
 */
export const getUpcomingEvents = async (limit = 3) => {
  try {
    const response = await axios.get(`${API_URL}/news/upcoming-events?limit=${limit}`, {
      // Don't throw an error for 404 responses
      validateStatus: (status) => status >= 200 && status < 300 || status === 404
    });

    // If the endpoint returns 404, return sample data
    if (response.status === 404) {
      console.warn('Upcoming events endpoint not found, using sample data');
      return {
        success: true,
        data: limit ? SAMPLE_EVENTS.slice(0, limit) : SAMPLE_EVENTS,
        error: null
      };
    }

    return {
      success: true,
      data: response.data,
      error: null
    };
  } catch (error) {
    console.error('Error fetching upcoming events:', error);
    // Return sample data on error
    return {
      success: true,
      data: limit ? SAMPLE_EVENTS.slice(0, limit) : SAMPLE_EVENTS,
      error: 'Using sample data as the events service is temporarily unavailable.'
    };
  }
};

/**
 * Fetch a single event by ID
 * @param {string} id - The ID of the event to fetch
 * @returns {Promise<Object>} Object containing success status, data, and error message if any
 */
export const getEventById = async (id) => {
  try {
    const response = await axios.get(`${API_URL}/events/${id}`, {
      validateStatus: (status) => status >= 200 && status < 300 || status === 404
    });

    // If the endpoint returns 404, try to find in sample data
    if (response.status === 404) {
      console.warn(`Event ${id} not found, checking sample data`);
      const sampleEvent = SAMPLE_EVENTS.find(item => item.id === id);
      if (sampleEvent) {
        return {
          success: true,
          data: sampleEvent,
          error: null
        };
      }
      throw new Error('Event not found');
    }

    return {
      success: true,
      data: response.data,
      error: null
    };
  } catch (error) {
    console.error(`Error fetching event ${id}:`, error);
    // Try to find in sample data as fallback
    const sampleEvent = SAMPLE_EVENTS.find(item => item.id === id);
    if (sampleEvent) {
      return {
        success: true,
        data: sampleEvent,
        error: 'Using sample data as the events service is temporarily unavailable.'
      };
    }
    
    return {
      success: false,
      data: null,
      error: 'Event not found. It may have been removed or is temporarily unavailable.'
    };
  }
};

/**
 * Get all available news categories
 * @returns {Promise<Array>} Array of category names
 */
export const getNewsCategories = async () => {
  try {
    const response = await axios.get(`${API_URL}/news/categories`);
    return {
      success: true,
      data: response.data,
      error: null
    };
  } catch (error) {
    console.error('Error fetching news categories:', error);
    return {
      success: false,
      data: ['Announcements', 'Events', 'Achievements'], // Fallback categories
      error: 'Failed to load categories. Using default categories.'
    };
  }
};
