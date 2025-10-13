import { useState, useEffect } from 'react';
import { FaUserGraduate, FaChalkboardTeacher, FaBell, FaFileAlt, FaMoneyBillWave, FaCalendarAlt } from 'react-icons/fa';
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

const ParentDashboard = () => {
  const [activeChild, setActiveChild] = useState(0);
  const [children, setChildren] = useState([
    {
      id: 1,
      name: 'Alex Johnson',
      class: '10-A',
      rollNumber: '2023-010',
      attendance: 92.5,
      overallGrade: 'A-',
      nextExam: { subject: 'Mathematics', date: '2023-11-15', type: 'Midterm' },
      pendingAssignments: 2,
    },
    {
      id: 2,
      name: 'Sarah Johnson',
      class: '8-B',
      rollNumber: '2023-125',
      attendance: 88.2,
      overallGrade: 'B+',
      nextExam: { subject: 'Science', date: '2023-11-16', type: 'Quiz' },
      pendingAssignments: 3,
    },
  ]);

  const [recentActivities, setRecentActivities] = useState([
    { 
      id: 1, 
      childName: 'Alex Johnson',
      type: 'grade', 
      title: 'Math Assignment Graded', 
      description: 'Scored 18/20 on Algebra Homework',
      date: '2023-11-10',
      time: '2:30 PM'
    },
    { 
      id: 2, 
      childName: 'Sarah Johnson',
      type: 'attendance', 
      title: 'Absence Noted', 
      description: 'Marked absent on November 9, 2023',
      date: '2023-11-09',
      time: '9:15 AM'
    },
    { 
      id: 3, 
      childName: 'Alex Johnson',
      type: 'fee', 
      title: 'Fee Payment Received', 
      description: 'Monthly tuition fee of $200 received',
      date: '2023-11-05',
      time: '11:45 AM'
    },
  ]);

  const [upcomingEvents, setUpcomingEvents] = useState([
    { id: 1, title: 'Parent-Teacher Meeting', date: '2023-11-20', time: '2:00 PM - 4:00 PM', type: 'meeting' },
    { id: 2, title: 'Science Fair', date: '2023-11-25', time: '9:00 AM - 12:00 PM', type: 'event' },
    { id: 3, title: 'Midterm Exams Begin', date: '2023-12-01', time: 'All Day', type: 'exam' },
  ]);

  const [isLoading, setIsLoading] = useState(false);
  const [activeTab, setActiveTab] = useState('overview');

  // Performance chart data
  const performanceData = {
    labels: ['Math', 'Physics', 'Chemistry', 'English', 'History'],
    datasets: [
      {
        label: 'Your Child',
        data: [92, 88, 85, 90, 82],
        backgroundColor: 'rgba(99, 102, 241, 0.8)',
      },
      {
        label: 'Class Average',
        data: [82, 84, 81, 85, 78],
        backgroundColor: 'rgba(156, 163, 175, 0.8)',
      },
    ],
  };

  const attendanceTrend = {
    labels: ['Aug', 'Sep', 'Oct', 'Nov'],
    datasets: [
      {
        label: 'Attendance %',
        data: [90, 92, 91, 92.5],
        borderColor: 'rgba(16, 185, 129, 1)',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
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
        min: 0,
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

  const ActivityItem = ({ activity }) => {
    const getIcon = () => {
      switch (activity.type) {
        case 'grade':
          return <FaFileAlt className="w-5 h-5 text-green-500" />;
        case 'attendance':
          return <FaCalendarAlt className="w-5 h-5 text-yellow-500" />;
        case 'fee':
          return <FaMoneyBillWave className="w-5 h-5 text-blue-500" />;
        default:
          return <FaBell className="w-5 h-5 text-gray-500" />;
      }
    };

    return (
      <div className="flex items-start py-3 border-b border-gray-100 last:border-0">
        <div className="flex-shrink-0 mt-1">
          {getIcon()}
        </div>
        <div className="ml-3">
          <div className="flex items-center">
            <p className="text-sm font-medium text-gray-900">{activity.title}</p>
            <span className="ml-2 px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">
              {activity.childName}
            </span>
          </div>
          <p className="text-sm text-gray-600">{activity.description}</p>
          <p className="mt-1 text-xs text-gray-400">
            {activity.date} • {activity.time}
          </p>
        </div>
      </div>
    );
  };

  const renderOverviewTab = () => (
    <div className="space-y-6">
      {/* Child Selector */}
      <div className="flex space-x-3 overflow-x-auto pb-2">
        {children.map((child, index) => (
          <button
            key={child.id}
            onClick={() => setActiveChild(index)}
            className={`px-4 py-2 rounded-lg whitespace-nowrap ${
              activeChild === index
                ? 'bg-indigo-600 text-white'
                : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
            }`}
          >
            {child.name}
          </button>
        ))}
      </div>

      {/* Child Summary */}
      <div className="bg-white p-5 rounded-lg shadow-sm border border-gray-100">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between">
          <div>
            <h3 className="text-lg font-medium text-gray-900">{children[activeChild].name}</h3>
            <p className="text-sm text-gray-500">
              Class {children[activeChild].class} • Roll No: {children[activeChild].rollNumber}
            </p>
          </div>
          <div className="mt-3 md:mt-0">
            <button className="px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-md hover:bg-indigo-100">
              View Full Profile
            </button>
          </div>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-1 gap-5 mt-6 sm:grid-cols-2 lg:grid-cols-4">
          <StatCard
            icon={<FaUserGraduate className="w-5 h-5" />}
            title="Overall Grade"
            value={children[activeChild].overallGrade}
          />
          <StatCard
            icon={<FaCalendarAlt className="w-5 h-5" />}
            title="Attendance"
            value={`${children[activeChild].attendance}%`}
            change="2.5"
          />
          <StatCard
            icon={<FaFileAlt className="w-5 h-5" />}
            title="Pending Assignments"
            value={children[activeChild].pendingAssignments}
          />
          <StatCard
            icon={<FaChalkboardTeacher className="w-5 h-5" />}
            title="Next Exam"
            value={children[activeChild].nextExam.subject}
          />
        </div>
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-lg font-medium text-gray-900">Subject-wise Performance</h3>
          <p className="text-sm text-gray-500">Comparison with class average</p>
          <div className="h-64 mt-4">
            <Bar data={performanceData} options={barChartOptions} />
          </div>
        </div>
        <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-lg font-medium text-gray-900">Attendance Trend</h3>
          <p className="text-sm text-gray-500">Last 4 months</p>
          <div className="h-64 mt-4">
            <Line data={attendanceTrend} options={chartOptions} />
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        {/* Recent Activities */}
        <div>
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-medium text-gray-900">Recent Activities</h3>
              <button className="text-sm text-indigo-600 hover:text-indigo-500">View All</button>
            </div>
            <div className="mt-4 space-y-3">
              {recentActivities.map((activity) => (
                <ActivityItem key={activity.id} activity={activity} />
              ))}
            </div>
          </div>
        </div>

        {/* Upcoming Events */}
        <div>
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-medium text-gray-900">Upcoming Events</h3>
              <button className="text-sm text-indigo-600 hover:text-indigo-500">View Calendar</button>
            </div>
            <div className="mt-4 space-y-4">
              {upcomingEvents.map((event) => (
                <div key={event.id} className="flex items-start p-3 border border-gray-100 rounded-lg hover:bg-gray-50">
                  <div className="flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex-shrink-0">
                    <FaCalendarAlt className="w-5 h-5" />
                  </div>
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-900">{event.title}</p>
                    <p className="text-sm text-gray-500">{event.date} • {event.time}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Quick Actions */}
          <div className="p-5 mt-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">Quick Actions</h3>
            <div className="grid grid-cols-2 gap-3 mt-4">
              <button className="flex flex-col items-center justify-center p-4 text-center text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100">
                <FaMoneyBillWave className="w-6 h-6 text-green-500 mb-2" />
                Pay Fees
              </button>
              <button className="flex flex-col items-center justify-center p-4 text-center text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100">
                <FaFileAlt className="w-6 h-6 text-blue-500 mb-2" />
                Request Report Card
              </button>
              <button className="flex flex-col items-center justify-center p-4 text-center text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100">
                <FaChalkboardTeacher className="w-6 h-6 text-purple-500 mb-2" />
                Contact Teacher
              </button>
              <button className="flex flex-col items-center justify-center p-4 text-center text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100">
                <FaCalendarAlt className="w-6 h-6 text-yellow-500 mb-2" />
                Schedule Meeting
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  const renderAttendanceTab = () => (
    <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
      <h3 className="text-lg font-medium text-gray-900">Attendance Records</h3>
      <p className="mt-1 text-sm text-gray-500">View and track your child's attendance.</p>
      {/* Attendance records would go here */}
    </div>
  );

  const renderPerformanceTab = () => (
    <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
      <h3 className="text-lg font-medium text-gray-900">Academic Performance</h3>
      <p className="mt-1 text-sm text-gray-500">Detailed view of your child's performance.</p>
      {/* Performance details would go here */}
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
        <h2 className="text-2xl font-bold text-gray-900">Parent Dashboard</h2>
        <p className="mt-1 text-sm text-gray-500">Welcome back! Here's what's happening with your children.</p>
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
            onClick={() => setActiveTab('attendance')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'attendance'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Attendance
          </button>
          <button
            onClick={() => setActiveTab('performance')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'performance'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Performance
          </button>
          <button
            onClick={() => setActiveTab('fees')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'fees'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Fees & Payments
          </button>
        </nav>
      </div>

      {/* Tab Content */}
      <div className="py-4">
        {activeTab === 'overview' && renderOverviewTab()}
        {activeTab === 'attendance' && renderAttendanceTab()}
        {activeTab === 'performance' && renderPerformanceTab()}
        {activeTab === 'fees' && (
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">Fees & Payments</h3>
            <p className="mt-1 text-sm text-gray-500">View and manage fee payments for your children.</p>
            {/* Fee and payment details would go here */}
          </div>
        )}
      </div>
    </div>
  );
};

export default ParentDashboard;
