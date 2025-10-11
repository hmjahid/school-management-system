import React, { useCallback, useState } from 'react';
import PropTypes from 'prop-types';
import { XIcon, UploadIcon, DocumentTextIcon, PhotographIcon, PaperClipIcon } from '@heroicons/react/outline';

/**
 * FileUpload component for handling file uploads with preview and validation
 * @param {Object} props - Component props
 * @param {string} [props.accept] - Comma-separated list of accepted file types (e.g., 'image/*,.pdf')
 * @param {number} [props.maxSize=5] - Maximum file size in MB
 * @param {Function} [props.onChange] - Callback when files are selected/removed
 * @param {string} [props.label='Upload files'] - Label text
 * @param {string} [props.helpText] - Help text to display below the input
 * @param {boolean} [props.multiple=false] - Allow multiple file selection
 * @param {Array} [props.value=[]] - Array of currently selected files
 * @param {Function} [props.onRemove] - Callback when a file is removed
 * @returns {JSX.Element} FileUpload component
 */
const FileUpload = ({
  accept = 'image/*,.pdf,.doc,.docx,.xls,.xlsx',
  maxSize = 5, // MB
  onChange,
  label = 'Upload files',
  helpText,
  multiple = false,
  value = [],
  onRemove,
  ...props
}) => {
  const [dragActive, setDragActive] = useState(false);
  const [error, setError] = useState('');

  const handleDrag = useCallback((e) => {
    e.preventDefault();
    e.stopPropagation();
    
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  }, []);

  const processFile = (file) => {
    // Check file size
    if (file.size > maxSize * 1024 * 1024) {
      setError(`File size should be less than ${maxSize}MB`);
      return null;
    }

    // Check file type
    const acceptedTypes = accept.split(',').map(type => type.trim().toLowerCase());
    const fileExtension = file.name.split('.').pop().toLowerCase();
    const fileType = file.type.toLowerCase();
    
    const isAccepted = acceptedTypes.some(type => {
      if (type.startsWith('.')) {
        return `.${fileExtension}` === type;
      }
      if (type.endsWith('/*')) {
        return fileType.startsWith(type.split('/*')[0]);
      }
      return fileType === type;
    });

    if (!isAccepted) {
      setError(`File type not supported. Accepted types: ${accept}`);
      return null;
    }

    return file;
  };

  const handleDrop = useCallback((e) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    setError('');

    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      const files = Array.from(e.dataTransfer.files);
      handleFiles(files);
    }
  }, [accept, maxSize, multiple, onChange]);

  const handleChange = (e) => {
    e.preventDefault();
    setError('');

    if (e.target.files && e.target.files.length > 0) {
      const files = Array.from(e.target.files);
      handleFiles(files);
      // Reset the input value to allow selecting the same file again
      e.target.value = '';
    }
  };

  const handleFiles = (files) => {
    const processedFiles = files.map(processFile).filter(Boolean);
    
    if (processedFiles.length === 0) {
      return;
    }

    const newFiles = multiple ? [...value, ...processedFiles] : processedFiles;
    
    if (onChange) {
      onChange(multiple ? newFiles : newFiles[0]);
    }
  };

  const handleRemove = (index, e) => {
    e.stopPropagation();
    
    if (onRemove) {
      onRemove(index);
    } else if (onChange) {
      const newFiles = [...value];
      newFiles.splice(index, 1);
      onChange(multiple ? newFiles : null);
    }
  };

  const getFileIcon = (file) => {
    const type = file.type || '';
    
    if (type.startsWith('image/')) {
      return <PhotographIcon className="h-5 w-5 text-blue-500" />;
    }
    
    if (type === 'application/pdf') {
      return <DocumentTextIcon className="h-5 w-5 text-red-500" />;
    }
    
    if (type.includes('word') || type.includes('document')) {
      return <DocumentTextIcon className="h-5 w-5 text-blue-600" />;
    }
    
    if (type.includes('excel') || type.includes('spreadsheet')) {
      return <DocumentTextIcon className="h-5 w-5 text-green-600" />;
    }
    
    return <PaperClipIcon className="h-5 w-5 text-gray-500" />;
  };

  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  return (
    <div className="space-y-2">
      {label && (
        <label className="block text-sm font-medium text-gray-700">
          {label}
          {props.required && <span className="text-red-500 ml-1">*</span>}
        </label>
      )}
      
      <div
        className={`mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed rounded-md ${
          dragActive ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300'
        } ${error ? 'border-red-300' : ''}`}
        onDragEnter={handleDrag}
        onDragLeave={handleDrag}
        onDragOver={handleDrag}
        onDrop={handleDrop}
      >
        <div className="space-y-1 text-center">
          <div className="flex text-sm text-gray-600 justify-center">
            <label
              htmlFor="file-upload"
              className="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
            >
              <span>Upload a file</span>
              <input
                id="file-upload"
                name="file-upload"
                type="file"
                className="sr-only"
                onChange={handleChange}
                accept={accept}
                multiple={multiple}
              />
            </label>
            <p className="pl-1">or drag and drop</p>
          </div>
          <p className="text-xs text-gray-500">
            {accept} up to {maxSize}MB
          </p>
        </div>
      </div>
      
      {error && (
        <p className="mt-2 text-sm text-red-600" id="file-upload-error">
          {error}
        </p>
      )}
      
      {helpText && (
        <p className="mt-2 text-sm text-gray-500">
          {helpText}
        </p>
      )}
      
      {value && value.length > 0 && (
        <div className="mt-4 space-y-2">
          <h4 className="text-sm font-medium text-gray-700">
            {multiple ? 'Selected Files' : 'Selected File'}
          </h4>
          <ul className="border border-gray-200 rounded-md divide-y divide-gray-200">
            {(Array.isArray(value) ? value : [value]).map((file, index) => (
              <li key={index} className="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
                <div className="w-0 flex-1 flex items-center">
                  {file.type?.startsWith('image/') ? (
                    <img
                      src={URL.createObjectURL(file)}
                      alt={file.name}
                      className="flex-shrink-0 h-10 w-10 object-cover rounded"
                    />
                  ) : (
                    <div className="flex-shrink-0 h-10 w-10 flex items-center justify-center bg-gray-100 rounded">
                      {getFileIcon(file)}
                    </div>
                  )}
                  <div className="ml-4 flex-1 truncate">
                    <div className="text-sm font-medium text-gray-900 truncate">
                      {file.name}
                    </div>
                    <div className="text-sm text-gray-500">
                      {formatFileSize(file.size)}
                    </div>
                  </div>
                </div>
                <div className="ml-4 flex-shrink-0">
                  <button
                    type="button"
                    onClick={(e) => handleRemove(index, e)}
                    className="text-gray-400 hover:text-gray-500 focus:outline-none"
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
  );
};

FileUpload.propTypes = {
  accept: PropTypes.string,
  maxSize: PropTypes.number,
  onChange: PropTypes.func,
  onRemove: PropTypes.func,
  label: PropTypes.string,
  helpText: PropTypes.string,
  multiple: PropTypes.bool,
  value: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.instanceOf(File)),
    PropTypes.instanceOf(File)
  ]),
  required: PropTypes.bool,
};

export default FileUpload;
