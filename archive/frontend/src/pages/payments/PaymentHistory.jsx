import React, { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useQuery } from 'react-query';
import { format } from 'date-fns';
import { 
  SearchIcon, 
  FilterIcon, 
  DownloadIcon, 
  ArrowSmRightIcon,
  CheckCircleIcon,
  XCircleIcon,
  ClockIcon,
  ExclamationCircleIcon
} from '@heroicons/react/outline';
import axios from 'axios';

const PaymentHistory = () => {
  const navigate = useNavigate();
  const [searchTerm, setSearchTerm] = useState('');
  const [filters, setFilters] = useState({
    status: '',
    fromDate: '',
    toDate: '',
    gateway: '',
  });
  const [showFilters, setShowFilters] = useState(false);
  const [gateways, setGateways] = useState([]);

  // Fetch payment gateways for filter dropdown
  useEffect(() => {
    const fetchGateways = async () => {
      try {
        const response = await axios.get('/api/payments/gateways');
        setGateways(response.data.data || []);
      } catch (error) {
        console.error('Error fetching gateways:', error);
      }
    };

    fetchGateways();
  }, []);

  // Fetch payment history with filters
  const fetchPayments = async ({ queryKey }) => {
    const [_, { search, status, fromDate, toDate, gateway }] = queryKey;
    const params = new URLSearchParams();
    
    if (search) params.append('search', search);
    if (status) params.append('status', status);
    if (fromDate) params.append('date_from', fromDate);
    if (toDate) params.append('date_to', toDate);
    if (gateway) params.append('gateway', gateway);
    
    const response = await axios.get(`/api/payments?${params.toString()}`);
    return response.data.data;
  };

  const { data: payments, isLoading, isError, refetch } = useQuery(
    ['payments', { 
      search: searchTerm, 
      status: filters.status,
      fromDate: filters.fromDate,
      toDate: filters.toDate,
      gateway: filters.gateway,
    }],
    fetchPayments,
    {
      keepPreviousData: true,
      refetchOnWindowFocus: false,
    }
  );

  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const resetFilters = () => {
    setFilters({
      status: '',
      fromDate: '',
      toDate: '',
      gateway: '',
    });
    setSearchTerm('');
  };

  const getStatusBadge = (status) => {
    const statusMap = {
      completed: {
        bg: 'bg-green-100 text-green-800',
        icon: <CheckCircleIcon className="h-4 w-4 mr-1" />,
        label: 'Completed'
      },
      pending: {
        bg: 'bg-yellow-100 text-yellow-800',
        icon: <ClockIcon className="h-4 w-4 mr-1" />,
        label: 'Pending'
      },
      failed: {
        bg: 'bg-red-100 text-red-800',
        icon: <XCircleIcon className="h-4 w-4 mr-1" />,
        label: 'Failed'
      },
      refunded: {
        bg: 'bg-blue-100 text-blue-800',
        icon: <ArrowSmRightIcon className="h-4 w-4 mr-1 transform rotate-180" />,
        label: 'Refunded'
      },
      cancelled: {
        bg: 'bg-gray-100 text-gray-800',
        icon: <XCircleIcon className="h-4 w-4 mr-1" />,
        label: 'Cancelled'
      },
      expired: {
        bg: 'bg-gray-100 text-gray-800',
        icon: <ExclamationCircleIcon className="h-4 w-4 mr-1" />,
        label: 'Expired'
      },
    };

    const statusInfo = statusMap[status] || { bg: 'bg-gray-100 text-gray-800', label: status };
    
    return (
      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusInfo.bg}`}>
        {statusInfo.icon}
        {statusInfo.label}
      </span>
    );
  };

  const getMethodBadge = (method) => {
    const methodMap = {
      bkash: 'bg-green-100 text-green-800',
      nagad: 'bg-purple-100 text-purple-800',
      rocket: 'bg-blue-100 text-blue-800',
      cash: 'bg-gray-100 text-gray-800',
      bank_transfer: 'bg-indigo-100 text-indigo-800',
      cheque: 'bg-yellow-100 text-yellow-800',
      stripe: 'bg-indigo-100 text-indigo-800',
      paypal: 'bg-blue-100 text-blue-800',
      default: 'bg-gray-100 text-gray-800',
    };

    return (
      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${methodMap[method] || methodMap.default} capitalize`}>
        {method.replace('_', ' ')}
      </span>
    );
  };

  const exportToCSV = async () => {
    try {
      const response = await axios.get('/api/payments/export?format=csv', {
        responseType: 'blob',
      });
      
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `payments-${new Date().toISOString().split('T')[0]}.csv`);
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      console.error('Error exporting payments:', error);
    }
  };

  return (
    <div className="px-4 sm:px-6 lg:px-8 py-8">
      <div className="sm:flex sm:items-center">
        <div className="sm:flex-auto">
          <h1 className="text-2xl font-semibold text-gray-900">Payment History</h1>
          <p className="mt-2 text-sm text-gray-700">
            A list of all your payment transactions including details and status.
          </p>
        </div>
        <div className="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
          <button
            type="button"
            onClick={() => navigate('/payments/new')}
            className="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-auto"
          >
            Make a Payment
          </button>
        </div>
      </div>

      {/* Search and Filter Bar */}
      <div className="mt-6">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div className="relative rounded-md shadow-sm w-full md:w-1/3">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <SearchIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
            </div>
            <input
              type="text"
              name="search"
              id="search"
              className="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md h-10"
              placeholder="Search payments..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
          
          <div className="flex items-center space-x-3
">
            <button
              type="button"
              onClick={() => setShowFilters(!showFilters)}
              className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <FilterIcon className="h-4 w-4 mr-1" />
              Filters
            </button>
            
            <button
              type="button"
              onClick={exportToCSV}
              className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <DownloadIcon className="h-4 w-4 mr-1" />
              Export
            </button>
          </div>
        </div>
        
        {/* Filters Panel */}
        {showFilters && (
          <div className="mt-4 bg-white p-4 rounded-lg shadow border border-gray-200">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div>
                <label htmlFor="status" className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select
                  id="status"
                  name="status"
                  className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                  value={filters.status}
                  onChange={handleFilterChange}
                >
                  <option value="">All Statuses</option>
                  <option value="completed">Completed</option>
                  <option value="pending">Pending</option>
                  <option value="failed">Failed</option>
                  <option value="refunded">Refunded</option>
                  <option value="cancelled">Cancelled</option>
                  <option value="expired">Expired</option>
                </select>
              </div>
              
              <div>
                <label htmlFor="gateway" className="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                <select
                  id="gateway"
                  name="gateway"
                  className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                  value={filters.gateway}
                  onChange={handleFilterChange}
                >
                  <option value="">All Methods</option>
                  {gateways.map((gateway) => (
                    <option key={gateway.code} value={gateway.code}>
                      {gateway.name}
                    </option>
                  ))}
                </select>
              </div>
              
              <div>
                <label htmlFor="fromDate" className="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input
                  type="date"
                  name="fromDate"
                  id="fromDate"
                  className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  value={filters.fromDate}
                  onChange={handleFilterChange}
                />
              </div>
              
              <div>
                <label htmlFor="toDate" className="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input
                  type="date"
                  name="toDate"
                  id="toDate"
                  className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  value={filters.toDate}
                  onChange={handleFilterChange}
                  min={filters.fromDate}
                />
              </div>
            </div>
            
            <div className="mt-4 flex justify-end space-x-3">
              <button
                type="button"
                onClick={resetFilters}
                className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Reset Filters
              </button>
              <button
                type="button"
                onClick={() => setShowFilters(false)}
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Apply Filters
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Payment List */}
      <div className="mt-8 flex flex-col">
        <div className="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div className="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
              {isLoading ? (
                <div className="flex justify-center items-center p-12">
                  <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
                </div>
              ) : isError ? (
                <div className="text-center p-8">
                  <p className="text-red-600">Error loading payments. Please try again.</p>
                  <button
                    onClick={() => refetch()}
                    className="mt-2 text-blue-600 hover:text-blue-800"
                  >
                    Retry
                  </button>
                </div>
              ) : payments?.length === 0 ? (
                <div className="text-center p-8">
                  <p className="text-gray-500">No payments found.</p>
                  <button
                    onClick={() => navigate('/payments/new')}
                    className="mt-2 text-blue-600 hover:text-blue-800"
                  >
                    Make your first payment
                  </button>
                </div>
              ) : (
                <table className="min-w-full divide-y divide-gray-300">
                  <thead className="bg-gray-50">
                    <tr>
                      <th scope="col" className="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                        Invoice
                      </th>
                      <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                        Date
                      </th>
                      <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                        Method
                      </th>
                      <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                        Amount
                      </th>
                      <th scope="col" className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                        Status
                      </th>
                      <th scope="col" className="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span className="sr-only">View</span>
                      </th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-200 bg-white">
                    {payments?.map((payment) => (
                      <tr key={payment.id} className="hover:bg-gray-50">
                        <td className="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                          {payment.invoice_number}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                          {format(new Date(payment.created_at), 'MMM d, yyyy')}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                          {getMethodBadge(payment.payment_method)}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-900 font-medium">
                          {payment.currency || 'BDT'} {parseFloat(payment.amount).toFixed(2)}
                        </td>
                        <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                          {getStatusBadge(payment.payment_status)}
                        </td>
                        <td className="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                          <Link
                            to={`/payments/${payment.id}`}
                            className="text-blue-600 hover:text-blue-900"
                          >
                            View<span className="sr-only">, {payment.invoice_number}</span>
                          </Link>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
            
            {/* Pagination would go here */}
            {/* <Pagination
              currentPage={currentPage}
              totalPages={totalPages}
              onPageChange={handlePageChange}
            /> */}
          </div>
        </div>
      </div>
    </div>
  );
};

export default PaymentHistory;
