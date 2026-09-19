import api from './api';

const SAMPLE_NEWS = [
  {
    id: '1',
    title: 'Welcome to Our New Website',
    excerpt: 'We are excited to launch our new school website with improved features and better user experience.',
    content: 'Our new website offers a modern design, easy navigation, and up-to-date information about our school.',
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
    content: 'The annual science fair was a great success with over 50 student projects on display.',
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
    content: 'We are proud to announce the opening of our new sports complex.',
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
    description: 'Annual parent-teacher conference to discuss student progress.',
    date: new Date(Date.now() + 5 * 24 * 60 * 60 * 1000).toISOString(),
    endDate: new Date(Date.now() + 5 * 24 * 60 * 60 * 1000 + 2 * 60 * 60 * 1000).toISOString(),
    location: 'School Auditorium',
    category: 'Academic',
    registrationRequired: true,
    registrationDeadline: new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString()
  },
  {
    id: 'e2',
    title: 'School Talent Show',
    description: 'An evening of performances showcasing our students\' diverse talents.',
    date: new Date(Date.now() + 10 * 24 * 60 * 60 * 1000).toISOString(),
    endDate: new Date(Date.now() + 10 * 24 * 60 * 60 * 1000 + 3 * 60 * 60 * 1000).toISOString(),
    location: 'School Auditorium',
    category: 'Performing Arts',
    registrationRequired: false
  },
  {
    id: 'e3',
    title: 'College Fair',
    description: 'Meet representatives from top universities.',
    date: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString(),
    endDate: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000 + 5 * 60 * 60 * 1000).toISOString(),
    location: 'School Gymnasium',
    category: 'Academic',
    registrationRequired: true,
    registrationDeadline: new Date(Date.now() + 12 * 24 * 60 * 60 * 1000).toISOString()
  },
  {
    id: 'e4',
    title: 'Sports Day',
    description: 'Annual inter-house sports competition.',
    date: new Date(Date.now() + 21 * 24 * 60 * 60 * 1000).toISOString(),
    endDate: new Date(Date.now() + 21 * 24 * 60 * 60 * 1000 + 8 * 60 * 60 * 1000).toISOString(),
    location: 'School Sports Field',
    category: 'Sports',
    registrationRequired: true,
    registrationDeadline: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString()
  }
];

export const getNews = async ({ limit, category } = {}) => {
  try {
    const params = {};
    if (limit) params.limit = limit;
    if (category) params.category = category;

    const response = await api.get('/v1/news', { params });

    return {
      success: true,
      data: response.data.data || response.data,
      error: null
    };
  } catch (error) {
    console.error('Error fetching news:', error);
    return {
      success: true,
      data: limit ? SAMPLE_NEWS.slice(0, limit) : SAMPLE_NEWS,
      error: 'Using sample data as the news service is temporarily unavailable.'
    };
  }
};

export const getNewsById = async (id) => {
  try {
    const response = await api.get(`/v1/news/${id}`);
    return {
      success: true,
      data: response.data.data || response.data,
      error: null
    };
  } catch (error) {
    console.error(`Error fetching news item ${id}:`, error);
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
      error: 'News article not found.'
    };
  }
};

export const getUpcomingEvents = async (limit = 3) => {
  try {
    const response = await api.get('/v1/news/upcoming-events', {
      params: { limit }
    });

    return {
      success: true,
      data: response.data.data || response.data,
      error: null
    };
  } catch (error) {
    console.error('Error fetching upcoming events:', error);
    return {
      success: true,
      data: limit ? SAMPLE_EVENTS.slice(0, limit) : SAMPLE_EVENTS,
      error: 'Using sample data as the events service is temporarily unavailable.'
    };
  }
};

export const getEventById = async (id) => {
  try {
    const response = await api.get(`/v1/events/${id}`);
    return {
      success: true,
      data: response.data,
      error: null
    };
  } catch (error) {
    console.error(`Error fetching event ${id}:`, error);
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
      error: 'Event not found.'
    };
  }
};

export const getNewsCategories = async () => {
  try {
    const response = await api.get('/v1/news/categories');
    return {
      success: true,
      data: response.data.data || response.data,
      error: null
    };
  } catch (error) {
    console.error('Error fetching news categories:', error);
    return {
      success: false,
      data: ['Announcements', 'Events', 'Achievements'],
      error: 'Failed to load categories. Using default categories.'
    };
  }
};
