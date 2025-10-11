/**
 * Validates fee form data
 * @param {Object} values - Form values to validate
 * @returns {Object} Validation errors, if any
 */
export const validateFeeForm = (values) => {
  const errors = {};
  
  // Required fields
  if (!values.name?.trim()) {
    errors.name = 'Fee name is required';
  } else if (values.name.length < 3) {
    errors.name = 'Fee name must be at least 3 characters';
  }
  
  if (!values.code?.trim()) {
    errors.code = 'Fee code is required';
  }
  
  if (!values.amount || isNaN(values.amount) || parseFloat(values.amount) <= 0) {
    errors.amount = 'A valid amount is required';
  }
  
  if (!values.dueDate) {
    errors.dueDate = 'Due date is required';
  } else if (new Date(values.dueDate) < new Date()) {
    errors.dueDate = 'Due date must be in the future';
  }
  
  // Validate fine amount if provided
  if (values.fineAmount && (isNaN(values.fineAmount) || parseFloat(values.fineAmount) < 0)) {
    errors.fineAmount = 'Fine amount must be a positive number';
  }
  
  // Validate discount amount if provided
  if (values.discountAmount && (isNaN(values.discountAmount) || parseFloat(values.discountAmount) < 0)) {
    errors.discountAmount = 'Discount amount must be a positive number';
  }
  
  // Validate recurring fee settings if enabled
  if (values.isRecurring) {
    if (!values.startDate) {
      errors.startDate = 'Start date is required for recurring fees';
    }
    
    if (!values.endDate) {
      errors.endDate = 'End date is required for recurring fees';
    } else if (values.startDate && new Date(values.endDate) <= new Date(values.startDate)) {
      errors.endDate = 'End date must be after start date';
    }
    
    if (!values.frequency) {
      errors.frequency = 'Frequency is required for recurring fees';
    }
  }
  
  return errors;
};

/**
 * Validates payment form data
 * @param {Object} values - Payment form values
 * @returns {Object} Validation errors, if any
 */
export const validatePaymentForm = (values) => {
  const errors = {};
  
  if (!values.amount || isNaN(values.amount) || parseFloat(values.amount) <= 0) {
    errors.amount = 'A valid amount is required';
  }
  
  if (!values.paymentMethod) {
    errors.paymentMethod = 'Payment method is required';
  }
  
  if (values.paymentMethod === 'bank' && !values.transactionId) {
    errors.transactionId = 'Transaction ID is required for bank transfers';
  }
  
  if (values.paymentMethod === 'bkash' && !values.transactionId) {
    errors.transactionId = 'Transaction ID is required for bKash payments';
  }
  
  if (!values.paymentDate) {
    errors.paymentDate = 'Payment date is required';
  } else if (new Date(values.paymentDate) > new Date()) {
    errors.paymentDate = 'Payment date cannot be in the future';
  }
  
  return errors;
};
