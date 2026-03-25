import { useState, useEffect } from 'react';
import { FaUsers, FaUserGraduate, FaChalkboardTeacher, FaFileInvoiceDollar, FaCalendarAlt, FaClipboardCheck, FaBell, FaRegCalendarAlt } from 'react-icons/fa';
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

const StaffDashboard = () => {
  const [stats, setStats] = useState({
    totalStudents: 0,
    totalTeachers: 0,
    totalParents: 0,
    pendingApprovals: 0,
    feeCollection: 0,
    attendanceRate: 0,
  });
  
  const [recentActivities, setRecentActivities] = useState([]);
  const [upcomingEvents, setUpcomingEvents] = useState([]);
  const [pendingTasks, setPendingTasks] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('overview');

  // Mock data - replace with actual API calls
  useEffect(() => {
    const fetchStaffData = async () => {
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        setStats({
          totalStudents: 1245,
          totalTeachers: 68,
          totalParents: 980,
          pendingApprovals: 12,
          feeCollection: 125400,
          attendanceRate: 92.5,
        });
        
        setRecentActivities([
          { 
            id: 1, 
            type: 'admission', 
            title: 'New admission application', 
            description: 'John Doe applied for Grade 10',
            time: '10 minutes ago',
            status: 'pending'
          },
          { 
            id: 2, 
            type: 'fee', 
            title: 'Fee payment received', 
            description: 'Alex Johnson paid $200',
            time: '1 hour ago',
            status: 'completed'
          },
          { 
            id: 3, 
            type: 'leave', 
            title: 'Leave application', 
            description: 'Sarah Williams applied for 2 days leave',
            time: '3 hours ago',
            status: 'pending'
          },
        ]);
        
        setUpcomingEvents([
          { id: 1, title: 'Staff Meeting', date: '2023-11-15', time: '2:00 PM - 4:00 PM' },
          { id: 2, title: 'Parent-Teacher Meeting', date: '2023-11-20', time: '9:00 AM - 12:00 PM' },
          { id: 3, title: 'Midterm Exams Begin', date: '2023-12-01', time: 'All Day' },
        ]);
        
        setPendingTasks([
          { id: 1, title: 'Approve leave requests', due: 'Today', priority: 'high' },
          { id: 2, title: 'Update student records', due: 'Tomorrow', priority: 'medium' },
          { id: 3, title: 'Prepare monthly report', due: 'Nov 20, 2023', priority: 'high' },
        ]);
        
        setIsLoading(false);
      } catch (error) {
        console.error('Error fetching staff data:', error);
        setIsLoading(false);
      }
    };
    
    fetchStaffData();
  }, []);

  // Fee collection data
  const feeCollectionData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
    datasets: [
      {
        label: 'Fee Collection',
        data: [8500, 9200, 10200, 9800, 11000, 12500, 13200, 11800, 12400, 13800],
        backgroundColor: 'rgba(16, 185, 129, 0.8)',
      },
    ],
  };

  const attendanceTrend = {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
    datasets: [
      {
        label: 'Student Attendance %',
        data: [92, 95, 90, 94, 93],
        borderColor: 'rgba(99, 102, 241, 1)',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        borderWidth: 2,
        tension: 0.3,
        fill: true,
      },
      {
        label: 'Staff Attendance %',
        data: [95, 97, 94, 96, 95],
        borderColor: 'rgba(245, 158, 11, 1)',
        backgroundColor: 'rgba(245, 158, 11, 0.1)',
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
        position: 'top',
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: (value) => `$${value}`,
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
          <p className="text-xl font-semibold text-gray-900">
            {title.includes('$') ? `$${value.toLocaleString()}` : value.toLocaleString()}
          </p>
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
    const getStatusColor = (status) => {
      switch (status) {
        case 'pending':
          return 'bg-yellow-100 text-yellow-800';
        case 'completed':
          return 'bg-green-100 text-green-800';
        case 'rejected':
          return 'bg-red-100 text-red-800';
        default:
          return 'bg-gray-100 text-gray-800';
      }
    };

    const getIcon = () => {
      switch (activity.type) {
        case 'admission':
          return <FaUserGraduate className="w-5 h-5 text-blue-500" />;
        case 'fee':
          return <FaFileInvoiceDollar className="w-5 h-5 text-green-500" />;
        case 'leave':
          return <FaCalendarAlt className="w-5 h-5 text-purple-500" />;
        default:
          return <FaBell className="w-5 h-5 text-gray-500" />;
      }
    };

    return (
      <div className="flex items-start py-3 border-b border-gray-100 last:border-0">
        <div className="flex-shrink-0 mt-1">
          {getIcon()}
        </div>
        <div className="ml-3 flex-1">
          <div className="flex items-center justify-between">
            <p className="text-sm font-medium text-gray-900">{activity.title}</p>
            <span className={`px-2 py-0.5 text-xs font-medium rounded-full ${getStatusColor(activity.status)}`}>
              {activity.status}
            </span>
          </div>
          <p className="text-sm text-gray-600">{activity.description}</p>
          <p className="mt-1 text-xs text-gray-400">{activity.time}</p>
        </div>
      </div>
    );
  };

  const TaskItem = ({ task }) => {
    const getPriorityColor = (priority) => {
      switch (priority) {
        case 'high':
          return 'bg-red-100 text-red-800';
        case 'medium':
          return 'bg-yellow-100 text-yellow-800';
        case 'low':
          return 'bg-blue-100 text-blue-800';
        default:
          return 'bg-gray-100 text-gray-800';
      }
    };

    return (
      <div className="flex items-center justify-between p-3 border border-gray-100 rounded-lg hover:bg-gray-50">
        <div className="flex items-center">
          <input
            type="checkbox"
            className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
          />
          <div className="ml-3">
            <p className="text-sm font-medium text-gray-900">{task.title}</p>
            <p className="text-xs text-gray-500">Due: {task.due}</p>
          </div>
        </div>
        <span className={`px-2 py-0.5 text-xs font-medium rounded-full ${getPriorityColor(task.priority)}`}>
          {task.priority}
        </span>
      </div>
    );
  };

  const renderOverviewTab = () => (
    <div className="space-y-6">
      {/* Stats Grid */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          icon={<FaUserGraduate className="w-5 h-5" />}
          title="Total Students"
          value={stats.totalStudents}
          change="5.2"
        />
        <StatCard
          icon={<FaChalkboardTeacher className="w-5 h-5" />}
          title="Total Teachers"
          value={stats.totalTeachers}
          change="2.1"
        />
        <StatCard
          icon={<FaUsers className="w-5 h-5" />}
          title="Total Parents"
          value={stats.totalParents}
          change="3.8"
        />
        <StatCard
          icon={<FaFileInvoiceDollar className="w-5 h-5" />}
          title="Fee Collection"
          value={stats.feeCollection}
          change="12.4"
        />
      </div>

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        {/* Recent Activities */}
        <div className="lg:col-span-2">
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

          {/* Pending Tasks */}
          <div className="p-5 mt-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-medium text-gray-900">Pending Tasks</h3>
              <button className="text-sm text-indigo-600 hover:text-indigo-500">View All</button>
            </div>
            <div className="mt-4 space-y-3">
              {pendingTasks.map((task) => (
                <TaskItem key={task.id} task={task} />
              ))}
            </div>
          </div>
        </div>

        {/* Right Sidebar */}
        <div className="space-y-5">
          {/* Fee Collection */}
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">Fee Collection</h3>
            <p className="text-sm text-gray-500">Monthly fee collection trend</p>
            <div className="h-48 mt-4">
              <Bar data={feeCollectionData} options={barChartOptions} />
            </div>
          </div>

          {/* Attendance Trend */}
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">Attendance Trend</h3>
            <p className="text-sm text-gray-500">This week's attendance</p>
            <div className="h-48 mt-4">
              <Line data={attendanceTrend} options={chartOptions} />
            </div>
          </div>

          {/* Upcoming Events */}
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-medium text-gray-900">Upcoming Events</h3>
              <button className="text-sm text-indigo-600 hover:text-indigo-500">View All</button>
            </div>
            <div className="mt-4 space-y-4">
              {upcomingEvents.map((event) => (
                <div key={event.id} className="flex items-start p-3 border border-gray-100 rounded-lg hover:bg-gray-50">
                  <div className="flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex-shrink-0">
                    <FaRegCalendarAlt className="w-5 h-5" />
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
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">Quick Actions</h3>
            <div className="grid grid-cols-2 gap-3 mt-4">
              <button className="flex flex-col items-center justify-center p-4 text-center text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100">
                <FaUserGraduate className="w-5 h-5 text-indigo-500 mb-2" />
                New Admission
              </button>
              <button className="flex flex-col items-center justify-center p-4 text-center text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100">
                <FaFileInvoiceDollar className="w-5 h-5 text-green-500 mb-2" />
                Record Payment
              </button>
              <button className="flex flex-col items-center justify-center p-4 text-center text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100">
                <FaClipboardCheck className="w-5 h-5 text-yellow-500 mb-2" />
                Mark Attendance
              </button>
              <button className="flex flex-col items-center justify-center p-4 text-center text-sm font-medium text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100">
                <FaBell className="w-5 h-5 text-purple-500 mb-2" />
                Send Notice
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );

  const renderStudentsTab = () => (
    <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
      <h3 className="text-lg font-medium text-gray-900">Student Management</h3>
      <p className="mt-1 text-sm text-gray-500">Manage student records and information.</p>
      {/* Student management would go here */}
    </div>
  );

  const renderStaffTab = () => (
    <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
      <h3 className="text-lg font-medium text-gray-900">Staff Management</h3>
      <p className="mt-1 text-sm text-gray-500">Manage staff records and information.</p>
      {/* Staff management would go here */}
    </div>
  );

  const renderFinanceTab = () => (
    <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
      <h3 className="text-lg font-medium text-gray-900">Financial Management</h3>
      <p className="mt-1 text-sm text-gray-500">Manage fees, expenses, and financial reports.</p>
      {/* Financial management would go here */}
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
        <h2 className="text-2xl font-bold text-gray-900">Staff Dashboard</h2>
        <p className="mt-1 text-sm text-gray-500">Welcome back! Here's what's happening today.</p>
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
            onClick={() => setActiveTab('students')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'students'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Students
          </button>
          <button
            onClick={() => setActiveTab('staff')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'staff'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Staff
          </button>
          <button
            onClick={() => setActiveTab('finance')}
            className={`py-4 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'finance'
                ? 'border-indigo-500 text-indigo-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            Finance
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
        {activeTab === 'students' && renderStudentsTab()}
        {activeTab === 'staff' && renderStaffTab()}
        {activeTab === 'finance' && renderFinanceTab()}
        {activeTab === 'reports' && (
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">Reports & Analytics</h3>
            <p className="mt-1 text-sm text-gray-500">Generate and view various reports.</p>
            {/* Reports would go here */}
          </div>
        )}
      </div>
    </div>
  );
};

export default StaffDashboard;
