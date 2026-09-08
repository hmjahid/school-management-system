import React, { useState } from 'react';
import { FiDatabase, FiDownload, FiUpload, FiClock, FiHardDrive, FiMail, FiInfo, FiAlertCircle } from 'react-icons/fi';

const BackupSettings = () => {
  const [backupSettings, setBackupSettings] = useState({
    autoBackup: true,
    backupFrequency: 'daily',
    backupTime: '02:00',
    backupRetention: 30,
    backupStorage: 'local',
    backupEmail: '',
    backupPath: '/var/backups/school-management',
    lastBackup: '2023-04-15 02:15:23',
    backupSize: '2.4 GB',
    backupStatus: 'success',
  });

  const [isCreatingBackup, setIsCreatingBackup] = useState(false);
  const [backupMessage, setBackupMessage] = useState({ type: '', text: '' });

  const frequencies = [
    { value: 'hourly', label: 'Hourly' },
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
  ];

  const storageOptions = [
    { value: 'local', label: 'Local Server', icon: <FiHardDrive className="h-5 w-5 mr-2 text-gray-500" /> },
    { value: 's3', label: 'Amazon S3', icon: <FiDatabase className="h-5 w-5 mr-2 text-yellow-500" /> },
    { value: 'google', label: 'Google Drive', icon: <FiDatabase className="h-5 w-5 mr-2 text-blue-500" /> },
    { value: 'dropbox', label: 'Dropbox', icon: <FiDatabase className="h-5 w-5 mr-2 text-blue-400" /> },
  ];

  const backupHistory = [
    { id: 1, date: '2023-04-15 02:15:23', type: 'Automatic', status: 'success', size: '2.4 GB' },
    { id: 2, date: '2023-04-14 02:15:22', type: 'Automatic', status: 'success', size: '2.3 GB' },
    { id: 3, date: '2023-04-13 02:15:20', type: 'Manual', status: 'success', size: '2.3 GB' },
    { id: 4, date: '2023-04-12 02:15:25', type: 'Automatic', status: 'failed', size: '0 B' },
    { id: 5, date: '2023-04-11 02:15:21', type: 'Automatic', status: 'success', size: '2.3 GB' },
  ];

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setBackupSettings(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const handleCreateBackup = () => {
    setIsCreatingBackup(true);
    setBackupMessage({ type: '', text: '' });
    
    // Simulate backup creation
    setTimeout(() => {
      setIsCreatingBackup(false);
      setBackupMessage({ 
        type: 'success', 
        text: 'Backup created successfully! You can download it from the backup history below.'
      });
      
      // Update last backup time
      const now = new Date();
      const formattedDate = now.toISOString().replace('T', ' ').substring(0, 19);
      setBackupSettings(prev => ({
        ...prev,
        lastBackup: formattedDate,
        backupSize: '2.5 GB',
        backupStatus: 'success'
      }));
      
      // Add to backup history
      backupHistory.unshift({
        id: backupHistory.length + 1,
        date: formattedDate,
        type: 'Manual',
        status: 'success',
        size: '2.5 GB'
      });
    }, 3000);
  };

  const handleDownloadBackup = (backupId) => {
    // In a real app, this would trigger a download
    console.log(`Downloading backup ${backupId}`);
    setBackupMessage({ 
      type: 'info', 
      text: 'Starting backup download...' 
    });
  };

  const StatusBadge = ({ status }) => {
    const statusClasses = {
      success: 'bg-green-100 text-green-800',
      failed: 'bg-red-100 text-red-800',
      pending: 'bg-yellow-100 text-yellow-800',
    };
    
    return (
      <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClasses[status] || 'bg-gray-100 text-gray-800'}`}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </span>
    );
  };

  return (
    <div className="space-y-6">
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg font-medium text-gray-900">Backup Settings</h3>
          <p className="mt-1 text-sm text-gray-500">
            Configure automated backups and storage settings.
          </p>
        </div>
      </div>

      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-lg leading-6 font-medium text-gray-900">Backup Status</h3>
              <p className="mt-1 max-w-2xl text-sm text-gray-500">
                Last backup: {backupSettings.lastBackup}
              </p>
            </div>
            <div className="flex items-center">
              <div className="mr-4 text-right">
                <p className="text-sm text-gray-500">Backup Size</p>
                <p className="text-lg font-medium">{backupSettings.backupSize}</p>
              </div>
              <StatusBadge status={backupSettings.backupStatus} />
            </div>
          </div>
          
          <div className="mt-6">
            <button
              type="button"
              onClick={handleCreateBackup}
              disabled={isCreatingBackup}
              className={`inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white ${isCreatingBackup ? 'bg-indigo-400' : 'bg-indigo-600 hover:bg-indigo-700'} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500`}
            >
              {isCreatingBackup ? (
                <>
                  <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Creating Backup...
                </>
              ) : (
                <>
                  <FiDatabase className="-ml-1 mr-2 h-5 w-5" />
                  Create Backup Now
                </>
              )}
            </button>
            
            <button
              type="button"
              className="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <FiDownload className="-ml-1 mr-2 h-5 w-5 text-gray-500" />
              Download Latest Backup
            </button>
          </div>
          
          {backupMessage.text && (
            <div className={`mt-4 p-4 rounded-md ${backupMessage.type === 'error' ? 'bg-red-50 border-l-4 border-red-400' : 'bg-green-50 border-l-4 border-green-400'}`}>
              <div className="flex">
                <div className="flex-shrink-0">
                  {backupMessage.type === 'error' ? (
                    <FiAlertCircle className="h-5 w-5 text-red-400" />
                  ) : (
                    <FiInfo className="h-5 w-5 text-green-400" />
                  )}
                </div>
                <div className="ml-3">
                  <p className={`text-sm ${backupMessage.type === 'error' ? 'text-red-700' : 'text-green-700'}`}>
                    {backupMessage.text}
                  </p>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>

      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900">Automatic Backups</h3>
          <p className="mt-1 text-sm text-gray-500">
            Configure when and how often automatic backups should be created.
          </p>
          
          <div className="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
            <div className="sm:col-span-6">
              <div className="flex items-start">
                <div className="flex items-center h-5">
                  <input
                    id="autoBackup"
                    name="autoBackup"
                    type="checkbox"
                    checked={backupSettings.autoBackup}
                    onChange={handleChange}
                    className="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                  />
                </div>
                <div className="ml-3 text-sm">
                  <label htmlFor="autoBackup" className="font-medium text-gray-700">
                    Enable Automatic Backups
                  </label>
                  <p className="text-gray-500">
                    Automatically create backups on a schedule
                  </p>
                </div>
              </div>
            </div>
            
            {backupSettings.autoBackup && (
              <>
                <div className="sm:col-span-3">
                  <label htmlFor="backupFrequency" className="block text-sm font-medium text-gray-700">
                    Frequency
                  </label>
                  <div className="mt-1">
                    <select
                      id="backupFrequency"
                      name="backupFrequency"
                      value={backupSettings.backupFrequency}
                      onChange={handleChange}
                      className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                    >
                      {frequencies.map((freq) => (
                        <option key={freq.value} value={freq.value}>
                          {freq.label}
                        </option>
                      ))}
                    </select>
                  </div>
                </div>
                
                <div className="sm:col-span-3">
                  <label htmlFor="backupTime" className="block text-sm font-medium text-gray-700">
                    Time of Day
                  </label>
                  <div className="mt-1">
                    <input
                      type="time"
                      name="backupTime"
                      id="backupTime"
                      value={backupSettings.backupTime}
                      onChange={handleChange}
                      className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                    />
                  </div>
                  <p className="mt-1 text-xs text-gray-500">
                    Server time: {new Date().toLocaleTimeString()}
                  </p>
                </div>
                
                <div className="sm:col-span-6">
                  <label htmlFor="backupRetention" className="block text-sm font-medium text-gray-700">
                    Retention Period
                  </label>
                  <div className="mt-1 flex rounded-md shadow-sm">
                    <input
                      type="number"
                      name="backupRetention"
                      id="backupRetention"
                      min="1"
                      value={backupSettings.backupRetention}
                      onChange={handleChange}
                      className="focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-l-md"
                    />
                    <span className="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">
                      days
                    </span>
                  </div>
                  <p className="mt-1 text-xs text-gray-500">
                    Number of days to keep backup files before automatic deletion
                  </p>
                </div>
              </>
            )}
          </div>
        </div>
      </div>

      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900">Backup Storage</h3>
          <p className="mt-1 text-sm text-gray-500">
            Choose where to store your backup files.
          </p>
          
          <div className="mt-6">
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
              {storageOptions.map((storage) => (
                <div 
                  key={storage.value}
                  onClick={() => setBackupSettings(prev => ({ ...prev, backupStorage: storage.value }))}
                  className={`relative rounded-lg border ${backupSettings.backupStorage === storage.value ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 bg-white'} p-4 shadow-sm flex items-center cursor-pointer hover:border-indigo-500`}
                >
                  <div className="flex-shrink-0">
                    {storage.icon}
                  </div>
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-900">{storage.label}</p>
                  </div>
                  {backupSettings.backupStorage === storage.value && (
                    <div className="absolute top-0 right-0 -mt-2 -mr-2">
                      <div className="bg-indigo-600 rounded-full p-1">
                        <FiCheck className="h-3 w-3 text-white" />
                      </div>
                    </div>
                  )}
                </div>
              ))}
            </div>
            
            {backupSettings.backupStorage === 'local' && (
              <div className="mt-6">
                <label htmlFor="backupPath" className="block text-sm font-medium text-gray-700">
                  Backup Directory Path
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    name="backupPath"
                    id="backupPath"
                    value={backupSettings.backupPath}
                    onChange={handleChange}
                    className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  />
                </div>
                <p className="mt-1 text-xs text-gray-500">
                  Absolute path to the directory where backups should be stored
                </p>
              </div>
            )}
            
            {(backupSettings.backupStorage === 's3' || backupSettings.backupStorage === 'google' || backupSettings.backupStorage === 'dropbox') && (
              <div className="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div className="flex">
                  <div className="flex-shrink-0">
                    <FiAlertCircle className="h-5 w-5 text-yellow-400" />
                  </div>
                  <div className="ml-3">
                    <p className="text-sm text-yellow-700">
                      {backupSettings.backupStorage === 's3' && (
                        <>Amazon S3 integration requires additional configuration. Please set up your S3 credentials in the .env file.</>
                      )}
                      {backupSettings.backupStorage === 'google' && (
                        <>Google Drive integration requires OAuth authentication. Click <a href="#" className="font-medium underline">here</a> to connect your Google account.</>
                      )}
                      {backupSettings.backupStorage === 'dropbox' && (
                        <>Dropbox integration requires OAuth authentication. Click <a href="#" className="font-medium underline">here</a> to connect your Dropbox account.</>
                      )}
                    </p>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg leading-6 font-medium text-gray-900">Email Notifications</h3>
          <p className="mt-1 text-sm text-gray-500">
            Receive email notifications about backup status.
          </p>
          
          <div className="mt-6">
            <div className="flex items-start">
              <div className="flex items-center h-5">
                <input
                  id="backupEmailNotifications"
                  name="backupEmailNotifications"
                  type="checkbox"
                  checked={!!backupSettings.backupEmail}
                  onChange={(e) => {
                    if (!e.target.checked) {
                      setBackupSettings(prev => ({ ...prev, backupEmail: '' }));
                    }
                  }}
                  className="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                />
              </div>
              <div className="ml-3 text-sm">
                <label htmlFor="backupEmailNotifications" className="font-medium text-gray-700">
                  Send email notifications
                </label>
                <p className="text-gray-500">
                  Receive email notifications after each backup
                </p>
              </div>
            </div>
            
            {!!backupSettings.backupEmail || backupSettings.backupEmail === '' ? (
              <div className="mt-4">
                <label htmlFor="backupEmail" className="block text-sm font-medium text-gray-700">
                  Email Address
                </label>
                <div className="mt-1">
                  <input
                    type="email"
                    name="backupEmail"
                    id="backupEmail"
                    value={backupSettings.backupEmail}
                    onChange={(e) => setBackupSettings(prev => ({ ...prev, backupEmail: e.target.value }))}
                    className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                    placeholder="admin@example.com"
                  />
                </div>
              </div>
            ) : null}
          </div>
        </div>
      </div>

      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <div className="flex items-center justify-between">
            <h3 className="text-lg leading-6 font-medium text-gray-900">Backup History</h3>
            <button
              type="button"
              className="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <FiDownload className="-ml-0.5 mr-2 h-4 w-4" />
              Export History
            </button>
          </div>
          
          <div className="mt-6 overflow-hidden border border-gray-200 rounded-lg">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date & Time
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Type
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Size
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th scope="col" className="relative px-6 py-3">
                    <span className="sr-only">Actions</span>
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {backupHistory.map((backup) => (
                  <tr key={backup.id}>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      <div className="flex items-center">
                        <FiClock className="flex-shrink-0 mr-2 h-4 w-4 text-gray-400" />
                        {backup.date}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {backup.type}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {backup.size}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <StatusBadge status={backup.status} />
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <button
                        onClick={() => handleDownloadBackup(backup.id)}
                        className="text-indigo-600 hover:text-indigo-900 mr-4"
                        disabled={backup.status === 'failed'}
                      >
                        Download
                      </button>
                      <button className="text-red-600 hover:text-red-900">
                        Delete
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          
          <div className="mt-4 flex items-center justify-between">
            <div className="text-sm text-gray-500">
              Showing <span className="font-medium">1</span> to <span className="font-medium">5</span> of <span className="font-medium">24</span> backups
            </div>
            <div className="flex-1 flex justify-between sm:justify-end">
              <button className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Previous
              </button>
              <button className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Next
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default BackupSettings;
