import React, { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { useMutation, useQuery, useQueryClient } from 'react-query';
import { toast } from 'react-toastify';
import { useNavigate } from 'react-router-dom';
import { XCircleIcon, CheckCircleIcon, InformationCircleIcon } from '@heroicons/react/24/outline';
import { getPayment, requestRefund } from '../../services/api/payments';
import { formatCurrency } from '../../utils/formatters';

const RefundRequestForm = ({ paymentId, onSuccess }) => {
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const [maxRefundable, setMaxRefundable] = useState(0);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Fetch payment details
  const { data: payment, isLoading, error } = useQuery(
    ['payment', paymentId],
    () => getPayment(paymentId),
    {
      onSuccess: (data) => {
        setMaxRefundable(data.amount - (data.refunded_amount || 0));
      },
    }
  );

  // Setup form
  const {
    register,
    handleSubmit,
    watch,
    setValue,
    formState: { errors },
  } = useForm({
    defaultValues: {
      amount: '',
      reason: '',
      isFullRefund: false,
    },
  });

  const watchIsFullRefund = watch('isFullRefund');
  const watchAmount = watch('amount');

  // Update amount field when full refund is toggled
  useEffect(() => {
    if (watchIsFullRefund && maxRefundable > 0) {
      setValue('amount', maxRefundable.toFixed(2));
    } else if (!watchIsFullRefund) {
      setValue('amount', '');
    }
  }, [watchIsFullRefund, maxRefundable, setValue]);

  // Handle refund request submission
  const mutation = useMutation(
    (data) => requestRefund(paymentId, data),
    {
      onSuccess: () => {
        toast.success('Refund request submitted successfully');
        queryClient.invalidateQueries(['payment', paymentId]);
        queryClient.invalidateQueries('refunds');
        if (onSuccess) onSuccess();
      },
      onError: (error) => {
        toast.error(error.response?.data?.message || 'Failed to submit refund request');
      },
      onSettled: () => {
        setIsSubmitting(false);
      },
    }
  );

  const onSubmit = (data) => {
    setIsSubmitting(true);
    mutation.mutate({
      amount: parseFloat(data.amount),
      reason: data.reason,
    });
  };

  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-12">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border-l-4 border-red-400 p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <XCircleIcon className="h-5 w-5 text-red-400" aria-hidden="true" />
          </div>
          <div className="ml-3">
            <p className="text-sm text-red-700">
              {error.response?.data?.message || 'Failed to load payment details'}
            </p>
          </div>
        </div>
      </div>
    );
  }

  if (!payment) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">No payment found</p>
      </div>
    );
  }

  // Check if payment is eligible for refund
  const isEligibleForRefund = 
    payment.payment_status === 'completed' && 
    (payment.refund_status === null || payment.refund_status === 'partially_refunded');

  if (!isEligibleForRefund) {
    return (
      <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <InformationCircleIcon className="h-5 w-5 text-yellow-400" aria-hidden="true" />
          </div>
          <div className="ml-3">
            <p className="text-sm text-yellow-700">
              This payment is not eligible for a refund. Only completed payments that haven't been fully refunded can be refunded.
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white shadow overflow-hidden sm:rounded-lg">
      <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 className="text-lg leading-6 font-medium text-gray-900">Request Refund</h3>
        <p className="mt-1 max-w-2xl text-sm text-gray-500">
          Submit a refund request for payment #{payment.id}
        </p>
      </div>

      <div className="px-4 py-5 sm:p-6">
        <div className="mb-6 p-4 bg-gray-50 rounded-md">
          <h4 className="text-sm font-medium text-gray-700">Payment Details</h4>
          <dl className="mt-2 grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
            <div className="sm:col-span-1">
              <dt className="text-sm font-medium text-gray-500">Amount</dt>
              <dd className="mt-1 text-sm text-gray-900">
                {formatCurrency(payment.amount, payment.currency)}
              </dd>
            </div>
            <div className="sm:col-span-1">
              <dt className="text-sm font-medium text-gray-500">Status</dt>
              <dd className="mt-1 text-sm text-gray-900 capitalize">
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                  payment.payment_status === 'completed' 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-yellow-100 text-yellow-800'
                }`}>
                  {payment.payment_status.replace('_', ' ')}
                </span>
              </dd>
            </div>
            {payment.refunded_amount > 0 && (
              <div className="sm:col-span-2">
                <dt className="text-sm font-medium text-gray-500">Already Refunded</dt>
                <dd className="mt-1 text-sm text-gray-900">
                  {formatCurrency(payment.refunded_amount, payment.currency)}
                </dd>
              </div>
            )}
            <div className="sm:col-span-2">
              <dt className="text-sm font-medium text-gray-500">Maximum Refundable</dt>
              <dd className="mt-1 text-lg font-semibold text-indigo-600">
                {formatCurrency(maxRefundable, payment.currency)}
              </dd>
            </div>
          </dl>
        </div>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          <div className="flex items-start">
            <div className="flex items-center h-5">
              <input
                id="isFullRefund"
                name="isFullRefund"
                type="checkbox"
                className="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                {...register('isFullRefund')}
              />
            </div>
            <div className="ml-3 text-sm">
              <label htmlFor="isFullRefund" className="font-medium text-gray-700">
                Request full refund ({formatCurrency(maxRefundable, payment.currency)})
              </label>
              <p className="text-gray-500">Refund the maximum available amount</p>
            </div>
          </div>

          {!watchIsFullRefund && (
            <div>
              <label htmlFor="amount" className="block text-sm font-medium text-gray-700">
                Refund Amount
              </label>
              <div className="mt-1 relative rounded-md shadow-sm">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span className="text-gray-500 sm:text-sm">
                    {payment.currency}
                  </span>
                </div>
                <input
                  type="number"
                  id="amount"
                  name="amount"
                  step="0.01"
                  min="0.01"
                  max={maxRefundable}
                  className="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-16 pr-12 sm:text-sm border-gray-300 rounded-md disabled:bg-gray-100"
                  placeholder="0.00"
                  disabled={watchIsFullRefund}
                  {...register('amount', {
                    required: 'Amount is required',
                    min: {
                      value: 0.01,
                      message: 'Amount must be greater than 0',
                    },
                    max: {
                      value: maxRefundable,
                      message: `Amount cannot exceed ${formatCurrency(maxRefundable, payment.currency)}`,
                    },
                    valueAsNumber: true,
                  })}
                />
              </div>
              {errors.amount && (
                <p className="mt-2 text-sm text-red-600" id="amount-error">
                  {errors.amount.message}
                </p>
              )}
              <p className="mt-2 text-sm text-gray-500">
                Available: {formatCurrency(maxRefundable, payment.currency)}
              </p>
            </div>
          )}

          <div>
            <label htmlFor="reason" className="block text-sm font-medium text-gray-700">
              Reason for Refund <span className="text-red-500">*</span>
            </label>
            <div className="mt-1">
              <textarea
                id="reason"
                name="reason"
                rows={3}
                className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border border-gray-300 rounded-md"
                placeholder="Please provide a reason for this refund request"
                {...register('reason', {
                  required: 'Reason is required',
                  minLength: {
                    value: 10,
                    message: 'Reason must be at least 10 characters',
                  },
                })}
              />
            </div>
            {errors.reason && (
              <p className="mt-2 text-sm text-red-600" id="reason-error">
                {errors.reason.message}
              </p>
            )}
            <p className="mt-2 text-sm text-gray-500">
              Please provide a detailed reason for this refund request (minimum 10 characters).
            </p>
          </div>

          <div className="pt-5">
            <div className="flex justify-end space-x-3">
              <button
                type="button"
                onClick={() => navigate(-1)}
                className="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={isSubmitting}
                className={`inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white ${
                  isSubmitting
                    ? 'bg-indigo-400 cursor-not-allowed'
                    : 'bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
                }`}
              >
                {isSubmitting ? (
                  <>
                    <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing...
                  </>
                ) : (
                  'Submit Refund Request'
                )}
              </button>
            </div>
          </div>
        </form>
      </div>

      {mutation.isError && (
        <div className="bg-red-50 border-l-4 border-red-400 p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <XCircleIcon className="h-5 w-5 text-red-400" aria-hidden="true" />
            </div>
            <div className="ml-3">
              <p className="text-sm text-red-700">
                {mutation.error?.response?.data?.message || 'Failed to submit refund request'}
              </p>
            </div>
          </div>
        </div>
      )}

      {mutation.isSuccess && (
        <div className="bg-green-50 border-l-4 border-green-400 p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <CheckCircleIcon className="h-5 w-5 text-green-400" aria-hidden="true" />
            </div>
            <div className="ml-3">
              <p className="text-sm text-green-700">
                Refund request submitted successfully. You will be notified once it's processed.
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default RefundRequestForm;
