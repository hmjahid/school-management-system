import React, { createContext, useContext, useState, useEffect } from 'react';
import { useAuth } from './AuthContext';
import teacherService from '../services/teacherService';

const TeacherContext = createContext();

export const TeacherProvider = ({ children }) => {
  const { user } = useAuth();
  const [classes, setClasses] = useState([]);
  const [selectedClass, setSelectedClass] = useState(null);
  const [students, setStudents] = useState([]);
  const [attendance, setAttendance] = useState({});
  const [grades, setGrades] = useState({});
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Load teacher's classes
  const loadTeacherClasses = async () => {
    if (!user?.id) return;
    
    setLoading(true);
    setError(null);
    try {
      const data = await teacherService.getTeacherClasses(user.id);
      setClasses(data);
      if (data.length > 0 && !selectedClass) {
        setSelectedClass(data[0].id);
      }
    } catch (err) {
      setError('Failed to load classes');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  // Load class students
  const loadClassStudents = async (classId) => {
    if (!classId) return;
    
    setLoading(true);
    setError(null);
    try {
      const data = await teacherService.getClassStudents(classId);
      setStudents(data);
    } catch (err) {
      setError('Failed to load students');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  // Load class attendance
  const loadClassAttendance = async (classId, date) => {
    if (!classId || !date) return;
    
    setLoading(true);
    setError(null);
    try {
      const data = await teacherService.getClassAttendance(classId, date);
      setAttendance(prev => ({
        ...prev,
        [classId]: {
          ...prev[classId],
          [date]: data
        }
      }));
    } catch (err) {
      setError('Failed to load attendance');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  // Update attendance
  const updateStudentAttendance = async (classId, studentId, date, status) => {
    try {
      await teacherService.updateAttendance({
        classId,
        studentId,
        date,
        status
      });
      
      // Update local state
      setAttendance(prev => ({
        ...prev,
        [classId]: {
          ...prev[classId],
          [date]: prev[classId]?.[date]?.map(record => 
            record.studentId === studentId 
              ? { ...record, status }
              : record
          ) || []
        }
      }));
      
      return true;
    } catch (err) {
      setError('Failed to update attendance');
      console.error(err);
      return false;
    }
  };

  // Load class grades
  const loadClassGrades = async (classId, examId = null) => {
    if (!classId) return;
    
    setLoading(true);
    setError(null);
    try {
      const data = await teacherService.getClassGrades(classId, examId);
      setGrades(prev => ({
        ...prev,
        [classId]: {
          ...prev[classId],
          [examId || 'default']: data
        }
      }));
    } catch (err) {
      setError('Failed to load grades');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  // Update student grade
  const updateStudentGrade = async (classId, studentId, examId, grade) => {
    try {
      await teacherService.updateGrade({
        classId,
        studentId,
        examId,
        grade
      });
      
      // Update local state
      setGrades(prev => ({
        ...prev,
        [classId]: {
          ...prev[classId],
          [examId]: (prev[classId]?.[examId] || []).map(g => 
            g.studentId === studentId 
              ? { ...g, grade }
              : g
          )
        }
      }));
      
      return true;
    } catch (err) {
      setError('Failed to update grade');
      console.error(err);
      return false;
    }
  };

  // Export grades
  const exportClassGrades = async (classId, examId, format = 'csv') => {
    try {
      const blob = await teacherService.exportGrades(classId, examId, format);
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `grades_${classId}_${examId}.${format}`);
      document.body.appendChild(link);
      link.click();
      link.parentNode.removeChild(link);
      return true;
    } catch (err) {
      setError('Failed to export grades');
      console.error(err);
      return false;
    }
  };

  // Import grades
  const importClassGrades = async (classId, examId, file) => {
    try {
      await teacherService.importGrades(classId, examId, file);
      // Refresh grades after import
      await loadClassGrades(classId, examId);
      return true;
    } catch (err) {
      setError('Failed to import grades');
      console.error(err);
      return false;
    }
  };

  // Effect to load classes when user changes
  useEffect(() => {
    if (user?.id) {
      loadTeacherClasses();
    }
  }, [user?.id]);

  // Effect to load students when selected class changes
  useEffect(() => {
    if (selectedClass) {
      loadClassStudents(selectedClass);
      loadClassGrades(selectedClass);
    }
  }, [selectedClass]);

  return (
    <TeacherContext.Provider
      value={{
        classes,
        selectedClass,
        setSelectedClass,
        students,
        attendance,
        grades,
        loading,
        error,
        loadTeacherClasses,
        loadClassStudents,
        loadClassAttendance,
        updateStudentAttendance,
        loadClassGrades,
        updateStudentGrade,
        exportClassGrades,
        importClassGrades
      }}
    >
      {children}
    </TeacherContext.Provider>
  );
};

export const useTeacher = () => {
  const context = useContext(TeacherContext);
  if (!context) {
    throw new Error('useTeacher must be used within a TeacherProvider');
  }
  return context;
};

export default TeacherContext;
