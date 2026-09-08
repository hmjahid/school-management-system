import React, { useState, useEffect } from 'react';
import { 
  FiSearch, 
  FiFilter, 
  FiDownload, 
  FiUpload, 
  FiEdit2, 
  FiSave, 
  FiX, 
  FiUser, 
  FiLoader, 
  FiAlertCircle,
  FiRefreshCw
} from 'react-icons/fi';
import { useTeacher } from '../../contexts/TeacherContext';
import { toast } from 'react-hot-toast';

const GradesPage = () => {
  const { 
    classes, 
    selectedClass, 
    setSelectedClass, 
    students, 
    grades, 
    loading, 
    error, 
    loadTeacherClasses, 
    loadClassStudents, 
    loadClassGrades, 
    updateStudentGrade,
    exportGrades
  } = useTeacher();

  const [selectedExam, setSelectedExam] = useState(null);
  const [editingGrade, setEditingGrade] = useState(null);
  const [gradeValue, setGradeValue] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  const [isExporting, setIsExporting] = useState(false);

  // Load classes on component mount
  useEffect(() => {
    const fetchData = async () => {
      try {
        await loadTeacherClasses();
      } catch (err) {
        toast.error('Failed to load classes');
        console.error('Error loading classes:', err);
      }
    };
    
    fetchData();
  }, [loadTeacherClasses]);
  
  // Load students when selected class changes
  useEffect(() => {
    if (selectedClass) {
      loadClassStudents(selectedClass);
      // Reset selected exam when class changes
      setSelectedExam(null);
    }
  }, [selectedClass, loadClassStudents]);
  
  // Load grades when class or exam changes
  useEffect(() => {
    if (selectedClass && selectedExam) {
      loadClassGrades(selectedClass, selectedExam);
    }
  }, [selectedClass, selectedExam, loadClassGrades]);

  // Get current exam grades
  const currentGrades = selectedClass && selectedExam 
    ? grades[selectedClass]?.[selectedExam] || {}
    : {};

  // Filter students based on search term
  const filteredStudents = students.filter(student => 
    student.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    student.rollNumber?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const handleEditGrade = (studentId, currentGrade) => {
    setEditingGrade(studentId);
    setGradeValue(currentGrade || '');
  };

  const handleSaveGrade = async (studentId) => {
    if (!selectedClass || !selectedExam || !gradeValue) return;
    
    try {
      await updateStudentGrade({
        classId: selectedClass,
        examId: selectedExam,
        studentId,
        grade: gradeValue,
        // Include marks if available in your data model
        // marks: parseFloat(gradeValue)
      });
      
      toast.success('Grade updated successfully');
      setEditingGrade(null);
    } catch (err) {
      toast.error('Failed to update grade');
      console.error('Error updating grade:', err);
    }
  };

  const handleCancelEdit = () => {
    setEditingGrade(null);
    setGradeValue('');
  };

  const handleExportGrades = async () => {
    if (!selectedClass || !selectedExam) {
      toast.error('Please select a class and exam first');
      return;
    }
    
    setIsExporting(true);
    try {
      await exportGrades(selectedClass, selectedExam, 'csv');
      toast.success('Grades exported successfully');
    } catch (err) {
      toast.error('Failed to export grades');
      console.error('Error exporting grades:', err);
    } finally {
      setIsExporting(false);
    }
  };
  
  // Get available exams for the selected class
  const availableExams = selectedClass 
    ? classes.find(c => c.id === selectedClass)?.exams || []
    : [];

  const handleImportGrades = () => {
    // Implement import logic here
    console.log('Importing grades...');
  };

  const getGradeColor = (grade) => {
    switch (grade) {
      case 'A':
      case 'A+':
        return 'bg-green-100 text-green-800';
      case 'A-':
      case 'B+':
        return 'bg-blue-100 text-blue-800';
      case 'B':
      case 'B-':
        return 'bg-yellow-100 text-yellow-800';
      case 'C+':
      case 'C':
        return 'bg-orange-100 text-orange-800';
      case 'C-':
      case 'D':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div className="p-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Student Grades</h1>
        <div className="mt-4 md:mt-0 flex space-x-3">
          <button
            onClick={handleExportGrades}
            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <FiDownload className="-ml-1 mr-2 h-5 w-5 text-gray-500" />
            Export
          </button>
          <button
            onClick={handleImportGrades}
            className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <FiUpload className="-ml-1 mr-2 h-5 w-5" />
            Import
          </button>
        </div>
      </div>

      {/* Loading State */}
      {loading && !classes.length && (
        <div className="flex flex-col items-center justify-center py-12">
          <FiLoader className="animate-spin h-10 w-10 text-indigo-600 mb-4" />
          <p className="text-gray-600">Loading classes...</p>
        </div>
      )}
      
      {/* Error State */}
      {error && !loading && (
        <div className="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
          <div className="flex">
            <div className="flex-shrink-0">
              <FiAlertCircle className="h-5 w-5 text-red-400" aria-hidden="true" />
            </div>
            <div className="ml-3">
              <p className="text-sm text-red-700">{error}</p>
            </div>
          </div>
        </div>
      )}
      
      <div className="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div className="p-6 border-b border-gray-200">
          <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div className="w-full md:w-1/3">
              <label htmlFor="class" className="block text-sm font-medium text-gray-700 mb-1">
                Select Class
              </label>
              <select
                id="class"
                className="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:opacity-50"
                value={selectedClass || ''}
                onChange={(e) => setSelectedClass(e.target.value)}
                disabled={loading}
              >
                <option value="" disabled>Select a class</option>
                {classes.map((classItem) => (
                  <option key={classItem.id} value={classItem.id}>
                    {classItem.name}
                  </option>
                ))}
              </select>
            </div>
            
            <div className="w-full md:w-1/3">
              <label htmlFor="exam" className="block text-sm font-medium text-gray-700 mb-1">
                Select Exam
              </label>
              <div className="flex">
                <select
                  id="exam"
                  className="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-r-0 border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm disabled:opacity-50"
                  value={selectedExam || ''}
                  onChange={(e) => setSelectedExam(e.target.value)}
                  disabled={!selectedClass || loading}
                >
                  <option value="" disabled>Select an exam</option>
                  {availableExams.map((exam) => (
                    <option key={exam.id} value={exam.id}>
                      {exam.name} ({new Date(exam.date).toLocaleDateString()})
                    </option>
                  ))}
                </select>
                <button
                  type="button"
                  className="mt-1 inline-flex items-center px-3 py-2 border border-l-0 border-gray-300 bg-gray-50 text-gray-700 text-sm rounded-r-md hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-50"
                  disabled={!selectedClass || loading}
                  onClick={() => selectedClass && loadClassGrades(selectedClass, selectedExam)}
                >
                  <FiRefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
                </button>
              </div>
            </div>
            
            <div className="flex items-end space-x-2">
              <button
                type="button"
                onClick={handleExportGrades}
                disabled={!selectedClass || !selectedExam || loading || isExporting}
                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
              >
                {isExporting ? (
                  <>
                    <FiLoader className="animate-spin -ml-1 mr-2 h-4 w-4" />
                    Exporting...
                  </>
                ) : (
                  <>
                    <FiDownload className="-ml-1 mr-2 h-4 w-4" />
                    Export
                  </>
                )}
              </button>
              <button
                type="button"
                disabled={!selectedClass || !selectedExam || loading}
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
              >
                <FiUpload className="-ml-1 mr-2 h-4 w-4" />
                Import
              </button>
            </div>
          </div>
        </div>
        
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Student
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Roll Number
                </th>
                <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Marks
                </th>
                <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Grade
                </th>
                <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {students.map((student) => (
                <tr key={student.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="flex items-center">
                      <div className="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <FiUser className="h-5 w-5 text-indigo-600" />
                      </div>
                      <div className="ml-4">
                        <div className="text-sm font-medium text-gray-900">{student.name}</div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {student.rollNumber}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                    {student.marks}/100
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    {editingGrade === student.id ? (
                      <select
                        className="mt-1 block w-full pl-3 pr-10 py-1 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                        value={gradeValue}
                        onChange={(e) => setGradeValue(e.target.value)}
                      >
                        <option value="A+">A+</option>
                        <option value="A">A</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B">B</option>
                        <option value="B-">B-</option>
                        <option value="C+">C+</option>
                        <option value="C">C</option>
                        <option value="C-">C-</option>
                        <option value="D">D</option>
                        <option value="F">F</option>
                      </select>
                    ) : (
                      <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getGradeColor(student.grade)}`}>
                        {student.grade}
                      </span>
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    {editingGrade === student.id ? (
                      <div className="flex justify-end space-x-2">
                        <button
                          onClick={() => handleSaveGrade(student.id)}
                          className="text-green-600 hover:text-green-900"
                        >
                          <FiSave className="h-5 w-5" />
                        </button>
                        <button
                          onClick={handleCancelEdit}
                          className="text-red-600 hover:text-red-900"
                        >
                          <FiX className="h-5 w-5" />
                        </button>
                      </div>
                    ) : (
                      <button
                        onClick={() => handleEditGrade(student.id, student.grade)}
                        className="text-indigo-600 hover:text-indigo-900"
                      >
                        <FiEdit2 className="h-5 w-5" />
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
      
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="p-6">
          <h2 className="text-lg font-medium text-gray-900 mb-4">Grading Scale</h2>
          <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
            {[
              { grade: 'A+', range: '97-100%', color: 'bg-green-100 text-green-800' },
              { grade: 'A', range: '93-96%', color: 'bg-green-100 text-green-800' },
              { grade: 'A-', range: '90-92%', color: 'bg-blue-100 text-blue-800' },
              { grade: 'B+', range: '87-89%', color: 'bg-blue-100 text-blue-800' },
              { grade: 'B', range: '83-86%', color: 'bg-yellow-100 text-yellow-800' },
              { grade: 'B-', range: '80-82%', color: 'bg-yellow-100 text-yellow-800' },
              { grade: 'C+', range: '77-79%', color: 'bg-orange-100 text-orange-800' },
              { grade: 'C', range: '73-76%', color: 'bg-orange-100 text-orange-800' },
              { grade: 'C-', range: '70-72%', color: 'bg-red-100 text-red-800' },
              { grade: 'D', range: '60-69%', color: 'bg-red-100 text-red-800' },
              { grade: 'F', range: 'Below 60%', color: 'bg-gray-100 text-gray-800' },
            ].map((item, index) => (
              <div key={index} className="p-3 rounded-lg text-center">
                <span className={`inline-block px-3 py-1 rounded-full text-sm font-semibold ${item.color}`}>
                  {item.grade}: {item.range}
                </span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
};

export default GradesPage;
