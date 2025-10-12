import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:5000/api';

// Sample data to use when the backend is not available
const SAMPLE_ACADEMIC_CONTENT = {
  'academics/curriculum': {
    pageTitle: 'Our Curriculum',
    heroTitle: 'Comprehensive Learning Experience',
    heroSubtitle: 'A well-rounded curriculum designed to foster academic excellence and personal growth',
    overview: 'Our curriculum is carefully designed to provide students with a balanced education that combines academic rigor with practical skills and character development.',
    programs: [
      {
        id: 1,
        title: 'Core Subjects',
        description: 'Strong foundation in Mathematics, Science, Languages, and Social Studies',
        icon: 'FaBookOpen',
        features: [
          'Mathematics & Statistics',
          'Sciences (Physics, Chemistry, Biology)',
          'Languages & Literature',
          'Social Studies & Humanities'
        ]
      },
      // ... other program items
    ]
  },
  'academics/programs': {
    pageTitle: 'Academic Programs',
    heroTitle: 'Diverse Learning Opportunities',
    heroSubtitle: 'Explore our range of academic programs designed to meet every student\'s needs',
    programs: [
      {
        id: 1,
        name: 'Elementary School',
        description: 'Foundational learning for young minds (Grades 1-5)',
        features: [
          'Literacy & Numeracy Focus',
          'Exploratory Learning',
          'Social-Emotional Development',
          'Creative Expression'
        ]
      },
      // ... other program items
    ]
  },
  'academics/faculty': {
    pageTitle: 'Our Faculty',
    heroTitle: 'Dedicated Educators',
    heroSubtitle: 'Meet our team of experienced and passionate educators',
    faculty: [
      {
        id: 1,
        name: 'Dr. Sarah Johnson',
        position: 'Head of Science Department',
        bio: 'PhD in Physics with 15 years of teaching experience',
        image: '/images/faculty/sarah-johnson.jpg'
      },
      // ... other faculty members
    ]
  }
};

/**
 * Fetches academic content from the backend or returns sample data if the endpoint is not available
 * @param {string} endpoint - The API endpoint to fetch content from (e.g., 'academics/curriculum')
 * @returns {Promise<Object>} The academic content
 */
const getAcademicContent = async (endpoint) => {
  try {
    const response = await axios.get(`${API_URL}/${endpoint}`, {
      validateStatus: (status) => status === 200 || status === 404
    });

    if (response.status === 200) {
      return { success: true, data: response.data };
    }

    // If 404, return sample data
    if (SAMPLE_ACADEMIC_CONTENT[endpoint]) {
      console.warn(`[academicService] Using sample data for ${endpoint}`);
      return { success: true, data: SAMPLE_ACADEMIC_CONTENT[endpoint] };
    }

    throw new Error('Content not found and no sample data available');
  } catch (error) {
    console.error(`[academicService] Error fetching ${endpoint}:`, error);
    
    // Fallback to sample data if available
    if (SAMPLE_ACADEMIC_CONTENT[endpoint]) {
      console.warn(`[academicService] Using sample data due to error for ${endpoint}`);
      return { success: true, data: SAMPLE_ACADEMIC_CONTENT[endpoint] };
    }
    
    return { success: false, error: error.message };
  }
};

export default {
  getAcademicContent
};
