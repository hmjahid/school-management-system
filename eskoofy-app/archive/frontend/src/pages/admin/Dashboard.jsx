import React, { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { 
  FaUserGraduate, 
  FaChalkboardTeacher, 
  FaUsers, 
  FaDollarSign, 
  FaClipboardList, 
  FaCalendarAlt,
  FaPlus,
  FaChartLine,
  FaUserPlus,
  FaLayerGroup,
  FaBell,
  FaFileInvoiceDollar
} from 'react-icons/fa';
import { Card, CardBody, CardHeader, CardTitle, CardText, Row, Col } from 'reactstrap';
import { Bar, Line, Doughnut } from 'react-chartjs-2';
import { Chart as ChartJS, registerables } from 'chart.js';
import { dashboardService } from '../../services/admin/dashboardService';
import LoadingSpinner from '../../components/common/LoadingSpinner';
import StatCard from '../../components/admin/StatCard';
import RecentActivity from '../../components/admin/RecentActivity';
import QuickActions from '../../components/admin/QuickActions';

// Register ChartJS components
ChartJS.register(...registerables);

const AdminDashboard = () => {
  const [dashboardData, setDashboardData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  
  // Debug mount
  useEffect(() => {
    console.log('AdminDashboard mounted');
    return () => console.log('AdminDashboard unmounted');
  }, []);

  // Fetch dashboard data
  const { data, isLoading: isDataLoading, error: fetchError } = useQuery(
    ['dashboardData'],
    dashboardService.fetchDashboardData,
    {
      refetchOnWindowFocus: false,
      retry: 2,
    }
  );

  useEffect(() => {
    if (data) {
      setDashboardData(data);
      setIsLoading(false);
    }
    if (fetchError) {
      setError(fetchError.message);
      setIsLoading(false);
    }
  }, [data, fetchError]);

  // Chart options
  const chartOptions = {
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
        ticks: {
          callback: (value) => {
            if (value >= 1000) {
              return `$${value / 1000}k`;
            }
            return `$${value}`;
          },
        },
      },
    },
  };

  // Debug logging
  console.log('Dashboard State:', {
    isLoading,
    isDataLoading,
    error,
    data,
    dashboardData,
    fetchError
  });

  // Loading and error states
  if (isLoading || isDataLoading) {
    return (
      <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '50vh' }}>
        <div className="text-center">
          <LoadingSpinner size="lg" />
          <p className="mt-2">Loading dashboard data...</p>
        </div>
      </div>
    );
  }

  if (error || fetchError || !dashboardData) {
    console.error('Dashboard Error:', { error, fetchError, data });
    return (
      <div className="container mt-5">
        <div className="alert alert-danger">
          <h4>Error loading dashboard</h4>
          <p className="mb-2">{error || fetchError?.message || 'Unable to load dashboard data.'}</p>
          <button 
            className="btn btn-sm btn-outline-secondary mt-2"
            onClick={() => window.location.reload()}
          >
            Retry
          </button>
          <div className="mt-3 small text-muted">
            <p className="mb-1">Troubleshooting tips:</p>
            <ul className="mb-0 pl-3">
              <li>Check your internet connection</li>
              <li>Verify the backend API is running</li>
              <li>Check browser console for detailed errors</li>
            </ul>
          </div>
        </div>
      </div>
    );
  }

  const { stats, charts, recentActivity, quickActions } = dashboardData;

  return (
    <div className="container-fluid">
      {/* Page Header */}
      <div className="d-flex justify-content-between align-items-center mb-4">
        <h1 className="h3 mb-0">Dashboard</h1>
        <div className="text-muted">
          Last updated: {new Date(dashboardData.lastUpdated).toLocaleString()}
        </div>
      </div>

      {/* Stats Cards */}
      <Row className="mb-4">
        <Col md={6} lg={3} className="mb-4">
          <StatCard
            title="Total Students"
            value={stats.totalStudents}
            icon={<FaUserGraduate size={24} />}
            trend={stats.studentsTrend || 0}
            trendLabel="vs last month"
            color="primary"
          />
        </Col>
        <Col md={6} lg={3} className="mb-4">
          <StatCard
            title="Total Teachers"
            value={stats.totalTeachers}
            icon={<FaChalkboardTeacher size={24} />}
            trend={stats.teachersTrend || 0}
            trendLabel="vs last month"
            color="success"
          />
        </Col>
        <Col md={6} lg={3} className="mb-4">
          <StatCard
            title="Total Classes"
            value={stats.totalClasses}
            icon={<FaLayerGroup size={24} />}
            trend={stats.classesTrend || 0}
            trendLabel="vs last month"
            color="info"
          />
        </Col>
        <Col md={6} lg={3} className="mb-4">
          <StatCard
            title="Total Revenue"
            value={`$${stats.totalRevenue?.toLocaleString()}`}
            icon={<FaDollarSign size={24} />}
            trend={stats.revenueGrowth || 0}
            trendLabel="vs last month"
            trendSuffix="%"
            color="warning"
          />
        </Col>
      </Row>

      {/* Main Content */}
      <Row>
        {/* Left Column - Charts */}
        <Col lg={8}>
          {/* Revenue Chart */}
          <Card className="mb-4">
            <CardHeader>
              <CardTitle tag="h5" className="mb-0">
                <FaChartLine className="me-2" />
                {charts.monthlyRevenue.title}
              </CardTitle>
            </CardHeader>
            <CardBody style={{ height: '300px' }}>
              <Line
                data={{
                  labels: charts.monthlyRevenue.labels,
                  datasets: [
                    {
                      label: 'Revenue',
                      data: charts.monthlyRevenue.data,
                      borderColor: 'rgba(75, 192, 192, 1)',
                      backgroundColor: 'rgba(75, 192, 192, 0.2)',
                      tension: 0.3,
                      fill: true,
                    },
                  ],
                }}
                options={{
                  ...chartOptions,
                  plugins: {
                    ...chartOptions.plugins,
                    tooltip: {
                      callbacks: {
                        label: (context) => {
                          return `$${context.raw.toLocaleString()}`;
                        },
                      },
                    },
                  },
                }}
              />
            </CardBody>
          </Card>

          {/* Additional Charts */}
          <Row>
            <Col md={6} className="mb-4">
              <Card className="h-100">
                <CardHeader>
                  <CardTitle tag="h5" className="mb-0">
                    <FaClipboardList className="me-2" />
                    {charts.studentPerformance.title}
                  </CardTitle>
                </CardHeader>
                <CardBody style={{ height: '250px' }}>
                  <Doughnut
                    data={{
                      labels: charts.studentPerformance.labels,
                      datasets: [
                        {
                          data: charts.studentPerformance.data,
                          backgroundColor: [
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(255, 159, 64, 0.6)',
                            'rgba(255, 99, 132, 0.6)',
                          ],
                          borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(255, 99, 132, 1)',
                          ],
                          borderWidth: 1,
                        },
                      ],
                    }}
                    options={{
                      ...chartOptions,
                      plugins: {
                        ...chartOptions.plugins,
                        legend: {
                          ...chartOptions.plugins.legend,
                          position: 'bottom',
                        },
                      },
                    }}
                  />
                </CardBody>
              </Card>
            </Col>
            <Col md={6} className="mb-4">
              <Card className="h-100">
                <CardHeader>
                  <CardTitle tag="h5" className="mb-0">
                    <FaUsers className="me-2" />
                    {charts.classDistribution.title}
                  </CardTitle>
                </CardHeader>
                <CardBody style={{ height: '250px' }}>
                  <Bar
                    data={{
                      labels: charts.classDistribution.labels,
                      datasets: [
                        {
                          label: 'Students',
                          data: charts.classDistribution.data,
                          backgroundColor: 'rgba(54, 162, 235, 0.6)',
                          borderColor: 'rgba(54, 162, 235, 1)',
                          borderWidth: 1,
                        },
                      ],
                    }}
                    options={{
                      ...chartOptions,
                      indexAxis: 'y',
                      scales: {
                        ...chartOptions.scales,
                        x: {
                          beginAtZero: true,
                        },
                      },
                    }}
                  />
                </CardBody>
              </Card>
            </Col>
          </Row>
        </Col>

        {/* Right Column - Quick Actions & Recent Activity */}
        <Col lg={4}>
          {/* Quick Actions */}
          <QuickActions actions={quickActions} />

          {/* Recent Activity */}
          <Card className="mb-4">
            <CardHeader>
              <div className="d-flex justify-content-between align-items-center">
                <CardTitle tag="h5" className="mb-0">
                  <FaBell className="me-2" />
                  Recent Activity
                </CardTitle>
                <a href="/admin/activity" className="btn btn-sm btn-outline-primary">
                  View All
                </a>
              </div>
            </CardHeader>
            <CardBody>
              <RecentActivity activities={recentActivity} />
            </CardBody>
          </Card>

          {/* Upcoming Deadlines */}
          <Card>
            <CardHeader>
              <div className="d-flex justify-content-between align-items-center">
                <CardTitle tag="h5" className="mb-0">
                  <FaCalendarAlt className="me-2" />
                  Upcoming Deadlines
                </CardTitle>
                <a href="/admin/calendar" className="btn btn-sm btn-outline-primary">
                  View Calendar
                </a>
              </div>
            </CardHeader>
            <CardBody>
              <div className="list-group list-group-flush">
                <div className="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <h6 className="mb-1">End of Term Exams</h6>
                    <p className="mb-0 text-muted small">Dec 15, 2023</p>
                  </div>
                  <span className="badge bg-warning rounded-pill">Exams</span>
                </div>
                <div className="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <h6 className="mb-1">Fee Payment Due</h6>
                    <p className="mb-0 text-muted small">Dec 5, 2023</p>
                  </div>
                  <span className="badge bg-danger rounded-pill">Finance</span>
                </div>
                <div className="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <h6 className="mb-1">Parent-Teacher Meeting</h6>
                    <p className="mb-0 text-muted small">Dec 10, 2023</p>
                  </div>
                  <span className="badge bg-info rounded-pill">Event</span>
                </div>
              </div>
            </CardBody>
          </Card>
        </Col>
      </Row>
    </div>
  );
};

export default AdminDashboard;
