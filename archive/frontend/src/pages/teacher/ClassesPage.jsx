import React, { useState, useEffect } from 'react';
import { FiUsers, FiCalendar, FiClock, FiBookOpen, FiSearch, FiAlertCircle, FiLoader } from 'react-icons/fi';
import { useTeacher } from '../../contexts/TeacherContext';
import { toast } from 'react-hot-toast';

const ClassesPage = () => {
  const { 
    classes, 
    loading, 
    error, 
    loadTeacherClasses 
  } = useTeacher();
  
  const [searchTerm, setSearchTerm] = useState('');
  
  // Load classes on component mount
  useEffect(() => {
    const fetchClasses = async () => {
      try {
        await loadTeacherClasses();
      } catch (err) {
        toast.error('Failed to load classes');
        console.error('Error loading classes:', err);
      }
    };
    
    fetchClasses();
  }, [loadTeacherClasses]);
  
  // Filter classes based on search term
  const filteredClasses = classes.filter(cls => 
    cls.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    cls.subject.toLowerCase().includes(searchTerm.toLowerCase()) ||
    cls.grade.toLowerCase().includes(searchTerm.toLowerCase())
  );
  
  // Format schedule days
  const formatSchedule = (schedule) => {
    if (!schedule || !Array.isArray(schedule) || schedule.length === 0) {
      return 'Schedule not available';
    }
    return schedule.map(day => day.substring(0, 3)).join(', ');
  };
  
  // Format time range
  const formatTimeRange = (startTime, endTime) => {
    if (!startTime || !endTime) return 'Time not set';
    const formatTime = (timeStr) => {
      const [hours, minutes] = timeStr.split(':');
      const hour = parseInt(hours, 10);
      const period = hour >= 12 ? 'PM' : 'AM';
      const displayHour = hour % 12 || 12;
      return `${displayHour}:${minutes} ${period}`;
    };
    return `${formatTime(startTime)} - ${formatTime(endTime)}`;
  };

  return (
    <div className="p-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-800">My Classes</h1>
        <div className="mt-4 md:mt-0">
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <FiSearch className="h-5 w-5 text-gray-400" />
            </div>
            <input
              type="text"
              className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="Search classes..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
        </div>
      </div>

      {/* Loading State */}
      {loading && !classes.length && (
        <div className="flex flex-col items-center justify-center py-12">
          <FiLoader className="animate-spin h-10 w-10 text-indigo-600 mb-4" />
          <p className="text-gray-600">Loading your classes...</p>
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
      
      {/* Empty State */}
      {!loading && !error && filteredClasses.length === 0 && (
        <div className="text-center py-12">
          <FiBookOpen className="mx-auto h-12 w-12 text-gray-400" />
          <h3 className="mt-2 text-sm font-medium text-gray-900">No classes found</h3>
          <p className="mt-1 text-sm text-gray-500">
            {searchTerm 
              ? 'No classes match your search. Try a different term.'
              : 'You are not assigned to any classes yet.'}
          </p>
        </div>
      )}
      
      {/* Classes Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {filteredClasses.map((classItem) => (
          <div key={classItem.id} className="bg-white rounded-lg shadow overflow-hidden border border-gray-200 hover:shadow-md transition-shadow duration-200">
            <div className="p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-indigo-100 p-3 rounded-lg">
                  <FiBookOpen className="h-6 w-6 text-indigo-600" />
                </div>
                <div className="ml-4">
                  <h3 className="text-lg font-medium text-gray-900">{classItem.name || 'Unnamed Class'}</h3>
                  <p className="text-sm text-gray-500">
                    {classItem.subject || 'No subject'} • {classItem.grade || 'No grade'}
                  </p>
                </div>
              </div>
              
              <div className="mt-4 space-y-2">
                <div className="flex items-center text-sm text-gray-500">
                  <FiCalendar className="flex-shrink-0 mr-2 h-4 w-4 text-gray-400" />
                  <span>{formatSchedule(classItem.schedule_days)}</span>
                </div>
                <div className="flex items-center text-sm text-gray-500">
                  <FiClock className="flex-shrink-0 mr-2 h-4 w-4 text-gray-400" />
                  <span>{formatTimeRange(classItem.start_time, classItem.end_time)}</span>
                </div>
                <div className="flex items-center text-sm text-gray-500">
                  <FiUsers className="flex-shrink-0 mr-2 h-4 w-4 text-gray-400" />
                  <span>{classItem.students_count || 0} student{classItem.students_count !== 1 ? 's' : ''}</span>
                </div>
              </div>

              <div className="mt-6">
                <button
                  type="button"
                  className="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  View Class
                </button>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default ClassesPage;
