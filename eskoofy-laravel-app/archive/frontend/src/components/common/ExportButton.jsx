import React, { Fragment, useState } from 'react';
import { Menu, Transition } from '@headlessui/react';
import { 
  DocumentDownloadIcon,
  DocumentTextIcon,
  TableIcon,
  DocumentReportIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/react/outline';
import { toast } from 'react-toastify';

/**
 * ExportButton component for exporting data in different formats
 * @param {Object} props - Component props
 * @param {string} [props.type='fees'] - Type of data to export ('fees' or 'payments')
 * @param {Object} [props.filters={}] - Filters to apply to the export
 * @param {string} [props.filename] - Base filename for the exported file
 * @param {Function} [props.onExport] - Callback when export is initiated
 * @param {Function} [props.onError] - Callback when export fails
 * @returns {JSX.Element} ExportButton component
 */
const ExportButton = ({
  type = 'fees',
  filters = {},
  filename,
  onExport,
  onError,
  children,
  ...props
}) => {
  const [isExporting, setIsExporting] = useState(false);
  const [lastExport, setLastExport] = useState(null);
  
  // Import the export function dynamically to reduce bundle size
  const handleExport = async (format) => {
    try {
      setIsExporting(true);
      
      // Dynamically import the export service
      const { exportFees, exportPayments } = await import('../../services/exportService');
      
      // Determine which export function to use based on the type
      const exportFn = type === 'payments' ? exportPayments : exportFees;
      
      // Generate a filename if not provided
      const exportFilename = filename || `${type}-export`;
      
      // Call the export function
      await exportFn(filters, format, exportFilename);
      
      // Update last export time
      const now = new Date();
      setLastExport(now);
      
      // Show success message
      toast.success(
        <div>
          <div className="flex items-center">
            <CheckCircleIcon className="h-5 w-5 text-green-500 mr-2" />
            <span>Export completed successfully!</span>
          </div>
          <div className="text-xs text-gray-500 mt-1">
            {now.toLocaleString()}
          </div>
        </div>,
        { autoClose: 3000 }
      );
      
      // Call the onExport callback if provided
      if (onExport) {
        onExport({ format, filename: exportFilename, timestamp: now });
      }
    } catch (error) {
      console.error(`Error exporting ${type}:`, error);
      
      // Show error message
      toast.error(
        <div>
          <div className="flex items-center">
            <XCircleIcon className="h-5 w-5 text-red-500 mr-2" />
            <span>Export failed: {error.message || 'Unknown error'}</span>
          </div>
        </div>,
        { autoClose: 5000 }
      );
      
      // Call the onError callback if provided
      if (onError) {
        onError(error);
      }
    } finally {
      setIsExporting(false);
    }
  };
  
  // Format the last export time
  const formatLastExport = (date) => {
    if (!date) return null;
    
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
      return `Last exported ${diffInSeconds} seconds ago`;
    } else if (diffInSeconds < 3600) {
      const minutes = Math.floor(diffInSeconds / 60);
      return `Last exported ${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    } else if (diffInSeconds < 86400) {
      const hours = Math.floor(diffInSeconds / 3600);
      return `Last exported ${hours} hour${hours > 1 ? 's' : ''} ago`;
    } else {
      return `Last exported on ${date.toLocaleString()}`;
    }
  };

  return (
    <div className="relative">
      <Menu as="div" className="relative inline-block text-left">
        <div>
          <Menu.Button
            className={`inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ${
              isExporting ? 'opacity-75 cursor-not-allowed' : ''
            }`}
            disabled={isExporting}
            {...props}
          >
            {isExporting ? (
              <>
                <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {children || 'Exporting...'}
              </>
            ) : (
              <>
                <DocumentDownloadIcon className="-ml-1 mr-2 h-5 w-5 text-gray-500" aria-hidden="true" />
                {children || 'Export'}
              </>
            )}
          </Menu.Button>
        </div>

        <Transition
          as={Fragment}
          enter="transition ease-out duration-100"
          enterFrom="transform opacity-0 scale-95"
          enterTo="transform opacity-100 scale-100"
          leave="transition ease-in duration-75"
          leaveFrom="transform opacity-100 scale-100"
          leaveTo="transform opacity-0 scale-95"
        >
          <Menu.Items className="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
            <div className="py-1">
              <Menu.Item>
                {({ active }) => (
                  <button
                    onClick={() => handleExport('excel')}
                    className={`${
                      active ? 'bg-gray-100 text-gray-900' : 'text-gray-700'
                    } group flex items-center px-4 py-2 text-sm w-full text-left`}
                  >
                    <TableIcon className="mr-3 h-5 w-5 text-green-600" aria-hidden="true" />
                    <span>Export to Excel</span>
                  </button>
                )}
              </Menu.Item>
              <Menu.Item>
                {({ active }) => (
                  <button
                    onClick={() => handleExport('csv')}
                    className={`${
                      active ? 'bg-gray-100 text-gray-900' : 'text-gray-700'
                    } group flex items-center px-4 py-2 text-sm w-full text-left`}
                  >
                    <DocumentTextIcon className="mr-3 h-5 w-5 text-blue-600" aria-hidden="true" />
                    <span>Export to CSV</span>
                  </button>
                )}
              </Menu.Item>
              <Menu.Item>
                {({ active }) => (
                  <button
                    onClick={() => handleExport('pdf')}
                    className={`${
                      active ? 'bg-gray-100 text-gray-900' : 'text-gray-700'
                    } group flex items-center px-4 py-2 text-sm w-full text-left`}
                  >
                    <DocumentReportIcon className="mr-3 h-5 w-5 text-red-600" aria-hidden="true" />
                    <span>Export to PDF</span>
                  </button>
                )}
              </Menu.Item>
            </div>
          </Menu.Items>
        </Transition>
      </Menu>
      
      {lastExport && (
        <p className="mt-1 text-xs text-gray-500 text-right">
          {formatLastExport(lastExport)}
        </p>
      )}
    </div>
  );
};

export default ExportButton;
