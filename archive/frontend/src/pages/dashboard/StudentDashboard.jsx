import { useState, useEffect } from 'react';
import { FaBook, FaCalendarAlt, FaClipboardList, FaBell, FaChartLine, FaFileAlt } from 'react-icons/fa';
import { Line, Doughnut } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  LineElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js';

// Register ChartJS components
ChartJS.register(
  CategoryScale,
  LinearScale,
  LineElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
);

const StudentDashboard = () => {
  const [studentData, setStudentData] = useState({
    name: 'Alex Johnson',
    class: '10-A',
    rollNumber: '2023-010',
    attendance: 92.5,
    overallGrade: 'A',
    upcomingExams: [],
    recentAssignments: [],
    schedule: [],
  });
  
  const [isLoading, setIsLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('overview');

  // Mock data - replace with actual API calls
  useEffect(() => {
    const fetchStudentData = async () => {
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        setStudentData({
          ...studentData,
          upcomingExams: [
            { id: 1, subject: 'Mathematics', date: '2023-11-15', time: '10:00 AM', type: 'Midterm' },
            { id: 2, subject: 'Physics', date: '2023-11-18', time: '11:30 AM', type: 'Quiz' },
            { id: 3, subject: 'Chemistry', date: '2023-11-22', time: '09:00 AM', type: 'Chapter Test' },
          ],
          recentAssignments: [
            { id: 1, subject: 'Mathematics', title: 'Algebra Homework', dueDate: '2023-11-12', status: 'Submitted', score: '18/20' },
            { id: 2, subject: 'Physics', title: 'Motion Problems', dueDate: '2023-11-10', status: 'Graded', score: '17/20' },
            { id: 3, subject: 'English', title: 'Book Report', dueDate: '2023-11-20', status: 'Pending', score: '-' },
          ],
          schedule: [
            { day: 'Monday', periods: ['Math', 'Physics', 'Chemistry', 'Break', 'English', 'History'] },
            { day: 'Tuesday', periods: ['Physics', 'Math', 'Biology', 'Break', 'Geography', 'Computer'] },
            { day: 'Wednesday', periods: ['Chemistry', 'English', 'Math', 'Break', 'Physics Lab', 'Physics Lab'] },
            { day: 'Thursday', periods: ['English', 'Chemistry', 'Math', 'Break', 'PT', 'Library'] },
            { day: 'Friday', periods: ['Math', 'Physics', 'Chemistry', 'Break', 'English', 'Art'] },
          ],
        });
        
        setIsLoading(false);
      } catch (error) {
        console.error('Error fetching student data:', error);
        setIsLoading(false);
      }
    };
    
    fetchStudentData();
  }, []);

  // Performance chart data
  const performanceData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [
      {
        label: 'Your Performance',
        data: [78, 82, 85, 88, 90, 92],
        borderColor: 'rgba(99, 102, 241, 1)',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        borderWidth: 2,
        tension: 0.3,
        fill: true,
      },
      {
        label: 'Class Average',
        data: [72, 75, 78, 80, 82, 84],
        borderColor: 'rgba(156, 163, 175, 1)',
        borderWidth: 2,
        borderDash: [5, 5],
        tension: 0.3,
      },
    ],
  };

  const subjectWisePerformance = {
    labels: ['Math', 'Physics', 'Chemistry', 'English', 'History'],
    datasets: [
      {
        data: [92, 88, 85, 90, 82],
        backgroundColor: [
          'rgba(99, 102, 241, 0.8)',
          'rgba(16, 185, 129, 0.8)',
          'rgba(245, 158, 11, 0.8)',
          'rgba(239, 68, 68, 0.8)',
          'rgba(139, 92, 246, 0.8)',
        ],
        borderWidth: 0,
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
        min: 0,
        max: 100,
        ticks: {
          callback: (value) => `${value}%`,
        },
      },
    },
  };

  const StatCard = ({ icon, title, value, description }) => (
    <div className="p-4 bg-white rounded-lg shadow-sm border border-gray-100">
      <div className="flex items-center">
        <div className="p-3 rounded-full bg-indigo-50 text-indigo-600">
          {icon}
        </div>
        <div className="ml-4">
          <p className="text-sm font-medium text-gray-500">{title}</p>
          <p className="text-xl font-semibold text-gray-900">{value}</p>
          {description && <p className="text-xs text-gray-500">{description}</p>}
        </div>
      </div>
    </div>
  );

  const renderOverviewTab = () => (
    <div className="space-y-6">
      {/* Stats Grid */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          icon={<FaBook className="w-5 h-5" />}
          title="Current Class"
          value={studentData.class}
          description="Section A"
        />
        <StatCard
          icon={<FaChartLine className="w-5 h-5" />}
          title="Overall Grade"
          value={studentData.overallGrade}
          description="Top 10% of class"
        />
        <StatCard
          icon={<FaCalendarAlt className="w-5 h-5" />}
          title="Attendance"
          value={`${studentData.attendance}%`}
          description="This semester"
        />
        <StatCard
          icon={<FaClipboardList className="w-5 h-5" />}
          title="Assignments Due"
          value={studentData.recentAssignments.filter(a => a.status === 'Pending').length}
          description="This week"
        />
      </div>

      {/* Performance Charts */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-lg font-medium text-gray-900">Performance Trend</h3>
          <div className="h-64 mt-4">
            <Line data={performanceData} options={chartOptions} />
          </div>
        </div>
        <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-lg font-medium text-gray-900">Subject-wise Performance</h3>
          <div className="h-64 mt-4">
            <Doughnut data={subjectWisePerformance} options={chartOptions} />
          </div>
        </div>
      </div>

      {/* Upcoming Exams and Assignments */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-medium text-gray-900">Upcoming Exams</h3>
            <button className="text-sm text-indigo-600 hover:text-indigo-500">View All</button>
          </div>
          <div className="mt-4 space-y-4">
            {studentData.upcomingExams.map((exam) => (
              <div key={exam.id} className="flex items-start p-3 border border-gray-100 rounded-lg hover:bg-gray-50">
                <div className="flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 text-indigo-600">
                  <FaClipboardList className="w-5 h-5" />
                </div>
                <div className="ml-3">
                  <p className="text-sm font-medium text-gray-900">{exam.subject} - {exam.type}</p>
                  <p className="text-sm text-gray-500">{exam.date} • {exam.time}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-medium text-gray-900">Recent Assignments</h3>
            <button className="text-sm text-indigo-600 hover:text-indigo-500">View All</button>
          </div>
          <div className="mt-4 space-y-4">
            {studentData.recentAssignments.map((assignment) => (
              <div key={assignment.id} className="flex items-start justify-between p-3 border border-gray-100 rounded-lg hover:bg-gray-50">
                <div className="flex">
                  <div className="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-600">
                    <FaFileAlt className="w-5 h-5" />
                  </div>
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-900">{assignment.subject}</p>
                    <p className="text-sm text-gray-500">{assignment.title}</p>
                  </div>
                </div>
                <div className="text-right">
                  <span className={`text-xs px-2 py-1 rounded-full ${assignment.status === 'Submitted' ? 'bg-green-100 text-green-800' : assignment.status === 'Graded' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'}`}>
                    {assignment.status}
                  </span>
                  <p className="text-sm font-medium text-gray-900 mt-1">{assignment.score}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );

  const renderScheduleTab = () => (
    <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
      <h3 className="text-lg font-medium text-gray-900">Weekly Class Schedule</h3>
      <div className="mt-4 overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
              {[1, 2, 3, 4, 5, 6].map((period) => (
                <th key={period} className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Period {period}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {studentData.schedule.map((daySchedule, index) => (
              <tr key={index} className={index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{daySchedule.day}</td>
                {daySchedule.periods.map((subject, idx) => (
                  <td key={idx} className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                    {subject}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="mt-4 text-sm text-gray-500">
        <p>Note: Break is scheduled after Period 3 every day.</p>
      </div>
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
        <h2 className="text-2xl font-bold text-gray-900">Welcome back, {studentData.name}!</h2>
        <p className="mt-1 text-sm text-gray-500">Here's what's happening with your studies today.</p>
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
            onClick={() => setActiveTab('schedule')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'schedule'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            My Schedule
          </button>
          <button
            onClick={() => setActiveTab('assignments')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'assignments'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Assignments
          </button>
          <button
            onClick={() => setActiveTab('exams')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'exams'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Exams
          </button>
        </nav>
      </div>

      {/* Tab Content */}
      <div className="py-4">
        {activeTab === 'overview' && renderOverviewTab()}
        {activeTab === 'schedule' && renderScheduleTab()}
        {activeTab === 'assignments' && (
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">My Assignments</h3>
            <p className="mt-1 text-sm text-gray-500">View and submit your assignments here.</p>
            {/* Assignment list would go here */}
          </div>
        )}
        {activeTab === 'exams' && (
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">Exam Schedule & Results</h3>
            <p className="mt-1 text-sm text-gray-500">View your upcoming exams and past results.</p>
            {/* Exam schedule and results would go here */}
          </div>
        )}
      </div>
    </div>
  );
};

export default StudentDashboard;
