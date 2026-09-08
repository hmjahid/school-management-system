import React, { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { Link, useNavigate } from 'react-router-dom';
import { format, parseISO } from 'date-fns';
import { 
  CheckCircleIcon, 
  XCircleIcon, 
  ClockIcon, 
  ExclamationTriangleIcon,
  ArrowPathIcon,
  FunnelIcon,
  XMarkIcon,
  ArrowDownTrayIcon,
  DocumentMagnifyingGlassIcon,
  CheckBadgeIcon,
  XMarkCircleIcon,
  ArrowsRightLeftIcon
} from '@heroicons/react/24/outline';
import { toast } from 'react-toastify';
import { getRefunds, processRefund, cancelRefund, exportRefunds } from '../../services/api/refunds';
import { formatCurrency } from '../../utils/formatters';
import Pagination from '../common/Pagination';
import StatusBadge from '../common/StatusBadge';
import ConfirmDialog from '../common/ConfirmDialog';
import Button from '../common/Button';
import SearchInput from '../common/SearchInput';
import { useAuth } from '../../contexts/AuthContext';

const statuses = {
  pending: {
    icon: ClockIcon,
    color: 'yellow',
    text: 'Pending',
  },
  processing: {
    icon: ArrowPathIcon,
    color: 'blue',
    text: 'Processing',
  },
  completed: {
    icon: CheckCircleIcon,
    color: 'green',
    text: 'Completed',
  },
  failed: {
    icon: XMarkCircleIcon,
    color: 'red',
    text: 'Failed',
  },
  cancelled: {
    icon: XMarkIcon,
    color: 'gray',
    text: 'Cancelled',
  },
};

const RefundList = () => {
  const { hasPermission } = useAuth();
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  
  // State for filters and pagination
  const [filters, setFilters] = useState({
    status: '',
    payment_id: '',
    user_id: '',
    date_from: '',
    date_to: '',
    search: '',
    sort_by: 'created_at',
    sort_order: 'desc',
  });
  
  const [page, setPage] = useState(1);
  const [selectedRefund, setSelectedRefund] = useState(null);
  const [showFilters, setShowFilters] = useState(false);
  const [showProcessDialog, setShowProcessDialog] = useState(false);
  const [showCancelDialog, setShowCancelDialog] = useState(false);
  const [transactionId, setTransactionId] = useState('');
  const [exporting, setExporting] = useState(false);

  // Fetch refunds with filters and pagination
  const {
    data: refundsData,
    isLoading,
    isError,
    error,
    refetch,
  } = useQuery(
    ['refunds', { ...filters, page }],
    () => getRefunds({ ...filters, page, per_page: 10 }),
    {
      keepPreviousData: true,
      staleTime: 5 * 60 * 1000, // 5 minutes
    }
  );

  // Mutations
  const processRefundMutation = useMutation(
    ({ refundId, transactionId }) => processRefund(refundId, { transaction_id: transactionId }),
    {
      onSuccess: () => {
        toast.success('Refund processed successfully');
        queryClient.invalidateQueries('refunds');
        setShowProcessDialog(false);
        setSelectedRefund(null);
        setTransactionId('');
      },
      onError: (error) => {
        toast.error(error.response?.data?.message || 'Failed to process refund');
      },
    }
  );

  const cancelRefundMutation = useMutation(
    ({ refundId, reason }) => cancelRefund(refundId, { reason: reason || 'Cancelled by admin' }),
    {
      onSuccess: () => {
        toast.success('Refund cancelled successfully');
        queryClient.invalidateQueries('refunds');
        setShowCancelDialog(false);
        setSelectedRefund(null);
      },
      onError: (error) => {
        toast.error(error.response?.data?.message || 'Failed to cancel refund');
      },
    }
  );

  // Handle filter changes
  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters(prev => ({
      ...prev,
      [name]: value,
    }));
    setPage(1); // Reset to first page when filters change
  };

  // Handle search
  const handleSearch = (searchTerm) => {
    setFilters(prev => ({
      ...prev,
      search: searchTerm,
    }));
    setPage(1);
  };

  // Handle sort
  const handleSort = (column) => {
    setFilters(prev => ({
      ...prev,
      sort_by: column,
      sort_order: filters.sort_by === column && filters.sort_order === 'asc' ? 'desc' : 'asc',
    }));
  };

  // Handle export
  const handleExport = async () => {
    try {
      setExporting(true);
      const response = await exportRefunds(filters);
      
      // Create a download link
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `refunds-${new Date().toISOString().split('T')[0]}.csv`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      
      toast.success('Refunds exported successfully');
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to export refunds');
    } finally {
      setExporting(false);
    }
  };

  // Get sort indicator
  const getSortIndicator = (column) => {
    if (filters.sort_by !== column) return null;
    return filters.sort_order === 'asc' ? '↑' : '↓';
  };

  // Render loading state
  if (isLoading && !refundsData) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  // Render error state
  if (isError) {
    return (
      <div className="rounded-md bg-red-50 p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <XCircleIcon className="h-5 w-5 text-red-400" aria-hidden="true" />
          </div>
          <div className="ml-3">
            <h3 className="text-sm font-medium text-red-800">
              Failed to load refunds
            </h3>
            <div className="mt-2 text-sm text-red-700">
              <p>{error.message}</p>
            </div>
            <div className="mt-4">
              <button
                type="button"
                onClick={() => refetch()}
                className="rounded-md bg-red-50 text-sm font-medium text-red-700 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
              >
                Retry
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  const { data: refunds, meta } = refundsData || { data: [], meta: {} };

  return (
    <div className="px-4 sm:px-6 lg:px-8">
      <div className="sm:flex sm:items-center">
        <div className="sm:flex-auto">
          <h1 className="text-2xl font-semibold text-gray-900">Refund Management</h1>
          <p className="mt-2 text-sm text-gray-700">
            View and manage refund requests from customers.
          </p>
        </div>
        <div className="mt-4 sm:mt-0 sm:ml-16 sm:flex-none space-x-2">
          <Button
            variant="secondary"
            onClick={() => setShowFilters(!showFilters)}
            icon={showFilters ? XMarkIcon : FunnelIcon}
          >
            {showFilters ? 'Hide Filters' : 'Filters'}
          </Button>
          <Button
            variant="secondary"
            onClick={handleExport}
            disabled={exporting || refunds.length === 0}
            icon={ArrowDownTrayIcon}
          >
            {exporting ? 'Exporting...' : 'Export'}
          </Button>
        </div>
      </div>

      {/* Filters */}
      {showFilters && (
        <div className="mt-6 bg-white shadow overflow-hidden sm:rounded-lg p-4 mb-6">
          <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
            <div className="sm:col-span-2">
              <label htmlFor="status" className="block text-sm font-medium text-gray-700">
                Status
              </label>
              <select
                id="status"
                name="status"
                value={filters.status}
                onChange={handleFilterChange}
                className="mt-1 block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
              >
                <option value="">All Statuses</option>
                {Object.entries(statuses).map(([key, { text }]) => (
                  <option key={key} value={key}>
                    {text}
                  </option>
                ))}
              </select>
            </div>

            <div className="sm:col-span-2">
              <label htmlFor="date_from" className="block text-sm font-medium text-gray-700">
                From Date
              </label>
              <input
                type="date"
                name="date_from"
                id="date_from"
                value={filters.date_from}
                onChange={handleFilterChange}
                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
            </div>

            <div className="sm:col-span-2">
              <label htmlFor="date_to" className="block text-sm font-medium text-gray-700">
                To Date
              </label>
              <input
                type="date"
                name="date_to"
                id="date_to"
                value={filters.date_to}
                onChange={handleFilterChange}
                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
            </div>

            <div className="sm:col-span-6">
              <SearchInput
                placeholder="Search by ID, payment ID, or user..."
                onSearch={handleSearch}
                initialValue={filters.search}
              />
            </div>
          </div>
        </div>
      )}

      <div className="mt-8 flex flex-col">
        <div className="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div className="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div className="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
              <table className="min-w-full divide-y divide-gray-300">
                <thead className="bg-gray-50">
                  <tr>
                    <th
                      scope="col"
                      className="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6 cursor-pointer"
                      onClick={() => handleSort('id')}
                    >
                      ID {getSortIndicator('id')}
                    </th>
                    <th
                      scope="col"
                      className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 cursor-pointer"
                      onClick={() => handleSort('amount')}
                    >
                      Amount {getSortIndicator('amount')}
                    </th>
                    <th
                      scope="col"
                      className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                    >
                      Payment
                    </th>
                    <th
                      scope="col"
                      className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 cursor-pointer"
                      onClick={() => handleSort('status')}
                    >
                      Status {getSortIndicator('status')}
                    </th>
                    <th
                      scope="col"
                      className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 cursor-pointer"
                      onClick={() => handleSort('created_at')}
                    >
                      Requested {getSortIndicator('created_at')}
                    </th>
                    <th
                      scope="col"
                      className="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                    >
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200 bg-white">
                  {refunds.length === 0 ? (
                    <tr>
                      <td colSpan="6" className="py-8 text-center text-sm text-gray-500">
                        <div className="flex flex-col items-center justify-center space-y-2">
                          <DocumentMagnifyingGlassIcon className="h-12 w-12 text-gray-300" />
                          <p>No refunds found</p>
                          {Object.values(filters).some(Boolean) && (
                            <button
                              type="button"
                              onClick={() => {
                                setFilters({
                                  status: '',
                                  payment_id: '',
                                  user_id: '',
                                  date_from: '',
                                  date_to: '',
                                  search: '',
                                  sort_by: 'created_at',
                                  sort_order: 'desc',
                                });
                                setPage(1);
                              }}
                              className="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                            >
                              Clear all filters
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ) : (
                    refunds.map((refund) => {
                      const StatusIcon = statuses[refund.status]?.icon || XCircleIcon;
                      const statusColor = statuses[refund.status]?.color || 'gray';
                      
                      return (
                        <tr key={refund.id} className="hover:bg-gray-50">
                          <td className="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                            <div className="flex items-center">
                              <StatusIcon
                                className={`h-5 w-5 mr-2 text-${statusColor}-400`}
                                aria-hidden="true"
                              />
                              <Link
                                to={`/refunds/${refund.id}`}
                                className="text-indigo-600 hover:text-indigo-900 hover:underline"
                              >
                                #{refund.id}
                              </Link>
                            </div>
                          </td>
                          <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-900 font-medium">
                            {formatCurrency(refund.amount, refund.currency)}
                          </td>
                          <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            <div className="flex flex-col">
                              <Link
                                to={`/payments/${refund.payment_id}`}
                                className="text-indigo-600 hover:text-indigo-900 hover:underline"
                              >
                                Payment #{refund.payment_id}
                              </Link>
                              {refund.user && (
                                <span className="text-gray-500 text-xs">
                                  {refund.user.name} ({refund.user.email})
                                </span>
                              )}
                            </div>
                          </td>
                          <td className="whitespace-nowrap px-3 py-4 text-sm">
                            <StatusBadge status={refund.status} statuses={statuses} />
                          </td>
                          <td className="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                            <div className="flex flex-col">
                              <span>
                                {format(parseISO(refund.created_at), 'MMM d, yyyy')}
                              </span>
                              <span className="text-xs text-gray-400">
                                {format(parseISO(refund.created_at), 'h:mm a')}
                              </span>
                              {refund.processed_at && (
                                <div className="mt-1 text-xs text-gray-400">
                                  <ArrowsRightLeftIcon className="h-3 w-3 inline-block mr-1" />
                                  {format(parseISO(refund.processed_at), 'MMM d, yyyy h:mm a')}
                                </div>
                              )}
                            </div>
                          </td>
                          <td className="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 space-x-2">
                            <Link
                              to={`/refunds/${refund.id}`}
                              className="text-indigo-600 hover:text-indigo-900"
                              title="View Details"
                            >
                              <DocumentMagnifyingGlassIcon className="h-5 w-5" />
                              <span className="sr-only">View</span>
                            </Link>
                            
                            {hasPermission('refunds.process') && refund.status === 'pending' && (
                              <button
                                type="button"
                                onClick={() => {
                                  setSelectedRefund(refund);
                                  setShowProcessDialog(true);
                                }}
                                className="text-green-600 hover:text-green-900"
                                title="Process Refund"
                              >
                                <CheckBadgeIcon className="h-5 w-5" />
                                <span className="sr-only">Process</span>
                              </button>
                            )}
                            
                            {hasPermission('refunds.cancel') && 
                              ['pending', 'processing'].includes(refund.status) && (
                              <button
                                type="button"
                                onClick={() => {
                                  setSelectedRefund(refund);
                                  setShowCancelDialog(true);
                                }}
                                className="text-red-600 hover:text-red-900"
                                title="Cancel Refund"
                              >
                                <XMarkCircleIcon className="h-5 w-5" />
                                <span className="sr-only">Cancel</span>
                              </button>
                            )}
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
              
              {/* Pagination */}
              {meta?.total > 0 && (
                <div className="border-t border-gray-200 px-4 py-3 flex items-center justify-between sm:px-6">
                  <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                      <p className="text-sm text-gray-700">
                        Showing <span className="font-medium">{meta.from}</span> to{' '}
                        <span className="font-medium">{meta.to}</span> of{' '}
                        <span className="font-medium">{meta.total}</span> results
                      </p>
                    </div>
                    <div>
                      <Pagination
                        currentPage={meta.current_page}
                        totalPages={meta.last_page}
                        onPageChange={(page) => setPage(page)}
                        showPageNumbers={true}
                      />
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Process Refund Dialog */}
      <ConfirmDialog
        isOpen={showProcessDialog}
        onClose={() => {
          setShowProcessDialog(false);
          setSelectedRefund(null);
          setTransactionId('');
        }}
        title="Process Refund"
        confirmText="Process Refund"
        onConfirm={() => {
          if (!transactionId.trim()) {
            toast.error('Transaction ID is required');
            return;
          }
          processRefundMutation.mutate({
            refundId: selectedRefund?.id,
            transactionId: transactionId.trim(),
          });
        }}
        confirmVariant="primary"
        isProcessing={processRefundMutation.isLoading}
      >
        <div className="space-y-4">
          <p className="text-sm text-gray-500">
            You are about to process a refund of{' '}
            <span className="font-medium">
              {selectedRefund && formatCurrency(selectedRefund.amount, selectedRefund.currency)}
            </span>{' '}
            for payment #{selectedRefund?.payment_id}.
          </p>
          
          <div>
            <label htmlFor="transaction_id" className="block text-sm font-medium text-gray-700">
              Transaction ID <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              id="transaction_id"
              value={transactionId}
              onChange={(e) => setTransactionId(e.target.value)}
              className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Enter the transaction ID from payment gateway"
              required
            />
            <p className="mt-1 text-xs text-gray-500">
              This is the reference ID provided by the payment gateway for this refund.
            </p>
          </div>
          
          {selectedRefund?.reason && (
            <div className="mt-2">
              <p className="text-sm font-medium text-gray-700">Refund Reason:</p>
              <p className="mt-1 text-sm text-gray-600 bg-gray-50 p-2 rounded">
                {selectedRefund.reason}
              </p>
            </div>
          )}
        </div>
      </ConfirmDialog>

      {/* Cancel Refund Dialog */}
      <ConfirmDialog
        isOpen={showCancelDialog}
        onClose={() => {
          setShowCancelDialog(false);
          setSelectedRefund(null);
        }}
        title="Cancel Refund"
        confirmText="Cancel Refund"
        onConfirm={() => {
          cancelRefundMutation.mutate({
            refundId: selectedRefund?.id,
            reason: 'Cancelled by admin',
          });
        }}
        confirmVariant="danger"
        isProcessing={cancelRefundMutation.isLoading}
      >
        <div className="space-y-4">
          <p className="text-sm text-gray-500">
            Are you sure you want to cancel this refund of{' '}
            <span className="font-medium">
              {selectedRefund && formatCurrency(selectedRefund.amount, selectedRefund.currency)}
            </span>?
          </p>
          
          {selectedRefund?.reason && (
            <div>
              <p className="text-sm font-medium text-gray-700">Refund Reason:</p>
              <p className="mt-1 text-sm text-gray-600 bg-gray-50 p-2 rounded">
                {selectedRefund.reason}
              </p>
            </div>
          )}
          
          <div>
            <label htmlFor="cancel_reason" className="block text-sm font-medium text-gray-700">
              Reason for Cancellation
            </label>
            <textarea
              id="cancel_reason"
              rows={3}
              className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Please provide a reason for cancelling this refund"
              defaultValue="Cancelled by admin"
            />
          </div>
        </div>
      </ConfirmDialog>
    </div>
  );
};

export default RefundList;
