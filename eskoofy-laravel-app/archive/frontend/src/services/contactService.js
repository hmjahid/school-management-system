import api from './api';

export const submitContactForm = async (formData) => {
  try {
    const response = await api.post('/v1/contact', formData);
    return {
      success: true,
      data: response.data,
      message: 'Your message has been sent successfully! We will get back to you soon.'
    };
  } catch (error) {
    console.error('Error submitting contact form:', error);

    let errorMessage = 'An error occurred while sending your message. Please try again later.';

    if (error.response) {
      if (error.response.status === 400) {
        errorMessage = 'Please check your form for errors and try again.';
      } else if (error.response.status === 429) {
        errorMessage = 'Too many requests. Please try again later.';
      } else if (error.response.data?.message) {
        errorMessage = error.response.data.message;
      }
    } else if (error.request) {
      errorMessage = 'Unable to connect to the server. Please check your internet connection.';
    }

    return {
      success: false,
      error: errorMessage
    };
  }
};

export const getContactPageContent = async () => {
  try {
    const response = await api.get('/v1/website/contact');
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
