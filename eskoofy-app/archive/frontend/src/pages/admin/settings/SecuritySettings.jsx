import React, { useState } from 'react';
import { FiShield, FiLock, FiUser, FiGlobe, FiKey, FiAlertTriangle, FiCheck, FiX, FiCopy } from 'react-icons/fi';

const SecuritySettings = () => {
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [twoFactorEnabled, setTwoFactorEnabled] = useState(false);
  const [showRecoveryCodes, setShowRecoveryCodes] = useState(false);
  const [activeSessions, setActiveSessions] = useState([
    { id: 1, device: 'Chrome on Windows 10', ip: '192.168.1.1', lastActive: '2 hours ago', current: true },
    { id: 2, device: 'Safari on iPhone', ip: '192.168.1.2', lastActive: '1 day ago', current: false },
  ]);
  const [passwordStrength, setPasswordStrength] = useState(0);
  const [passwordError, setPasswordError] = useState('');
  const [passwordSuccess, setPasswordSuccess] = useState('');

  const handlePasswordChange = (e) => {
    const { name, value } = e.target;
    if (name === 'newPassword') {
      setNewPassword(value);
      // Simple password strength check
      let strength = 0;
      if (value.length >= 8) strength += 1;
      if (value.match(/[a-z]+/)) strength += 1;
      if (value.match(/[A-Z]+/)) strength += 1;
      if (value.match(/[0-9]+/)) strength += 1;
      if (value.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength += 1;
      setPasswordStrength(strength);
    } else if (name === 'confirmPassword') {
      setConfirmPassword(value);
    } else if (name === 'currentPassword') {
      setCurrentPassword(value);
    }
  };

  const handlePasswordSubmit = (e) => {
    e.preventDefault();
    setPasswordError('');
    setPasswordSuccess('');

    if (newPassword !== confirmPassword) {
      setPasswordError('New password and confirm password do not match');
      return;
    }

    if (newPassword.length < 8) {
      setPasswordError('Password must be at least 8 characters long');
      return;
    }

    // Simulate API call
    setTimeout(() => {
      setPasswordSuccess('Password updated successfully');
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      setPasswordStrength(0);
    }, 1000);
  };

  const revokeSession = (sessionId) => {
    setActiveSessions(activeSessions.filter(session => session.id !== sessionId));
  };

  const generateRecoveryCodes = () => {
    // In a real app, this would generate actual recovery codes
    setShowRecoveryCodes(true);
  };

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    // Show a toast or notification in a real app
  };

  const PasswordStrengthMeter = ({ strength }) => {
    const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const strengthColors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'];
    
    return (
      <div className="mt-2">
        <div className="grid grid-cols-5 gap-2 mb-1">
          {[1, 2, 3, 4, 5].map((i) => (
            <div 
              key={i} 
              className={`h-1 rounded-full ${i <= strength ? strengthColors[strength - 1] : 'bg-gray-200'}`}
            />
          ))}
        </div>
        <p className={`text-xs ${strength > 0 ? 'font-medium' : 'text-gray-500'}`}>
          {strength > 0 ? strengthText[strength - 1] : 'Password strength'}
        </p>
      </div>
    );
  };

  const SecurityCard = ({ title, description, children }) => (
    <div className="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
      <div className="px-4 py-5 sm:px-6">
        <h3 className="text-lg leading-6 font-medium text-gray-900">{title}</h3>
        {description && <p className="mt-1 max-w-2xl text-sm text-gray-500">{description}</p>}
      </div>
      <div className="border-t border-gray-200 px-4 py-5 sm:p-6">
        {children}
      </div>
    </div>
  );

  return (
    <div className="space-y-6">
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg font-medium text-gray-900">Security Settings</h3>
          <p className="mt-1 text-sm text-gray-500">
            Manage your account security and access settings.
          </p>
        </div>
      </div>

      <SecurityCard 
        title="Change Password" 
        description="Update your account password"
      >
        <form onSubmit={handlePasswordSubmit} className="space-y-6">
          <div>
            <label htmlFor="currentPassword" className="block text-sm font-medium text-gray-700">
              Current Password
            </label>
            <div className="mt-1 relative rounded-md shadow-sm">
              <input
                type="password"
                name="currentPassword"
                id="currentPassword"
                value={currentPassword}
                onChange={handlePasswordChange}
                className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                required
              />
            </div>
          </div>

          <div>
            <label htmlFor="newPassword" className="block text-sm font-medium text-gray-700">
              New Password
            </label>
            <div className="mt-1 relative rounded-md shadow-sm">
              <input
                type="password"
                name="newPassword"
                id="newPassword"
                value={newPassword}
                onChange={handlePasswordChange}
                className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                required
              />
            </div>
            <PasswordStrengthMeter strength={passwordStrength} />
          </div>

          <div>
            <label htmlFor="confirmPassword" className="block text-sm font-medium text-gray-700">
              Confirm New Password
            </label>
            <div className="mt-1 relative rounded-md shadow-sm">
              <input
                type="password"
                name="confirmPassword"
                id="confirmPassword"
                value={confirmPassword}
                onChange={handlePasswordChange}
                className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                required
              />
            </div>
          </div>

          {passwordError && (
            <div className="bg-red-50 border-l-4 border-red-400 p-4">
              <div className="flex">
                <div className="flex-shrink-0">
                  <FiX className="h-5 w-5 text-red-400" />
                </div>
                <div className="ml-3">
                  <p className="text-sm text-red-700">{passwordError}</p>
                </div>
              </div>
            </div>
          )}

          {passwordSuccess && (
            <div className="bg-green-50 border-l-4 border-green-400 p-4">
              <div className="flex">
                <div className="flex-shrink-0">
                  <FiCheck className="h-5 w-5 text-green-400" />
                </div>
                <div className="ml-3">
                  <p className="text-sm text-green-700">{passwordSuccess}</p>
                </div>
              </div>
            </div>
          )}

          <div className="pt-2">
            <button
              type="submit"
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <FiLock className="-ml-1 mr-2 h-5 w-5" />
              Update Password
            </button>
          </div>
        </form>
      </SecurityCard>

      <SecurityCard 
        title="Two-Factor Authentication" 
        description="Add an extra layer of security to your account"
      >
        <div className="flex items-center justify-between">
          <div>
            <h4 className="text-md font-medium text-gray-900">
              {twoFactorEnabled ? 'Two-Factor Authentication is enabled' : 'Two-Factor Authentication is disabled'}
            </h4>
            <p className="text-sm text-gray-500 mt-1">
              {twoFactorEnabled 
                ? 'Your account is protected with two-factor authentication.'
                : 'Protect your account with an extra layer of security.'}
            </p>
          </div>
          <button
            type="button"
            onClick={() => setTwoFactorEnabled(!twoFactorEnabled)}
            className={`inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white ${twoFactorEnabled ? 'bg-red-600 hover:bg-red-700' : 'bg-indigo-600 hover:bg-indigo-700'} focus:outline-none focus:ring-2 focus:ring-offset-2 ${twoFactorEnabled ? 'focus:ring-red-500' : 'focus:ring-indigo-500'}`}
          >
            {twoFactorEnabled ? 'Disable' : 'Enable'} Two-Factor
          </button>
        </div>

        {twoFactorEnabled && (
          <div className="mt-6 border-t border-gray-200 pt-6">
            <h4 className="text-md font-medium text-gray-900 mb-4">Recovery Codes</h4>
            <p className="text-sm text-gray-500 mb-4">
              Save these recovery codes in a safe place. You can use them to regain access to your account if you lose access to your authentication device.
            </p>
            
            {showRecoveryCodes ? (
              <div className="bg-gray-50 p-4 rounded-md">
                <div className="grid grid-cols-2 gap-2">
                  {['A1B2-C3D4', 'E5F6-G7H8', 'I9J0-K1L2', 'M3N4-O5P6', 'Q7R8-S9T0', 'U1V2-W3X4'].map((code) => (
                    <div key={code} className="flex justify-between items-center bg-white p-2 rounded border border-gray-200">
                      <code className="font-mono text-sm">{code}</code>
                      <button
                        type="button"
                        onClick={() => copyToClipboard(code)}
                        className="text-gray-400 hover:text-gray-500"
                        title="Copy to clipboard"
                      >
                        <FiCopy className="h-4 w-4" />
                      </button>
                    </div>
                  ))}
                </div>
                <p className="mt-3 text-xs text-gray-500">
                  <FiAlertTriangle className="inline mr-1 h-4 w-4 text-yellow-500" />
                  These codes will only be shown once. Please save them in a secure location.
                </p>
              </div>
            ) : (
              <button
                type="button"
                onClick={generateRecoveryCodes}
                className="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <FiKey className="-ml-0.5 mr-2 h-4 w-4" />
                Generate New Recovery Codes
              </button>
            )}
          </div>
        )}
      </SecurityCard>

      <SecurityCard 
        title="Active Sessions" 
        description="Manage and review devices that are logged into your account"
      >
        <div className="overflow-hidden border border-gray-200 rounded-md">
          <ul className="divide-y divide-gray-200">
            {activeSessions.map((session) => (
              <li key={session.id} className="px-4 py-4 sm:px-6">
                <div className="flex items-center justify-between">
                  <div className="flex items-center">
                    <div className={`flex-shrink-0 h-3 w-3 rounded-full ${session.current ? 'bg-green-400' : 'bg-gray-300'}`}></div>
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-900">
                        {session.device}
                        {session.current && <span className="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Current Session</span>}
                      </p>
                      <div className="flex flex-col sm:flex-row sm:space-x-4 text-sm text-gray-500">
                        <span>{session.ip}</span>
                        <span className="hidden sm:inline-block">•</span>
                        <span>Last active {session.lastActive}</span>
                      </div>
                    </div>
                  </div>
                  {!session.current && (
                    <button
                      type="button"
                      onClick={() => revokeSession(session.id)}
                      className="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                    >
                      Revoke
                    </button>
                  )}
                </div>
              </li>
            ))}
          </ul>
        </div>
        <div className="mt-4">
          <p className="text-sm text-gray-500">
            <FiAlertTriangle className="inline mr-1 h-4 w-4 text-yellow-500" />
            If you see any suspicious activity, revoke access and change your password immediately.
          </p>
        </div>
      </SecurityCard>

      <SecurityCard 
        title="Security Logs" 
        description="Review recent security events for your account"
      >
        <div className="overflow-hidden border border-gray-200 rounded-md">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Event
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  IP Address
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Time
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {[
                { id: 1, event: 'Login successful', ip: '192.168.1.1', time: '2 hours ago' },
                { id: 2, event: 'Password changed', ip: '192.168.1.1', time: '1 day ago' },
                { id: 3, event: 'Login attempt failed', ip: '45.33.6.223', time: '2 days ago' },
                { id: 4, event: 'Two-factor authentication enabled', ip: '192.168.1.1', time: '1 week ago' },
                { id: 5, event: 'Password reset requested', ip: '192.168.1.1', time: '2 weeks ago' },
              ].map((log) => (
                <tr key={log.id}>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {log.event}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {log.ip}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {log.time}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="mt-4">
          <button
            type="button"
            className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
          >
            View all security logs
          </button>
        </div>
      </SecurityCard>
    </div>
  );
};

export default SecuritySettings;
