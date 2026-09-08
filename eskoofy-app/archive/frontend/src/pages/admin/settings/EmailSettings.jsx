import React, { useState } from 'react';
import { FiMail, FiLock, FiSend, FiInfo } from 'react-icons/fi';

const EmailSettings = () => {
  const [formData, setFormData] = useState({
    mailDriver: 'smtp',
    mailHost: '',
    mailPort: 587,
    mailUsername: '',
    mailPassword: '',
    mailEncryption: 'tls',
    mailFromAddress: 'noreply@example.com',
    mailFromName: 'School Management System',
  });

  const mailDrivers = [
    { value: 'smtp', label: 'SMTP' },
    { value: 'mailgun', label: 'Mailgun' },
    { value: 'sendmail', label: 'Sendmail' },
    { value: 'mail', label: 'PHP Mail' },
  ];

  const encryptionOptions = [
    { value: 'tls', label: 'TLS' },
    { value: 'ssl', label: 'SSL' },
    { value: '', label: 'None' },
  ];

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const handleTestEmail = async (e) => {
    e.preventDefault();
    // Implement test email functionality
    console.log('Sending test email...');
  };

  return (
    <div className="space-y-6">
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg font-medium text-gray-900">Mail Server Configuration</h3>
          <p className="mt-1 text-sm text-gray-500">
            Configure your email server settings for system notifications.
          </p>
          
          <div className="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
            <div className="sm:col-span-6">
              <label htmlFor="mailDriver" className="block text-sm font-medium text-gray-700">
                Mail Driver
              </label>
              <select
                id="mailDriver"
                name="mailDriver"
                value={formData.mailDriver}
                onChange={handleChange}
                className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              >
                {mailDrivers.map((driver) => (
                  <option key={driver.value} value={driver.value}>
                    {driver.label}
                  </option>
                ))}
              </select>
            </div>

            <div className="sm:col-span-3">
              <label htmlFor="mailHost" className="block text-sm font-medium text-gray-700">
                Mail Host
              </label>
              <div className="mt-1">
                <input
                  type="text"
                  name="mailHost"
                  id="mailHost"
                  value={formData.mailHost}
                  onChange={handleChange}
                  placeholder="smtp.mailtrap.io"
                  className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                />
              </div>
            </div>

            <div className="sm:col-span-3">
              <label htmlFor="mailPort" className="block text-sm font-medium text-gray-700">
                Mail Port
              </label>
              <div className="mt-1">
                <input
                  type="number"
                  name="mailPort"
                  id="mailPort"
                  value={formData.mailPort}
                  onChange={handleChange}
                  className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                />
              </div>
            </div>

            <div className="sm:col-span-3">
              <label htmlFor="mailUsername" className="block text-sm font-medium text-gray-700">
                Username
              </label>
              <div className="mt-1">
                <input
                  type="text"
                  name="mailUsername"
                  id="mailUsername"
                  value={formData.mailUsername}
                  onChange={handleChange}
                  className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                />
              </div>
            </div>

            <div className="sm:col-span-3">
              <label htmlFor="mailPassword" className="block text-sm font-medium text-gray-700">
                Password
              </label>
              <div className="mt-1 relative rounded-md shadow-sm">
                <input
                  type="password"
                  name="mailPassword"
                  id="mailPassword"
                  value={formData.mailPassword}
                  onChange={handleChange}
                  className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md pr-10"
                  placeholder="••••••••"
                />
                <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <FiLock className="h-5 w-5 text-gray-400" />
                </div>
              </div>
            </div>

            <div className="sm:col-span-3">
              <label htmlFor="mailEncryption" className="block text-sm font-medium text-gray-700">
                Encryption
              </label>
              <select
                id="mailEncryption"
                name="mailEncryption"
                value={formData.mailEncryption}
                onChange={handleChange}
                className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              >
                {encryptionOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg font-medium text-gray-900">Email Settings</h3>
          <p className="mt-1 text-sm text-gray-500">
            Configure the default sender information for system emails.
          </p>
          
          <div className="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
            <div className="sm:col-span-3">
              <label htmlFor="mailFromAddress" className="block text-sm font-medium text-gray-700">
                From Email Address
              </label>
              <div className="mt-1">
                <input
                  type="email"
                  name="mailFromAddress"
                  id="mailFromAddress"
                  value={formData.mailFromAddress}
                  onChange={handleChange}
                  className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                />
              </div>
            </div>

            <div className="sm:col-span-3">
              <label htmlFor="mailFromName" className="block text-sm font-medium text-gray-700">
                From Name
              </label>
              <div className="mt-1">
                <input
                  type="text"
                  name="mailFromName"
                  id="mailFromName"
                  value={formData.mailFromName}
                  onChange={handleChange}
                  className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg font-medium text-gray-900">Test Email Configuration</h3>
          <p className="mt-1 text-sm text-gray-500">
            Send a test email to verify your configuration is working correctly.
          </p>
          
          <div className="mt-6">
            <div className="sm:col-span-4">
              <label htmlFor="testEmail" className="block text-sm font-medium text-gray-700">
                Test Email Address
              </label>
              <div className="mt-1">
                <input
                  type="email"
                  name="testEmail"
                  id="testEmail"
                  placeholder="test@example.com"
                  className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                />
              </div>
            </div>
            
            <div className="mt-4">
              <button
                type="button"
                onClick={handleTestEmail}
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <FiSend className="-ml-1 mr-2 h-5 w-5" />
                Send Test Email
              </button>
            </div>
          </div>
        </div>
      </div>

      <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <FiInfo className="h-5 w-5 text-yellow-400" />
          </div>
          <div className="ml-3">
            <p className="text-sm text-yellow-700">
              For security reasons, email settings might require additional configuration in your <code className="bg-yellow-100 px-1 rounded">.env</code> file.
              Please refer to the documentation for more information.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default EmailSettings;
