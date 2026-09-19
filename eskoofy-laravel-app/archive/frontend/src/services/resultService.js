import api from './api';

const getFilters = async () => {
  try {
    const response = await api.get('/v1/academics/results/filters');
    if (response.status === 200) {
      return { success: true, data: response.data };
    }
    return { success: false, error: 'Unexpected response' };
  } catch (error) {
    return { success: false, error: error.response?.data?.message || error.message };
  }
};

const lookupResults = async ({ class_id, academic_session_id, roll }) => {
  try {
    const response = await api.get('/v1/academics/results/lookup', {
      params: { class_id, academic_session_id, roll },
    });
    if (response.status === 200) {
      return { success: true, data: response.data };
    }
    return { success: false, error: 'Unexpected response' };
  } catch (error) {
    return { success: false, error: error.response?.data?.message || error.message };
  }
};

export default {
  getFilters,
  lookupResults,
};
