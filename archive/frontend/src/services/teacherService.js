import api from './api';

const teacherService = {
  // Get all classes for the authenticated teacher
  async getTeacherClasses() {
    try {
      // The backend will use the authenticated user's token to identify the teacher
      const response = await api.get('/teacher/classes');
      console.log('Teacher classes response:', response);
      return response.data;
    } catch (error) {
      console.error('Error fetching teacher classes:', error);
      // Return empty array instead of throwing to prevent UI errors
      return [];
    }
  },

  // Get class details
  async getClassDetails(classId) {
    try {
      const response = await api.get(`/api/classes/${classId}`);
      return response.data;
    } catch (error) {
      console.error('Error fetching class details:', error);
      throw error;
    }
  },

  // Get class students
  async getClassStudents(classId) {
    try {
      const response = await api.get(`/api/teacher/classes/${classId}/students`);
      return response.data;
    } catch (error) {
      console.error('Error fetching class students:', error);
      // Return empty array instead of throwing to prevent UI errors
      return [];
    }
  },

  // Get attendance for a class on a specific date
  async getClassAttendance(classId, date) {
    try {
      const response = await api.get(`/api/classes/${classId}/attendance`, {
        params: { date }
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching attendance:', error);
      throw error;
    }
  },

  // Update attendance
  async updateAttendance(attendanceData) {
    try {
      const response = await api.post('/api/attendance', attendanceData);
      return response.data;
    } catch (error) {
      console.error('Error updating attendance:', error);
      throw error;
    }
  },

  // Get student grades for a class
  async getClassGrades(classId, examId = null) {
    try {
      const params = examId ? { exam_id: examId } : {};
      const response = await api.get(`/api/teacher/classes/${classId}/grades`, { params });
      return response.data;
    } catch (error) {
      console.error('Error fetching grades:', error);
      // Return empty array instead of throwing to prevent UI errors
      return [];
    }
  },

  // Update student grade
  async updateGrade(gradeData) {
    try {
      const response = await api.post('/api/grades', gradeData);
      return response.data;
    } catch (error) {
      console.error('Error updating grade:', error);
      throw error;
    }
  },

  // Export grades
  async exportGrades(classId, examId, format = 'csv') {
    try {
      const response = await api.get(`/api/classes/${classId}/export-grades`, {
        params: { exam_id: examId, format },
        responseType: 'blob'
      });
      return response.data;
    } catch (error) {
      console.error('Error exporting grades:', error);
      throw error;
    }
  },

  // Import grades
  async importGrades(classId, examId, file) {
    try {
      const formData = new FormData();
      formData.append('file', file);
      formData.append('exam_id', examId);
      
      const response = await api.post(
        `/api/classes/${classId}/import-grades`,
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        }
      );
      return response.data;
    } catch (error) {
      console.error('Error importing grades:', error);
      throw error;
    }
  },

  // Get upcoming classes
  async getUpcomingClasses(teacherId, days = 7) {
    try {
      const response = await api.get(`/api/teachers/${teacherId}/upcoming-classes`, {
        params: { days }
      });
      return response.data;
    } catch (error) {
      console.error('Error fetching upcoming classes:', error);
      throw error;
    }
  },

  // Get class statistics
  async getClassStatistics(classId) {
    try {
      const response = await api.get(`/api/classes/${classId}/statistics`);
      return response.data;
    } catch (error) {
      console.error('Error fetching class statistics:', error);
      throw error;
    }
  }
};

export default teacherService;
