import React, { useState, useEffect } from 'react';
import { FiDownload, FiPrinter, FiFilter, FiCalendar, FiUsers, FiBook, FiDollarSign, FiBarChart2 } from 'react-icons/fi';
import { Line, Bar, Pie } from 'react-chartjs-2';
import { saveAs } from 'file-saver';
import { jsPDF } from 'jspdf';
import 'jspdf-autotable';
import { toast } from 'react-hot-toast';
import api from '../../services/api';
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
  Filler,
} from 'chart.js';

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

const ReportsPage = () => {
  const [loading, setLoading] = useState(true);
  const [reportType, setReportType] = useState('attendance');
  const [dateRange, setDateRange] = useState({
    startDate: new Date(new Date().setMonth(new Date().getMonth() - 1)).toISOString().split('T')[0],
    endDate: new Date().toISOString().split('T')[0],
  });
  const [filters, setFilters] = useState({
    class_id: 'all',
    grade_level: 'all',
    status: 'all',
  });
  const [reportData, setReportData] = useState(null);
  const [availableClasses, setAvailableClasses] = useState([]);
  const [availableGrades, setAvailableGrades] = useState([
    { id: 'all', name: 'All Grades' },
    { id: '1', name: 'Grade 1' },
    { id: '2', name: 'Grade 2' },
    { id: '3', name: 'Grade 3' },
    { id: '4', name: 'Grade 4' },
    { id: '5', name: 'Grade 5' },
    { id: '6', name: 'Grade 6' },
    { id: '7', name: 'Grade 7' },
    { id: '8', name: 'Grade 8' },
    { id: '9', name: 'Grade 9' },
    { id: '10', name: 'Grade 10' },
    { id: '11', name: 'Grade 11' },
    { id: '12', name: 'Grade 12' },
  ]);

  const reportTypes = [
    { id: 'attendance', name: 'Attendance', icon: <FiUsers className="h-5 w-5" /> },
    { id: 'academic', name: 'Academic Performance', icon: <FiBook className="h-5 w-5" /> },
    { id: 'financial', name: 'Financial', icon: <FiDollarSign className="h-5 w-5" /> },
    { id: 'enrollment', name: 'Enrollment', icon: <FiBarChart2 className="h-5 w-5" /> },
  ];

  // Sample data for charts (replace with actual API data)
  const sampleChartData = {
    attendance: {
      labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      datasets: [
        {
          label: 'Present',
          data: [85, 90, 88, 92],
          borderColor: 'rgb(16, 185, 129)',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          fill: true,
          tension: 0.4,
        },
        {
          label: 'Absent',
          data: [15, 10, 12, 8],
          borderColor: 'rgb(239, 68, 68)',
          backgroundColor: 'rgba(239, 68, 68, 0.1)',
          fill: true,
          tension: 0.4,
        },
      ],
    },
    academic: {
      labels: ['Math', 'Science', 'English', 'History', 'Art'],
      datasets: [
        {
          label: 'Average Score',
          data: [85, 78, 92, 88, 95],
          backgroundColor: [
            'rgba(99, 102, 241, 0.7)',
            'rgba(59, 130, 246, 0.7)',
            'rgba(16, 185, 129, 0.7)',
            'rgba(245, 158, 11, 0.7)',
            'rgba(139, 92, 246, 0.7)',
          ],
          borderColor: [
            'rgba(99, 102, 241, 1)',
            'rgba(59, 130, 246, 1)',
            'rgba(16, 185, 129, 1)',
            'rgba(245, 158, 11, 1)',
            'rgba(139, 92, 246, 1)',
          ],
          borderWidth: 1,
        },
      ],
    },
    financial: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
      datasets: [
        {
          label: 'Income',
          data: [5000, 6000, 5500, 7000, 8000, 7500],
          borderColor: 'rgb(16, 185, 129)',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          fill: true,
          tension: 0.4,
        },
        {
          label: 'Expenses',
          data: [3000, 4000, 3500, 4500, 5000, 4000],
          borderColor: 'rgb(239, 68, 68)',
          backgroundColor: 'rgba(239, 68, 68, 0.1)',
          fill: true,
          tension: 0.4,
        },
      ],
    },
    enrollment: {
      labels: ['2020', '2021', '2022', '2023'],
      datasets: [
        {
          label: 'Total Students',
          data: [450, 520, 580, 620],
          borderColor: 'rgb(99, 102, 241)',
          backgroundColor: 'rgba(99, 102, 241, 0.1)',
          fill: true,
          tension: 0.4,
        },
      ],
    },
  };

  const chartOptions = {
    responsive: true,
    plugins: {
      legend: {
        position: 'top',
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            let label = context.dataset.label || '';
            if (label) {
              label += ': ';
            }
            if (context.parsed.y !== null) {
              if (reportType === 'financial') {
                label += new Intl.NumberFormat('en-US', {
                  style: 'currency',
                  currency: 'USD',
                }).format(context.parsed.y);
              } else if (reportType === 'attendance') {
                label += context.parsed.y + '%';
              } else {
                label += context.parsed.y;
              }
            }
            return label;
          }
        }
      },
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: function(value) {
            if (reportType === 'financial') {
              return '$' + value.toLocaleString();
            } else if (reportType === 'attendance') {
              return value + '%';
            }
            return value;
          }
        }
      }
    },
  };

  useEffect(() => {
    fetchClasses();
    generateReport();
  }, [reportType, dateRange, filters]);

  const fetchClasses = async () => {
    try {
      const response = await api.get('/api/classes', {
        params: { simple: true }
      });
      setAvailableClasses([
        { id: 'all', name: 'All Classes' },
        ...response.data
      ]);
    } catch (error) {
      console.error('Error fetching classes:', error);
      toast.error('Failed to load classes');
    }
  };

  const generateReport = async () => {
    try {
      setLoading(true);
      // In a real app, you would make an API call to generate the report
      // const response = await api.get(`/api/reports/${reportType}`, {
      //   params: { ...dateRange, ...filters }
      // });
      // setReportData(response.data);
      
      // For demo purposes, we'll use sample data
      setTimeout(() => {
        setReportData({
          summary: {
            total: 100,
            present: 85,
            absent: 10,
            late: 5,
            percentage: 85,
          },
          details: Array(10).fill().map((_, i) => ({
            id: i + 1,
            name: `Student ${i + 1}`,
            class: `Class ${Math.ceil(Math.random() * 5)}`,
            present: Math.random() > 0.2 ? 'Present' : 'Absent',
            date: new Date().toISOString().split('T')[0],
            remarks: Math.random() > 0.8 ? 'Late' : '',
          })),
        });
        setLoading(false);
      }, 1000);
    } catch (error) {
      console.error('Error generating report:', error);
      toast.error('Failed to generate report');
      setLoading(false);
    }
  };

  const handleDateChange = (e) => {
    const { name, value } = e.target;
    setDateRange(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const exportToCSV = () => {
    if (!reportData) return;
    
    const headers = Object.keys(reportData.details[0]).join(',');
    const rows = reportData.details.map(item => 
      Object.values(item).map(value => 
        `"${value}"`
      ).join(',')
    ).join('\n');
    
    const csvContent = `${headers}\n${rows}`;
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    saveAs(blob, `${reportType}_report_${new Date().toISOString().split('T')[0]}.csv`);
    
    toast.success('Report exported to CSV');
  };

  const exportToPDF = () => {
    if (!reportData) return;
    
    const doc = new jsPDF();
    const title = `${reportType.charAt(0).toUpperCase() + reportType.slice(1)} Report`;
    const dateRangeText = `${new Date(dateRange.startDate).toLocaleDateString()} - ${new Date(dateRange.endDate).toLocaleDateString()}`;
    
    // Add title and date range
    doc.setFontSize(18);
    doc.text(title, 14, 22);
    doc.setFontSize(11);
    doc.setTextColor(100);
    doc.text(`Date Range: ${dateRangeText}`, 14, 30);
    
    // Add summary section
    doc.setFontSize(14);
    doc.setTextColor(0, 0, 0);
    doc.text('Summary', 14, 45);
    
    doc.setFontSize(11);
    doc.text(`Total: ${reportData.summary.total}`, 14, 55);
    doc.text(`Present: ${reportData.summary.present}`, 60, 55);
    doc.text(`Absent: ${reportData.summary.absent}`, 110, 55);
    doc.text(`Percentage: ${reportData.summary.percentage}%`, 160, 55);
    
    // Add details table
    doc.setFontSize(14);
    doc.text('Details', 14, 75);
    
    const tableColumn = Object.keys(reportData.details[0]).map(key => 
      key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1')
    );
    const tableRows = reportData.details.map(item => Object.values(item));
    
    doc.autoTable({
      head: [tableColumn],
      body: tableRows,
      startY: 80,
      styles: { fontSize: 10 },
      headStyles: { fillColor: [59, 130, 246] },
    });
    
    // Save the PDF
    doc.save(`${reportType}_report_${new Date().toISOString().split('T')[0]}.pdf`);
    
    toast.success('Report exported to PDF');
  };

  const renderReportContent = () => {
    if (loading) {
      return (
        <div className="flex justify-center items-center py-12">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
        </div>
      );
    }

    if (!reportData) {
      return (
        <div className="text-center py-12">
          <div className="mx-auto h-12 w-12 text-gray-400">
            <FiBarChart2 className="h-full w-full" />
          </div>
          <h3 className="mt-2 text-sm font-medium text-gray-900">No data available</h3>
          <p className="mt-1 text-sm text-gray-500">
            Adjust your filters and try again.
          </p>
        </div>
      );
    }

    return (
      <div className="space-y-8">
        {/* Summary Cards */}
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="p-5">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                  <FiUsers className="h-6 w-6 text-white" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 truncate">
                      Total
                    </dt>
                    <dd className="flex items-baseline">
                      <div className="text-2xl font-semibold text-gray-900">
                        {reportData.summary.total}
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="p-5">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-green-500 rounded-md p-3">
                  <FiUsers className="h-6 w-6 text-white" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 truncate">
                      Present
                    </dt>
                    <dd className="flex items-baseline">
                      <div className="text-2xl font-semibold text-gray-900">
                        {reportData.summary.present}
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="p-5">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-red-500 rounded-md p-3">
                  <FiUsers className="h-6 w-6 text-white" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 truncate">
                      Absent
                    </dt>
                    <dd className="flex items-baseline">
                      <div className="text-2xl font-semibold text-gray-900">
                        {reportData.summary.absent}
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="p-5">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-blue-500 rounded-md p-3">
                  <FiBarChart2 className="h-6 w-6 text-white" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 truncate">
                      Percentage
                    </dt>
                    <dd className="flex items-baseline">
                      <div className="text-2xl font-semibold text-gray-900">
                        {reportData.summary.percentage}%
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Chart */}
        <div className="bg-white p-6 rounded-lg shadow">
          <h3 className="text-lg font-medium text-gray-900 mb-4">
            {reportType === 'attendance' && 'Attendance Overview'}
            {reportType === 'academic' && 'Academic Performance'}
            {reportType === 'financial' && 'Financial Overview'}
            {reportType === 'enrollment' && 'Enrollment Trends'}
          </h3>
          <div className="h-80">
            {reportType === 'academic' ? (
              <Pie data={sampleChartData[reportType]} options={chartOptions} />
            ) : (
              <Line data={sampleChartData[reportType] || sampleChartData.attendance} options={chartOptions} />
            )}
          </div>
        </div>

        {/* Details Table */}
        <div className="bg-white shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 className="text-lg font-medium text-gray-900">Detailed Report</h3>
          </div>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  {reportData.details.length > 0 && Object.keys(reportData.details[0]).map((key) => (
                    <th 
                      key={key}
                      scope="col" 
                      className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                    >
                      {key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {reportData.details.map((row, rowIndex) => (
                  <tr key={rowIndex} className="hover:bg-gray-50">
                    {Object.values(row).map((cell, cellIndex) => (
                      <td key={cellIndex} className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {cell}
                      </td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className="container mx-auto px-4 py-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Reports</h1>
          <p className="mt-1 text-sm text-gray-500">
            Generate and analyze various reports
          </p>
        </div>
        <div className="mt-4 md:mt-0 flex space-x-3">
          <button
            onClick={exportToPDF}
            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <FiDownload className="-ml-1 mr-2 h-5 w-5 text-gray-500" />
            Export PDF
          </button>
          <button
            onClick={exportToCSV}
            className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <FiDownload className="-ml-1 mr-2 h-5 w-5" />
            Export CSV
          </button>
        </div>
      </div>

      {/* Report Type Selector */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Select Report Type</h3>
          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {reportTypes.map((type) => (
              <button
                key={type.id}
                type="button"
                onClick={() => setReportType(type.id)}
                className={`relative rounded-lg border px-6 py-5 shadow-sm flex items-center space-x-3 hover:border-gray-400 focus:outline-none ${
                  reportType === type.id ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 bg-white'
                }`}
              >
                <div className={`flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center ${
                  reportType === type.id ? 'bg-indigo-600' : 'bg-gray-200'
                }`}>
                  {React.cloneElement(type.icon, {
                    className: `h-6 w-6 ${reportType === type.id ? 'text-white' : 'text-gray-600'}`
                  })}
                </div>
                <div className="flex-1 min-w-0">
                  <span className="text-sm font-medium text-gray-900 text-left">
                    {type.name}
                  </span>
                </div>
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div className="px-4 py-5 sm:p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-medium text-gray-900">Filters</h3>
            <div className="flex items-center">
              <FiFilter className="h-5 w-5 text-gray-400 mr-2" />
              <span className="text-sm text-gray-500">Filter Results</span>
            </div>
          </div>
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <label htmlFor="startDate" className="block text-sm font-medium text-gray-700 mb-1">
                Start Date
              </label>
              <div className="relative rounded-md shadow-sm">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <FiCalendar className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  type="date"
                  name="startDate"
                  id="startDate"
                  value={dateRange.startDate}
                  onChange={handleDateChange}
                  className="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md h-10"
                />
              </div>
            </div>

            <div>
              <label htmlFor="endDate" className="block text-sm font-medium text-gray-700 mb-1">
                End Date
              </label>
              <div className="relative rounded-md shadow-sm">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <FiCalendar className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  type="date"
                  name="endDate"
                  id="endDate"
                  value={dateRange.endDate}
                  onChange={handleDateChange}
                  className="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md h-10"
                />
              </div>
            </div>

            <div>
              <label htmlFor="class_id" className="block text-sm font-medium text-gray-700 mb-1">
                Class
              </label>
              <select
                id="class_id"
                name="class_id"
                value={filters.class_id}
                onChange={handleFilterChange}
                className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md h-10"
              >
                {availableClasses.map((cls) => (
                  <option key={cls.id} value={cls.id}>
                    {cls.name}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label htmlFor="grade_level" className="block text-sm font-medium text-gray-700 mb-1">
                Grade Level
              </label>
              <select
                id="grade_level"
                name="grade_level"
                value={filters.grade_level}
                onChange={handleFilterChange}
                className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md h-10"
              >
                {availableGrades.map((grade) => (
                  <option key={grade.id} value={grade.id}>
                    {grade.name}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>
      </div>

      {/* Report Content */}
      {renderReportContent()}
    </div>
  );
};

export default ReportsPage;
