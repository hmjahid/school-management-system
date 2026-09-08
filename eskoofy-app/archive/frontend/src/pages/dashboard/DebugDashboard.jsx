import { useEffect, useMemo } from 'react';
import { useAuth } from '../../contexts/AuthContext_debug';

// Helper function to extract role names from role objects
const extractRoleNames = (roles) => {
  if (!roles) return [];
  
  return roles.map(role => {
    if (typeof role === 'string') return role;
    if (role && typeof role === 'object') {
      return role.name || role.role || JSON.stringify(role);
    }
    return String(role);
  });
};

const DebugDashboard = () => {
  const { user, hasRole, hasAnyRole } = useAuth();
  
  // Extract and log role information
  const roleInfo = useMemo(() => {
    if (!user) return { roleNames: [], isAdmin: false, isTeacher: false, isStudent: false, isParent: false, isStaff: false };
    
    const roleNames = extractRoleNames(user.roles);
    
    return {
      roleNames,
      isAdmin: hasRole('admin'),
      isTeacher: hasRole('teacher'),
      isStudent: hasRole('student'),
      isParent: hasRole('parent'),
      isStaff: hasRole('staff'),
      rawRoles: user.roles
    };
  }, [user, hasRole]);

  // Log user data when component mounts or updates
  useEffect(() => {
    console.log('[DebugDashboard] User data:', {
      id: user?.id,
      name: user?.name,
      email: user?.email,
      role: user?.role,
      roles: user?.roles,
      roleInfo,
      rawUser: user
    });
  }, [user, roleInfo]);

  if (!user) {
    return (
      <div className="p-6">
        <h1 className="text-2xl font-bold mb-6">Debug Dashboard</h1>
        <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <svg className="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
              </svg>
            </div>
            <div className="ml-3">
              <p className="text-sm text-yellow-700">
                No user data available. Please log in to view dashboard information.
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-6">Debug Dashboard</h1>
      
      <div className="bg-white shadow rounded-lg p-6 mb-6">
        <h2 className="text-xl font-semibold mb-4">User Information</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p className="font-medium">User ID:</p>
            <p className="text-gray-700 font-mono text-sm">{user?.id || 'N/A'}</p>
          </div>
          <div>
            <p className="font-medium">Name:</p>
            <p className="text-gray-700">{user?.name || 'N/A'}</p>
          </div>
          <div>
            <p className="font-medium">Email:</p>
            <p className="text-gray-700">{user?.email || 'N/A'}</p>
          </div>
          <div>
            <p className="font-medium">Legacy Role:</p>
            <p className="text-gray-700 font-mono">{user?.role || 'N/A'}</p>
          </div>
        </div>
      </div>
      
      <div className="bg-white shadow rounded-lg p-6 mb-6">
        <h2 className="text-xl font-semibold mb-4">Role Information</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p className="font-medium">Role Names:</p>
            <div className="mt-1">
              {roleInfo.roleNames.length > 0 ? (
                <ul className="list-disc pl-5">
                  {roleInfo.roleNames.map((role, index) => (
                    <li key={index} className="text-gray-700">{role}</li>
                  ))}
                </ul>
              ) : (
                <p className="text-gray-500 italic">No roles found</p>
              )}
            </div>
          </div>
          <div>
            <p className="font-medium">Role Checks:</p>
            <ul className="mt-1 space-y-1">
              <li className="flex items-center">
                <span className={`inline-block w-3 h-3 rounded-full mr-2 ${roleInfo.isAdmin ? 'bg-green-500' : 'bg-gray-300'}`}></span>
                <span>Admin: {roleInfo.isAdmin ? 'Yes' : 'No'}</span>
              </li>
              <li className="flex items-center">
                <span className={`inline-block w-3 h-3 rounded-full mr-2 ${roleInfo.isTeacher ? 'bg-green-500' : 'bg-gray-300'}`}></span>
                <span>Teacher: {roleInfo.isTeacher ? 'Yes' : 'No'}</span>
              </li>
              <li className="flex items-center">
                <span className={`inline-block w-3 h-3 rounded-full mr-2 ${roleInfo.isStudent ? 'bg-green-500' : 'bg-gray-300'}`}></span>
                <span>Student: {roleInfo.isStudent ? 'Yes' : 'No'}</span>
              </li>
              <li className="flex items-center">
                <span className={`inline-block w-3 h-3 rounded-full mr-2 ${roleInfo.isParent ? 'bg-green-500' : 'bg-gray-300'}`}></span>
                <span>Parent: {roleInfo.isParent ? 'Yes' : 'No'}</span>
              </li>
              <li className="flex items-center">
                <span className={`inline-block w-3 h-3 rounded-full mr-2 ${roleInfo.isStaff ? 'bg-green-500' : 'bg-gray-300'}`}></span>
                <span>Staff: {roleInfo.isStaff ? 'Yes' : 'No'}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div className="bg-white shadow rounded-lg p-6">
        <h2 className="text-xl font-semibold mb-4">Page Information</h2>
        <div className="space-y-4">
          <div>
            <p className="font-medium">Page URL:</p>
            <p className="text-gray-700">{window.location.href}</p>
          </div>
          <div>
            <p className="font-medium">User Agent:</p>
            <p className="text-gray-700 text-sm">{navigator.userAgent}</p>
          </div>
          <div>
            <p className="font-medium">Local Storage:</p>
            <pre className="bg-gray-100 p-2 rounded text-xs overflow-x-auto">
              {JSON.stringify({
                token: localStorage.getItem('token') ? '***' : 'Not found',
                hasUser: !!user,
                timestamp: new Date().toISOString()
              }, null, 2)}
            </pre>
          </div>
        </div>
      </div>

      <div className="mt-6 p-4 bg-yellow-50 border-l-4 border-yellow-400">
        <div className="flex">
          <div className="flex-shrink-0">
            <svg className="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="ml-3">
            <p className="text-sm text-yellow-700">
              This is a debug view. The dashboard is not loading any data. Please check the browser console for more details.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default DebugDashboard;
