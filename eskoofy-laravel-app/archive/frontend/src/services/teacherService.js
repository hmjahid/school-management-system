import api from './api';

const teacherService = {
  async getTeacherClasses() {
    try {
      const response = await api.get('/v1/teacher/classes');
      return response.data;
    } catch (error) {
      console.error('Error fetching teacher classes:', error);
      return [];
    }
  },

  async getClassDetails(classId) {
    try {
      const response = await api.get(`/v1/classes/${classId}`);
      return response.data;
    } catch (error) {
      console.error('Error fetching class details:', error);
      throw error;
    }
  },

  async getClassStudents(classId) {
    try {
      const response = await api.get(`/v1/teacher/classes/${classId}/students`);
      return response.data;
    } catch (error) {
      console.error('Error fetching class students:', error);
      return [];
    }
  },

  async getClassAttendance(classId, date) {
    try {
      const response = await api.get(`/v1/classes/${classId}/attendance`, {
        params: { date }
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching attendance:', error);
      throw error;
    }
  },

  async updateAttendance(attendanceData) {
    try {
      const response = await api.post('/v1/attendance', attendanceData);
      return response.data;
    } catch (error) {
      console.error('Error updating attendance:', error);
      throw error;
    }
  },

  async getClassGrades(classId, examId = null) {
    try {
      const params = examId ? { exam_id: examId } : {};
      const response = await api.get(`/v1/teacher/classes/${classId}/grades`, { params });
      return response.data;
    } catch (error) {
      console.error('Error fetching grades:', error);
      return [];
    }
  },

  async updateGrade(gradeData) {
    try {
      const response = await api.post('/v1/grades', gradeData);
      return response.data;
    } catch (error) {
      console.error('Error updating grade:', error);
      throw error;
    }
  },

  async exportGrades(classId, examId, format = 'csv') {
    try {
      const response = await api.get(`/v1/classes/${classId}/export-grades`, {
        params: { exam_id: examId, format },
        responseType: 'blob'
      });
      return response.data;
    } catch (error) {
      console.error('Error exporting grades:', error);
      throw error;
    }
  },

  async importGrades(classId, examId, file) {
    try {
      const formData = new FormData();
      formData.append('file', file);
      formData.append('exam_id', examId);

      const response = await api.post(
        `/v1/classes/${classId}/import-grades`,
        formData,
        {
          headers: { 'Content-Type': 'multipart/form-data' }
        }
      );
      return response.data;
    } catch (error) {
      console.error('Error importing grades:', error);
      throw error;
    }
  },

  async getUpcomingClasses(teacherId, days = 7) {
    try {
      const response = await api.get(`/v1/teachers/${teacherId}/upcoming-classes`, {
        params: { days }
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching upcoming classes:', error);
      throw error;
    }
  },

  async getClassStatistics(classId) {
    try {
      const response = await api.get(`/v1/classes/${classId}/statistics`);
      return response.data;
    } catch (error) {
      console.error('Error fetching class statistics:', error);
      throw error;
    }
  }
};

export default teacherService;
