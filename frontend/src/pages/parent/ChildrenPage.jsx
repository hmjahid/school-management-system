import { useState, useEffect } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { 
  FiUser, 
  FiCalendar, 
  FiBook, 
  FiAward, 
  FiClock,
  FiPlus,
  FiSearch,
  FiFilter
} from 'react-icons/fi';

const ChildrenPage = () => {
  const { user } = useAuth();
  const [children, setChildren] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');

  // Simulated data - replace with actual API call
  useEffect(() => {
    const fetchChildren = async () => {
      try {
        // Simulate API call
        setTimeout(() => {
          setChildren([
            {
              id: 1,
              name: 'Emma Johnson',
              grade: 'Grade 5A',
              attendance: '95%',
              averageGrade: 'A-',
              nextClass: 'Mathematics (10:00 AM)',
              teacher: 'Mr. Smith'
            },
            {
              id: 2,
              name: 'Noah Johnson',
              grade: 'Grade 7B',
              attendance: '92%',
              averageGrade: 'B+',
              nextClass: 'Science (11:30 AM)',
              teacher: 'Ms. Williams'
            }
          ]);
          setLoading(false);
        }, 1000);
      } catch (error) {
        console.error('Error fetching children data:', error);
        setLoading(false);
      }
    };

    fetchChildren();
  }, []);

  const filteredChildren = children.filter(child => 
    child.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    child.grade.toLowerCase().includes(searchTerm.toLowerCase())
  );

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">My Children</h1>
          <p className="mt-1 text-sm text-gray-500">
            View and manage your children's academic progress and activities
          </p>
        </div>
        <div className="mt-4 md:mt-0 flex space-x-3">
          <div className="relative rounded-md shadow-sm">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <FiSearch className="h-4 w-4 text-gray-400" />
            </div>
            <input
              type="text"
              className="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
              placeholder="Search children..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
          <button
            type="button"
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <FiPlus className="-ml-1 mr-2 h-4 w-4" />
            Add Child
          </button>
        </div>
      </div>

      {filteredChildren.length > 0 ? (
        <div className="grid gap-6 md:grid-cols-2">
          {filteredChildren.map((child) => (
            <div key={child.id} className="bg-white overflow-hidden shadow rounded-lg">
              <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
                <div className="flex items-center">
                  <div className="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center">
                    <FiUser className="h-6 w-6 text-indigo-600" />
                  </div>
                  <div className="ml-4">
                    <h3 className="text-lg leading-6 font-medium text-gray-900">{child.name}</h3>
                    <p className="text-sm text-gray-500">{child.grade}</p>
                  </div>
                </div>
              </div>
              <div className="px-4 py-5 sm:p-6">
                <dl className="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                  <div className="sm:col-span-1">
                    <dt className="text-sm font-medium text-gray-500 flex items-center">
                      <FiCalendar className="mr-2 h-4 w-4 text-gray-400" />
                      Next Class
                    </dt>
                    <dd className="mt-1 text-sm text-gray-900">{child.nextClass}</dd>
                  </div>
                  <div className="sm:col-span-1">
                    <dt className="text-sm font-medium text-gray-500 flex items-center">
                      <FiUser className="mr-2 h-4 w-4 text-gray-400" />
                      Teacher
                    </dt>
                    <dd className="mt-1 text-sm text-gray-900">{child.teacher}</dd>
                  </div>
                  <div className="sm:col-span-1">
                    <dt className="text-sm font-medium text-gray-500 flex items-center">
                      <FiBook className="mr-2 h-4 w-4 text-gray-400" />
                      Attendance
                    </dt>
                    <dd className="mt-1 text-sm text-gray-900">{child.attendance}</dd>
                  </div>
                  <div className="sm:col-span-1">
                    <dt className="text-sm font-medium text-gray-500 flex items-center">
                      <FiAward className="mr-2 h-4 w-4 text-gray-400" />
                      Average Grade
                    </dt>
                    <dd className="mt-1 text-sm text-gray-900">{child.averageGrade}</dd>
                  </div>
                </dl>
                <div className="mt-6">
                  <button
                    type="button"
                    className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  >
                    View Full Profile
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="text-center py-12 bg-white shadow rounded-lg">
          <svg
            className="mx-auto h-12 w-12 text-gray-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            aria-hidden="true"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
            />
          </svg>
          <h3 className="mt-2 text-sm font-medium text-gray-900">No children found</h3>
          <p className="mt-1 text-sm text-gray-500">
            {searchTerm 
              ? 'No children match your search criteria.'
              : 'You have not added any children to your account yet.'}
          </p>
          <div className="mt-6">
            <button
              type="button"
              className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <FiPlus className="-ml-1 mr-2 h-4 w-4" />
              Add Child
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default ChildrenPage;
