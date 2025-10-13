import { useState, useEffect } from "react";
import {
  FaUsers,
  FaUserGraduate,
  FaChalkboardTeacher,
  FaDollarSign,
  FaCalendarAlt,
  FaClipboardList,
  FaChartLine,
  FaBell,
} from "react-icons/fa";
import { Bar, Line, Doughnut } from "react-chartjs-2";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  ArcElement,
  PointElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from "chart.js";
import api from "../../services/api";
import { toast } from "react-hot-toast";

// Register ChartJS components
ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  ArcElement,
  PointElement,
  Title,
  Tooltip,
  Legend,
  Filler,
);

const AdminDashboard = () => {
  const [dashboardData, setDashboardData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    let isMounted = true;

    const fetchDashboardData = async () => {
      try {
        console.log("[AdminDashboard] Fetching dashboard data...");
        setIsLoading(true);
        setError(null);

        // Try to fetch from API first
        try {
          console.log("[AdminDashboard] Making API call to /admin/dashboard");
          const response = await api.get("/admin/dashboard", {
            timeout: 10000,
            headers: {
              "Cache-Control": "no-cache",
              "Authorization": `Bearer ${localStorage.getItem('token')}` // Ensure auth token is sent
            },
          });

          console.log("[AdminDashboard] API Response Status:", response.status);
          console.log("[AdminDashboard] Dashboard data received:", response.data);

          if (!isMounted) return;

          if (response.data) {
            console.log("[AdminDashboard] Setting dashboard data");
            setDashboardData(response.data);
            setError(null);
            return;
          }
        } catch (apiError) {
          console.log("[AdminDashboard] API call failed, using mock data:", apiError.message);
        }

        // Fallback to mock data if API fails
        if (!isMounted) return;

        const mockData = {
          stats: {
            totalStudents: 1245,
            totalTeachers: 68,
            totalClasses: 42,
            totalStaff: 25,
            totalRevenue: 125400,
            attendanceRate: 92.5,
            pendingAssignments: 15,
            upcomingEvents: 3,
            newStudentsThisMonth: 42,
            revenueGrowth: 12.4,
          },
          charts: {
            monthlyRevenue: {
              labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
              data: [8500, 9200, 10200, 9800, 11000, 12500],
              title: "Monthly Revenue",
              type: "line",
              color: "primary",
            },
            attendanceTrend: {
              labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
              data: [92, 95, 90, 94, 93, 89],
              title: "Attendance Rate",
              type: "line",
              color: "info",
              suffix: "%",
            },
            classDistribution: {
              labels: ["Class 1", "Class 2", "Class 3", "Class 4", "Class 5"],
              data: [45, 42, 48, 50, 46],
              title: "Students by Class",
              type: "doughnut",
              color: "warning",
            },
          },
          recentActivity: [
            {
              id: 1,
              type: "enrollment",
              title: "New Student Enrollment",
              message: "5 new students enrolled",
              time: "2 hours ago",
              icon: "user-plus",
              color: "success",
            },
            {
              id: 2,
              type: "payment",
              title: "Fee Payment Received",
              message: "Monthly fee collected from 12 students",
              time: "1 day ago",
              icon: "dollar-sign",
              color: "primary",
            },
            {
              id: 3,
              type: "attendance",
              title: "Attendance Marked",
              message: "Attendance marked for Class 10-A (95% present)",
              time: "2 days ago",
              icon: "clipboard-check",
              color: "info",
            },
            {
              id: 4,
              type: "assignment",
              title: "New Assignment",
              message: "New assignment added for Mathematics",
              time: "3 days ago",
              icon: "book",
              color: "warning",
            },
          ],
          quickActions: [
            {
              id: 1,
              title: "Add New Student",
              description: "Register a new student",
              icon: "user-plus",
              url: "/admin/students/create",
              permission: "students.create",
            },
            {
              id: 2,
              title: "Create New Class",
              description: "Add a new class/section",
              icon: "layers",
              url: "/admin/classes/create",
              permission: "classes.create",
            },
            {
              id: 3,
              title: "Record Payment",
              description: "Record manual payment",
              icon: "dollar-sign",
              url: "/admin/payments/create",
              permission: "payments.create",
            },
            {
              id: 4,
              title: "Send Announcement",
              description: "Send notification to users",
              icon: "bell",
              url: "/admin/announcements/create",
              permission: "announcements.create",
            },
          ],
        };

        setDashboardData(mockData);
        setError("Using demo data. Connect to backend for live data.");

      } catch (error) {
        console.error("[AdminDashboard] Error:", error);

        if (!isMounted) return;

        setError("Failed to load dashboard data. Please try again later.");

        // Even on error, show mock data
        const mockData = {
          stats: {
            totalStudents: 1245,
            totalTeachers: 68,
            totalClasses: 42,
            totalStaff: 25,
            totalRevenue: 125400,
            attendanceRate: 92.5,
            pendingAssignments: 15,
            upcomingEvents: 3,
            newStudentsThisMonth: 42,
            revenueGrowth: 12.4,
          },
          charts: {
            monthlyRevenue: {
              labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
              data: [8500, 9200, 10200, 9800, 11000, 12500],
              title: "Monthly Revenue",
              type: "line",
              color: "primary",
            },
            attendanceTrend: {
              labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
              data: [92, 95, 90, 94, 93, 89],
              title: "Attendance Rate",
              type: "line",
              color: "info",
              suffix: "%",
            },
            classDistribution: {
              labels: ["Class 1", "Class 2", "Class 3", "Class 4", "Class 5"],
              data: [45, 42, 48, 50, 46],
              title: "Students by Class",
              type: "doughnut",
              color: "warning",
            },
          },
          recentActivity: [
            {
              id: 1,
              type: "enrollment",
              title: "New Student Enrollment",
              message: "5 new students enrolled",
              time: "2 hours ago",
              icon: "user-plus",
              color: "success",
            },
          ],
          quickActions: [
            {
              id: 1,
              title: "Add New Student",
              description: "Register a new student",
              icon: "user-plus",
              url: "/admin/students/create",
              permission: "students.create",
            },
          ],
        };

        setDashboardData(mockData);
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    };

    fetchDashboardData();

    return () => {
      isMounted = false;
    };
  }, []);

  // Debug: log render state to help diagnose empty dashboard
  console.log('[AdminDashboard] Render state:', { isLoading, error, dashboardData, apiBase: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8001/api' });

  const StatCard = ({
    icon: Icon,
    title,
    value,
    change,
    changeType = "increase",
    color = "indigo",
  }) => (
    <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
      <div className="flex items-center justify-between">
        <div className="flex-1">
          <p className="text-sm font-medium text-gray-500">{title}</p>
          <p className="mt-2 text-3xl font-semibold text-gray-900">{value}</p>
          {change !== undefined && (
            <p
              className={`mt-2 text-sm flex items-center ${changeType === "increase" ? "text-green-600" : "text-red-600"}`}
            >
              <span className="mr-1">
                {changeType === "increase" ? "↑" : "↓"}
              </span>
              {Math.abs(change)}% from last month
            </p>
          )}
        </div>
        <div className={`p-3 rounded-full bg-${color}-50 text-${color}-600`}>
          <Icon className="w-8 h-8" />
        </div>
      </div>
    </div>
  );

  const ActivityItem = ({ activity }) => {
    const getIconComponent = () => {
      switch (activity.type) {
        case "enrollment":
        case "admission":
          return FaUserGraduate;
        case "payment":
          return FaDollarSign;
        case "exam":
        case "assignment":
          return FaClipboardList;
        case "attendance":
          return FaCalendarAlt;
        case "announcement":
          return FaBell;
        default:
          return FaUsers;
      }
    };

    const Icon = getIconComponent();

    const getColorClass = () => {
      switch (activity.color || activity.type) {
        case "success":
        case "enrollment":
          return "text-green-500 bg-green-50";
        case "primary":
        case "payment":
          return "text-blue-500 bg-blue-50";
        case "warning":
        case "assignment":
          return "text-yellow-500 bg-yellow-50";
        case "info":
        case "attendance":
          return "text-cyan-500 bg-cyan-50";
        case "danger":
        case "announcement":
          return "text-red-500 bg-red-50";
        default:
          return "text-gray-500 bg-gray-50";
      }
    };

    return (
      <div className="flex items-start py-3 px-4 hover:bg-gray-50 rounded-lg transition-colors">
        <div className={`flex-shrink-0 p-2 rounded-lg ${getColorClass()}`}>
          <Icon className="w-5 h-5" />
        </div>
        <div className="ml-4 flex-1">
          <p className="text-sm font-medium text-gray-900">{activity.title}</p>
          <p className="text-sm text-gray-500 mt-1">{activity.message}</p>
          <p className="text-xs text-gray-400 mt-1">{activity.time}</p>
        </div>
      </div>
    );
  };

  if (isLoading) {
    console.log("[AdminDashboard] Rendering loading state");
    return (
      <div className="flex flex-col items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  if (!dashboardData) {
    console.log("[AdminDashboard] Dashboard data before render:", dashboardData);
    // Use an empty fallback object instead of referencing `mockData` which is out of scope here
    const data = dashboardData || {};
    console.log("[AdminDashboard] Data being used for render:", data);
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] p-6 text-center">
        <div className="p-4 bg-red-50 text-red-700 rounded-lg max-w-md">
          <h3 className="font-medium text-lg">Error Loading Dashboard</h3>
          <p className="mt-2 text-sm">
            Unable to load dashboard data. Please try again later.
          </p>
          <button
            onClick={() => window.location.reload()}
            className="mt-4 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
          >
            Retry
          </button>
        </div>
      </div>
    );
  }

  // If there's an error but dashboardData exists, show a banner in the page header
  if (error && !dashboardData) {
    console.log("[AdminDashboard] Rendering error state without data:", error);
    return (
      <div className="p-6">
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
          <strong className="font-bold">Error: </strong>
          <span className="block sm:inline">{error}</span>
        </div>
      </div>
    );
  }

  const stats = dashboardData.stats || {};
  const charts = dashboardData.charts || {};
  const recentActivity = dashboardData.recentActivity || [];
  const quickActions = dashboardData.quickActions || [];

  // Chart configurations
  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: true,
        position: "top",
      },
      tooltip: {
        mode: "index",
        intersect: false,
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          precision: 0,
        },
      },
    },
  };

  const lineChartOptions = {
    ...chartOptions,
    elements: {
      line: {
        tension: 0.4,
      },
    },
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Admin Dashboard</h2>
          <p className="mt-1 text-sm text-gray-500">
            Welcome back! Here's what's happening with your school today.
          </p>
        </div>
        {error && (
          <div className="px-4 py-2 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p className="text-sm text-yellow-800">{error}</p>
          </div>
        )}
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          icon={FaUserGraduate}
          title="Total Students"
          value={stats.totalStudents?.toLocaleString() || "0"}
          change={stats.studentGrowth || 5.2}
          color="indigo"
        />
        <StatCard
          icon={FaChalkboardTeacher}
          title="Total Teachers"
          value={stats.totalTeachers?.toLocaleString() || "0"}
          change={stats.teacherGrowth || 2.1}
          color="green"
        />
        <StatCard
          icon={FaUsers}
          title="Total Classes"
          value={stats.totalClasses?.toLocaleString() || "0"}
          change={stats.classGrowth || 1.5}
          color="blue"
        />
        <StatCard
          icon={FaDollarSign}
          title="Total Revenue"
          value={`$${stats.totalRevenue?.toLocaleString() || "0"}`}
          change={stats.revenueGrowth || 12.4}
          color="purple"
        />
      </div>

      {/* Secondary Stats */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
          <div className="flex items-center">
            <div className="p-2 bg-green-50 rounded-lg">
              <FaChartLine className="w-5 h-5 text-green-600" />
            </div>
            <div className="ml-3">
              <p className="text-sm text-gray-500">Attendance Rate</p>
              <p className="text-xl font-semibold text-gray-900">
                {stats.attendanceRate || 0}%
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
          <div className="flex items-center">
            <div className="p-2 bg-yellow-50 rounded-lg">
              <FaClipboardList className="w-5 h-5 text-yellow-600" />
            </div>
            <div className="ml-3">
              <p className="text-sm text-gray-500">Pending Assignments</p>
              <p className="text-xl font-semibold text-gray-900">
                {stats.pendingAssignments || 0}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
          <div className="flex items-center">
            <div className="p-2 bg-purple-50 rounded-lg">
              <FaCalendarAlt className="w-5 h-5 text-purple-600" />
            </div>
            <div className="ml-3">
              <p className="text-sm text-gray-500">Upcoming Events</p>
              <p className="text-xl font-semibold text-gray-900">
                {stats.upcomingEvents || 0}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100">
          <div className="flex items-center">
            <div className="p-2 bg-blue-50 rounded-lg">
              <FaUserGraduate className="w-5 h-5 text-blue-600" />
            </div>
            <div className="ml-3">
              <p className="text-sm text-gray-500">New Students</p>
              <p className="text-xl font-semibold text-gray-900">
                {stats.newStudentsThisMonth || 0}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Charts Row */}
      {charts.monthlyRevenue && charts.attendanceTrend && (
        <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
          <div className="p-6 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Monthly Revenue
            </h3>
            <div className="h-72">
              <Line
                data={{
                  labels: charts.monthlyRevenue.labels,
                  datasets: [
                    {
                      label: "Revenue ($)",
                      data: charts.monthlyRevenue.data,
                      backgroundColor: "rgba(99, 102, 241, 0.1)",
                      borderColor: "rgba(99, 102, 241, 1)",
                      borderWidth: 2,
                      fill: true,
                    },
                  ],
                }}
                options={lineChartOptions}
              />
            </div>
          </div>
          <div className="p-6 bg-white rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Attendance Trend
            </h3>
            <div className="h-72">
              <Line
                data={{
                  labels: charts.attendanceTrend.labels,
                  datasets: [
                    {
                      label: "Attendance (%)",
                      data: charts.attendanceTrend.data,
                      backgroundColor: "rgba(16, 185, 129, 0.1)",
                      borderColor: "rgba(16, 185, 129, 1)",
                      borderWidth: 2,
                      fill: true,
                    },
                  ],
                }}
                options={lineChartOptions}
              />
            </div>
          </div>
        </div>
      )}

      {/* Bottom Row */}
      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        {/* Recent Activities */}
        {recentActivity.length > 0 && (
          <div className="lg:col-span-2">
            <div className="p-6 bg-white rounded-lg shadow-sm border border-gray-100">
              <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg font-semibold text-gray-900">
                  Recent Activities
                </h3>
                <button className="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                  View All
                </button>
              </div>
              <div className="space-y-2">
                {recentActivity.slice(0, 5).map((activity) => (
                  <ActivityItem key={activity.id} activity={activity} />
                ))}
              </div>
            </div>
          </div>
        )}

        {/* Quick Actions & Class Distribution */}
        <div className="space-y-5">
          {/* Students by Class Chart */}
          {charts.classDistribution && (
            <div className="p-6 bg-white rounded-lg shadow-sm border border-gray-100">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">
                Students by Class
              </h3>
              <div className="h-64">
                <Doughnut
                  data={{
                    labels: charts.classDistribution.labels,
                    datasets: [
                      {
                        data: charts.classDistribution.data,
                        backgroundColor: [
                          "rgba(99, 102, 241, 0.8)",
                          "rgba(16, 185, 129, 0.8)",
                          "rgba(245, 158, 11, 0.8)",
                          "rgba(239, 68, 68, 0.8)",
                          "rgba(139, 92, 246, 0.8)",
                        ],
                        borderWidth: 2,
                        borderColor: "#fff",
                      },
                    ],
                  }}
                  options={{
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                      legend: {
                        position: "bottom",
                      },
                    },
                  }}
                />
              </div>
            </div>
          )}

          {/* Quick Actions */}
          {quickActions.length > 0 && (
            <div className="p-6 bg-white rounded-lg shadow-sm border border-gray-100">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">
                Quick Actions
              </h3>
              <div className="space-y-3">
                {quickActions.slice(0, 4).map((action) => (
                  <button
                    key={action.id}
                    className="flex items-center w-full px-4 py-3 text-sm font-medium text-left text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                  >
                    <div className="w-10 h-10 flex items-center justify-center bg-indigo-100 rounded-lg mr-3">
                      {action.icon === "user-plus" && (
                        <FaUserGraduate className="w-5 h-5 text-indigo-600" />
                      )}
                      {action.icon === "layers" && (
                        <FaUsers className="w-5 h-5 text-indigo-600" />
                      )}
                      {action.icon === "dollar-sign" && (
                        <FaDollarSign className="w-5 h-5 text-indigo-600" />
                      )}
                      {action.icon === "bell" && (
                        <FaBell className="w-5 h-5 text-indigo-600" />
                      )}
                    </div>
                    <div className="flex-1">
                      <p className="font-medium">{action.title}</p>
                      <p className="text-xs text-gray-500">
                        {action.description}
                      </p>
                    </div>
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AdminDashboard;
