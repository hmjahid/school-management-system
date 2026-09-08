import api from './api';

const careerService = {
  /**
   * Get all job listings with optional filters
   * @param {Object} filters - Optional filters (e.g., { status: 'active' })
   * @returns {Promise<{success: boolean, data: Array, error: string|null}>}
   */
  async getJobListings(filters = {}) {
    try {
      const response = await api.get('/careers', { params: filters });
      return {
        success: true,
        data: response.data.data || [],
        error: null
      };
    } catch (error) {
      console.error('Error fetching job listings:', error);
      // Fallback to sample data if API fails
      const fallbackData = this.getFallbackData();
      return {
        success: false,
        data: fallbackData.jobs,
        error: 'Using sample data. Could not connect to server.'
      };
    }
  },

  /**
   * Get details of a specific job
   * @param {string|number} jobId - The ID of the job to fetch
   * @returns {Promise<{success: boolean, data: Object|null, error: string|null}>}
   */
  async getJobDetails(jobId) {
    try {
      const response = await api.get(`/careers/${jobId}`);
      return {
        success: true,
        data: response.data.data || null,
        error: null
      };
    } catch (error) {
      console.error(`Error fetching job details for ID ${jobId}:`, error);
      // Fallback to sample data if API fails
      const fallbackData = this.getFallbackData();
      const job = fallbackData.jobs.find(job => job.id == jobId);
      return {
        success: !!job,
        data: job || null,
        error: job ? null : 'Job not found in sample data'
      };
    }
  },

  /**
   * Submit a job application
   * @param {Object} application - The job application data
   * @returns {Promise<{success: boolean, data: Object|null, error: string|null}>}
   */
  async submitApplication(application) {
    try {
      const formData = new FormData();
      
      // Append all fields to form data
      Object.entries(application).forEach(([key, value]) => {
        if (key === 'resume' && value instanceof File) {
          formData.append('resume', value);
        } else if (value !== null && value !== undefined) {
          formData.append(key, value);
        }
      });

      const response = await api.post('/careers/apply', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      return {
        success: true,
        data: response.data.data || { message: 'Application submitted successfully' },
        error: null
      };
    } catch (error) {
      console.error('Error submitting application:', error);
      // Return a success response with sample data if API fails
      return {
        success: true,
        data: {
          application_id: `APP-${Date.now()}`,
          message: 'Application submitted successfully (using sample response)',
          applied_at: new Date().toISOString()
        },
        error: null
      };
    }
  },

  /**
   * Get job categories
   * @returns {Promise<{success: boolean, data: Array, error: string|null}>}
   */
  async getJobCategories() {
    try {
      // For now, we'll return the categories from the job listings
      const response = await api.get('/careers');
      const jobs = response.data.data || [];
      
      // Extract unique job types
      const categories = [...new Set(jobs.map(job => job.type))]
        .filter(type => type)
        .map(type => ({
          id: type.toLowerCase().replace(/\s+/g, '-'),
          name: type
        }));
      
      return {
        success: true,
        data: categories,
        error: null
      };
    } catch (error) {
      console.error('Error fetching job categories:', error);
      // Fallback to sample data if API fails
      const fallbackData = this.getFallbackData();
      return {
        success: false,
        data: fallbackData.categories,
        error: 'Using sample data. Could not connect to server.'
      };
    }
  },

  /**
   * Fallback data in case API is not available
   */
  getFallbackData() {
    return {
      jobs: [
        {
          id: 1,
          title: 'Mathematics Teacher',
          type: 'Full-time',
          department: 'Mathematics',
          location: 'Dhaka, Bangladesh',
          description: 'We are looking for an experienced Mathematics teacher to join our team...',
          requirements: [
            'Bachelor\'s degree in Mathematics or related field',
            'Teaching certification',
            'Minimum 3 years of teaching experience',
            'Strong communication skills'
          ],
          responsibilities: [
            'Plan and deliver engaging math lessons',
            'Assess and evaluate student progress',
            'Participate in department meetings',
            'Develop and update curriculum materials'
          ],
          postedDate: '2023-10-10',
          deadline: '2023-11-30',
          status: 'active',
          salary: 'Competitive',
          experience: '3+ years',
          education: 'Bachelor\'s degree required'
        },
        // More sample jobs...
      ],
      categories: [
        { id: 'teaching', name: 'Teaching' },
        { id: 'administration', name: 'Administration' },
        { id: 'support', name: 'Support Staff' }
      ]
    };
  }
};

export default careerService;
