import React, { useState, useEffect } from 'react';
import { 
  FiCalendar, 
  FiClock, 
  FiMapPin, 
  FiInfo, 
  FiChevronLeft, 
  FiChevronRight,
  FiLoader,
  FiAlertCircle
} from 'react-icons/fi';
import { format, addDays, subDays, isToday, isSameDay, parseISO } from 'date-fns';

const SchedulePage = () => {
  const [currentDate, setCurrentDate] = useState(new Date());
  const [schedule, setSchedule] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Sample schedule data - replace with API call
  const sampleSchedule = [
    {
      id: 1,
      title: 'Mathematics',
      time: '09:00 - 10:30',
      type: 'Lecture',
      room: 'Room 101',
      instructor: 'Dr. Smith',
      date: format(new Date(), 'yyyy-MM-dd')
    },
    {
      id: 2,
      title: 'Physics',
      time: '11:00 - 12:30',
      type: 'Lab',
      room: 'Lab A',
      instructor: 'Prof. Johnson',
      date: format(new Date(), 'yyyy-MM-dd')
    },
    {
      id: 3,
      title: 'Computer Science',
      time: '14:00 - 15:30',
      type: 'Lecture',
      room: 'Room 205',
      instructor: 'Dr. Williams',
      date: format(addDays(new Date(), 1), 'yyyy-MM-dd')
    }
  ];

  useEffect(() => {
    const fetchSchedule = async () => {
      try {
        setLoading(true);
        // TODO: Replace with actual API call
        // const response = await api.get(`/api/student/schedule?date=${format(currentDate, 'yyyy-MM-dd')}`);
        // setSchedule(response.data);
        
        // For now, use sample data
        setSchedule(sampleSchedule);
        setError(null);
      } catch (err) {
        console.error('Error fetching schedule:', err);
        setError('Failed to load schedule. Please try again later.');
      } finally {
        setLoading(false);
      }
    };

    fetchSchedule();
  }, [currentDate]);

  const handlePrevDay = () => {
    setCurrentDate(subDays(currentDate, 1));
  };

  const handleNextDay = () => {
    setCurrentDate(addDays(currentDate, 1));
  };

  const handleToday = () => {
    setCurrentDate(new Date());
  };

  const filteredSchedule = schedule.filter(item => 
    isSameDay(parseISO(item.date), currentDate)
  );

  return (
    <div className="p-6">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-800">My Schedule</h1>
        <div className="flex items-center space-x-2">
          <button
            onClick={handlePrevDay}
            className="p-2 rounded-full hover:bg-gray-100"
            aria-label="Previous day"
          >
            <FiChevronLeft className="h-5 w-5 text-gray-600" />
          </button>
          <button
            onClick={handleToday}
            className={`px-4 py-2 text-sm font-medium rounded-md ${
              isToday(currentDate)
                ? 'bg-indigo-100 text-indigo-700'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            Today
          </button>
          <button
            onClick={handleNextDay}
            className="p-2 rounded-full hover:bg-gray-100"
            aria-label="Next day"
          >
            <FiChevronRight className="h-5 w-5 text-gray-600" />
          </button>
          <div className="ml-4 text-lg font-medium text-gray-800">
            {format(currentDate, 'EEEE, MMMM d, yyyy')}
          </div>
        </div>
      </div>

      {loading ? (
        <div className="flex flex-col items-center justify-center py-12">
          <FiLoader className="animate-spin h-10 w-10 text-indigo-600 mb-4" />
          <p className="text-gray-600">Loading schedule...</p>
        </div>
      ) : error ? (
        <div className="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
          <div className="flex">
            <div className="flex-shrink-0">
              <FiAlertCircle className="h-5 w-5 text-red-400" />
            </div>
            <div className="ml-3">
              <p className="text-sm text-red-700">{error}</p>
            </div>
          </div>
        </div>
      ) : filteredSchedule.length === 0 ? (
        <div className="text-center py-12 bg-white rounded-lg shadow">
          <FiCalendar className="mx-auto h-12 w-12 text-gray-400" />
          <h3 className="mt-2 text-sm font-medium text-gray-900">No classes scheduled</h3>
          <p className="mt-1 text-sm text-gray-500">You don't have any classes scheduled for this day.</p>
        </div>
      ) : (
        <div className="space-y-4">
          {filteredSchedule.map((item) => (
            <div key={item.id} className="bg-white rounded-lg shadow overflow-hidden">
              <div className="p-4">
                <div className="flex items-start">
                  <div className="flex-shrink-0 bg-indigo-100 p-3 rounded-lg">
                    <FiCalendar className="h-6 w-6 text-indigo-600" />
                  </div>
                  <div className="ml-4 flex-1">
                    <div className="flex items-center justify-between">
                      <h3 className="text-lg font-medium text-gray-900">{item.title}</h3>
                      <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                        {item.type}
                      </span>
                    </div>
                    <div className="mt-1 flex items-center text-sm text-gray-500">
                      <FiClock className="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" />
                      {item.time}
                    </div>
                    <div className="mt-1 flex items-center text-sm text-gray-500">
                      <FiMapPin className="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" />
                      {item.room}
                    </div>
                    <div className="mt-1 flex items-center text-sm text-gray-500">
                      <FiUser className="flex-shrink-0 mr-1.5 h-4 w-4 text-gray-400" />
                      {item.instructor}
                    </div>
                    {item.note && (
                      <div className="mt-2 flex text-sm text-gray-500">
                        <FiInfo className="flex-shrink-0 mr-1.5 h-4 w-4 text-yellow-400 mt-0.5" />
                        <p className="text-yellow-700">{item.note}</p>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default SchedulePage;
