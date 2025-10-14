import { useState, useEffect } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { 
  FiCalendar, 
  FiCheckCircle, 
  FiXCircle, 
  FiClock,
  FiFilter,
  FiDownload,
  FiChevronDown,
  FiChevronUp
} from 'react-icons/fi';

const ParentAttendancePage = () => {
  const { user } = useAuth();
  const [attendance, setAttendance] = useState([]);
  const [selectedChild, setSelectedChild] = useState(null);
  const [showFilters, setShowFilters] = useState(false);
  const [filters, setFilters] = useState({
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
    status: 'all'
  });
  const [loading, setLoading] = useState(true);

  // Simulated data - replace with actual API call
  useEffect(() => {
    const fetchAttendance = async () => {
      try {
        // Simulate API call
        setTimeout(() => {
          setAttendance([
            {
              id: 1,
              date: '2023-06-15',
              status: 'present',
              checkIn: '08:15 AM',
              checkOut: '03:30 PM',
              childName: 'Emma Johnson',
              className: 'Grade 5A',
              teacher: 'Mr. Smith'
            },
            {
              id: 2,
              date: '2023-06-14',
              status: 'absent',
              checkIn: '--',
              checkOut: '--',
              childName: 'Emma Johnson',
              className: 'Grade 5A',
              teacher: 'Mr. Smith',
              reason: 'Sick leave'
            },
            {
              id: 3,
              date: '2023-06-13',
              status: 'present',
              checkIn: '08:20 AM',
              checkOut: '03:25 PM',
              childName: 'Emma Johnson',
              className: 'Grade 5A',
              teacher: 'Mr. Smith'
            },
            {
              id: 4,
              date: '2023-06-12',
              status: 'late',
              checkIn: '09:15 AM',
              checkOut: '03:30 PM',
              childName: 'Emma Johnson',
              className: 'Grade 5A',
              teacher: 'Mr. Smith',
              lateMinutes: 45
            },
            {
              id: 5,
              date: '2023-06-09',
              status: 'present',
              checkIn: '08:10 AM',
              checkOut: '03:35 PM',
              childName: 'Emma Johnson',
              className: 'Grade 5A',
              teacher: 'Mr. Smith'
            }
          ]);
          setSelectedChild('Emma Johnson');
          setLoading(false);
        }, 1000);
      } catch (error) {
        console.error('Error fetching attendance data:', error);
        setLoading(false);
      }
    };

    fetchAttendance();
  }, [filters]);

  const getStatusBadge = (status) => {
    switch (status) {
      case 'present':
        return (
          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
            <FiCheckCircle className="mr-1 h-3 w-3" /> Present
          </span>
        );
      case 'absent':
        return (
          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
            <FiXCircle className="mr-1 h-3 w-3" /> Absent
          </span>
        );
      case 'late':
        return (
          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
            <FiClock className="mr-1 h-3 w-3" /> Late
          </span>
        );
      default:
        return null;
    }
  };

  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];

  const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Attendance</h1>
          <p className="mt-1 text-sm text-gray-500">
            Track your child's attendance and punctuality
          </p>
        </div>
        <div className="mt-4 md:mt-0">
          <button
            type="button"
            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <FiDownload className="-ml-1 mr-2 h-4 w-4 text-gray-500" />
            Export
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white shadow rounded-lg overflow-hidden">
        <button
          type="button"
          className="w-full px-4 py-3 flex items-center justify-between text-sm font-medium text-left text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none"
          onClick={() => setShowFilters(!showFilters)}
        >
          <span>Filters</span>
          {showFilters ? (
            <FiChevronUp className="h-5 w-5 text-gray-500" />
          ) : (
            <FiChevronDown className="h-5 w-5 text-gray-500" />
          )}
        </button>
        
        {showFilters && (
          <div className="px-4 py-4 border-t border-gray-200">
            <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
              <div className="sm:col-span-2">
                <label htmlFor="month" className="block text-sm font-medium text-gray-700">
                  Month
                </label>
                <select
                  id="month"
                  name="month"
                  value={filters.month}
                  onChange={handleFilterChange}
                  className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                >
                  {months.map((month, index) => (
                    <option key={index} value={index + 1}>
                      {month}
                    </option>
                  ))}
                </select>
              </div>

              <div className="sm:col-span-2">
                <label htmlFor="year" className="block text-sm font-medium text-gray-700">
                  Year
                </label>
                <select
                  id="year"
                  name="year"
                  value={filters.year}
                  onChange={handleFilterChange}
                  className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                >
                  {years.map((year) => (
                    <option key={year} value={year}>
                      {year}
                    </option>
                  ))}
                </select>
              </div>

              <div className="sm:col-span-2">
                <label htmlFor="status" className="block text-sm font-medium text-gray-700">
                  Status
                </label>
                <select
                  id="status"
                  name="status"
                  value={filters.status}
                  onChange={handleFilterChange}
                  className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                >
                  <option value="all">All Status</option>
                  <option value="present">Present</option>
                  <option value="absent">Absent</option>
                  <option value="late">Late</option>
                </select>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Attendance Summary */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:px-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900">
            {selectedChild || 'Select a child'}
          </h3>
          <p className="mt-1 max-w-2xl text-sm text-gray-500">
            Attendance details for {months[filters.month - 1]} {filters.year}
          </p>
        </div>
        <div className="border-t border-gray-200 px-4 py-5 sm:p-0">
          <dl className="sm:divide-y sm:divide-gray-200">
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">Total School Days</dt>
              <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">22</dd>
            </div>
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">Present</dt>
              <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">20 days (90.9%)</dd>
            </div>
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">Absent</dt>
              <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">2 days (9.1%)</dd>
            </div>
            <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">Late Arrivals</dt>
              <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">3 times</dd>
            </div>
          </dl>
        </div>
      </div>

      {/* Attendance Table */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:px-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900">
            Daily Attendance
          </h3>
        </div>
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Date
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Check In
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Check Out
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Teacher
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {loading ? (
                <tr>
                  <td colSpan="5" className="px-6 py-4 text-center text-sm text-gray-500">
                    Loading attendance data...
                  </td>
                </tr>
              ) : attendance.length > 0 ? (
                attendance.map((record) => (
                  <tr key={record.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {new Date(record.date).toLocaleDateString('en-US', {
                        weekday: 'short',
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                      })}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {getStatusBadge(record.status)}
                      {record.lateMinutes && (
                        <span className="ml-2 text-xs text-gray-500">
                          ({record.lateMinutes} min late)
                        </span>
                      )}
                      {record.reason && (
                        <p className="mt-1 text-xs text-gray-500">Reason: {record.reason}</p>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {record.checkIn}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {record.checkOut}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {record.teacher}
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="5" className="px-6 py-4 text-center text-sm text-gray-500">
                    No attendance records found for the selected filters.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default ParentAttendancePage;
