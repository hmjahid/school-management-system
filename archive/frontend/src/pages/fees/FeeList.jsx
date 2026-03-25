import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { 
  PencilIcon, 
  TrashIcon, 
  EyeIcon, 
  PlusIcon,
  SearchIcon,
  FilterIcon,
  DownloadIcon,
  ArrowPathIcon
} from '@heroicons/react/24/outline';
import ExportButton from '../common/ExportButton';
import { toast } from 'react-toastify';
import useFeeUpdates from '../../hooks/useFeeUpdates';

const FeeList = () => {
  const [fees, setFees] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [filters, setFilters] = useState({
    status: '',
    feeType: '',
    classId: '',
    search: ''
  });
  const [showFilters, setShowFilters] = useState(false);

  // Handle successful export
  const handleExportSuccess = ({ format, filename, timestamp }) => {
    console.log(`Exported ${filename}.${format} at ${timestamp}`);
  };
  
  // Handle export error
  const handleExportError = (error) => {
    console.error('Export error:', error);
  };

  // Fetch fees from API
  const fetchFees = useCallback(async () => {
    try {
      setLoading(true);
      // In a real app, you would make an API call here
      // const response = await axios.get('/api/fees', { 
      //   params: { 
      //     ...filters, 
      //     search: searchTerm,
      //     _t: Date.now() // Cache buster
      //   } 
      // });
      // setFees(response.data.data || []);
      
      // Mock data for now
      setTimeout(() => {
        setFees([
          {
            id: 1,
            name: 'Tuition Fee - January 2025',
            code: 'TUI-JAN25',
            description: 'Tuition fee for January 2025',
            amount: 5000,
            feeType: 'tuition',
            status: 'active',
            dueDate: '2025-01-31',
            classId: 1,
            className: 'Class 1',
            updatedAt: new Date().toISOString()
          },
          {
            id: 2,
            name: 'Admission Fee',
            code: 'ADM-2025',
            description: 'One-time admission fee',
            amount: 10000,
            feeType: 'admission',
            status: 'active',
            dueDate: '2025-12-31',
            classId: null,
            className: 'All Classes'
          },
          {
            id: 3,
            name: 'Exam Fee - Mid Term',
            code: 'EXAM-MID25',
            description: 'Mid-term examination fee',
            amount: 2000,
            feeType: 'exam',
            status: 'inactive',
            dueDate: '2025-06-30',
            classId: 1,
            className: 'Class 1'
          }
        ]);
        setLoading(false);
      }, 500);
    } catch (error) {
      console.error('Error fetching fees:', error);
      toast.error('Failed to load fees. Please try again.');
      setLoading(false);
    }
  }, [filters, searchTerm]);

  // Set up real-time updates
  const handleFeeCreated = useCallback((newFee) => {
    setFees(prevFees => {
      // Check if fee already exists to avoid duplicates
      const exists = prevFees.some(fee => fee.id === newFee.id);
      return exists ? prevFees : [newFee, ...prevFees];
    });
  }, []);

  const handleFeeUpdated = useCallback((updatedFee) => {
    setFees(prevFees => 
      prevFees.map(fee => 
        fee.id === updatedFee.id ? { ...fee, ...updatedFee, updatedAt: new Date().toISOString() } : fee
      )
    );
  }, []);

  const handleFeeDeleted = useCallback((deletedFeeId) => {
    setFees(prevFees => prevFees.filter(fee => fee.id !== deletedFeeId));
  }, []);

  // Initialize real-time updates
  const { isConnected: isWebSocketConnected } = useFeeUpdates({
    onFeeCreated: handleFeeCreated,
    onFeeUpdated: handleFeeUpdated,
    onFeeDeleted: handleFeeDeleted,
    enableToasts: true
  });

  // Initial fetch and refetch when filters change
  useEffect(() => {
    fetchFees();
  }, [filters, searchTerm, fetchFees]);

  // Show connection status
  useEffect(() => {
    if (isWebSocketConnected) {
      toast.dismiss('websocket-status');
    } else {
      toast.info('Connecting to real-time updates...', {
        toastId: 'websocket-status',
        autoClose: false,
        closeOnClick: false,
        closeButton: false,
        draggable: false,
      });
    }

    return () => {
      toast.dismiss('websocket-status');
    };
  }, [isWebSocketConnected]);

  // Handle fee deletion
  const handleDelete = async (id) => {
    if (window.confirm('Are you sure you want to delete this fee?')) {
      try {
        // In a real app, you would make an API call here
        // await axios.delete(`/api/fees/${id}`);
        
        // Update local state
        setFees(fees.filter(fee => fee.id !== id));
        toast.success('Fee deleted successfully');
      } catch (error) {
        console.error('Error deleting fee:', error);
        toast.error('Failed to delete fee. Please try again.');
      }
    }
  };

  // Format currency
  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-BD', {
      style: 'currency',
      currency: 'BDT',
      minimumFractionDigits: 2
    }).format(amount);
  };

  // Get status badge class
  const getStatusBadgeClass = (status) => {
    switch (status) {
      case 'active':
        return 'bg-green-100 text-green-800';
      case 'inactive':
        return 'bg-gray-100 text-gray-800';
      case 'archived':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  // Get fee type label
  const getFeeTypeLabel = (type) => {
    const types = {
      tuition: 'Tuition',
      admission: 'Admission',
      exam: 'Exam',
      transport: 'Transport',
      library: 'Library',
      other: 'Other'
    };
    return types[type] || type;
  };

  return (
    <div className="px-4 sm:px-6 lg:px-8">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 space-y-4 sm:space-y-0">
        <div className="flex items-center space-x-3">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Fees Management</h1>
            <p className="mt-1 text-sm text-gray-500 flex items-center">
              <span>Manage and track all fee records</span>
              <span className="ml-2 flex items-center">
                <span className={`h-2 w-2 rounded-full mr-1 ${isWebSocketConnected ? 'bg-green-500' : 'bg-yellow-500 animate-pulse'}`}></span>
                <span className="text-xs text-gray-400">
                  {isWebSocketConnected ? 'Live' : 'Connecting...'}
                </span>
              </span>
            </p>
          </div>
          <button
            onClick={fetchFees}
            disabled={loading}
            className="p-1.5 text-gray-500 hover:text-gray-700 transition-colors"
            title="Refresh"
          >
            <ArrowPathIcon className={`h-5 w-5 ${loading ? 'animate-spin' : ''}`} />
          </button>
        </div>
        <div className="flex flex-wrap gap-3">
          <ExportButton 
            type="fees"
            filters={filters}
            onExport={handleExportSuccess}
            onError={handleExportError}
            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <DownloadIcon className="-ml-1 mr-2 h-5 w-5 text-gray-500" aria-hidden="true" />
            Export
          </ExportButton>
          <Link
            to="/fees/new"
            className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <PlusIcon className="-ml-1 mr-2 h-5 w-5" aria-hidden="true" />
            Add New Fee
          </Link>
        </div>
      </div>

      {/* Search and Filter */}
      <div className="mt-6">
        <div className="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
          <div className="relative rounded-md shadow-sm w-full md:max-w-xs">
            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <SearchIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
            </div>
            <input
              type="text"
              name="search"
              id="search"
              className="block w-full rounded-md border-gray-300 pl-10 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Search fees..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
          <div className="flex items-center space-x-3">
            <button
              type="button"
              className="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
              onClick={() => setShowFilters(!showFilters)}
            >
              <FilterIcon className="-ml-1 mr-2 h-5 w-5 text-gray-500" aria-hidden="true" />
              Filter
            </button>
          </div>
        </div>

        {/* Filter Panel */}
        {showFilters && (
          <div className="mt-4 p-4 bg-gray-50 rounded-md">
            <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
              <div className="sm:col-span-2">
                <label htmlFor="status" className="block text-sm font-medium text-gray-700">
                  Status
                </label>
                <select
                  id="status"
                  name="status"
                  className="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                  value={filters.status}
                  onChange={(e) => setFilters({ ...filters, status: e.target.value })}
                >
                  <option value="">All Status</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="archived">Archived</option>
                </select>
              </div>

              <div className="sm:col-span-2">
                <label htmlFor="feeType" className="block text-sm font-medium text-gray-700">
                  Fee Type
                </label>
                <select
                  id="feeType"
                  name="feeType"
                  className="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                  value={filters.feeType}
                  onChange={(e) => setFilters({ ...filters, feeType: e.target.value })}
                >
                  <option value="">All Types</option>
                  <option value="tuition">Tuition</option>
                  <option value="admission">Admission</option>
                  <option value="exam">Exam</option>
                  <option value="transport">Transport</option>
                  <option value="library">Library</option>
                  <option value="other">Other</option>
                </select>
              </div>

              <div className="sm:col-span-2">
                <label htmlFor="classId" className="block text-sm font-medium text-gray-700">
                  Class
                </label>
                <select
                  id="classId"
                  name="classId"
                  className="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                  value={filters.classId}
                  onChange={(e) => setFilters({ ...filters, classId: e.target.value })}
                >
                  <option value="">All Classes</option>
                  <option value="1">Class 1</option>
                  <option value="2">Class 2</option>
                  <option value="3">Class 3</option>
                </select>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Fees Table */}
      <div className="mt-8 flex flex-col">
        <div className="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div className="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            {loading ? (
              <div className="flex justify-center items-center h-64">
                <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500"></div>
              </div>
            ) : (
              <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table className="min-w-full divide-y divide-gray-300">
                  <thead className="bg-gray-50">
                    <tr>
                      <th scope="col" className="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                        Name
                      </th>
                      <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                        Type
                      </th>
                      <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                        Amount
                      </th>
                      <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                        Class
                      </th>
                      <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                        Due Date
                      </th>
                      <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                        Status
                      </th>
                      <th scope="col" className="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span className="sr-only">Actions</span>
                      </th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-200 bg-white">
                    {fees.length === 0 ? (
                      <tr>
                        <td colSpan="7" className="px-3 py-4 text-sm text-gray-500 text-center">
                          No fees found. Create a new fee to get started.
                        </td>
                      </tr>
                    ) : (
                      fees.map((fee) => (
                        <tr key={fee.id}>
                          <td className="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                            <div className="font-medium text-indigo-600 hover:text-indigo-900">
                              <Link to={`/fees/${fee.id}`}>{fee.name}</Link>
                            </div>
                            <div className="text-gray-500 text-xs mt-1">{fee.code}</div>
                          </td>
                          <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {getFeeTypeLabel(fee.feeType)}
                          </td>
                          <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-900 font-medium">
                            {formatCurrency(fee.amount)}
                          </td>
                          <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {fee.className || 'N/A'}
                          </td>
                          <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            {new Date(fee.dueDate).toLocaleDateString()}
                          </td>
                          <td className="whitespace-nowrap px-3 py-4">
                            <span
                              className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${getStatusBadgeClass(
                                fee.status
                              )}`}
                            >
                              {fee.status.charAt(0).toUpperCase() + fee.status.slice(1)}
                            </span>
                          </td>
                          <td className="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                            <div className="flex items-center space-x-2 justify-end">
                              <Link
                                to={`/fees/${fee.id}`}
                                className="text-indigo-600 hover:text-indigo-900"
                                title="View"
                              >
                                <EyeIcon className="h-4 w-4" />
                              </Link>
                              <Link
                                to={`/fees/${fee.id}/edit`}
                                className="text-indigo-600 hover:text-indigo-900"
                                title="Edit"
                              >
                                <PencilIcon className="h-4 w-4" />
                              </Link>
                              <button
                                onClick={() => handleDelete(fee.id)}
                                className="text-red-600 hover:text-red-900"
                                title="Delete"
                              >
                                <TrashIcon className="h-4 w-4" />
                              </button>
                            </div>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default FeeList;
