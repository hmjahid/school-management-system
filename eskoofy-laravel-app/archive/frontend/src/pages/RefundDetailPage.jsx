import React, { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { format, parseISO } from 'date-fns';
import { 
  ArrowLeftIcon,
  CheckCircleIcon, 
  XCircleIcon, 
  ClockIcon, 
  ArrowPathIcon,
  ExclamationTriangleIcon,
  DocumentTextIcon,
  CurrencyDollarIcon,
  UserCircleIcon,
  CreditCardIcon,
  ReceiptRefundIcon,
  ArrowPathRoundedSquareIcon,
  XMarkCircleIcon,
  CheckBadgeIcon,
  ArrowDownTrayIcon,
  PrinterIcon,
  EnvelopeIcon,
  ChatBubbleLeftRightIcon,
  DocumentArrowDownIcon,
  DocumentCheckIcon,
  DocumentDuplicateIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
  BanknotesIcon,
  ClockIcon as ClockIconSolid,
} from '@heroicons/react/24/outline';
import { toast } from 'react-toastify';
import { getRefund, processRefund, cancelRefund, exportRefundReceipt } from '../services/api/refunds';
import { formatCurrency, formatDate, formatDateTime } from '../utils/formatters';
import Button from '../components/common/Button';
import StatusBadge from '../components/common/StatusBadge';
import ConfirmDialog from '../components/common/ConfirmDialog';
import { useAuth } from '../contexts/AuthContext';

const statuses = {
  pending: {
    icon: ClockIconSolid,
    color: 'yellow',
    text: 'Pending',
    description: 'Refund request has been submitted and is awaiting processing.',
  },
  processing: {
    icon: ArrowPathIcon,
    color: 'blue',
    text: 'Processing',
    description: 'Refund is currently being processed by our payment processor.',
  },
  completed: {
    icon: CheckCircleIcon,
    color: 'green',
    text: 'Completed',
    description: 'Refund has been successfully processed and completed.',
  },
  failed: {
    icon: XMarkCircleIcon,
    color: 'red',
    text: 'Failed',
    description: 'Refund processing failed. Please check the details and try again.',
  },
  cancelled: {
    icon: XCircleIcon,
    color: 'gray',
    text: 'Cancelled',
    description: 'Refund request was cancelled.',
  },
};

const RefundDetailPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { hasPermission } = useAuth();
  
  const [showProcessDialog, setShowProcessDialog] = useState(false);
  const [showCancelDialog, setShowCancelDialog] = useState(false);
  const [transactionId, setTransactionId] = useState('');
  const [exporting, setExporting] = useState(false);
  const [printMode, setPrintMode] = useState(false);

  // Fetch refund details
  const {
    data: refund,
    isLoading,
    isError,
    error,
    refetch,
  } = useQuery(['refund', id], () => getRefund(id), {
    enabled: !!id,
    refetchOnWindowFocus: false,
  });

  // Mutations
  const processRefundMutation = useMutation(
    ({ refundId, transactionId }) => processRefund(refundId, { transaction_id: transactionId }),
    {
      onSuccess: () => {
        toast.success('Refund processed successfully');
        queryClient.invalidateQueries(['refund', id]);
        queryClient.invalidateQueries('refunds');
        setShowProcessDialog(false);
        setTransactionId('');
      },
      onError: (error) => {
        toast.error(error.response?.data?.message || 'Failed to process refund');
      },
    }
  );

  const cancelRefundMutation = useMutation(
    ({ refundId, reason }) => cancelRefund(refundId, { reason }),
    {
      onSuccess: () => {
        toast.success('Refund cancelled successfully');
        queryClient.invalidateQueries(['refund', id]);
        queryClient.invalidateQueries('refunds');
        setShowCancelDialog(false);
      },
      onError: (error) => {
        toast.error(error.response?.data?.message || 'Failed to cancel refund');
      },
    }
  );

  // Handle print
  const handlePrint = () => {
    setPrintMode(true);
    setTimeout(() => {
      window.print();
      // Small delay to ensure print dialog is shown before resetting
      setTimeout(() => setPrintMode(false), 1000);
    }, 500);
  };

  // Handle export
  const handleExport = async (format = 'pdf') => {
    try {
      setExporting(true);
      const response = await exportRefundReceipt(id, format);
      
      // Create a download link
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `refund-${id}-receipt.${format}`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      
      toast.success(`Refund receipt exported as ${format.toUpperCase()}`);
    } catch (error) {
      toast.error(error.response?.data?.message || 'Failed to export receipt');
    } finally {
      setExporting(false);
    }
  };

  // Handle process refund
  const handleProcessRefund = () => {
    if (!transactionId.trim()) {
      toast.error('Transaction ID is required');
      return;
    }
    processRefundMutation.mutate({
      refundId: id,
      transactionId: transactionId.trim(),
    });
  };

  // Handle cancel refund
  const handleCancelRefund = (reason = 'Cancelled by admin') => {
    cancelRefundMutation.mutate({
      refundId: id,
      reason,
    });
  };

  // Loading state
  if (isLoading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  // Error state
  if (isError) {
    return (
      <div className="rounded-md bg-red-50 p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <XCircleIcon className="h-5 w-5 text-red-400" aria-hidden="true" />
          </div>
          <div className="ml-3">
            <h3 className="text-sm font-medium text-red-800">
              Failed to load refund details
            </h3>
            <div className="mt-2 text-sm text-red-700">
              <p>{error.message}</p>
            </div>
            <div className="mt-4">
              <Button
                variant="secondary"
                onClick={() => refetch()}
                icon={ArrowPathIcon}
              >
                Retry
              </Button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // No refund found
  if (!refund) {
    return (
      <div className="text-center py-12">
        <DocumentTextIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900">No refund found</h3>
        <p className="mt-1 text-sm text-gray-500">The requested refund could not be found.</p>
        <div className="mt-6">
          <Button
            variant="primary"
            onClick={() => navigate('/refunds')}
            icon={ArrowLeftIcon}
          >
            Back to Refunds
          </Button>
        </div>
      </div>
    );
  }

  const status = statuses[refund.status] || statuses.pending;
  const canProcess = hasPermission('refunds.process') && refund.status === 'pending';
  const canCancel = hasPermission('refunds.cancel') && ['pending', 'processing'].includes(refund.status);
  const canExport = hasPermission('refunds.view');

  // Print-specific styles and content
  if (printMode) {
    return (
      <div className="p-6 print:p-0">
        <div className="max-w-4xl mx-auto bg-white p-8 rounded-lg">
          {/* Header */}
          <div className="border-b border-gray-200 pb-4 mb-6">
            <div className="flex justify-between items-start">
              <div>
                <h1 className="text-2xl font-bold text-gray-900">Refund Receipt</h1>
                <p className="text-sm text-gray-500">
                  Refund ID: <span className="font-medium">#{refund.id}</span>
                </p>
              </div>
              <div className="text-right">
                <p className="text-sm text-gray-500">
                  Issued: {formatDateTime(refund.created_at)}
                </p>
                {refund.processed_at && (
                  <p className="text-sm text-gray-500">
                    Processed: {formatDateTime(refund.processed_at)}
                  </p>
                )}
              </div>
            </div>
          </div>

          {/* Refund Details */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div>
              <h2 className="text-lg font-medium text-gray-900 mb-4">Refund To</h2>
              <div className="bg-gray-50 p-4 rounded-lg">
                <p className="font-medium">
                  {refund.user?.name || 'Customer'}
                </p>
                {refund.user?.email && (
                  <p className="text-sm text-gray-600">{refund.user.email}</p>
                )}
                {refund.user?.phone && (
                  <p className="text-sm text-gray-600 mt-1">{refund.user.phone}</p>
                )}
              </div>
            </div>

            <div>
              <h2 className="text-lg font-medium text-gray-900 mb-4">Payment Details</h2>
              <div className="bg-gray-50 p-4 rounded-lg">
                <p className="font-medium">
                  Payment #{refund.payment?.id || 'N/A'}
                </p>
                <p className="text-sm text-gray-600">
                  {refund.payment?.payment_method ? 
                    refund.payment.payment_method.charAt(0).toUpperCase() + refund.payment.payment_method.slice(1) : 
                    'N/A'}
                </p>
                {refund.transaction_id && (
                  <p className="text-sm text-gray-600 mt-1">
                    Transaction: {refund.transaction_id}
                  </p>
                )}
              </div>
            </div>
          </div>

          {/* Refund Summary */}
          <div className="border border-gray-200 rounded-lg overflow-hidden mb-8">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Description
                  </th>
                  <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Amount
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                <tr>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    Refund for Payment #{refund.payment?.id || 'N/A'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                    {formatCurrency(refund.amount, refund.currency)}
                  </td>
                </tr>
                <tr>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    Status
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${status.color}-100 text-${status.color}-800`}>
                      {status.text}
                    </span>
                  </td>
                </tr>
                {refund.reason && (
                  <tr>
                    <td colSpan="2" className="px-6 py-4 whitespace-pre-line text-sm text-gray-500 border-t border-gray-200">
                      <p className="font-medium text-gray-900">Reason for Refund:</p>
                      {refund.reason}
                    </td>
                  </tr>
                )}
                {refund.metadata?.notes && (
                  <tr>
                    <td colSpan="2" className="px-6 py-4 whitespace-pre-line text-sm text-gray-500 border-t border-gray-200">
                      <p className="font-medium text-gray-900">Notes:</p>
                      {refund.metadata.notes}
                    </td>
                  </tr>
                )}
              </tbody>
              <tfoot className="bg-gray-50">
                <tr>
                  <th scope="row" colSpan="1" className="px-6 py-3 text-left text-sm font-medium text-gray-900">
                    Total Refund
                  </th>
                  <td className="px-6 py-3 text-right text-sm font-medium text-gray-900">
                    {formatCurrency(refund.amount, refund.currency)}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          {/* Footer */}
          <div className="mt-12 pt-8 border-t border-gray-200 text-center text-xs text-gray-500">
            <p>Thank you for your business!</p>
            <p className="mt-1">
              If you have any questions about this refund, please contact our support team.
            </p>
            <div className="mt-4 flex items-center justify-center space-x-4">
              <p>Powered by School Management System</p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Normal view
  return (
    <div className="px-4 sm:px-6 lg:px-8">
      <div className="sm:flex sm:items-center sm:justify-between mb-6">
        <div className="flex items-center">
          <Button
            variant="secondary"
            size="sm"
            onClick={() => navigate(-1)}
            className="mr-4"
            icon={ArrowLeftIcon}
          >
            Back
          </Button>
          <h1 className="text-2xl font-semibold text-gray-900">Refund Details</h1>
        </div>
        <div className="mt-4 sm:mt-0 flex space-x-3">
          {canExport && (
            <div className="relative inline-block text-left" data-headlessui-state="">
              <Button
                variant="secondary"
                size="sm"
                disabled={exporting}
                icon={ArrowDownTrayIcon}
              >
                {exporting ? 'Exporting...' : 'Export'}
              </Button>
              <div className="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden" data-headlessui-state="closed">
                <div className="py-1">
                  <button
                    onClick={() => handleExport('pdf')}
                    className="text-gray-700 block w-full text-left px-4 py-2 text-sm hover:bg-gray-100"
                  >
                    <DocumentTextIcon className="h-4 w-4 inline-block mr-2" />
                    Export as PDF
                  </button>
                  <button
                    onClick={() => handleExport('csv')}
                    className="text-gray-700 block w-full text-left px-4 py-2 text-sm hover:bg-gray-100"
                  >
                    <DocumentDuplicateIcon className="h-4 w-4 inline-block mr-2" />
                    Export as CSV
                  </button>
                </div>
              </div>
            </div>
          )}
          <Button
            variant="secondary"
            size="sm"
            onClick={handlePrint}
            icon={PrinterIcon}
          >
            Print
          </Button>
          {canProcess && (
            <Button
              variant="primary"
              size="sm"
              onClick={() => setShowProcessDialog(true)}
              icon={CheckBadgeIcon}
            >
              Process Refund
            </Button>
          )}
          {canCancel && (
            <Button
              variant="danger"
              size="sm"
              onClick={() => setShowCancelDialog(true)}
              icon={XMarkCircleIcon}
            >
              Cancel Refund
            </Button>
          )}
        </div>
      </div>

      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 className="text-lg font-medium text-gray-900">
                Refund #{refund.id}
              </h2>
              <div className="mt-1 flex items-center">
                <StatusBadge status={refund.status} statuses={statuses} size="lg" />
                {refund.processed_at && (
                  <span className="ml-2 text-sm text-gray-500">
                    Processed on {formatDate(refund.processed_at)}
                  </span>
                )}
              </div>
            </div>
            <div className="mt-2 sm:mt-0 text-right">
              <p className="text-2xl font-semibold text-gray-900">
                {formatCurrency(refund.amount, refund.currency)}
              </p>
              <p className="text-sm text-gray-500">
                Refund for Payment #{refund.payment_id}
              </p>
            </div>
          </div>
        </div>

        <div className="border-b border-gray-200">
          <dl className="divide-y divide-gray-200">
            <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">Refund ID</dt>
              <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                {refund.id}
              </dd>
            </div>
            <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">Payment</dt>
              <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                <Link
                  to={`/payments/${refund.payment_id}`}
                  className="text-indigo-600 hover:text-indigo-900 hover:underline"
                >
                  View Payment #{refund.payment_id}
                </Link>
              </dd>
            </div>
            <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">Customer</dt>
              <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                {refund.user ? (
                  <>
                    <Link
                      to={`/users/${refund.user.id}`}
                      className="text-indigo-600 hover:text-indigo-900 hover:underline"
                    >
                      {refund.user.name}
                    </Link>
                    {refund.user.email && (
                      <span className="text-gray-500 ml-2">({refund.user.email})</span>
                    )}
                  </>
                ) : (
                  'N/A'
                )}
              </dd>
            </div>
            <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt className="text-sm font-medium text-gray-500">Requested On</dt>
              <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                {formatDateTime(refund.created_at)}
              </dd>
            </div>
            {refund.processed_by && (
              <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">
                  {refund.status === 'cancelled' ? 'Cancelled By' : 'Processed By'}
                </dt>
                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {refund.processor ? (
                    <Link
                      to={`/users/${refund.processor.id}`}
                      className="text-indigo-600 hover:text-indigo-900 hover:underline"
                    >
                      {refund.processor.name}
                    </Link>
                  ) : (
                    'System'
                  )}
                </dd>
              </div>
            )}
            {refund.transaction_id && (
              <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">
                  Transaction ID
                </dt>
                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-mono">
                  {refund.transaction_id}
                </dd>
              </div>
            )}
            {refund.reason && (
              <div className="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">Reason</dt>
                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 whitespace-pre-line">
                  {refund.reason}
                </dd>
              </div>
            )}
            {refund.metadata && Object.keys(refund.metadata).length > 0 && (
              <div className="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">Metadata</dt>
                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  <pre className="bg-gray-50 p-3 rounded-md overflow-x-auto text-xs">
                    {JSON.stringify(refund.metadata, null, 2)}
                  </pre>
                </dd>
              </div>
            )}
          </dl>
        </div>

        <div className="px-4 py-4 bg-gray-50 sm:px-6 flex justify-between items-center">
          <div className="text-sm text-gray-500">
            <p>Last updated: {formatDateTime(refund.updated_at)}</p>
          </div>
          <div className="space-x-3">
            {canProcess && (
              <Button
                variant="primary"
                size="sm"
                onClick={() => setShowProcessDialog(true)}
                icon={CheckBadgeIcon}
              >
                Process Refund
              </Button>
            )}
            {canCancel && (
              <Button
                variant="danger"
                size="sm"
                onClick={() => setShowCancelDialog(true)}
                icon={XMarkCircleIcon}
              >
                Cancel Refund
              </Button>
            )}
          </div>
        </div>
      </div>

      {/* Process Refund Dialog */}
      <ConfirmDialog
        isOpen={showProcessDialog}
        onClose={() => {
          setShowProcessDialog(false);
          setTransactionId('');
        }}
        title="Process Refund"
        confirmText="Process Refund"
        onConfirm={handleProcessRefund}
        confirmVariant="primary"
        isProcessing={processRefundMutation.isLoading}
      >
        <div className="space-y-4">
          <p className="text-sm text-gray-500">
            You are about to process a refund of{' '}
            <span className="font-medium">
              {formatCurrency(refund.amount, refund.currency)}
            </span>{' '}
            for payment #{refund.payment_id}.
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
          
          {refund.reason && (
            <div className="mt-2">
              <p className="text-sm font-medium text-gray-700">Refund Reason:</p>
              <p className="mt-1 text-sm text-gray-600 bg-gray-50 p-2 rounded">
                {refund.reason}
              </p>
            </div>
          )}
        </div>
      </ConfirmDialog>

      {/* Cancel Refund Dialog */}
      <ConfirmDialog
        isOpen={showCancelDialog}
        onClose={() => setShowCancelDialog(false)}
        title="Cancel Refund"
        confirmText="Cancel Refund"
        onConfirm={() => handleCancelRefund('Cancelled by admin')}
        confirmVariant="danger"
        isProcessing={cancelRefundMutation.isLoading}
      >
        <div className="space-y-4">
          <p className="text-sm text-gray-500">
            Are you sure you want to cancel this refund of{' '}
            <span className="font-medium">
              {formatCurrency(refund.amount, refund.currency)}
            </span>?
            This action cannot be undone.
          </p>
          
          {refund.reason && (
            <div>
              <p className="text-sm font-medium text-gray-700">Original Reason:</p>
              <p className="mt-1 text-sm text-gray-600 bg-gray-50 p-2 rounded">
                {refund.reason}
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
              onChange={(e) => {
                // Update the reason if user types something
                if (e.target.value.trim()) {
                  handleCancelRefund(e.target.value);
                }
              }}
            />
          </div>
        </div>
      </ConfirmDialog>
    </div>
  );
};

export default RefundDetailPage;
