import { useEffect, useRef, useState, useCallback } from 'react';
import { motion } from 'framer-motion';
import { 
  FaUserGraduate, 
  FaChalkboardTeacher, 
  FaUsers, 
  FaDollarSign, 
  FaClipboardList,
  FaFileAlt,
  FaUserCheck,
  FaCalendarDay
} from 'react-icons/fa';
import { Bar, Line, Doughnut } from 'react-chartjs-2';
import { useQuery } from '@tanstack/react-query';
import LoadingSpinner from "../../components/common/LoadingSpinner";
import { fetchDashboardStats } from '../../services/dashboardService';

// Register ChartJS components
import {
  Chart as ChartJS,
  // Controllers
  LineController,
  BarController,
  DoughnutController,
  // Elements
  LineElement,
  PointElement,
  BarElement,
  ArcElement,
  // Scales
  CategoryScale,
  LinearScale,
  TimeScale,
  TimeSeriesScale,
  // Plugins
  Title,
  Tooltip,
  Legend,
  Filler,
  // Base
  Chart,
} from 'chart.js';

// Register ChartJS components
ChartJS.register(
  // Controllers
  LineController,
  BarController,
  DoughnutController,
  // Elements
  LineElement,
  PointElement,
  BarElement,
  ArcElement,
  // Scales
  CategoryScale,
  LinearScale,
  TimeScale,
  TimeSeriesScale,
  // Plugins
  Title,
  Tooltip,
  Legend,
  Filler
);

// Chart components with proper cleanup
const ChartComponent = ({ type, data, options, chartId }) => {
  const chartRef = useRef(null);
  const canvasId = `chart-${chartId || Math.random().toString(36).substr(2, 9)}`;
  
  // Clean up chart on unmount
  useEffect(() => {
    return () => {
      if (chartRef.current) {
        chartRef.current.destroy();
        chartRef.current = null;
      }
      
      // Clean up any remaining chart instances with the same ID
      const existingChart = Chart.getChart(canvasId);
      if (existingChart) {
        existingChart.destroy();
      }
    };
  }, []);
  
  // Initialize or update chart
  useEffect(() => {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    // Clean up any existing chart with this ID
    const existingChart = Chart.getChart(canvas);
    if (existingChart) {
      existingChart.destroy();
    }
    
    // Create new chart instance
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    
    chartRef.current = new Chart(ctx, {
      type: type,
      data: data,
      options: {
        ...options,
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        plugins: {
          ...options?.plugins,
          legend: {
            position: 'top',
            ...options?.plugins?.legend
          }
        },
        scales: {
          x: {
            grid: {
              display: false,
            },
            ticks: {
              color: '#9CA3AF',
            },
            ...options?.scales?.x
          },
          y: {
            grid: {
              color: 'rgba(229, 231, 235, 0.5)',
            },
            ticks: {
              color: '#9CA3AF',
              ...(options?.scales?.y?.ticks || {})
            },
            ...options?.scales?.y
          },
        },
      },
    });
    
    return () => {
      if (chartRef.current) {
        chartRef.current.destroy();
        chartRef.current = null;
      }
    };
  }, [type, data, options, canvasId]);

  return (
    <div className="w-full h-full">
      <canvas id={canvasId} />
    </div>
  );
};

const EnhancedAdminDashboard = () => {
  // Clean up charts on unmount
  const chartCleanup = () => {
    // This will clean up any chart instances when the component unmounts
    if (window.Chart && window.Chart.instances) {
      window.Chart.instances.forEach(instance => {
        instance.destroy();
      });
    }
  };

  useEffect(() => {
    return () => {
      console.log('Dashboard unmounting, cleaning up charts');
      chartCleanup();
    };
  }, []);

  // Fetch dashboard data using React Query
  const { data: dashboardData, isLoading, error } = useQuery({
    queryKey: ['dashboardStats'],
    queryFn: fetchDashboardStats,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });

  // Extract data from the query result
  const stats = dashboardData?.stats || {
    totalStudents: 0,
    totalTeachers: 0,
    totalParents: 0,
    totalRevenue: 0,
    attendanceRate: 0,
  };

  const recentActivities = dashboardData?.recentActivities || [];

  // Chart data
  const studentData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [
      {
        label: 'New Students',
        data: [12, 19, 3, 5, 2, 3],
        backgroundColor: 'rgba(59, 130, 246, 0.5)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 1,
      },
    ],
  };

  const revenueData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [
      {
        label: 'Monthly Revenue',
        data: [12500, 19000, 3000, 5000, 2000, 3000],
        borderColor: 'rgba(16, 185, 129, 1)',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        tension: 0.3,
        fill: true,
      },
    ],
  };

  const attendanceData = {
    labels: ['Present', 'Absent', 'Late'],
    datasets: [
      {
        data: [85, 10, 5],
        backgroundColor: [
          'rgba(16, 185, 129, 0.8)',
          'rgba(239, 68, 68, 0.8)',
          'rgba(245, 158, 11, 0.8)',
        ],
        borderColor: [
          'rgba(16, 185, 129, 1)',
          'rgba(239, 68, 68, 1)',
          'rgba(245, 158, 11, 1)',
        ],
        borderWidth: 1,
      },
    ],
  };

  const chartOptions = (chartId) => {
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: {
            color: '#6B7280',
            font: {
              family: 'Inter, sans-serif',
            },
          },
        },
      },
      scales: {
        x: {
          grid: {
            display: false,
          },
          ticks: {
            color: '#9CA3AF',
          },
        },
        y: {
          grid: {
            color: 'rgba(229, 231, 235, 0.5)',
          },
          ticks: {
            color: '#9CA3AF',
          },
        },
      },
    };
  };

  const performanceChartData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [
      {
        label: 'Performance',
        data: [12, 19, 3, 5, 2, 3],
        backgroundColor: 'rgba(59, 130, 246, 0.5)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 1,
      },
    ],
  };

  const attendanceChartData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [
      {
        label: 'Attendance',
        data: [12500, 19000, 3000, 5000, 2000, 3000],
        borderColor: 'rgba(16, 185, 129, 1)',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        tension: 0.3,
        fill: true,
      },
    ],
  };

  const revenueChartData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [
      {
        label: 'Revenue',
        data: [85, 10, 5],
        backgroundColor: [
          'rgba(16, 185, 129, 0.8)',
          'rgba(239, 68, 68, 0.8)',
          'rgba(245, 158, 11, 0.8)',
        ],
        borderColor: [
          'rgba(16, 185, 129, 1)',
          'rgba(239, 68, 68, 1)',
          'rgba(245, 158, 11, 1)',
        ],
        borderWidth: 1,
      },
    ],
  };

  // Quick actions
  const quickActions = [
    {
      id: 1,
      title: 'Add New Student',
      icon: <FaUserGraduate className="w-6 h-6 text-blue-500" />,
      color: 'bg-blue-50 dark:bg-blue-900/20',
      textColor: 'text-blue-600 dark:text-blue-300',
      onClick: () => console.log('Add New Student'),
    },
    {
      id: 2,
      title: 'Create Report',
      icon: <FaFileAlt className="w-6 h-6 text-green-500" />,
      color: 'bg-green-50 dark:bg-green-900/20',
      textColor: 'text-green-600 dark:text-green-300',
      onClick: () => console.log('Create Report'),
    },
    {
      id: 3,
      title: 'Mark Attendance',
      icon: <FaUserCheck className="w-6 h-6 text-purple-500" />,
      color: 'bg-purple-50 dark:bg-purple-900/20',
      textColor: 'text-purple-600 dark:text-purple-300',
      onClick: () => console.log('Mark Attendance'),
    },
    {
      id: 4,
      title: 'Add Event',
      icon: <FaCalendarDay className="w-6 h-6 text-yellow-500" />,
      color: 'bg-yellow-50 dark:bg-yellow-900/20',
      textColor: 'text-yellow-600 dark:text-yellow-300',
      onClick: () => console.log('Add Event'),
    },
  ];

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[50vh]">
        <LoadingSpinner size="lg" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 dark:bg-red-900/10 border-l-4 border-red-500 p-4 my-6 rounded-r">
        <div className="flex">
          <div className="flex-shrink-0">
            <svg className="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="ml-3">
            <p className="text-sm font-medium text-red-800 dark:text-red-200">
              Error loading dashboard data
            </p>
            <p className="mt-1 text-sm text-red-700 dark:text-red-300">
              {error.message || 'Please try refreshing the page.'}
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
      {/* Welcome Section */}
      <div className="mb-10">
        <h1 className="text-3xl font-bold text-gray-800 dark:text-white">
          Admin Dashboard
        </h1>
        <p className="mt-2 text-base text-gray-600 dark:text-gray-300">
          Welcome back! Here's what's happening with your school today.
        </p>
      </div>

      {/* Quick Actions */}
      <div className="mb-10">
        <h2 className="text-xl font-semibold text-gray-800 dark:text-white mb-4">
          Quick Actions
        </h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {quickActions.map((action) => (
            <motion.button
              key={action.id}
              whileHover={{ y: -2 }}
              whileTap={{ scale: 0.98 }}
              onClick={action.onClick}
              className={`p-4 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 ${action.color} ${action.textColor} text-left`}
            >
              <div className="flex items-center">
                <div className="p-2 rounded-lg bg-opacity-20 mr-3">
                  {action.icon}
                </div>
                <span className="font-medium">{action.title}</span>
              </div>
            </motion.button>
          ))}
        </div>
      </div>

      {/* Stats Grid */}
      <div className="mb-10">
        <h2 className="text-xl font-semibold text-gray-800 dark:text-white mb-6">
          Overview
        </h2>
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <motion.div 
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3 }}
            className="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-100 dark:border-gray-700"
          >
            <div className="flex items-center">
              <div className="p-3 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 mr-4">
                <FaUserGraduate size={20} />
              </div>
              <div>
                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total Students</p>
                <p className="text-2xl font-bold text-gray-800 dark:text-white">{stats.totalStudents}</p>
              </div>
            </div>
          </motion.div>
          
          <motion.div 
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3, delay: 0.1 }}
            className="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-100 dark:border-gray-700"
          >
            <div className="flex items-center">
              <div className="p-3 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-300 mr-4">
                <FaChalkboardTeacher size={20} />
              </div>
              <div>
                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total Teachers</p>
                <p className="text-2xl font-bold text-gray-800 dark:text-white">{stats.totalTeachers}</p>
              </div>
            </div>
          </motion.div>
          
          <motion.div 
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3, delay: 0.2 }}
            className="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-100 dark:border-gray-700"
          >
            <div className="flex items-center">
              <div className="p-3 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-300 mr-4">
                <FaUsers size={20} />
              </div>
              <div>
                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total Parents</p>
                <p className="text-2xl font-bold text-gray-800 dark:text-white">{stats.totalParents}</p>
              </div>
            </div>
          </motion.div>
          
          <motion.div 
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.3, delay: 0.3 }}
            className="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-100 dark:border-gray-700"
          >
            <div className="flex items-center">
              <div className="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-300 mr-4">
                <FaDollarSign size={20} />
              </div>
              <div>
                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</p>
                <p className="text-2xl font-bold text-gray-800 dark:text-white">
                  ${stats.totalRevenue.toLocaleString()}
                </p>
              </div>
            </div>
          </motion.div>
        </div>
      </div>

      {/* Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3, delay: 0.1 }}
          className="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-100 dark:border-gray-700"
        >
          <h3 className="text-lg font-semibold text-gray-800 dark:text-white mb-4">Student Enrollment</h3>
          <div className="h-64">
            <ChartComponent 
              type="bar"
              data={studentData}
              options={chartOptions('student-chart')}
              chartId="student-chart"
            />
          </div>
        </motion.div>
        
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3, delay: 0.2 }}
          className="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-100 dark:border-gray-700"
        >
          <h3 className="text-lg font-semibold text-gray-800 dark:text-white mb-4">Attendance Rate</h3>
          <div className="h-64 flex flex-col items-center justify-center">
            <div className="relative w-40 h-40 mb-4">
              <ChartComponent 
                type="doughnut"
                data={attendanceData}
                options={chartOptions('attendance-chart')}
                chartId="attendance-doughnut-chart"
              />
            </div>
            <div className="absolute inset-0 flex items-center justify-center">
              <div className="text-center">
                <span className="text-2xl font-bold text-gray-800 dark:text-white">{stats.attendanceRate}%</span>
                <p className="text-sm text-gray-500 dark:text-gray-400">Attendance</p>
              </div>
            </div>
          </div>
        </motion.div>
        
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3, delay: 0.3 }}
          className="lg:col-span-3 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-100 dark:border-gray-700"
        >
          <h3 className="text-lg font-semibold text-gray-800 dark:text-white mb-4">Revenue Overview</h3>
          <div className="h-80">
            <ChartComponent 
              type="line"
              data={revenueData}
              options={chartOptions('revenue-chart')}
              chartId="revenue-chart"
            />
          </div>
        </motion.div>
        
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3, delay: 0.4 }}
          className="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-100 dark:border-gray-700"
        >
          <h3 className="text-lg font-semibold text-gray-800 dark:text-white mb-4">Performance Overview</h3>
          <div className="h-64">
            <Doughnut 
              key="performance-chart"
              options={chartOptions('performance-chart')} 
              data={performanceChartData} 
              redraw
            />
          </div>
        </motion.div>
        
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3, delay: 0.5 }}
          className="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-100 dark:border-gray-700"
        >
          <h3 className="text-lg font-semibold text-gray-800 dark:text-white mb-4">Attendance Overview</h3>
          <div className="h-64">
            <Line 
              key="attendance-chart"
              options={{
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    position: 'top',
                    labels: {
                      color: '#6B7280',
                      font: {
                        family: 'Inter, sans-serif',
                      },
                    },
                  },
                },
                scales: {
                  x: {
                    grid: {
                      display: false,
                    },
                    ticks: {
                      color: '#9CA3AF',
                    },
                  },
                  y: {
                    grid: {
                      color: 'rgba(229, 231, 235, 0.5)',
                    },
                    ticks: {
                      color: '#9CA3AF',
                      callback: function(value) {
                        return value.toLocaleString();
                      }
                    },
                  },
                },
              }}
              data={attendanceChartData}
              redraw
            />
          </div>
        </motion.div>
      </div>
      
      {/* Recent Activities */}
      <motion.div 
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.3, delay: 0.4 }}
        className="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-100 dark:border-gray-700"
      >
        <div className="flex justify-between items-center mb-6">
          <h3 className="text-xl font-semibold text-gray-800 dark:text-white">Recent Activities</h3>
          <button className="text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
            View All
          </button>
        </div>
        <div className="space-y-4">
          {recentActivities.length > 0 ? (
            recentActivities.map((activity, index) => (
              <motion.div 
                key={index} 
                initial={{ opacity: 0, x: -10 }}
                animate={{ opacity: 1, x: 0 }}
                transition={{ duration: 0.3, delay: 0.1 * index }}
                className="flex items-start p-4 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200"
              >
                <div className="p-2 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 rounded-lg mr-4">
                  <FaClipboardList className="w-5 h-5" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-base font-medium text-gray-800 dark:text-white">{activity.title}</p>
                  <p className="text-sm text-gray-600 dark:text-gray-300 mt-1">{activity.description}</p>
                  <p className="text-xs text-gray-400 dark:text-gray-500 mt-2">
                    {new Date(activity.timestamp).toLocaleString()}
                  </p>
                </div>
              </motion.div>
            ))
          ) : (
            <div className="text-center py-8">
              <div className="mx-auto w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-3">
                <FaClipboardList className="w-8 h-8 text-gray-400" />
              </div>
              <p className="text-gray-500 dark:text-gray-400">No recent activities found</p>
              <p className="text-sm text-gray-400 mt-1">New activities will appear here</p>
            </div>
          )}
        </div>
      </motion.div>
    </div>
  );
};

export default EnhancedAdminDashboard;
