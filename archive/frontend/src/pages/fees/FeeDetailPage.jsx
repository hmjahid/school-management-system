import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { 
  ArrowLeftIcon,
  PencilIcon,
  TrashIcon,
  CurrencyDollarIcon,
  ClockIcon,
  CheckCircleIcon,
  XCircleIcon,
  InformationCircleIcon,
  ReceiptTaxIcon,
  ReceiptRefundIcon,
  CalendarIcon,
  UserGroupIcon,
  TagIcon
} from '@heroicons/react/outline';
import { toast } from 'react-toastify';
import axios from 'axios';

const FeeDetailPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [fee, setFee] = useState(null);
  const [payments, setPayments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);
  const [deleting, setDeleting] = useState(false);

  // Fetch fee details and related payments
  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        
        // In a real app, you would make API calls here
        // const [feeRes, paymentsRes] = await Promise.all([
        //   axios.get(`/api/fees/${id}`),
        //   axios.get(`/api/fees/${id}/payments`)
        // ]);
        
        // Mock data for now
        setTimeout(() => {
          setFee({
            id: parseInt(id),
            name: 'Tuition Fee - January 2025',
            code: 'TUI-JAN25',
            description: 'Tuition fee for January 2025',
            amount: 5000,
            feeType: 'tuition',
            status: 'active',
            dueDate: '2025-01-31',
            classId: 1,
            className: 'Class 1',
            fineAmount: 100,
            fineType: 'fixed',
            discountAmount: 0,
            discountType: 'fixed',
            isRecurring: false,
            frequency: 'one_time',
            startDate: '2025-01-01',
            endDate: '2025-01-31',
            createdAt: '2024-12-15T10:30:00.000Z',
            updatedAt: '2024-12-15T10:30:00.000Z',
          });

          setPayments([
            {
              id: 1,
              amount: 5000,
              status: 'completed',
              paymentMethod: 'bkash',
              transactionId: 'TXN123456789',
              paidAt: '2025-01-15T14:32:00.000Z',
              studentId: 1,
              studentName: 'John Doe',
              studentClass: 'Class 1',
              studentRoll: '101'
            },
            {
              id: 2,
              amount: 4900,
              status: 'completed',
              paymentMethod: 'bank',
              transactionId: 'TXN987654321',
              paidAt: '2025-01-10T11:15:00.000Z',
              studentId: 2,
              studentName: 'Jane Smith',
              studentClass: 'Class 1',
              studentRoll: '102',
              discountApplied: 100,
              discountType: 'fixed'
            },
            {
              id: 3,
              amount: 2500,
              status: 'pending',
              paymentMethod: 'cash',
              studentId: 3,
              studentName: 'Bob Johnson',
              studentClass: 'Class 1',
              studentRoll: '103',
              dueDate: '2025-01-31'
            }
          ]);
          
          setLoading(false);
        }, 500);
      } catch (error) {
        console.error('Error fetching fee details:', error);
        toast.error('Failed to load fee details');
        setLoading(false);
      }
    };

    fetchData();
  }, [id]);

  // Handle fee deletion
  const handleDelete = async () => {
    try {
      setDeleting(true);
      // In a real app, you would make an API call here
      // await axios.delete(`/api/fees/${id}`);
      
      toast.success('Fee deleted successfully');
      navigate('/fees');
    } catch (error) {
      console.error('Error deleting fee:', error);
      toast.error('Failed to delete fee. Please try again.');
      setDeleting(false);
      setShowDeleteConfirm(false);
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

  // Format date
  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  };

  // Format date and time
  const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return 'N/A';
    return new Date(dateTimeString).toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  // Get status badge class
  const getStatusBadgeClass = (status) => {
    switch (status) {
      case 'active':
        return 'bg-green-100 text-green-800';
      case 'inactive':
        return 'bg-gray-100 text-gray-800';
      case 'pending':
        return 'bg-yellow-100 text-yellow-800';
      case 'completed':
        return 'bg-blue-100 text-blue-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  // Get payment method icon
  const getPaymentMethodIcon = (method) => {
    switch (method) {
      case 'bkash':
        return <div className="h-5 w-5 rounded-full bg-green-500 flex items-center justify-center text-white">bK</div>;
      case 'nagad':
        return <div className="h-5 w-5 rounded-full bg-red-500 flex items-center justify-center text-white text-xs">N</div>;
      case 'bank':
        return <div className="h-5 w-5 rounded-full bg-blue-500 flex items-center justify-center text-white">B</div>;
      case 'cash':
        return <div className="h-5 w-5 rounded-full bg-gray-500 flex items-center justify-center text-white">C</div>;
      default:
        return <div className="h-5 w-5 rounded-full bg-gray-200 flex items-center justify-center">?</div>;
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500"></div>
      </div>
    );
  }

  if (!fee) {
    return (
      <div className="text-center py-12">
        <h3 className="text-lg font-medium text-gray-900">Fee not found</h3>
        <p className="mt-2 text-sm text-gray-500">The requested fee could not be found.</p>
        <div className="mt-6">
          <Link
            to="/fees"
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <ArrowLeftIcon className="-ml-1 mr-2 h-5 w-5" />
            Back to Fees
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-6">
        <button
          onClick={() => navigate(-1)}
          className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-900"
        >
          <ArrowLeftIcon className="h-5 w-5 mr-1" />
          Back to Fees
        </button>
      </div>

      {/* Header */}
      <div className="md:flex md:items-center md:justify-between mb-6">
        <div>
          <h1 className="text-2xl font-semibold text-gray-900">{fee.name}</h1>
          <p className="mt-1 text-sm text-gray-500">
            Fee Code: {fee.code} • Created on {formatDate(fee.createdAt)}
          </p>
        </div>
        <div className="mt-4 flex space-x-3 md:mt-0">
          <Link
            to={`/fees/${fee.id}/edit`}
            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <PencilIcon className="-ml-1 mr-2 h-5 w-5 text-gray-500" />
            Edit
          </Link>
          <button
            type="button"
            onClick={() => setShowDeleteConfirm(true)}
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
          >
            <TrashIcon className="-ml-1 mr-2 h-5 w-5" />
            Delete
          </button>
        </div>
      </div>

      {/* Fee Details */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
        <div className="px-4 py-5 sm:px-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900">Fee Information</h3>
          <p className="mt-1 max-w-2xl text-sm text-gray-500">
            Detailed information about the fee.
          </p>
        </div>
        <div className="border-t border-gray-200 px-4 py-5 sm:px-6">
          <dl className="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
            <div className="sm:col-span-1">
              <dt className="text-sm font-medium text-gray-500">Fee Type</dt>
              <dd className="mt-1 text-sm text-gray-900 capitalize">{fee.feeType}</dd>
            </div>
            <div className="sm:col-span-1">
              <dt className="text-sm font-medium text-gray-500">Amount</dt>
              <dd className="mt-1 text-sm text-gray-900 font-medium">{formatCurrency(fee.amount)}</dd>
            </div>
            <div className="sm:col-span-1">
              <dt className="text-sm font-medium text-gray-500">Status</dt>
              <dd className="mt-1">
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusBadgeClass(fee.status)}`}>
                  {fee.status.charAt(0).toUpperCase() + fee.status.slice(1)}
                </span>
              </dd>
            </div>
            <div className="sm:col-span-1">
              <dt className="text-sm font-medium text-gray-500">Due Date</dt>
              <dd className="mt-1 text-sm text-gray-900">{formatDate(fee.dueDate)}</dd>
            </div>
            {fee.className && (
              <div className="sm:col-span-1">
                <dt className="text-sm font-medium text-gray-500">Class</dt>
                <dd className="mt-1 text-sm text-gray-900">{fee.className}</dd>
              </div>
            )}
            {fee.isRecurring && (
              <div className="sm:col-span-1">
                <dt className="text-sm font-medium text-gray-500">Frequency</dt>
                <dd className="mt-1 text-sm text-gray-900 capitalize">
                  {fee.frequency.replace('_', ' ')}
                </dd>
              </div>
            )}
            {fee.description && (
              <div className="sm:col-span-2">
                <dt className="text-sm font-medium text-gray-500">Description</dt>
                <dd className="mt-1 text-sm text-gray-900 whitespace-pre-line">{fee.description}</dd>
              </div>
            )}
          </dl>
        </div>
      </div>

      {/* Payment Summary */}
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                <CurrencyDollarIcon className="h-6 w-6 text-white" />
              </div>
              <div className="ml-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">Total Amount</dt>
                  <dd className="flex items-baseline">
                    <div className="text-2xl font-semibold text-gray-900">
                      {formatCurrency(fee.amount)}
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-green-500 rounded-md p-3">
                <CheckCircleIcon className="h-6 w-6 text-white" />
              </div>
              <div className="ml-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">Paid</dt>
                  <dd className="flex items-baseline">
                    <div className="text-2xl font-semibold text-gray-900">
                      {formatCurrency(9900)}
                    </div>
                    <div className="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                      66%
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                <ClockIcon className="h-6 w-6 text-white" />
              </div>
              <div className="ml-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">Pending</dt>
                  <dd className="flex items-baseline">
                    <div className="text-2xl font-semibold text-gray-900">
                      {formatCurrency(2500)}
                    </div>
                    <div className="ml-2 flex items-baseline text-sm font-semibold text-yellow-600">
                      17%
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div className="bg-white overflow-hidden shadow rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <div className="flex items-center">
              <div className="flex-shrink-0 bg-red-500 rounded-md p-3">
                <XCircleIcon className="h-6 w-6 text-white" />
              </div>
              <div className="ml-5 w-0 flex-1">
                <dl>
                  <dt className="text-sm font-medium text-gray-500 truncate">Overdue</dt>
                  <dd className="flex items-baseline">
                    <div className="text-2xl font-semibold text-gray-900">
                      {formatCurrency(2600)}
                    </div>
                    <div className="ml-2 flex items-baseline text-sm font-semibold text-red-600">
                      17%
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Payment Actions */}
      <div className="bg-white shadow sm:rounded-lg mb-8">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900">Payment Actions</h3>
          <div className="mt-5">
            <div className="rounded-md bg-blue-50 p-4">
              <div className="flex">
                <div className="flex-shrink-0">
                  <InformationCircleIcon className="h-5 w-5 text-blue-400" aria-hidden="true" />
                </div>
                <div className="ml-3 flex-1 md:flex md:justify-between">
                  <p className="text-sm text-blue-700">
                    Manage payments for this fee. You can record new payments, view payment history, or generate reports.
                  </p>
                </div>
              </div>
            </div>
            <div className="mt-5 flex flex-wrap gap-3">
              <button
                type="button"
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <ReceiptTaxIcon className="-ml-1 mr-2 h-5 w-5" />
                Record Payment
              </button>
              <button
                type="button"
                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <ReceiptRefundIcon className="-ml-1 mr-2 h-5 w-5 text-gray-500" />
                Issue Refund
              </button>
              <button
                type="button"
                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <svg className="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export Payments
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Payment History */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:px-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900">Payment History</h3>
          <p className="mt-1 max-w-2xl text-sm text-gray-500">
            A list of all payments made for this fee.
          </p>
        </div>
        <div className="border-t border-gray-200">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Student
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Payment
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Method
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th scope="col" className="relative px-6 py-3">
                    <span className="sr-only">Actions</span>
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {payments.length === 0 ? (
                  <tr>
                    <td colSpan="6" className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                      No payments found.
                    </td>
                  </tr>
                ) : (
                  payments.map((payment) => (
                    <tr key={payment.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <div className="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span className="text-indigo-600 font-medium">
                              {payment.studentName.split(' ').map(n => n[0]).join('')}
                            </span>
                          </div>
                          <div className="ml-4">
                            <div className="text-sm font-medium text-gray-900">{payment.studentName}</div>
                            <div className="text-sm text-gray-500">{payment.studentClass} - Roll: {payment.studentRoll}</div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">{formatCurrency(payment.amount)}</div>
                        {payment.discountApplied > 0 && (
                          <div className="text-xs text-green-600">
                            -{formatCurrency(payment.discountApplied)} ({payment.discountType} discount)
                          </div>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          {getPaymentMethodIcon(payment.paymentMethod)}
                          <span className="ml-2 text-sm text-gray-500 capitalize">
                            {payment.paymentMethod}
                          </span>
                        </div>
                        {payment.transactionId && (
                          <div className="text-xs text-gray-500 mt-1">#{payment.transactionId}</div>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {payment.paidAt ? formatDateTime(payment.paidAt) : 'N/A'}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusBadgeClass(payment.status)}`}>
                          {payment.status.charAt(0).toUpperCase() + payment.status.slice(1)}
                        </span>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button className="text-indigo-600 hover:text-indigo-900 mr-4">View</button>
                        <button className="text-indigo-600 hover:text-indigo-900">Receipt</button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Delete Confirmation Modal */}
      {showDeleteConfirm && (
        <div className="fixed z-10 inset-0 overflow-y-auto">
          <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 transition-opacity" aria-hidden="true">
              <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div className="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
              <div className="sm:flex sm:items-start">
                <div className="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                  <XCircleIcon className="h-6 w-6 text-red-600" aria-hidden="true" />
                </div>
                <div className="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                  <h3 className="text-lg leading-6 font-medium text-gray-900">Delete Fee</h3>
                  <div className="mt-2">
                    <p className="text-sm text-gray-500">
                      Are you sure you want to delete this fee? This action cannot be undone.
                    </p>
                  </div>
                </div>
              </div>
              <div className="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                  onClick={handleDelete}
                  disabled={deleting}
                >
                  {deleting ? 'Deleting...' : 'Delete'}
                </button>
                <button
                  type="button"
                  className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                  onClick={() => setShowDeleteConfirm(false)}
                  disabled={deleting}
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default FeeDetailPage;
