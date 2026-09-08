import { useEffect, useState } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { 
  FiUsers, 
  FiBook, 
  FiDollarSign, 
  FiCalendar, 
  FiAlertCircle, 
  FiCheckCircle,
  FiClock,
  FiTrendingUp,
  FiBookOpen
} from 'react-icons/fi';
import { Line, Bar, Doughnut } from 'react-chartjs-2';
import Header from '../../components/dashboard/Header';
import { 
  Chart as ChartJS, 
  CategoryScale, 
  LinearScale, 
  PointElement, 
  LineElement, 
  BarElement, 
  ArcElement,
  Title, 
  Tooltip, 
  Legend, 
  Filler 
} from 'chart.js';
import { format, subDays } from 'date-fns';
import api from '../../services/api';
import { toast } from 'react-hot-toast';

// Register ChartJS components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const StatCard = ({ title, value, icon, trend, trendText, trendType = 'up' }) => (
  <div className="bg-white rounded-xl shadow-sm p-6 flex flex-col">
    <div className="flex justify-between items-start">
      <div>
        <p className="text-sm font-medium text-gray-500">{title}</p>
        <p className="mt-2 text-3xl font-semibold text-gray-900">{value}</p>
      </div>
      <div className={`p-3 rounded-lg ${trendType === 'up' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'}`}>
        {icon}
      </div>
    </div>
    {trend && (
      <div className="mt-4 flex items-center text-sm">
        <span className={`font-medium ${trendType === 'up' ? 'text-green-600' : 'text-red-600'} flex items-center`}>
          {trendType === 'up' ? (
            <FiTrendingUp className="mr-1" />
          ) : (
            <FiTrendingUp className="mr-1 transform rotate-180" />
          )}
          {trend}
        </span>
        <span className="text-gray-500 ml-2">{trendText}</span>
      </div>
    )}
  </div>
);

const ActivityItem = ({ title, description, time, icon, color = 'indigo' }) => {
  const colors = {
    indigo: 'bg-indigo-100 text-indigo-600',
    green: 'bg-green-100 text-green-600',
    red: 'bg-red-100 text-red-600',
    yellow: 'bg-yellow-100 text-yellow-600',
    purple: 'bg-purple-100 text-purple-600',
  };

  return (
    <div className="flex items-start pb-4 last:pb-0">
      <div className={`p-2 rounded-lg ${colors[color]}`}>
        {icon}
      </div>
      <div className="ml-4">
        <p className="text-sm font-medium text-gray-900">{title}</p>
        <p className="text-sm text-gray-500">{description}</p>
        <p className="text-xs text-gray-400 mt-1 flex items-center">
          <FiClock className="mr-1" size={12} />
          {time}
        </p>
      </div>
    </div>
  );
};

const ModernDashboard = () => {
  // Hooks and state
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({
    upcomingClasses: 3,
    pendingAssignments: 2,
    averageGrade: 'A-',
    attendanceRate: 94.5,
    recentAnnouncements: [
      { id: 1, title: 'School Picnic', date: '2023-06-15', description: 'Annual school picnic at Central Park' },
      { id: 2, title: 'Science Fair', date: '2023-06-20', description: 'Submit your project proposals by June 18th' },
    ],
    upcomingExams: [
      { id: 1, subject: 'Mathematics', date: '2023-06-25', time: '09:00 AM' },
      { id: 2, subject: 'Science', date: '2023-06-28', time: '11:00 AM' },
    ],
    // Add recentActivity used by the default (admin/teacher) dashboard
    recentActivity: [
      { id: 1, title: 'New student registered', description: 'John Doe registered for class 3', time: '2 minutes ago', icon: <FiUsers />, color: 'indigo' },
      { id: 2, title: 'Payment Received', description: 'Payment received from Jane Smith', time: '1 hour ago', icon: <FiDollarSign />, color: 'green' }
    ]
  });

  // Chart data
  const [chartData] = useState({
    gradeTrend: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
      datasets: [
        {
          label: 'Average Grade',
          data: [85, 82, 88, 87, 89, 90],
          borderColor: 'rgb(79, 70, 229)',
          backgroundColor: 'rgba(79, 70, 229, 0.1)',
          fill: true,
          tension: 0.4
        }
      ]
    },
    subjectPerformance: {
      labels: ['Math', 'Science', 'English', 'History', 'Art'],
      datasets: [
        {
          label: 'Your Score',
          data: [92, 88, 85, 90, 95],
          backgroundColor: 'rgba(79, 70, 229, 0.8)',
          borderColor: 'rgb(79, 70, 229)',
          borderWidth: 1,
        },
        {
          label: 'Class Average',
          data: [78, 82, 80, 85, 88],
          backgroundColor: 'rgba(156, 163, 175, 0.8)',
          borderColor: 'rgb(156, 163, 175)',
          borderWidth: 1,
        }
      ]
    }
  });

  // Simulate data loading
  useEffect(() => {
    const timer = setTimeout(() => {
      setLoading(false);
    }, 1000);

    return () => clearTimeout(timer);
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500"></div>
      </div>
    );
  }

  // Render different dashboard based on user role
  if (user?.role === 'student') {
    return (
      <div className="min-h-screen bg-gray-50">
        <Header />
        <main className="py-6 px-4 sm:px-6 lg:px-8">
          {/* Welcome Section */}
          <div className="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h1 className="text-2xl font-bold text-gray-900">Welcome back, {user?.name || 'User'}!</h1>
            <p className="mt-1 text-gray-600">Here's what's happening with your account today.</p>
          </div>

          {/* Stats Cards */}
          <div className="grid grid-cols-1 gap-5 mt-6 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard 
              title="Upcoming Classes" 
              value={stats.upcomingClasses} 
              icon={<FiCalendar className="h-6 w-6 text-indigo-600" />}
              trend="2 today"
              trendText="Next: Math at 10:00 AM"
            />
            <StatCard 
              title="Pending Assignments" 
              value={stats.pendingAssignments} 
              icon={<FiBook className="h-6 w-6 text-red-600" />}
              trend="1 due tomorrow"
              trendText="Math homework"
            />
            <StatCard 
              title="Average Grade" 
              value={stats.averageGrade} 
              icon={<FiTrendingUp className="h-6 w-6 text-green-600" />}
              trend="+2.5%"
              trendText="from last term"
            />
            <StatCard 
              title="Attendance" 
              value={`${stats.attendanceRate}%`} 
              icon={<FiCheckCircle className="h-6 w-6 text-blue-600" />}
              trend="+1.2%"
              trendText="this month"
            />
          </div>

          {/* Charts */}
          <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {/* Grade Trend */}
            <div className="bg-white p-6 rounded-xl shadow">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-medium text-gray-900">Grade Trend</h3>
                <select className="text-sm border border-gray-300 rounded-md px-2 py-1">
                  <option>This Term</option>
                  <option>Last Term</option>
                  <option>This Year</option>
                </select>
              </div>
              <div className="h-64">
                <Line 
                  data={chartData.gradeTrend}
                  options={{
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                      legend: {
                        display: false
                      }
                    },
                    scales: {
                      y: {
                        min: 70,
                        max: 100,
                        grid: {
                          drawBorder: false
                        },
                        ticks: {
                          callback: function(value) {
                            return value + '%';
                          }
                        }
                      },
                      x: {
                        grid: {
                          display: false
                        }
                      }
                    }
                  }}
                />
              </div>
            </div>

            {/* Subject Performance */}
            <div className="bg-white p-6 rounded-xl shadow">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-medium text-gray-900">Subject Performance</h3>
                <span className="text-sm text-gray-500">Current Term</span>
              </div>
              <div className="h-64">
                <Bar 
                  data={chartData.subjectPerformance}
                  options={{
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                      legend: {
                        position: 'bottom',
                      },
                    },
                    scales: {
                      y: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                          drawBorder: false
                        },
                        ticks: {
                          callback: function(value) {
                            return value + '%';
                          }
                        }
                      },
                      x: {
                        grid: {
                          display: false
                        }
                      }
                    }
                  }}
                />
              </div>
            </div>
          </div>

          {/* Recent Activity and Upcoming Events */}
          <div className="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {/* Upcoming Exams */}
            <div className="bg-white p-6 rounded-xl shadow">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-medium text-gray-900">Upcoming Exams</h3>
                <a href="/exams" className="text-sm text-indigo-600 hover:text-indigo-800">View All</a>
              </div>
              <div className="space-y-4">
                {stats.upcomingExams.length > 0 ? (
                  stats.upcomingExams.map((exam) => (
                    <div key={exam.id} className="flex items-start p-3 hover:bg-gray-50 rounded-lg transition-colors">
                      <div className="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <FiCalendar className="h-5 w-5 text-indigo-600" />
                      </div>
                      <div className="ml-4 flex-1">
                        <div className="flex items-center justify-between">
                          <h4 className="text-sm font-medium text-gray-900">{exam.subject} Exam</h4>
                          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {exam.date}
                          </span>
                        </div>
                        <p className="mt-1 text-sm text-gray-500">{exam.time} • Room 205</p>
                      </div>
                    </div>
                  ))
                ) : (
                  <p className="text-sm text-gray-500 text-center py-4">No upcoming exams</p>
                )}
              </div>
            </div>

            {/* Recent Announcements */}
            <div className="bg-white p-6 rounded-xl shadow">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-medium text-gray-900">Recent Announcements</h3>
                <a href="/announcements" className="text-sm text-indigo-600 hover:text-indigo-800">View All</a>
              </div>
              <div className="space-y-4">
                {stats.recentAnnouncements.length > 0 ? (
                  stats.recentAnnouncements.map((announcement) => (
                    <div key={announcement.id} className="p-3 hover:bg-gray-50 rounded-lg transition-colors">
                      <div className="flex items-center justify-between">
                        <h4 className="text-sm font-medium text-gray-900">{announcement.title}</h4>
                        <span className="text-xs text-gray-500">{announcement.date}</span>
                      </div>
                      <p className="mt-1 text-sm text-gray-500 line-clamp-2">{announcement.description}</p>
                    </div>
                  ))
                ) : (
                  <p className="text-sm text-gray-500 text-center py-4">No recent announcements</p>
                )}
              </div>
            </div>
          </div>

          {/* Quick Actions */}
          <div className="mt-8 bg-white p-6 rounded-xl shadow">
            <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
              <a
                href="/assignments"
                className="flex flex-col items-center justify-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div className="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center mb-2">
                  <FiBook className="h-5 w-5 text-indigo-600" />
                </div>
                <span className="text-sm font-medium text-gray-700">Assignments</span>
              </a>
              <a
                href="/schedule"
                className="flex flex-col items-center justify-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div className="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mb-2">
                  <FiCalendar className="h-5 w-5 text-green-600" />
                </div>
                <span className="text-sm font-medium text-gray-700">Schedule</span>
              </a>
              <a
                href="/grades"
                className="flex flex-col items-center justify-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div className="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mb-2">
                  <FiTrendingUp className="h-5 w-5 text-blue-600" />
                </div>
                <span className="text-sm font-medium text-gray-700">Grades</span>
              </a>
              <a
                href="/resources"
                className="flex flex-col items-center justify-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div className="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center mb-2">
                  <FiBookOpen className="h-5 w-5 text-purple-600" />
                </div>
                <span className="text-sm font-medium text-gray-700">Resources</span>
              </a>
            </div>
          </div>
        </main>
      </div>
    );
  }

  // Default dashboard for other roles (admin/teacher)
  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <main className="py-6 px-4 sm:px-6 lg:px-8">
        {/* Welcome Section */}
        <div className="bg-white rounded-xl shadow-sm p-6 mb-6">
          <h1 className="text-2xl font-bold text-gray-900">Welcome back, {user?.name || 'User'}!</h1>
          <p className="mt-1 text-gray-600">Here's what's happening in your school today.</p>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 gap-5 mt-6 sm:grid-cols-2 lg:grid-cols-4">
          <StatCard 
            title="Total Students" 
            value="1,245" 
            icon={<FiUsers className="h-6 w-6 text-indigo-600" />}
            trend="12.5%"
            trendText="vs last month"
          />
          <StatCard 
            title="Total Teachers" 
            value="45" 
            icon={<FiBook className="h-6 w-6 text-green-600" />}
            trend="5.2%"
            trendText="vs last month"
          />
          <StatCard 
            title="Total Classes" 
            value="32" 
            icon={<FiCalendar className="h-6 w-6 text-blue-600" />}
            trend="3.1%"
            trendText="vs last month"
          />
          <StatCard 
            title="Attendance Rate" 
            value="94.5%" 
            icon={<FiCheckCircle className="h-6 w-6 text-purple-600" />}
            trend="1.2%"
            trendText="this month"
          />
        </div>

        {/* Recent Activity */}
        <div className="mt-8 bg-white p-6 rounded-xl shadow">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
          <div className="space-y-4">
            {stats.recentActivity.map((activity) => (
              <ActivityItem 
                key={activity.id}
                title={activity.title}
                description={activity.description}
                time={activity.time}
                icon={activity.icon}
                color={activity.color}
              />
            ))}
          </div>
        </div>

        {/* Quick Actions */}
        <div className="mt-8 bg-white p-6 rounded-xl shadow-sm">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <button className="flex flex-col items-center justify-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
              <div className="p-3 bg-indigo-100 rounded-lg text-indigo-600 mb-2">
                <FiUsers size={20} />
              </div>
              <span className="text-sm font-medium text-gray-700">Add Student</span>
            </button>
            <button className="flex flex-col items-center justify-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
              <div className="p-3 bg-green-100 rounded-lg text-green-600 mb-2">
                <FiDollarSign size={20} />
              </div>
              <span className="text-sm font-medium text-gray-700">Record Payment</span>
            </button>
            <button className="flex flex-col items-center justify-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
              <div className="p-3 bg-purple-100 rounded-lg text-purple-600 mb-2">
                <FiCalendar size={20} />
              </div>
              <span className="text-sm font-medium text-gray-700">Schedule Event</span>
            </button>
            <button className="flex flex-col items-center justify-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
              <div className="p-3 bg-yellow-100 rounded-lg text-yellow-600 mb-2">
                <FiAlertCircle size={20} />
              </div>
              <span className="text-sm font-medium text-gray-700">Send Notice</span>
            </button>
          </div>
        </div>
      </main>
    </div>
  );
};

export default ModernDashboard;
