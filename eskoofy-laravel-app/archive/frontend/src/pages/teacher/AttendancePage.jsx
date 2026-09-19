import React, { useState, useEffect } from 'react';
import { 
  FiCheck, 
  FiX, 
  FiClock, 
  FiCalendar, 
  FiUser, 
  FiSearch, 
  FiLoader, 
  FiAlertCircle,
  FiSave,
  FiRefreshCw
} from 'react-icons/fi';
import { useTeacher } from '../../contexts/TeacherContext';
import { toast } from 'react-hot-toast';

const AttendancePage = () => {
  const { 
    classes, 
    students, 
    attendance, 
    loading, 
    error, 
    selectedClass,
    setSelectedClass,
    loadClassStudents,
    loadClassAttendance,
    updateStudentAttendance,
    loadTeacherClasses
  } = useTeacher();
  
  const [selectedDate, setSelectedDate] = useState(new Date().toISOString().split('T')[0]);
  const [searchTerm, setSearchTerm] = useState('');
  const [isSaving, setIsSaving] = useState(false);
  const [localAttendance, setLocalAttendance] = useState({});

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
    }
  }, [selectedClass, loadClassStudents]);
  
  // Load attendance when class or date changes
  useEffect(() => {
    if (selectedClass && selectedDate) {
      loadClassAttendance(selectedClass, selectedDate);
    }
  }, [selectedClass, selectedDate, loadClassAttendance]);
  
  // Initialize local attendance state when attendance data changes
  useEffect(() => {
    if (selectedClass && selectedDate && attendance[selectedClass]?.[selectedDate]) {
      const attendanceMap = {};
      attendance[selectedClass][selectedDate].forEach(record => {
        attendanceMap[record.studentId] = record.status;
      });
      setLocalAttendance(attendanceMap);
    } else {
      setLocalAttendance({});
    }
  }, [attendance, selectedClass, selectedDate]);
  
  // Filter students based on search term
  const filteredStudents = students.filter(student => 
    student.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    student.rollNumber.toLowerCase().includes(searchTerm.toLowerCase())
  );
  
  // Toggle attendance status for a student
  const toggleAttendance = (studentId, currentStatus) => {
    const newStatus = currentStatus === 'present' ? 'absent' : 'present';
    setLocalAttendance(prev => ({
      ...prev,
      [studentId]: newStatus
    }));
  };
  
  // Save attendance to the server
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!selectedClass || !selectedDate) return;
    
    setIsSaving(true);
    
    try {
      // Update attendance for each student
      const updates = Object.entries(localAttendance).map(([studentId, status]) => 
        updateStudentAttendance(selectedClass, studentId, selectedDate, status)
      );
      
      await Promise.all(updates);
      toast.success('Attendance saved successfully');
    } catch (err) {
      toast.error('Failed to save attendance');
      console.error('Error saving attendance:', err);
    } finally {
      setIsSaving(false);
    }
  };
  
  // Format date for display
  const formatDisplayDate = (dateString) => {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
  };

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold text-gray-800 mb-6">Student Attendance</h1>
      
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
          <form onSubmit={handleSubmit}>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label htmlFor="class" className="block text-sm font-medium text-gray-700 mb-1">
                  Select Class
                </label>
                <select
                  id="class"
                  className="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
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
              
              <div>
                <label htmlFor="date" className="block text-sm font-medium text-gray-700 mb-1">
                  Select Date
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <FiCalendar className="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    type="date"
                    id="date"
                    className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    value={selectedDate}
                    onChange={(e) => setSelectedDate(e.target.value)}
                    disabled={!selectedClass || loading}
                    max={new Date().toISOString().split('T')[0]}
                  />
                </div>
              </div>
              
              <div className="flex items-end space-x-2">
                <button
                  type="button"
                  onClick={() => selectedClass && loadClassAttendance(selectedClass, selectedDate)}
                  disabled={!selectedClass || loading}
                  className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                  <FiRefreshCw className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
                  Refresh
                </button>
                <button
                  type="submit"
                  disabled={!selectedClass || isSaving || loading}
                  className="flex-1 inline-flex justify-center items-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                  {isSaving ? (
                    <>
                      <FiLoader className="animate-spin -ml-1 mr-2 h-4 w-4" />
                      Saving...
                    </>
                  ) : (
                    <>
                      <FiSave className="-ml-1 mr-2 h-4 w-4" />
                      Save Attendance
                    </>
                  )}
                </button>
              </div>
            </div>
          </form>
        </div>
        
        <div className="p-4 border-b border-gray-200 bg-gray-50">
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <FiSearch className="h-5 w-5 text-gray-400" />
            </div>
            <input
              type="text"
              className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="Search students..."
            />
          </div>
        </div>
      </div>
      
      <div className="p-4 border-b border-gray-200 bg-gray-50">
        <div className="relative">
          <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <FiSearch className="h-5 w-5 text-gray-400" />
          </div>
          <input
            type="text"
            className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            placeholder="Search students..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            disabled={!selectedClass || loading}
            onChange={(e) => setSearchTerm(e.target.value)}
            disabled={!selectedClass || loading}
          />
        </div>
      </div>
    
    {!selectedClass ? (
      <div className="text-center py-12">
        <FiUser className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900">No class selected</h3>
        <p className="mt-1 text-sm text-gray-500">Please select a class to view attendance</p>
      </div>
    ) : loading ? (
      <div className="flex flex-col items-center justify-center py-12">
        <FiLoader className="animate-spin h-10 w-10 text-indigo-600 mb-4" />
        <p className="text-gray-600">Loading attendance data...</p>
      </div>
    ) : filteredStudents.length === 0 ? (
      <div className="text-center py-12">
        <FiUser className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900">No students found</h3>
        <p className="mt-1 text-sm text-gray-500">
          {searchTerm 
            ? 'No students match your search. Try a different term.'
            : 'No students are enrolled in this class.'}
        </p>
      </div>
    ) : (
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
                Status
              </th>
              <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Action
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {filteredStudents.map((student) => {
              const status = localAttendance[student.id] || 'absent';
              return (
                <tr key={student.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="flex items-center">
                      <div className="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <FiUser className="h-5 w-5 text-indigo-600" />
                      </div>
                      <div className="ml-4">
                        <div className="text-sm font-medium text-gray-900">{student.name || 'Unknown Student'}</div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {student.rollNumber || 'N/A'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                      status === 'present' 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-red-100 text-red-800'
                    }`}>
                      {status === 'present' ? 'Present' : 'Absent'}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button
                      onClick={() => toggleAttendance(student.id, status)}
                      disabled={isSaving}
                      className={`inline-flex items-center px-3 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white ${
                        status === 'present'
                          ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500'
                          : 'bg-green-600 hover:bg-green-700 focus:ring-green-500'
                      } focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50`}
                    >
                      {status === 'present' ? (
                        <>
                          <FiX className="-ml-1 mr-1 h-4 w-4" />
                          Mark Absent
                        </>
                      ) : (
                        <>
                          <FiCheck className="-ml-1 mr-1 h-4 w-4" />
                          Mark Present
                        </>
                      )}
                    </button>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    )}
    
    {selectedClass && (
    <div className="bg-white rounded-lg shadow overflow-hidden mt-6">
      <div className="p-6">
        <h2 className="text-lg font-medium text-gray-900 mb-4">
          Attendance Summary for {formatDisplayDate(selectedDate)}
        </h2>
        {loading ? (
          <div className="flex justify-center py-4">
            <FiLoader className="animate-spin h-6 w-6 text-indigo-600" />
          </div>
        ) : (
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="bg-green-50 p-4 rounded-lg">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                      <FiCheck className="h-6 w-6 text-green-600" />
                    </div>
                    <div className="ml-4">
                      <p className="text-sm font-medium text-gray-500">Present</p>
                      <p className="text-2xl font-semibold text-gray-900">
                        {Object.values(localAttendance).filter(status => status === 'present').length}
                      </p>
                    </div>
                  </div>
                </div>
                
                <div className="bg-red-50 p-4 rounded-lg">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
                      <FiX className="h-6 w-6 text-red-600" />
                    </div>
                    <div className="ml-4">
                      <p className="text-sm font-medium text-gray-500">Absent</p>
                      <p className="text-2xl font-semibold text-gray-900">
                        {Object.values(localAttendance).filter(status => status === 'absent').length}
                      </p>
                    </div>
                  </div>
                </div>
                
                <div className="bg-blue-50 p-4 rounded-lg">
                  <div className="flex items-center">
                    <div className="flex-shrink-0 h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                      <FiClock className="h-6 w-6 text-blue-600" />
                    </div>
                    <div className="ml-4">
                      <p className="text-sm font-medium text-gray-500">Total Students</p>
                      <p className="text-2xl font-semibold text-gray-900">{students.length}</p>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default AttendancePage;
