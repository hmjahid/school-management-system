import { useState, useEffect } from 'react';
import { FaUsers, FaUserGraduate, FaChalkboardTeacher, FaDollarSign, FaCalendarAlt, FaClipboardList } from 'react-icons/fa';
import { Bar, Line, Doughnut } from 'react-chartjs-2';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
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
  BarElement,
  LineElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
);

const AdminDashboard = () => {
  const [stats, setStats] = useState({
    totalStudents: 0,
    totalTeachers: 0,
    totalParents: 0,
    totalRevenue: 0,
    attendanceRate: 0,
  });
  
  const [recentActivities, setRecentActivities] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  // Mock data - replace with actual API calls
  useEffect(() => {
    // Simulate API call
    const fetchDashboardData = async () => {
      try {
        // TODO: Replace with actual API calls
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        setStats({
          totalStudents: 1245,
          totalTeachers: 68,
          totalParents: 980,
          totalRevenue: 125400,
          attendanceRate: 92.5,
        });
        
        setRecentActivities([
          { id: 1, type: 'admission', title: 'New student admission', time: '10 minutes ago', user: 'John Doe' },
          { id: 2, type: 'payment', title: 'Fee payment received', time: '1 hour ago', amount: '$250' },
          { id: 3, type: 'exam', title: 'Math mid-term results published', time: '3 hours ago', subject: 'Mathematics' },
          { id: 4, type: 'attendance', title: 'Attendance marked for Class 10-A', time: '5 hours ago', present: 32, absent: 3 },
          { id: 5, type: 'announcement', title: 'School will remain closed tomorrow', time: '1 day ago', by: 'Principal' },
        ]);
        
        setIsLoading(false);
      } catch (error) {
        console.error('Error fetching dashboard data:', error);
        setIsLoading(false);
      }
    };
    
    fetchDashboardData();
  }, []);

  // Chart data
  const revenueData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    datasets: [
      {
        label: 'Revenue',
        data: [8500, 9200, 10200, 9800, 11000, 12500, 13200, 11800, 12400, 13800, 14500, 15000],
        backgroundColor: 'rgba(99, 102, 241, 0.2)',
        borderColor: 'rgba(99, 102, 241, 1)',
        borderWidth: 2,
        tension: 0.3,
      },
    ],
  };

  const attendanceData = {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    datasets: [
      {
        label: 'Attendance %',
        data: [92, 95, 90, 94, 93, 89],
        borderColor: 'rgba(16, 185, 129, 1)',
        backgroundColor: 'rgba(16, 185, 129, 0.2)',
        borderWidth: 2,
        tension: 0.3,
      },
    ],
  };

  const studentsByClass = {
    labels: ['Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10'],
    datasets: [
      {
        data: [45, 42, 48, 50, 46, 52, 48, 50, 55, 60],
        backgroundColor: [
          'rgba(99, 102, 241, 0.7)',
          'rgba(16, 185, 129, 0.7)',
          'rgba(245, 158, 11, 0.7)',
          'rgba(239, 68, 68, 0.7)',
          'rgba(139, 92, 246, 0.7)',
          'rgba(20, 184, 166, 0.7)',
          'rgba(249, 115, 22, 0.7)',
          'rgba(236, 72, 153, 0.7)',
          'rgba(6, 182, 212, 0.7)',
          'rgba(5, 150, 105, 0.7)',
        ],
        borderWidth: 1,
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
      },
    },
  };

  const StatCard = ({ icon, title, value, change, changeType = 'increase' }) => (
    <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-gray-500">{title}</p>
          <p className="mt-2 text-2xl font-semibold text-gray-900">{value}</p>
          {change && (
            <p className={`mt-1 text-sm ${changeType === 'increase' ? 'text-green-600' : 'text-red-600'}`}>
              {changeType === 'increase' ? '↑' : '↓'} {change}% from last month
            </p>
          )}
        </div>
        <div className="p-3 rounded-full bg-indigo-50 text-indigo-600">
          {icon}
        </div>
      </div>
    </div>
  );

  const ActivityItem = ({ activity }) => {
    const getIcon = () => {
      switch (activity.type) {
        case 'admission':
          return <FaUserGraduate className="w-5 h-5 text-green-500" />;
        case 'payment':
          return <FaDollarSign className="w-5 h-5 text-blue-500" />;
        case 'exam':
          return <FaClipboardList className="w-5 h-5 text-purple-500" />;
        case 'attendance':
          return <FaCalendarAlt className="w-5 h-5 text-yellow-500" />;
        default:
          return <FaUsers className="w-5 h-5 text-gray-500" />;
      }
    };

    return (
      <div className="flex items-start py-3 border-b border-gray-100 last:border-0">
        <div className="flex-shrink-0 mt-1">
          {getIcon()}
        </div>
        <div className="ml-3">
          <p className="text-sm font-medium text-gray-900">{activity.title}</p>
          <p className="text-sm text-gray-500">
            {activity.time} • {activity.user || activity.by || activity.subject || ''}
            {activity.amount && ` • ${activity.amount}`}
            {activity.present && ` • ${activity.present} present, ${activity.absent} absent`}
          </p>
        </div>
      </div>
    );
  };

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
        <h2 className="text-2xl font-bold text-gray-900">Dashboard Overview</h2>
        <p className="mt-1 text-sm text-gray-500">Welcome back! Here's what's happening with your school today.</p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 gap-5 mt-6 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          icon={<FaUserGraduate className="w-6 h-6" />}
          title="Total Students"
          value={stats.totalStudents.toLocaleString()}
          change="5.2"
        />
        <StatCard
          icon={<FaChalkboardTeacher className="w-6 h-6" />}
          title="Total Teachers"
          value={stats.totalTeachers}
          change="2.1"
        />
        <StatCard
          icon={<FaUsers className="w-6 h-6" />}
          title="Total Parents"
          value={stats.totalParents.toLocaleString()}
          change="3.8"
        />
        <StatCard
          icon={<FaDollarSign className="w-6 h-6" />}
          title="Total Revenue"
          value={`$${stats.totalRevenue.toLocaleString()}`}
          change="12.4"
        />
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 gap-5 mt-6 lg:grid-cols-2">
        <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-lg font-medium text-gray-900">Monthly Revenue</h3>
          <div className="h-64 mt-4">
            <Line data={revenueData} options={chartOptions} />
          </div>
        </div>
        <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
          <h3 className="text-lg font-medium text-gray-900">Weekly Attendance</h3>
          <div className="h-64 mt-4">
            <Line data={attendanceData} options={chartOptions} />
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-5 mt-6 lg:grid-cols-3">
        {/* Recent Activities */}
        <div className="lg:col-span-2">
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">Recent Activities</h3>
            <div className="mt-4 space-y-3">
              {recentActivities.map((activity) => (
                <ActivityItem key={activity.id} activity={activity} />
              ))}
            </div>
            <div className="mt-4 text-center">
              <button className="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                View all activities
              </button>
            </div>
          </div>
        </div>

        {/* Students by Class */}
        <div>
          <div className="p-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">Students by Class</h3>
            <div className="h-64 mt-4">
              <Doughnut data={studentsByClass} options={chartOptions} />
            </div>
          </div>

          {/* Quick Actions */}
          <div className="p-5 mt-5 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-medium text-gray-900">Quick Actions</h3>
            <div className="mt-4 space-y-3">
              <button className="flex items-center w-full px-4 py-3 text-sm font-medium text-left text-gray-700 bg-gray-50 rounded-md hover:bg-gray-100">
                <FaUserGraduate className="w-5 h-5 mr-3 text-indigo-600" />
                Add New Student
              </button>
              <button className="flex items-center w-full px-4 py-3 text-sm font-medium text-left text-gray-700 bg-gray-50 rounded-md hover:bg-gray-100">
                <FaDollarSign className="w-5 h-5 mr-3 text-green-600" />
                Record Payment
              </button>
              <button className="flex items-center w-full px-4 py-3 text-sm font-medium text-left text-gray-700 bg-gray-50 rounded-md hover:bg-gray-100">
                <FaCalendarAlt className="w-5 h-5 mr-3 text-yellow-600" />
                Mark Attendance
              </button>
              <button className="flex items-center w-full px-4 py-3 text-sm font-medium text-left text-gray-700 bg-gray-50 rounded-md hover:bg-gray-100">
                <FaClipboardList className="w-5 h-5 mr-3 text-purple-600" />
                Create Exam
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AdminDashboard;
