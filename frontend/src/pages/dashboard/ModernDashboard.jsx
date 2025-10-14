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
  FiTrendingUp
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
  const { user, logout } = useAuth();
  const [dashboardData, setDashboardData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [sidebarOpen, setSidebarOpen] = useState(false);

  // Get fallback data when API calls fail
  const getFallbackData = () => ({
    stats: [
      { 
        title: 'Total Students', 
        value: '1,254', 
        icon: <FiUsers size={20} />, 
        trend: '12%', 
        trendText: 'vs last month', 
        trendType: 'up' 
      },
      { 
        title: 'Active Classes', 
        value: '24', 
        icon: <FiBook size={20} />, 
        trend: '5%', 
        trendText: 'vs last month', 
        trendType: 'up' 
      },
      { 
        title: 'Total Teachers', 
        value: '45', 
        icon: <FiUsers size={20} />, 
        trend: '8%', 
        trendText: 'vs last month', 
        trendType: 'up' 
      },
      { 
        title: 'Monthly Revenue', 
        value: '$12,540', 
        icon: <FiDollarSign size={20} />, 
        trend: '12.5%', 
        trendText: 'vs last month', 
        trendType: 'up' 
      },
    ],
    recentActivity: [
      { 
        id: 1,
        title: 'New student enrolled', 
        description: 'John Doe joined Grade 10', 
        time: '2 hours ago', 
        icon: <FiUsers size={16} />,
        color: 'indigo'
      },
      { 
        id: 2,
        title: 'New assignment posted', 
        description: 'Math homework due on Friday', 
        time: '5 hours ago', 
        icon: <FiBook size={16} />,
        color: 'green'
      },
      { 
        id: 3,
        title: 'Payment received', 
        description: '$250 from Sarah Johnson', 
        time: '1 day ago', 
        icon: <FiDollarSign size={16} />,
        color: 'purple'
      },
    ],
    chartData: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
      datasets: [
        {
          label: 'Students',
          data: [65, 78, 66, 89, 96, 112],
          borderColor: 'rgb(99, 102, 241)',
          backgroundColor: 'rgba(99, 102, 241, 0.1)',
          fill: true,
        },
      ],
    },
    subjectDistribution: {
      labels: ['Math', 'Science', 'English', 'History'],
      datasets: [
        {
          label: 'Subjects',
          data: [30, 20, 40, 10],
          borderColor: 'rgb(99, 102, 241)',
          backgroundColor: [
            'rgba(255, 99, 132, 0.2)',
            'rgba(54, 162, 235, 0.2)',
            'rgba(255, 206, 86, 0.2)',
            'rgba(75, 192, 192, 0.2)',
          ],
          borderWidth: 1,
        },
      ],
    },
  });

  // Fetch dashboard data
  useEffect(() => {
    const fetchDashboardData = async () => {
      try {
        setLoading(true);
        
        // Check if user is authenticated
        const token = localStorage.getItem('token');
        if (!token) {
          console.log('No authentication token found, using fallback data');
          setDashboardData(getFallbackData());
          setLoading(false);
          return;
        }

        // Set auth header for all requests
        api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        
        // Get the main dashboard data
        const dashboardRes = await api.get('/api/admin/dashboard').catch(err => {
          console.warn('Failed to fetch dashboard data, using fallback data');
          return { data: null };
        });

        if (dashboardRes?.data) {
          // Transform the data to match our frontend structure
          const transformedData = {
            stats: [
              { 
                title: 'Total Students',
                value: (dashboardRes.data.total_students || 0).toLocaleString(),
                icon: <FiUsers size={20} />,
                trend: '0%',
                trendText: 'vs last month',
                trendType: 'up'
              },
              { 
                title: 'Active Classes',
                value: (dashboardRes.data.total_classes || 0).toString(),
                icon: <FiBook size={20} />,
                trend: '0%',
                trendText: 'vs last month',
                trendType: 'up'
              },
              { 
                title: 'Total Teachers',
                value: (dashboardRes.data.total_teachers || 0).toString(),
                icon: <FiUsers size={20} />,
                trend: '0%',
                trendText: 'vs last month',
                trendType: 'up'
              },
              { 
                title: 'Monthly Revenue',
                value: `$${(dashboardRes.data.monthly_revenue || 0).toLocaleString()}`,
                icon: <FiDollarSign size={20} />,
                trend: '0%',
                trendText: 'vs last month',
                trendType: 'up'
              }
            ],
            recentActivity: (dashboardRes.data.recent_activity || []).map((activity, index) => ({
              id: index,
              title: activity.title || 'Activity',
              description: activity.description || 'No description',
              time: activity.time || 'Just now',
              icon: activity.type === 'payment' ? <FiDollarSign size={16} /> : 
                    activity.type === 'enrollment' ? <FiUsers size={16} /> : 
                    <FiAlertCircle size={16} />,
              color: activity.type === 'payment' ? 'green' : 
                     activity.type === 'enrollment' ? 'indigo' : 'yellow'
            })),
            chartData: {
              labels: dashboardRes.data.chart_data?.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
              datasets: [
                {
                  label: 'Students',
                  data: dashboardRes.data.chart_data?.data || [65, 78, 66, 89, 96, 112],
                  borderColor: 'rgb(99, 102, 241)',
                  backgroundColor: 'rgba(99, 102, 241, 0.1)',
                  fill: true,
                },
              ],
            },
          };
          
          setDashboardData(transformedData);
        } else {
          // No data from API, use fallback
          setDashboardData(getFallbackData());
          toast.error('No dashboard data available. Using sample data.');
        }
      } catch (error) {
        console.error('Error fetching dashboard data:', error);
        setDashboardData(getFallbackData());
        toast.error('Error loading dashboard data. Using sample data.');
      } finally {
        setLoading(false);
      }
    };

    fetchDashboardData();
  }, [user?.role]);

  // Handle logout
  const handleLogout = async () => {
    try {
      await logout();
      // The logout function from AuthContext will handle the redirection
    } catch (error) {
      console.error('Logout failed:', error);
      toast.error('Failed to log out. Please try again.');
    }
  };

  // Show loading state
  if (loading) {
    return (
      <div className="flex items-center justify-center h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500"></div>
      </div>
    );
  }

  // Use fallback data if dashboardData is not available
  const data = dashboardData || getFallbackData();

  return (
    <div className="min-h-screen bg-gray-50">
      <Header toggleSidebar={() => setSidebarOpen(!sidebarOpen)} />
      
      <main className="py-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Welcome Header */}
          <div className="mb-8">
            <h1 className="text-2xl font-bold text-gray-900">Welcome back, {user?.name || 'User'}</h1>
            <p className="text-gray-500">Here's what's happening with your school today</p>
          </div>
          
          {/* Stats Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {data.stats.map((stat, index) => (
              <StatCard
                key={index}
                title={stat.title}
                value={stat.value}
                icon={stat.icon}
                trend={stat.trend}
                trendText={stat.trendText}
                trendType={stat.trendType}
              />
            ))}
          </div>

          {/* Charts Row */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div className="bg-white p-6 rounded-xl shadow-sm lg:col-span-2">
              <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg font-medium text-gray-900">Performance Overview</h3>
                <select className="text-sm border border-gray-300 rounded-md px-3 py-1 bg-white">
                  <option>Last 30 days</option>
                  <option>Last 60 days</option>
                  <option>This year</option>
                </select>
              </div>
              <div className="h-64">
                <Line
                  data={data.chartData}
                  options={{
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                      legend: {
                        display: false,
                      },
                    },
                    scales: {
                  y: {
                    beginAtZero: true,
                    grid: {
                      display: true,
                      drawBorder: false,
                    },
                    ticks: {
                      maxTicksLimit: 5,
                    },
                  },
                  x: {
                    grid: {
                      display: false,
                    },
                  },
                },
              }}
            />
          </div>
        </div>

        <div className="bg-white p-6 rounded-xl shadow-sm">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Subject Distribution</h3>
          <div className="h-56">
            <Doughnut
              data={data.subjectDistribution || data.chartData}
              options={{
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                  legend: {
                    position: 'bottom',
                    labels: {
                      usePointStyle: true,
                      padding: 20,
                    },
                  },
                },
              }}
            />
          </div>
        </div>
      </div>

      {/* Bottom Row */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="bg-white p-6 rounded-xl shadow-sm lg:col-span-2">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Weekly Attendance</h3>
          <div className="h-64">
            <Bar
              data={{
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                datasets: [{
                  label: 'Attendance %',
                  data: [85, 90, 88, 92, 87, 0],
                  backgroundColor: 'rgba(79, 70, 229, 0.7)',
                }]
              }}
              options={{
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    display: false,
                  },
                },
                scales: {
                  y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                      display: true,
                      drawBorder: false,
                    },
                    ticks: {
                      callback: (value) => `${value}%`,
                    },
                  },
                  x: {
                    grid: {
                      display: false,
                    },
                  },
                },
              }}
            />
          </div>
        </div>

        <div className="bg-white p-6 rounded-xl shadow-sm">
          <div className="flex justify-between items-center mb-4">
            <h3 className="text-lg font-medium text-gray-900">Recent Activity</h3>
            <button className="text-sm text-indigo-600 hover:text-indigo-800">
              View All
            </button>
          </div>
          <div className="space-y-4">
            {data.recentActivity.map((activity) => (
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
      </div>

      {/* Quick Actions */}
      <div className="bg-white p-6 rounded-xl shadow-sm">
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
        </div>
      </main>
    </div>
  );
};

export default ModernDashboard;
