import React, { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import axios from 'axios';

const PaymentForm = ({ paymentableType, paymentableId, amount, description }) => {
  const [loading, setLoading] = useState(false);
  const [gateways, setGateways] = useState([]);
  const [selectedGateway, setSelectedGateway] = useState(null);
  const navigate = useNavigate();
  
  const { register, handleSubmit, formState: { errors }, watch } = useForm({
    defaultValues: {
      amount: amount || '',
      description: description || '',
      paymentable_type: paymentableType,
      paymentable_id: paymentableId,
    }
  });

  // Fetch available payment gateways
  useEffect(() => {
    const fetchGateways = async () => {
      try {
        const response = await axios.get('/api/payments/gateways');
        setGateways(response.data.data || []);
      } catch (error) {
        console.error('Error fetching payment gateways:', error);
        toast.error('Failed to load payment options. Please try again.');
      }
    };

    fetchGateways();
  }, []);

  const onSubmit = async (data) => {
    if (!selectedGateway) {
      toast.warning('Please select a payment method');
      return;
    }

    setLoading(true);
    try {
      const response = await axios.post('/api/payments/initiate', {
        ...data,
        gateway: selectedGateway.code,
        currency: 'BDT',
      });

      const { payment, gateway } = response.data.data;
      
      if (gateway.redirect_url) {
        // For online payments that redirect to gateway
        window.location.href = gateway.redirect_url;
      } else if (gateway.offline_instructions) {
        // For offline payments, show instructions
        toast.info('Please follow the instructions to complete your payment');
        // Store payment ID for later reference
        localStorage.setItem('pendingPayment', JSON.stringify({
          id: payment.id,
          invoice: payment.invoice_number,
          amount: payment.amount,
          gateway: payment.payment_method,
        }));
        
        // Redirect to payment instructions page
        navigate(`/payments/${payment.id}/instructions`);
      }
    } catch (error) {
      console.error('Payment initiation failed:', error);
      const errorMessage = error.response?.data?.message || 'Failed to initiate payment. Please try again.';
      toast.error(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
      <h2 className="text-2xl font-bold mb-6 text-gray-800">Make a Payment</h2>
      
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
        {/* Payment Amount */}
        <div>
          <label htmlFor="amount" className="block text-sm font-medium text-gray-700 mb-1">
            Amount (BDT)
          </label>
          <input
            type="number"
            id="amount"
            {...register('amount', { 
              required: 'Amount is required',
              min: { value: 1, message: 'Amount must be greater than 0' }
            })}
            className={`w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
              errors.amount ? 'border-red-500' : 'border-gray-300'
            }`}
            disabled={!!amount}
          />
          {errors.amount && (
            <p className="mt-1 text-sm text-red-600">{errors.amount.message}</p>
          )}
        </div>

        {/* Payment Description */}
        <div>
          <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
            Description
          </label>
          <textarea
            id="description"
            {...register('description', { required: 'Description is required' })}
            rows={3}
            className={`w-full px-4 py-2 border rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 ${
              errors.description ? 'border-red-500' : 'border-gray-300'
            }`}
            disabled={!!description}
          />
          {errors.description && (
            <p className="mt-1 text-sm text-red-600">{errors.description.message}</p>
          )}
        </div>

        {/* Payment Method Selection */}
        <div>
          <h3 className="text-lg font-medium text-gray-900 mb-3">Select Payment Method</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {gateways.map((gateway) => (
              <div
                key={gateway.code}
                onClick={() => setSelectedGateway(gateway)}
                className={`p-4 border rounded-lg cursor-pointer transition-all ${
                  selectedGateway?.code === gateway.code
                    ? 'ring-2 ring-blue-500 border-blue-500 bg-blue-50'
                    : 'border-gray-300 hover:border-blue-300 hover:bg-gray-50'
                }`}
              >
                <div className="flex items-center space-x-3">
                  {gateway.logo_url && (
                    <img 
                      src={gateway.logo_url} 
                      alt={gateway.name} 
                      className="h-8 w-auto object-contain"
                    />
                  )}
                  <div>
                    <h4 className="font-medium text-gray-900">{gateway.name}</h4>
                    {gateway.fee_percentage > 0 && (
                      <p className="text-sm text-gray-500">
                        Fee: {gateway.fee_percentage}% + {gateway.currency} {gateway.fee_fixed}
                      </p>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
          {!selectedGateway && errors.gateway && (
            <p className="mt-1 text-sm text-red-600">Please select a payment method</p>
          )}
        </div>

        {/* Hidden fields for paymentable type and ID */}
        <input type="hidden" {...register('paymentable_type')} />
        <input type="hidden" {...register('paymentable_id')} />

        {/* Submit Button */}
        <div className="pt-4">
          <button
            type="submit"
            disabled={loading || !selectedGateway}
            className={`w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white ${
              loading || !selectedGateway
                ? 'bg-blue-400 cursor-not-allowed'
                : 'bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
            }`}
          >
            {loading ? (
              <>
                <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
              </>
            ) : (
              `Pay ${selectedGateway?.currency || 'BDT'} ${watch('amount') || '0.00'}`
            )}
          </button>
        </div>
      </form>
    </div>
  );
};

export default PaymentForm;
