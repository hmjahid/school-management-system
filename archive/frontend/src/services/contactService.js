import axios from 'axios';

// Use the environment variable if it exists, otherwise default to local development server
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8001/api';

/**
 * Send a contact form submission to the backend
 * @param {Object} formData - The contact form data
 * @returns {Promise<Object>} The response from the server
 */
export const submitContactForm = async (formData) => {
  try {
    const response = await axios.post(`${API_URL}/contact`, formData);
    return {
      success: true,
      data: response.data,
      message: 'Your message has been sent successfully! We will get back to you soon.'
    };
  } catch (error) {
    console.error('Error submitting contact form:', error);
    
    // Handle different error scenarios
    let errorMessage = 'An error occurred while sending your message. Please try again later.';
    
    if (error.response) {
      // The request was made and the server responded with a status code
      // that falls out of the range of 2xx
      if (error.response.status === 400) {
        errorMessage = 'Please check your form for errors and try again.';
      } else if (error.response.status === 429) {
        errorMessage = 'Too many requests. Please try again later.';
      } else if (error.response.data?.message) {
        errorMessage = error.response.data.message;
      }
    } else if (error.request) {
      // The request was made but no response was received
      errorMessage = 'Unable to connect to the server. Please check your internet connection.';
    }
    
    return {
      success: false,
      error: errorMessage
    };
  }
};

/**
 * Get the contact page content from the backend
 * @returns {Promise<Object>} The contact page content
 */
export const getContactPageContent = async () => {
  try {
    const response = await axios.get(`${API_URL}/website/contact`);
    return {
      success: true,
      data: response.data
    };
  } catch (error) {
    console.error('Error fetching contact page content:', error);
    return {
      success: false,
      error: 'Failed to load contact page content. Showing default content.'
    };
  }
};
