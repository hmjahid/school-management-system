import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { toast } from 'react-toastify';
import PaymentForm from '../../components/payments/PaymentForm';
import { ArrowLeftIcon } from '@heroicons/react/outline';

const NewPayment = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  // Get any pre-filled values from URL params
  const searchParams = new URLSearchParams(location.search);
  const initialAmount = searchParams.get('amount') || '';
  const initialDescription = searchParams.get('description') || '';
  const paymentableType = searchParams.get('type') || 'other';
  const paymentableId = searchParams.get('id') || null;

  // Handle payment submission
  const handlePaymentSubmit = async (paymentData) => {
    setIsSubmitting(true);
    try {
      // In a real app, you would make an API call to initiate the payment
      // For now, we'll simulate a successful payment initiation
      console.log('Payment data:', paymentData);
      
      // Simulate API call delay
      await new Promise(resolve => setTimeout(resolve, 1500));
      
      // Generate a mock payment ID (in a real app, this would come from the API)
      const mockPaymentId = `pay_${Math.random().toString(36).substr(2, 9)}`;
      
      // Redirect to payment status page
      navigate(`/payments/${mockPaymentId}/status`);
      
      toast.success('Payment initiated successfully!');
    } catch (error) {
      console.error('Payment submission failed:', error);
      toast.error(error.response?.data?.message || 'Failed to initiate payment. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
      <div className="max-w-4xl mx-auto">
        <div className="mb-8">
          <button
            onClick={() => navigate(-1)}
            className="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 mb-4"
          >
            <ArrowLeftIcon className="h-4 w-4 mr-1" />
            Back
          </button>
          <h1 className="text-2xl font-bold text-gray-900">Make a Payment</h1>
          <p className="mt-1 text-sm text-gray-500">
            Complete your payment using one of the available payment methods.
          </p>
        </div>

        <div className="bg-white shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 className="text-lg leading-6 font-medium text-gray-900">
              Payment Details
            </h3>
            <p className="mt-1 max-w-2xl text-sm text-gray-500">
              Enter your payment information below.
            </p>
          </div>
          
          <div className="px-4 py-5 sm:p-6">
            <PaymentForm 
              amount={initialAmount}
              description={initialDescription}
              paymentableType={paymentableType}
              paymentableId={paymentableId}
              onSubmit={handlePaymentSubmit}
              isSubmitting={isSubmitting}
            />
          </div>
        </div>
        
        <div className="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <svg className="h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
              </svg>
            </div>
            <div className="ml-3">
              <h3 className="text-sm font-medium text-blue-800">Secure Payment</h3>
              <div className="mt-2 text-sm text-blue-700">
                <p>
                  Your payment information is processed securely. We do not store your credit card details.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default NewPayment;
