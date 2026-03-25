import { useEffect, useCallback } from 'react';
import { useWebSocket } from '../contexts/WebSocketContext';
import { toast } from 'react-toastify';

/**
 * Custom hook for handling real-time fee updates
 * @param {Object} options - Configuration options
 * @param {Function} [options.onFeeCreated] - Callback when a new fee is created
 * @param {Function} [options.onFeeUpdated] - Callback when a fee is updated
 * @param {Function} [options.onFeeDeleted] - Callback when a fee is deleted
 * @param {Function} [options.onPaymentReceived] - Callback when a payment is received
 * @param {boolean} [options.enableToasts=true] - Whether to show toast notifications
 * @returns {Object} Methods to interact with fee updates
 */
const useFeeUpdates = ({
  onFeeCreated,
  onFeeUpdated,
  onFeeDeleted,
  onPaymentReceived,
  enableToasts = true,
} = {}) => {
  const { isConnected, subscribe: subscribeToEvent, sendMessage } = useWebSocket();

  // Handle fee created event
  useEffect(() => {
    if (!isConnected) return;

    const unsubscribe = subscribeToEvent('fee:created', (fee) => {
      if (typeof onFeeCreated === 'function') {
        onFeeCreated(fee);
      }
      
      if (enableToasts) {
        toast.success(`New fee created: ${fee.name}`, {
          position: 'top-right',
          autoClose: 5000,
          hideProgressBar: false,
          closeOnClick: true,
          pauseOnHover: true,
          draggable: true,
        });
      }
    });

    return () => {
      unsubscribe();
    };
  }, [isConnected, subscribeToEvent, onFeeCreated, enableToasts]);

  // Handle fee updated event
  useEffect(() => {
    if (!isConnected) return;

    const unsubscribe = subscribeToEvent('fee:updated', (fee) => {
      if (typeof onFeeUpdated === 'function') {
        onFeeUpdated(fee);
      }
      
      if (enableToasts) {
        toast.info(`Fee updated: ${fee.name}`, {
          position: 'top-right',
          autoClose: 5000,
          hideProgressBar: false,
          closeOnClick: true,
          pauseOnHover: true,
          draggable: true,
        });
      }
    });

    return () => {
      unsubscribe();
    };
  }, [isConnected, subscribeToEvent, onFeeUpdated, enableToasts]);

  // Handle fee deleted event
  useEffect(() => {
    if (!isConnected) return;

    const unsubscribe = subscribeToEvent('fee:deleted', (feeId) => {
      if (typeof onFeeDeleted === 'function') {
        onFeeDeleted(feeId);
      }
      
      if (enableToasts) {
        toast.warning(`Fee deleted: #${feeId}`, {
          position: 'top-right',
          autoClose: 5000,
          hideProgressBar: false,
          closeOnClick: true,
          pauseOnHover: true,
          draggable: true,
        });
      }
    });

    return () => {
      unsubscribe();
    };
  }, [isConnected, subscribeToEvent, onFeeDeleted, enableToasts]);

  // Handle payment received event
  useEffect(() => {
    if (!isConnected) return;

    const unsubscribe = subscribeToEvent('payment:received', (payment) => {
      if (typeof onPaymentReceived === 'function') {
        onPaymentReceived(payment);
      }
      
      if (enableToasts) {
        toast.success(`Payment received: $${payment.amount} for ${payment.feeName}`, {
          position: 'top-right',
          autoClose: 5000,
          hideProgressBar: false,
          closeOnClick: true,
          pauseOnHover: true,
          draggable: true,
        });
      }
    });

    return () => {
      unsubscribe();
    };
  }, [isConnected, subscribeToEvent, onPaymentReceived, enableToasts]);

  /**
   * Send a fee update request
   * @param {string} feeId - The ID of the fee to update
   * @param {Object} updates - The updates to apply
   * @returns {boolean} True if the message was sent successfully
   */
  const updateFee = useCallback((feeId, updates) => {
    return sendMessage('fee:update', { feeId, ...updates });
  }, [sendMessage]);

  /**
   * Send a payment received notification
   * @param {string} feeId - The ID of the fee the payment is for
   * @param {Object} payment - The payment details
   * @returns {boolean} True if the message was sent successfully
   */
  const sendPaymentNotification = useCallback((feeId, payment) => {
    return sendMessage('payment:create', { feeId, ...payment });
  }, [sendMessage]);

  return {
    isConnected,
    updateFee,
    sendPaymentNotification,
  };
};

export default useFeeUpdates;
