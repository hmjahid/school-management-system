import React, { useState, useEffect, useCallback, useRef } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { 
  ArrowLeftIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationCircleIcon,
  InformationCircleIcon,
  PaperClipIcon,
  XIcon
} from '@heroicons/react/outline';
import { toast } from 'react-toastify';
import useForm from '../../hooks/useForm';
import { validateFeeForm } from '../../utils/validations/feeValidations';
import LoadingSpinner from '../common/LoadingSpinner';
import FileUpload from '../common/FileUpload';
import { uploadFile, getFileUrl } from '../../services/fileService';

const FeeForm = ({ isEdit = false }) => {
  const navigate = useNavigate();
  const { id } = useParams();
  
  const initialValues = {
    name: '',
    code: '',
    description: '',
    amount: '',
    feeType: 'tuition',
    status: 'active',
    dueDate: '',
    classId: '',
    fineAmount: '',
    fineType: 'fixed',
    discountAmount: '',
    discountType: 'fixed',
    isRecurring: false,
    frequency: 'one_time',
    startDate: '',
    endDate: '',
    receipt: null, // For single file upload
    receipts: [],  // For multiple file uploads
  };

  // Refs
  const fileInputRef = useRef(null);
  
  // State for file uploads
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  
  // Initialize form with custom hook
  const {
    values: formData,
    errors,
    isSubmitting: submitting,
    submitError,
    handleChange,
    handleSubmit: handleFormSubmit,
    setFieldValue,
    setValues
  } = useForm(initialValues, validateFeeForm, handleSubmit);
  
  const [loading, setLoading] = useState(isEdit);

  // Fetch fee data if in edit mode
  useEffect(() => {
    if (isEdit && id) {
      const fetchFee = async () => {
        try {
          setLoading(true);
          // In a real app, you would make an API call here
          // const response = await axios.get(`/api/fees/${id}`);
          // setValues(response.data.data);
          
          // Mock data for now
          setTimeout(() => {
            setValues({
              name: 'Tuition Fee - January 2025',
              code: 'TUI-JAN25',
              description: 'Tuition fee for January 2025',
              amount: '5000',
              feeType: 'tuition',
              status: 'active',
              dueDate: '2025-01-31',
              classId: '1',
              fineAmount: '100',
              fineType: 'fixed',
              discountAmount: '0',
              discountType: 'fixed',
              isRecurring: false,
              frequency: 'one_time',
              startDate: '2025-01-01',
              endDate: '2025-01-31',
            });
            setLoading(false);
          }, 500);
        } catch (error) {
          console.error('Error fetching fee:', error);
          toast.error('Failed to load fee data');
          setLoading(false);
        }
      };
      
      fetchFee();
    }
  }, [isEdit, id, setValues]);

  // Handle file upload
  const handleFileUpload = useCallback(async (files) => {
    if (!files || files.length === 0) return;
    
    setUploading(true);
    setUploadProgress(0);
    
    try {
      // For single file upload
      if (!Array.isArray(files)) {
        files = [files];
      }
      
      // Upload files one by one to show progress
      const uploadedFiles = [];
      
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        setUploadProgress(Math.round(((i) / files.length) * 100));
        
        try {
          const response = await uploadFile(file, 'fee_receipts', {
            feeCode: formData.code,
            feeName: formData.name,
            uploadDate: new Date().toISOString()
          });
          
          uploadedFiles.push(response);
          setUploadProgress(Math.round(((i + 1) / files.length) * 100));
        } catch (error) {
          console.error(`Error uploading file ${file.name}:`, error);
          toast.error(`Failed to upload ${file.name}: ${error.message}`);
        }
      }
      
      // Update form data with uploaded files
      if (uploadedFiles.length > 0) {
        if (formData.receipts && Array.isArray(formData.receipts)) {
          // Add to existing receipts
          setFieldValue('receipts', [...formData.receipts, ...uploadedFiles]);
        } else if (uploadedFiles.length === 1) {
          // Single file upload
          setFieldValue('receipt', uploadedFiles[0]);
        } else {
          // Multiple files for the first time
          setFieldValue('receipts', uploadedFiles);
        }
        
        toast.success(`${uploadedFiles.length} file(s) uploaded successfully`);
      }
      
      return uploadedFiles;
    } catch (error) {
      console.error('Error in file upload:', error);
      toast.error(error.message || 'Failed to upload files');
      throw error;
    } finally {
      setUploading(false);
      setUploadProgress(0);
    }
  }, [formData.code, formData.name, setFieldValue]);
  
  // Handle file removal
  const handleRemoveFile = useCallback((index, e) => {
    e?.stopPropagation();
    
    if (formData.receipts && Array.isArray(formData.receipts)) {
      const updatedReceipts = [...formData.receipts];
      updatedReceipts.splice(index, 1);
      setFieldValue('receipts', updatedReceipts);
    } else if (formData.receipt) {
      setFieldValue('receipt', null);
    }
  }, [formData.receipt, formData.receipts, setFieldValue]);
  
  // Handle form submission
  async function handleSubmit(formData) {
    try {
      // Prepare the fee data
      const feeData = {
        ...formData,
        amount: parseFloat(formData.amount),
        fineAmount: formData.fineAmount ? parseFloat(formData.fineAmount) : 0,
        discountAmount: formData.discountAmount ? parseFloat(formData.discountAmount) : 0,
        classId: formData.classId || null,
        // Include file references in the fee data
        receipt: formData.receipt || null,
        receipts: formData.receipts || [],
      };
      
      // In a real app, you would make an API call here
      return new Promise((resolve) => {
        setTimeout(async () => {
          try {
            // Simulate API call
            if (isEdit) {
              // await axios.put(`/api/fees/${id}`, feeData);
              console.log('Updating fee with data:', feeData);
              toast.success('Fee updated successfully');
            } else {
              // await axios.post('/api/fees', feeData);
              console.log('Creating fee with data:', feeData);
              toast.success('Fee created successfully');
            }
            
            // Redirect to fees list
            navigate('/fees');
            resolve();
          } catch (error) {
            console.error('Error saving fee:', error);
            toast.error(error.response?.data?.message || 'Failed to save fee');
            throw error;
          }
        }, 1000);
      });
    } catch (error) {
      console.error('Error in handleSubmit:', error);
      throw error;
    }
  }

  // Fee type options
  const feeTypes = [
    { value: 'tuition', label: 'Tuition' },
    { value: 'admission', label: 'Admission' },
    { value: 'exam', label: 'Exam' },
    { value: 'transport', label: 'Transport' },
    { value: 'library', label: 'Library' },
    { value: 'other', label: 'Other' },
  ];

  // Class options (in a real app, this would come from an API)
  const classOptions = [
    { value: '', label: 'All Classes' },
    { value: '1', label: 'Class 1' },
    { value: '2', label: 'Class 2' },
    { value: '3', label: 'Class 3' },
    { value: '4', label: 'Class 4' },
    { value: '5', label: 'Class 5' },
  ];

  // Frequency options for recurring fees
  const frequencyOptions = [
    { value: 'one_time', label: 'One Time' },
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'quarterly', label: 'Quarterly' },
    { value: 'half_yearly', label: 'Half Yearly' },
    { value: 'yearly', label: 'Yearly' },
  ];

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <LoadingSpinner size="h-12 w-12" />
      </div>
    );
  }
  
  // Show loading overlay when submitting
  const loadingOverlay = submitting && (
    <div className="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
      <div className="bg-white p-6 rounded-lg shadow-xl">
        <div className="flex items-center space-x-4">
          <LoadingSpinner size="h-8 w-8" />
          <span className="text-gray-700">
            {isEdit ? 'Updating fee...' : 'Creating fee...'}
          </span>
        </div>
      </div>
    </div>
  );

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative">
      {loadingOverlay}
      
      {/* Error message */}
      {submitError && (
        <div className="mb-6 bg-red-50 border-l-4 border-red-500 p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <ExclamationCircleIcon className="h-5 w-5 text-red-500" aria-hidden="true" />
            </div>
            <div className="ml-3">
              <p className="text-sm text-red-700">{submitError}</p>
            </div>
          </div>
        </div>
      )}
      
      {/* Form info */}
      <div className="mb-6 bg-blue-50 border-l-4 border-blue-500 p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <InformationCircleIcon className="h-5 w-5 text-blue-500" aria-hidden="true" />
          </div>
          <div className="ml-3">
            <p className="text-sm text-blue-700">
              Fields marked with <span className="text-red-500">*</span> are required.
            </p>
            {uploading && (
              <div className="mt-2 w-full bg-gray-200 rounded-full h-2.5">
                <div 
                  className="bg-blue-600 h-2.5 rounded-full" 
                  style={{ width: `${uploadProgress}%` }}
                ></div>
                <p className="text-xs text-gray-600 mt-1">
                  Uploading: {uploadProgress}%
                </p>
              </div>
            )}
          </div>
        </div>
      </div>
      <div className="mb-6">
        <button
          onClick={() => navigate(-1)}
          className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-900"
        >
          <ArrowLeftIcon className="h-5 w-5 mr-1" />
          Back to Fees
        </button>
        <h1 className="mt-2 text-2xl font-semibold text-gray-900">
          {isEdit ? 'Edit Fee' : 'Add New Fee'}
        </h1>
      </div>

      <form onSubmit={handleFormSubmit} className="space-y-6">
        <div className="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
          <div className="md:grid md:grid-cols-3 md:gap-6">
            <div className="md:col-span-1">
              <h3 className="text-lg font-medium leading-6 text-gray-900">Fee Information</h3>
              <p className="mt-1 text-sm text-gray-500">
                Basic information about the fee.
              </p>
            </div>
            <div className="mt-5 md:mt-0 md:col-span-2">
              <div className="grid grid-cols-6 gap-6">
                <div className="col-span-6 sm:col-span-3">
                  <label htmlFor="name" className="block text-sm font-medium text-gray-700">
                    Fee Name <span className="text-red-500">*</span>
                  </label>
                  <div className="relative rounded-md shadow-sm">
                    <input
                      type="text"
                      name="name"
                      id="name"
                      value={formData.name}
                      onChange={handleChange}
                      className={`block w-full rounded-md ${errors.name ? 'border-red-300 pr-10 text-red-900 placeholder-red-300 focus:border-red-500 focus:outline-none focus:ring-red-500' : 'border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'} sm:text-sm`}
                      aria-invalid={!!errors.name}
                      aria-describedby={errors.name ? 'name-error' : ''}
                    />
                    {errors.name && (
                      <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <ExclamationCircleIcon className="h-5 w-5 text-red-500" aria-hidden="true" />
                      </div>
                    )}
                  </div>
                  {errors.name && (
                    <p className="mt-2 text-sm text-red-600" id="name-error">
                      {errors.name}
                    </p>
                  )}
                  {errors.name && (
                    <p className="mt-1 text-sm text-red-600">{errors.name}</p>
                  )}
                </div>

                <div className="col-span-6 sm:col-span-3">
                  <label htmlFor="code" className="block text-sm font-medium text-gray-700">
                    Code <span className="text-red-500">*</span>
                  </label>
                  <div className="relative rounded-md shadow-sm">
                    <input
                      type="text"
                      name="code"
                      id="code"
                      value={formData.code}
                      onChange={handleChange}
                      className={`block w-full rounded-md ${errors.code ? 'border-red-300 pr-10 text-red-900 placeholder-red-300 focus:border-red-500 focus:outline-none focus:ring-red-500' : 'border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'} sm:text-sm`}
                      aria-invalid={!!errors.code}
                      aria-describedby={errors.code ? 'code-error' : ''}
                    />
                    {errors.code && (
                      <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <ExclamationCircleIcon className="h-5 w-5 text-red-500" aria-hidden="true" />
                      </div>
                    )}
                  </div>
                  {errors.code && (
                    <p className="mt-2 text-sm text-red-600" id="code-error">
                      {errors.code}
                    </p>
                  )}
                  {errors.code && (
                    <p className="mt-1 text-sm text-red-600">{errors.code}</p>
                  )}
                </div>

                <div className="col-span-6">
                  <label htmlFor="description" className="block text-sm font-medium text-gray-700">
                    Description
                  </label>
                  <textarea
                    name="description"
                    id="description"
                    rows={3}
                    value={formData.description}
                    onChange={handleChange}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  />
                </div>

                <div className="col-span-6 sm:col-span-3">
                  <label htmlFor="feeType" className="block text-sm font-medium text-gray-700">
                    Fee Type <span className="text-red-500">*</span>
                  </label>
                  <select
                    id="feeType"
                    name="feeType"
                    value={formData.feeType}
                    onChange={handleChange}
                    className="mt-1 block w-full rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                  >
                    {feeTypes.map((type) => (
                      <option key={type.value} value={type.value}>
                        {type.label}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="col-span-6 sm:col-span-3">
                  <label htmlFor="classId" className="block text-sm font-medium text-gray-700">
                    Class
                  </label>
                  <select
                    id="classId"
                    name="classId"
                    value={formData.classId}
                    onChange={handleChange}
                    className="mt-1 block w-full rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                  >
                    {classOptions.map((option) => (
                      <option key={option.value} value={option.value}>
                        {option.label}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="col-span-6 sm:col-span-3">
                  <label htmlFor="amount" className="block text-sm font-medium text-gray-700">
                    Amount (BDT) <span className="text-red-500">*</span>
                  </label>
                  <div className="mt-1 relative rounded-md shadow-sm">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <span className="text-gray-500 sm:text-sm">৳</span>
                    </div>
                    <div className="relative rounded-md shadow-sm">
                      <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <span className="text-gray-500 sm:text-sm">৳</span>
                      </div>
                      <input
                        type="number"
                        name="amount"
                        id="amount"
                        min="0"
                        step="0.01"
                        value={formData.amount}
                        onChange={handleChange}
                        className={`block w-full pl-7 pr-10 sm:text-sm rounded-md ${errors.amount ? 'border-red-300 pr-10 text-red-900 placeholder-red-300 focus:border-red-500 focus:outline-none focus:ring-red-500' : 'border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'}`}
                        placeholder="0.00"
                        aria-invalid={!!errors.amount}
                        aria-describedby={errors.amount ? 'amount-error' : ''}
                      />
                      {errors.amount && (
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                          <ExclamationCircleIcon className="h-5 w-5 text-red-500" aria-hidden="true" />
                        </div>
                      )}
                    </div>
                    {errors.amount && (
                      <p className="mt-2 text-sm text-red-600" id="amount-error">
                        {errors.amount}
                      </p>
                    )}
                  </div>
                  {errors.amount && (
                    <p className="mt-1 text-sm text-red-600">{errors.amount}</p>
                  )}
                </div>

                <div className="col-span-6 sm:col-span-3">
                  <label htmlFor="dueDate" className="block text-sm font-medium text-gray-700">
                    Due Date <span className="text-red-500">*</span>
                  </label>
                  <div className="relative rounded-md shadow-sm">
                    <input
                      type="date"
                      name="dueDate"
                      id="dueDate"
                      value={formData.dueDate}
                      onChange={handleChange}
                      min={new Date().toISOString().split('T')[0]}
                      className={`block w-full rounded-md ${errors.dueDate ? 'border-red-300 pr-10 text-red-900 placeholder-red-300 focus:border-red-500 focus:outline-none focus:ring-red-500' : 'border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'} sm:text-sm`}
                      aria-invalid={!!errors.dueDate}
                      aria-describedby={errors.dueDate ? 'dueDate-error' : ''}
                    />
                    {errors.dueDate && (
                      <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                        <ExclamationCircleIcon className="h-5 w-5 text-red-500" aria-hidden="true" />
                      </div>
                    )}
                  </div>
                  {errors.dueDate && (
                    <p className="mt-2 text-sm text-red-600" id="dueDate-error">
                      {errors.dueDate}
                    </p>
                  )}
                  {errors.dueDate && (
                    <p className="mt-1 text-sm text-red-600">{errors.dueDate}</p>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Fine Settings */}
        <div className="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
          <div className="md:grid md:grid-cols-3 md:gap-6">
            <div className="md:col-span-1">
              <h3 className="text-lg font-medium leading-6 text-gray-900">Fine Settings</h3>
              <p className="mt-1 text-sm text-gray-500">
                Configure late payment fines.
              </p>
            </div>
            <div className="mt-5 md:mt-0 md:col-span-2">
              <div className="grid grid-cols-6 gap-6">
                <div className="col-span-6 sm:col-span-3">
                  <label htmlFor="fineAmount" className="block text-sm font-medium text-gray-700">
                    Fine Amount
                  </label>
                  <div className="mt-1 relative rounded-md shadow-sm">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <span className="text-gray-500 sm:text-sm">৳</span>
                    </div>
                    <div className="relative rounded-md shadow-sm">
                      <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <span className="text-gray-500 sm:text-sm">৳</span>
                      </div>
                      <input
                        type="number"
                        name="fineAmount"
                        id="fineAmount"
                        min="0"
                        step="0.01"
                        value={formData.fineAmount}
                        onChange={handleChange}
                        className={`block w-full pl-7 pr-10 sm:text-sm rounded-md ${errors.fineAmount ? 'border-red-300 pr-10 text-red-900 placeholder-red-300 focus:border-red-500 focus:outline-none focus:ring-red-500' : 'border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'}`}
                        placeholder="0.00"
                        aria-invalid={!!errors.fineAmount}
                        aria-describedby={errors.fineAmount ? 'fineAmount-error' : ''}
                      />
                      {errors.fineAmount && (
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                          <ExclamationCircleIcon className="h-5 w-5 text-red-500" aria-hidden="true" />
                        </div>
                      )}
                    </div>
                    {errors.fineAmount && (
                      <p className="mt-2 text-sm text-red-600" id="fineAmount-error">
                        {errors.fineAmount}
                      </p>
                    )}
                  </div>
                </div>

                <div className="col-span-6 sm:col-span-3">
                  <label htmlFor="fineType" className="block text-sm font-medium text-gray-700">
                    Fine Type
                  </label>
                  <select
                    id="fineType"
                    name="fineType"
                    value={formData.fineType}
                    onChange={handleChange}
                    className="mt-1 block w-full rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                  >
                    <option value="fixed">Fixed Amount</option>
                    <option value="percentage">Percentage of Fee</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Discount Settings */}
        <div className="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
          <div className="md:grid md:grid-cols-3 md:gap-6">
            <div className="md:col-span-1">
              <h3 className="text-lg font-medium leading-6 text-gray-900">Discount Settings</h3>
              <p className="mt-1 text-sm text-gray-500">
                Configure early payment discounts.
              </p>
            </div>
            <div className="mt-5 md:mt-0 md:col-span-2">
              <div className="grid grid-cols-6 gap-6">
                <div className="col-span-6 sm:col-span-3">
                  <label htmlFor="discountAmount" className="block text-sm font-medium text-gray-700">
                    Discount Amount
                  </label>
                  <div className="mt-1 relative rounded-md shadow-sm">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <span className="text-gray-500 sm:text-sm">৳</span>
                    </div>
                    <div className="relative rounded-md shadow-sm">
                      <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <span className="text-gray-500 sm:text-sm">৳</span>
                      </div>
                      <input
                        type="number"
                        name="discountAmount"
                        id="discountAmount"
                        min="0"
                        step="0.01"
                        value={formData.discountAmount}
                        onChange={handleChange}
                        className={`block w-full pl-7 pr-10 sm:text-sm rounded-md ${errors.discountAmount ? 'border-red-300 pr-10 text-red-900 placeholder-red-300 focus:border-red-500 focus:outline-none focus:ring-red-500' : 'border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'}`}
                        placeholder="0.00"
                        aria-invalid={!!errors.discountAmount}
                        aria-describedby={errors.discountAmount ? 'discountAmount-error' : ''}
                      />
                      {errors.discountAmount && (
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                          <ExclamationCircleIcon className="h-5 w-5 text-red-500" aria-hidden="true" />
                        </div>
                      )}
                    </div>
                    {errors.discountAmount && (
                      <p className="mt-2 text-sm text-red-600" id="discountAmount-error">
                        {errors.discountAmount}
                      </p>
                    )}
                  </div>
                </div>

                <div className="col-span-6 sm:col-span-3">
                  <label htmlFor="discountType" className="block text-sm font-medium text-gray-700">
                    Discount Type
                  </label>
                  <select
                    id="discountType"
                    name="discountType"
                    value={formData.discountType}
                    onChange={handleChange}
                    className="mt-1 block w-full rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                  >
                    <option value="fixed">Fixed Amount</option>
                    <option value="percentage">Percentage of Fee</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Recurring Fee Settings */}
        <div className="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
          <div className="md:grid md:grid-cols-3 md:gap-6">
            <div className="md:col-span-1">
              <h3 className="text-lg font-medium leading-6 text-gray-900">Recurring Fee</h3>
              <p className="mt-1 text-sm text-gray-500">
                Set up recurring fee generation.
              </p>
            </div>
            <div className="mt-5 md:mt-0 md:col-span-2">
              <div className="flex items-start">
                <div className="flex items-center h-5">
                  <input
                    id="isRecurring"
                    name="isRecurring"
                    type="checkbox"
                    checked={formData.isRecurring}
                    onChange={handleChange}
                    className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                  />
                </div>
                <div className="ml-3 text-sm">
                  <label htmlFor="isRecurring" className="font-medium text-gray-700">
                    This is a recurring fee
                  </label>
                  <p className="text-gray-500">
                    Enable to automatically generate this fee at regular intervals.
                  </p>
                </div>
              </div>

              {formData.isRecurring && (
                <div className="mt-4 grid grid-cols-6 gap-6">
                  <div className="col-span-6 sm:col-span-3">
                    <label htmlFor="frequency" className="block text-sm font-medium text-gray-700">
                      Frequency <span className="text-red-500">*</span>
                    </label>
                    <select
                      id="frequency"
                      name="frequency"
                      value={formData.frequency}
                      onChange={handleChange}
                      className="mt-1 block w-full rounded-md border border-gray-300 bg-white py-2 px-3 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                    >
                      {frequencyOptions.map((option) => (
                        <option key={option.value} value={option.value}>
                          {option.label}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="col-span-6 sm:col-span-3">
                    <label htmlFor="startDate" className="block text-sm font-medium text-gray-700">
                      Start Date <span className="text-red-500">*</span>
                    </label>
                    <div className="relative rounded-md shadow-sm">
                      <input
                        type="date"
                        name="startDate"
                        id="startDate"
                        value={formData.startDate}
                        onChange={handleChange}
                        min={new Date().toISOString().split('T')[0]}
                        className={`block w-full rounded-md ${errors.startDate ? 'border-red-300 pr-10 text-red-900 placeholder-red-300 focus:border-red-500 focus:outline-none focus:ring-red-500' : 'border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'} sm:text-sm`}
                        aria-invalid={!!errors.startDate}
                        aria-describedby={errors.startDate ? 'startDate-error' : ''}
                      />
                      {errors.startDate && (
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                          <ExclamationCircleIcon className="h-5 w-5 text-red-500" aria-hidden="true" />
                        </div>
                      )}
                    </div>
                    {errors.startDate && (
                      <p className="mt-2 text-sm text-red-600" id="startDate-error">
                        {errors.startDate}
                      </p>
                    )}
                    {errors.startDate && (
                      <p className="mt-1 text-sm text-red-600">{errors.startDate}</p>
                    )}
                  </div>

                  <div className="col-span-6 sm:col-span-3">
                    <label htmlFor="endDate" className="block text-sm font-medium text-gray-700">
                      End Date <span className="text-red-500">*</span>
                    </label>
                    <div className="relative rounded-md shadow-sm">
                      <input
                        type="date"
                        name="endDate"
                        id="endDate"
                        value={formData.endDate}
                        onChange={handleChange}
                        min={formData.startDate || new Date().toISOString().split('T')[0]}
                        className={`block w-full rounded-md ${errors.endDate ? 'border-red-300 pr-10 text-red-900 placeholder-red-300 focus:border-red-500 focus:outline-none focus:ring-red-500' : 'border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500'} sm:text-sm`}
                        aria-invalid={!!errors.endDate}
                        aria-describedby={errors.endDate ? 'endDate-error' : ''}
                      />
                      {errors.endDate && (
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                          <ExclamationCircleIcon className="h-5 w-5 text-red-500" aria-hidden="true" />
                        </div>
                      )}
                    </div>
                    {errors.endDate && (
                      <p className="mt-2 text-sm text-red-600" id="endDate-error">
                        {errors.endDate}
                      </p>
                    )}
                    {errors.endDate && (
                      <p className="mt-1 text-sm text-red-600">{errors.endDate}</p>
                    )}
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Status */}
        <div className="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
          <div className="md:grid md:grid-cols-3 md:gap-6">
            <div className="md:col-span-1">
              <h3 className="text-lg font-medium leading-6 text-gray-900">Status</h3>
              <p className="mt-1 text-sm text-gray-500">
                Set the fee status.
              </p>
            </div>
            <div className="mt-5 md:mt-0 md:col-span-2">
              <div className="space-y-4">
                <div className="flex items-center">
                  <input
                    id="status-active"
                    name="status"
                    type="radio"
                    value="active"
                    checked={formData.status === 'active'}
                    onChange={handleChange}
                    className="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                  />
                  <label htmlFor="status-active" className="ml-3 block text-sm font-medium text-gray-700">
                    <span className="flex items-center">
                      <CheckCircleIcon className="h-5 w-5 text-green-500 mr-2" />
                      Active
                    </span>
                    <span className="text-gray-500 text-xs">This fee is currently active and visible to students.</span>
                  </label>
                </div>

                <div className="flex items-start">
                  <input
                    id="status-inactive"
                    name="status"
                    type="radio"
                    value="inactive"
                    checked={formData.status === 'inactive'}
                    onChange={handleChange}
                    className="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1"
                  />
                  <label htmlFor="status-inactive" className="ml-3 block text-sm font-medium text-gray-700">
                    <span className="flex items-center">
                      <XCircleIcon className="h-5 w-5 text-gray-400 mr-2" />
                      Inactive
                    </span>
                    <span className="text-gray-500 text-xs">This fee is hidden from students but can be reactivated later.</span>
                  </label>
                </div>
              </div>
            </div>
            
            {/* Receipt Upload Section */}
            <div className="pt-5 border-t border-gray-200">
              <div className="mt-6">
                <h3 className="text-lg font-medium leading-6 text-gray-900">Receipts</h3>
                <p className="mt-1 text-sm text-gray-500">
                  Upload receipts or supporting documents for this fee.
                </p>
                
                <div className="mt-4">
                  <FileUpload
                    accept="image/*,.pdf"
                    maxSize={10}
                    onChange={handleFileUpload}
                    multiple={true}
                    value={formData.receipts || []}
                    label="Upload Receipts"
                    helpText="Supports JPG, PNG, and PDF files up to 10MB"
                  />
                </div>
                
                {/* Display uploaded receipts */}
                {(formData.receipt || (formData.receipts && formData.receipts.length > 0)) && (
                  <div className="mt-4">
                    <h4 className="text-sm font-medium text-gray-700 mb-2">
                      Attached Receipts
                    </h4>
                    <ul className="border border-gray-200 rounded-md divide-y divide-gray-200">
                      {formData.receipt && (
                        <li className="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
                          <div className="w-0 flex-1 flex items-center">
                            <PaperClipIcon className="flex-shrink-0 h-5 w-5 text-gray-400" />
                            <div className="ml-4 flex-1 truncate">
                              <div className="text-sm font-medium text-indigo-600 truncate">
                                {formData.receipt.name || 'Receipt'}
                              </div>
                              <div className="text-sm text-gray-500">
                                {formData.receipt.size ? `${(formData.receipt.size / 1024).toFixed(1)} KB` : 'N/A'}
                              </div>
                            </div>
                          </div>
                          <div className="ml-4 flex-shrink-0">
                            <button
                              type="button"
                              onClick={() => setFieldValue('receipt', null)}
                              className="text-gray-400 hover:text-gray-500"
                            >
                              <XIcon className="h-5 w-5" aria-hidden="true" />
                            </button>
                          </div>
                        </li>
                      )}
                      
                      {formData.receipts && formData.receipts.map((file, index) => (
                        <li key={index} className="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
                          <div className="w-0 flex-1 flex items-center">
                            <PaperClipIcon className="flex-shrink-0 h-5 w-5 text-gray-400" />
                            <div className="ml-4 flex-1 truncate">
                              <a
                                href={file.url || getFileUrl(file.path)}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-sm font-medium text-indigo-600 hover:text-indigo-500 truncate"
                              >
                                {file.originalName || file.name || 'Receipt'}
                              </a>
                              <div className="text-sm text-gray-500">
                                {file.size ? `${(file.size / 1024).toFixed(1)} KB` : 'N/A'}
                              </div>
                            </div>
                          </div>
                          <div className="ml-4 flex-shrink-0">
                            <button
                              type="button"
                              onClick={(e) => handleRemoveFile(index, e)}
                              className="text-gray-400 hover:text-gray-500"
                            >
                              <XIcon className="h-5 w-5" aria-hidden="true" />
                            </button>
                          </div>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>

        <div className="flex justify-end space-x-3">
          <button
            type="button"
            onClick={() => navigate('/fees')}
            className="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
            disabled={submitting}
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={submitting}
            className="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {submitting ? (
              <>
                <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {isEdit ? 'Updating...' : 'Creating...'}
              </>
            ) : isEdit ? (
              'Update Fee'
            ) : (
              'Create Fee'
            )}
          </button>
        </div>
      </form>
    </div>
  );
};

export default FeeForm;
