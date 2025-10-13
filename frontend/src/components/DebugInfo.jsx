import React from 'react';
import { useAuth } from '../contexts/AuthContext';

const DebugInfo = () => {
  const { user, loading, isAuthenticated } = useAuth();
  
  // Safely extract role names from user roles array
  const getRoleNames = (roles) => {
    if (!Array.isArray(roles)) return 'none';
    try {
      return roles.map(r => {
        if (typeof r === 'string') return r;
        if (typeof r === 'object' && r !== null) {
          return r.name || r.role || 'unknown';
        }
        return 'unknown';
      }).join(', ');
    } catch (error) {
      console.error('Error processing roles:', error);
      return 'error';
    }
  };
  
  // Don't render if no user data
  if (!user) {
    return (
      <div className="fixed top-4 right-4 bg-yellow-100 border border-yellow-300 rounded-lg p-4 z-50 max-w-sm">
        <h3 className="font-bold text-yellow-800 mb-2">Debug Info</h3>
        <div className="text-xs text-yellow-700 space-y-1">
          <div>Loading: {loading ? 'true' : 'false'}</div>
          <div>Authenticated: {isAuthenticated ? 'true' : 'false'}</div>
          <div>User: null</div>
          <div>Role: none</div>
          <div>Roles: none</div>
          <div>Path: {window.location.pathname}</div>
        </div>
      </div>
    );
  }
  
  return (
    <div className="fixed top-4 right-4 bg-yellow-100 border border-yellow-300 rounded-lg p-4 z-50 max-w-sm">
      <h3 className="font-bold text-yellow-800 mb-2">Debug Info</h3>
      <div className="text-xs text-yellow-700 space-y-1">
        <div>Loading: {loading ? 'true' : 'false'}</div>
        <div>Authenticated: {isAuthenticated ? 'true' : 'false'}</div>
        <div>User: {user.name || 'Unknown'} ({user.email || 'No email'})</div>
        <div>Role: {user.role || 'none'}</div>
        <div>Roles: {getRoleNames(user.roles)}</div>
        <div>Path: {window.location.pathname}</div>
      </div>
    </div>
  );
};

export default DebugInfo;
