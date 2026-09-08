import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import axios from 'axios';
import { FaCheckCircle, FaTimesCircle, FaClock, FaReceipt, FaPrint, FaDownload } from 'react-icons/fa';

const StatusPage = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [payment, setPayment] = useState(null);
  const [loading, setLoading] = useState(true);
  const [polling, setPolling] = useState(false);

  const fetchPaymentStatus = async () => {
    try {
      const response = await axios.get(`/api/payments/status/${id}`);
      setPayment(response.data.data);
      
      // If payment is still pending, set up polling
      if (response.data.data.payment_status === 'pending' && !polling) {
        setPolling(true);
        const interval = setInterval(async () => {
          const newStatus = await axios.get(`/api/payments/status/${id}`);
          setPayment(newStatus.data.data);
          
          // Stop polling if payment is no longer pending
          if (!['pending', 'processing'].includes(newStatus.data.data.payment_status)) {
            clearInterval(interval);
            setPolling(false);
            
            // Show success/error message
            if (newStatus.data.data.payment_status === 'completed') {
              toast.success('Payment completed successfully!');
            } else if (['failed', 'cancelled', 'expired'].includes(newStatus.data.data.payment_status)) {
              toast.error(`Payment ${newStatus.data.data.payment_status}. Please try again.`);
            }
          }
        }, 5000); // Poll every 5 seconds
        
        return () => clearInterval(interval);
      }
    } catch (error) {
      console.error('Error fetching payment status:', error);
      toast.error('Failed to fetch payment status. Please try again.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPaymentStatus();
  }, [id]);

  const getStatusIcon = () => {
    switch (payment?.payment_status) {
      case 'completed':
        return <FaCheckCircle className="h-12 w-12 text-green-500" />;
      case 'failed':
      case 'cancelled':
      case 'expired':
        return <FaTimesCircle className="h-12 w-12 text-red-500" />;
      case 'pending':
      case 'processing':
      default:
        return <FaClock className="h-12 w-12 text-yellow-500 animate-pulse" />;
    }
  };

  const getStatusText = () => {
    switch (payment?.payment_status) {
      case 'completed':
        return 'Payment Successful';
      case 'failed':
        return 'Payment Failed';
      case 'cancelled':
        return 'Payment Cancelled';
      case 'expired':
        return 'Payment Expired';
      case 'processing':
        return 'Processing Your Payment';
      case 'pending':
      default:
        return 'Payment Pending';
    }
  };

  const getStatusDescription = () => {
    switch (payment?.payment_status) {
      case 'completed':
        return 'Your payment has been successfully processed. Thank you for your payment!';
      case 'failed':
        return 'We encountered an issue processing your payment. Please try again or contact support.';
      case 'cancelled':
        return 'The payment was cancelled. No amount was deducted from your account.';
      case 'expired':
        return 'The payment session has expired. Please initiate a new payment if you wish to complete the transaction.';
      case 'processing':
        return 'Your payment is being processed. This may take a few moments. Please do not refresh the page.';
      case 'pending':
      default:
        return 'Your payment is being processed. This page will update automatically when there is a change in status.';
    }
  };

  const handlePrintReceipt = () => {
    window.print();
  };

  const handleDownloadReceipt = () => {
    // In a real app, this would generate and download a PDF receipt
    toast.info('Downloading receipt...');
    // Implement PDF generation and download
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading payment details...</p>
        </div>
      </div>
    );
  }

  if (!payment) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 px-4">
        <div className="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
          <FaTimesCircle className="h-16 w-16 text-red-500 mx-auto mb-4" />
          <h1 className="text-2xl font-bold text-gray-800 mb-2">Payment Not Found</h1>
          <p className="text-gray-600 mb-6">We couldn't find a payment with the provided ID.</p>
          <button
            onClick={() => navigate('/payments')}
            className="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          >
            Back to Payments
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-3xl mx-auto">
        {/* Payment Status Card */}
        <div className="bg-white shadow overflow-hidden sm:rounded-lg mb-8">
          <div className="px-4 py-5 sm:px-6 text-center">
            <div className="flex justify-center mb-4">
              {getStatusIcon()}
            </div>
            <h1 className="text-2xl font-bold text-gray-900">{getStatusText()}</h1>
            <p className="mt-2 text-gray-600">{getStatusDescription()}</p>
          </div>
          
          <div className="border-t border-gray-200 px-4 py-5 sm:p-0">
            <dl className="sm:divide-y sm:divide-gray-200">
              <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">Invoice Number</dt>
                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{payment.invoice_number}</dd>
              </div>
              <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">Amount</dt>
                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {payment.currency || 'BDT'} {parseFloat(payment.amount).toFixed(2)}
                </dd>
              </div>
              <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">Payment Method</dt>
                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 capitalize">
                  {payment.payment_method.replace('_', ' ')}
                </dd>
              </div>
              <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">Transaction ID</dt>
                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2 font-mono">
                  {payment.transaction_id || 'N/A'}
                </dd>
              </div>
              <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt className="text-sm font-medium text-gray-500">Date & Time</dt>
                <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                  {new Date(payment.created_at).toLocaleString()}
                </dd>
              </div>
              {payment.payment_status === 'completed' && payment.payment_date && (
                <div className="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                  <dt className="text-sm font-medium text-gray-500">Paid On</dt>
                  <dd className="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    {new Date(payment.payment_date).toLocaleString()}
                  </dd>
                </div>
              )}
            </dl>
          </div>
        </div>

        {/* Actions */}
        <div className="bg-white shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6">
            <h3 className="text-lg font-medium leading-6 text-gray-900">Receipt & Actions</h3>
          </div>
          <div className="border-t border-gray-200 px-4 py-5 sm:p-6">
            <div className="flex flex-col sm:flex-row gap-3">
              {payment.payment_status === 'completed' && (
                <>
                  <button
                    type="button"
                    onClick={handlePrintReceipt}
                    className="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    <FaPrint className="mr-2" /> Print Receipt
                  </button>
                  <button
                    type="button"
                    onClick={handleDownloadReceipt}
                    className="inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    <FaDownload className="mr-2" /> Download Receipt
                  </button>
                </>
              )}
              
              <button
                type="button"
                onClick={() => navigate('/dashboard')}
                className="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
              >
                Back to Dashboard
              </button>
              
              {['failed', 'cancelled', 'expired'].includes(payment.payment_status) && (
                <button
                  type="button"
                  onClick={() => navigate(`/payments/new?amount=${payment.amount}&description=${encodeURIComponent(payment.description || '')}`)}
                  className="ml-auto inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                  <FaReceipt className="mr-2" /> Try Again
                </button>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default StatusPage;
