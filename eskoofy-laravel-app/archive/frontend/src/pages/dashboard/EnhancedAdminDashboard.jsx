import { useEffect, useRef, useCallback } from 'react';
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
import { 
  fetchDashboardStats, 
  fetchAttendanceData, 
  fetchPerformanceData, 
  fetchFeeCollectionData 
} from '../../services/dashboardService';

// Register ChartJS components
import {
  Chart as ChartJS,
  LineController,
  BarController,
  DoughnutController,
  LineElement,
  PointElement,
  BarElement,
  ArcElement,
  CategoryScale,
  LinearScale,
  TimeScale,
  TimeSeriesScale,
  Title,
  Tooltip,
  Legend,
  Filler,
  Chart,
} from 'chart.js';

// Register ChartJS components
ChartJS.register(
  LineController,
  BarController,
  DoughnutController,
  LineElement,
  PointElement,
  BarElement,
  ArcElement,
  CategoryScale,
  LinearScale,
  TimeScale,
  TimeSeriesScale,
  Title,
  Tooltip,
  Legend,
  Filler
);

// Chart component with proper cleanup
const ChartComponent = ({ type, data, options, chartId }) => {
  const chartRef = useRef(null);
  const canvasId = `chart-${chartId || Math.random().toString(36).substr(2, 9)}`;
  
  useEffect(() => {
    return () => {
      if (chartRef.current) {
        chartRef.current.destroy();
        chartRef.current = null;
      }
      
      const existingChart = Chart.getChart(canvasId);
      if (existingChart) {
        existingChart.destroy();
      }
    };
  }, [canvasId]);
  
  useEffect(() => {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    const existingChart = Chart.getChart(canvas);
    if (existingChart) {
      existingChart.destroy();
    }
    
    const ctx = canvas.getContext('2d');
    chartRef.current = new Chart(ctx, {
      type,
      data,
      options: {
        ...options,
        responsive: true,
        maintainAspectRatio: false,
      },
    });
    
    return () => {
      if (chartRef.current) {
        chartRef.current.destroy();
        chartRef.current = null;
      }
    };
  }, [type, data, options, canvasId]);
  
  return <canvas id={canvasId} />;
};

const EnhancedAdminDashboard = () => {
  // Fetch all dashboard data using React Query
  const { data: dashboardData, isLoading: isDashboardLoading } = useQuery({
    queryKey: ['dashboardStats'],
    queryFn: fetchDashboardStats,
    staleTime: 5 * 60 * 1000,
  });

  const { data: attendanceData, isLoading: isAttendanceLoading } = useQuery({
    queryKey: ['attendanceData'],
    queryFn: fetchAttendanceData,
    staleTime: 5 * 60 * 1000,
  });

  const { data: performanceData, isLoading: isPerformanceLoading } = useQuery({
    queryKey: ['performanceData'],
    queryFn: fetchPerformanceData,
    staleTime: 5 * 60 * 1000,
  });

  const { data: feeData, isLoading: isFeeDataLoading } = useQuery({
    queryKey: ['feeData'],
    queryFn: fetchFeeCollectionData,
    staleTime: 5 * 60 * 1000,
  });

  const isLoading = isDashboardLoading || isAttendanceLoading || isPerformanceLoading || isFeeDataLoading;

  // Extract data from the query results
  const stats = dashboardData?.stats || {
    totalStudents: 0,
    totalTeachers: 0,
    totalParents: 0,
    totalRevenue: 0,
    attendanceRate: 0,
  };

  // Chart data
  const studentData = {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    datasets: [
      {
        label: 'Student Enrollment',
        data: [125, 190, 150, 200, 180, 220],
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
        data: [12500, 19000, 15000, 20000, 18000, 22000],
        borderColor: 'rgba(16, 185, 129, 1)',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        tension: 0.3,
        fill: true,
      },
    ],
  };

  const attendanceChartData = {
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

  const performanceChartData = {
    labels: ['A', 'B', 'C', 'D', 'F'],
    datasets: [
      {
        label: 'Grades Distribution',
        data: [28, 38, 22, 9, 3],
        backgroundColor: [
          'rgba(16, 185, 129, 0.7)',
          'rgba(59, 130, 246, 0.7)',
          'rgba(245, 158, 11, 0.7)',
          'rgba(249, 115, 22, 0.7)',
          'rgba(239, 68, 68, 0.7)',
        ],
        borderColor: [
          'rgba(16, 185, 129, 1)',
          'rgba(59, 130, 246, 1)',
          'rgba(245, 158, 11, 1)',
          'rgba(249, 115, 22, 1)',
          'rgba(239, 68, 68, 1)',
        ],
        borderWidth: 1,
      },
    ],
  };

  // Chart options
  const chartOptions = {
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
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        titleFont: {
          family: 'Inter, sans-serif',
          size: 14,
        },
        bodyFont: {
          family: 'Inter, sans-serif',
          size: 13,
        },
      },
    },
    scales: {
      x: {
        grid: {
          display: false,
          drawBorder: false,
        },
        ticks: {
          color: '#6B7280',
        },
      },
      y: {
        grid: {
          color: 'rgba(0, 0, 0, 0.05)',
          drawBorder: false,
        },
        ticks: {
          color: '#6B7280',
        },
      },
    },
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
              options={chartOptions}
              chartId="student-chart"
            />
          </div>
        </m.div>
        
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
                data={attendanceChartData}
                options={chartOptions}
                chartId="attendance-doughnut-chart"
              />
            </div>
            <div className="text-center">
              <span className="text-2xl font-bold text-gray-800 dark:text-white">{stats.attendanceRate}%</span>
              <p className="text-sm text-gray-500 dark:text-gray-400">Overall Attendance</p>
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
              options={chartOptions}
              chartId="revenue-chart"
            />
          </div>
        </motion.div>
        
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3, delay: 0.4 }}
          className="lg:col-span-3 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-100 dark:border-gray-700"
        >
          <h3 className="text-lg font-semibold text-gray-800 dark:text-white mb-4">Performance Overview</h3>
          <div className="h-64">
            <ChartComponent 
              type="doughnut"
              data={performanceChartData}
              options={chartOptions}
              chartId="performance-chart"
            />
          </div>
        </motion.div>
      </div>
    </div>
  );
};

export default EnhancedAdminDashboard;
