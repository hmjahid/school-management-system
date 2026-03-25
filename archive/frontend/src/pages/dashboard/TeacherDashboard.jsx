import { useState, useEffect } from 'react';
import { FaChalkboardTeacher, FaUserGraduate, FaClipboardList, FaCalendarAlt, FaChartLine, FaBookOpen, FaRegCommentDots } from 'react-icons/fa';
import { Bar, Line } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';

// Register ChartJS components
ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  Title,
  Tooltip,
  Legend
);

const TeacherDashboard = () => {
  const [teacherData, setTeacherData] = useState({
    name: 'Dr. Sarah Williams',
    subjects: ['Mathematics', 'Physics'],
    classes: ['10-A', '10-B', '11-A'],
    totalStudents: 125,
    upcomingClasses: [],
    recentAssignments: [],
    announcements: [],
  });
  
  const [isLoading, setIsLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('overview');
  const [activeClass, setActiveClass] = useState('10-A');

  // Mock data - replace with actual API calls
  useEffect(() => {
    const fetchTeacherData = async () => {
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        setTeacherData({
          ...teacherData,
          upcomingClasses: [
            { id: 1, subject: 'Mathematics', class: '10-A', startTime: '09:00 AM', endTime: '10:00 AM', type: 'Regular' },
            { id: 2, subject: 'Physics', class: '11-A', startTime: '11:30 AM', endTime: '12:30 PM', type: 'Lab' },
            { id: 3, subject: 'Mathematics', class: '10-B', startTime: '02:00 PM', endTime: '03:00 PM', type: 'Revision' },
          ],
          recentAssignments: [
            { id: 1, title: 'Algebra Homework', class: '10-A', dueDate: '2023-11-15', submissions: 32, totalStudents: 35 },
            { id: 2, title: 'Motion Problems', class: '11-A', dueDate: '2023-11-17', submissions: 28, totalStudents: 30 },
            { id: 3, title: 'Trigonometry Quiz', class: '10-B', dueDate: '2023-11-20', submissions: 15, totalStudents: 30 },
          ],
          announcements: [
            { id: 1, title: 'Staff Meeting', date: '2023-11-15', content: 'Monthly staff meeting in the conference room at 3:00 PM.' },
            { id: 2, title: 'Exam Schedule', date: '2023-11-10', content: 'Midterm exam schedule has been posted. Please review and provide feedback.' },
            { id: 3, title: 'Professional Development', date: '2023-11-05', content: 'Sign up for the upcoming professional development workshop on innovative teaching methods.' },
          ],
        });
        
        setIsLoading(false);
      } catch (error) {
        console.error('Error fetching teacher data:', error);
        setIsLoading(false);
      }
    };
    
    fetchTeacherData();
  }, []);

  // Performance data for the selected class
  const classPerformanceData = {
    labels: ['A+', 'A', 'A-', 'B+', 'B', 'C+', 'C', 'D', 'F'],
    datasets: [
      {
        label: 'Number of Students',
        data: [5, 8, 7, 6, 4, 3, 2, 1, 0],
        backgroundColor: [
          'rgba(16, 185, 129, 0.8)',
          'rgba(34, 197, 94, 0.8)',
          'rgba(132, 204, 22, 0.8)',
          'rgba(250, 204, 21, 0.8)',
          'rgba(249, 115, 22, 0.8)',
          'rgba(245, 158, 11, 0.8)',
          'rgba(239, 68, 68, 0.8)',
          'rgba(220, 38, 38, 0.8)',
          'rgba(185, 28, 28, 0.8)',
        ],
        borderWidth: 0,
      },
    ],
  };

  const attendanceTrend = {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
    datasets: [
      {
        label: 'Attendance %',
        data: [92, 95, 90, 94, 93],
        borderColor: 'rgba(99, 102, 241, 1)',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        borderWidth: 2,
        tension: 0.3,
        fill: true,
      },
    ],
  };

  const chartOptions = {
    responsive: true,
    plugins: {
      legend: {
        position: 'top',
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        max: 100,
        ticks: {
          callback: (value) => `${value}%`,
        },
      },
    },
  };

  const barChartOptions = {
    responsive: true,
    plugins: {
      legend: {
        display: false,
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          stepSize: 1,
        },
      },
    },
  };

  const StatCard = ({ icon, title, value, change, changeType = 'increase' }) => (
    <div className="p-4 bg-white rounded-lg shadow-sm border border-gray-100">
      <div className="flex items-center">
        <div className="p-3 rounded-full bg-indigo-50 text-indigo-600">
          {icon}
        </div>
        <div className="ml-4">
          <p className="text-sm font-medium text-gray-500">{title}</p>
          <p className="text-xl font-semibold text-gray-900">{value}</p>
          {change && (
            <p className={`mt-1 text-xs ${changeType === 'increase' ? 'text-green-600' : 'text-red-600'}`}>
              {changeType === 'increase' ? '↑' : '↓'} {change}% from last month
            </p>
          )}
        </div>
      </div>
    </div>
  );

  const renderOverviewTab = () => (
    <div className="space-y-6">
      {/* Stats Grid */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          icon={<FaChalkboardTeacher className="w-5 h-5" />}
          title="Total Classes"
          value={teacherData.classes.length}
        />
        <StatCard
          icon={<FaUserGraduate className="w-5 h-5" />}
          title="Total Students"
          value={teacherData.totalStudents}
        />
        <StatCard
          icon={<FaClipboardList className="w-5 h-5" />}
          title="Pending Grading"
          value={teacherData.recentAssignments.reduce((sum, a) => sum + (a.totalStudents - a.submissions), 0)}
        />
        <StatCard
          icon={<FaChartLine className="w-5 h-5" />}
          title="Avg. Performance"
          value="85%"
          change="2.5"
        />
      </div>

      {/* Main Content */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        {/* Upcoming Classes */}
        <div className="lg:col-span-2">
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-medium text-gray-900">Today's Schedule</h3>
              <button className="text-sm text-indigo-600 hover:text-indigo-500">View All</button>
            </div>
            <div className="mt-4 space-y-4">
              {teacherData.upcomingClasses.map((classItem) => (
                <div key={classItem.id} className="flex items-center p-4 border border-gray-100 rounded-lg hover:bg-gray-50">
                  <div className="flex items-center justify-center w-12 h-12 rounded-full bg-indigo-100 text-indigo-600 flex-shrink-0">
                    <FaBookOpen className="w-5 h-5" />
                  </div>
                  <div className="ml-4 flex-1">
                    <div className="flex items-center justify-between">
                      <h4 className="text-sm font-medium text-gray-900">{classItem.subject} - {classItem.class}</h4>
                      <span className="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                        {classItem.type}
                      </span>
                    </div>
                    <p className="mt-1 text-sm text-gray-500">
                      {classItem.startTime} - {classItem.endTime}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Recent Assignments */}
          <div className="p-5 mt-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-medium text-gray-900">Recent Assignments</h3>
              <button className="text-sm text-indigo-600 hover:text-indigo-500">View All</button>
            </div>
            <div className="mt-4 overflow-hidden border border-gray-100 rounded-lg">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submissions</th>
                    <th className="relative px-6 py-3">
                      <span className="sr-only">Actions</span>
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {teacherData.recentAssignments.map((assignment) => (
                    <tr key={assignment.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {assignment.title}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {assignment.class}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {assignment.dueDate}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <div className="w-20 bg-gray-200 rounded-full h-2.5 mr-2">
                            <div 
                              className="bg-indigo-600 h-2.5 rounded-full" 
                              style={{ width: `${(assignment.submissions / assignment.totalStudents) * 100}%` }}
                            ></div>
                          </div>
                          <span className="text-sm text-gray-500">
                            {assignment.submissions}/{assignment.totalStudents}
                          </span>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button className="text-indigo-600 hover:text-indigo-900">Grade</button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>

        {/* Right Sidebar */}
        <div className="space-y-5">
          {/* Class Performance */}
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-medium text-gray-900">Class Performance</h3>
              <select 
                value={activeClass}
                onChange={(e) => setActiveClass(e.target.value)}
                className="text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
                {teacherData.classes.map((cls) => (
                  <option key={cls} value={cls}>{cls}</option>
                ))}
              </select>
            </div>
            <div className="h-64 mt-4">
              <Bar data={classPerformanceData} options={barChartOptions} />
            </div>
          </div>

          {/* Attendance Trend */}
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">Attendance Trend - {activeClass}</h3>
            <p className="text-sm text-gray-500">This week's attendance percentage</p>
            <div className="h-64 mt-4">
              <Line data={attendanceTrend} options={chartOptions} />
            </div>
          </div>

          {/* Announcements */}
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-medium text-gray-900">Announcements</h3>
              <button className="text-sm text-indigo-600 hover:text-indigo-500">View All</button>
            </div>
            <div className="mt-4 space-y-4">
              {teacherData.announcements.map((announcement) => (
                <div key={announcement.id} className="p-3 border border-gray-100 rounded-lg hover:bg-gray-50">
                  <div className="flex items-start">
                    <div className="flex-shrink-0 mt-1">
                      <FaRegCommentDots className="w-5 h-5 text-gray-400" />
                    </div>
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-900">{announcement.title}</p>
                      <p className="mt-1 text-sm text-gray-600">{announcement.content}</p>
                      <p className="mt-1 text-xs text-gray-400">{announcement.date}</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Quick Actions */}
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">Quick Actions</h3>
            <div className="grid grid-cols-2 gap-3 mt-4">
              <button className="flex flex-col items-center justify-center p-4 text-center text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100">
                <FaClipboardList className="w-5 h-5 text-indigo-500 mb-2" />
                Create Assignment
              </button>
              <button className="flex flex-col items-center justify-center p-4 text-center text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100">
                <FaCalendarAlt className="w-5 h-5 text-green-500 mb-2" />
                Mark Attendance
              </button>
              <button className="flex flex-col items-center justify-center p-4 text-center text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100">
                <FaChartLine className="w-5 h-5 text-yellow-500 mb-2" />
                Enter Grades
              </button>
              <button className="flex flex-col items-center justify-center p-4 text-center text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100">
                <FaRegCommentDots className="w-5 h-5 text-purple-500 mb-2" />
                Send Message
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  const renderClassesTab = () => (
    <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
      <h3 className="text-lg font-medium text-gray-900">My Classes</h3>
      <p className="mt-1 text-sm text-gray-500">Manage your class sections and students.</p>
      {/* Class management would go here */}
    </div>
  );

  const renderGradesTab = () => (
    <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
      <h3 className="text-lg font-medium text-gray-900">Gradebook</h3>
      <p className="mt-1 text-sm text-gray-500">View and manage student grades.</p>
      {/* Gradebook would go here */}
    </div>
  );

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-900">Welcome back, {teacherData.name}!</h2>
        <p className="mt-1 text-sm text-gray-500">Here's what's happening in your classes today.</p>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="flex -mb-px space-x-8">
          <button
            onClick={() => setActiveTab('overview')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'overview'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Overview
          </button>
          <button
            onClick={() => setActiveTab('classes')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'classes'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            My Classes
          </button>
          <button
            onClick={() => setActiveTab('grades')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'grades'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Gradebook
          </button>
          <button
            onClick={() => setActiveTab('reports')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'reports'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Reports
          </button>
        </nav>
      </div>

      {/* Tab Content */}
      <div className="py-4">
        {activeTab === 'overview' && renderOverviewTab()}
        {activeTab === 'classes' && renderClassesTab()}
        {activeTab === 'grades' && renderGradesTab()}
        {activeTab === 'reports' && (
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">Reports & Analytics</h3>
            <p className="mt-1 text-sm text-gray-500">Generate and view class reports and analytics.</p>
            {/* Reports would go here */}
          </div>
        )}
      </div>
    </div>
  );
};

export default TeacherDashboard;
