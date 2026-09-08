import React from 'react';
import { useAuth } from '../contexts/AuthContext';

const formatRoleLabel = (role) => {
  if (role == null || role === '') return 'none';
  if (typeof role === 'string') return role;
  if (typeof role === 'object') return role.name || role.role || 'unknown';
  return String(role);
};

const DebugInfoSimple = () => {
  const { user, loading, isAuthenticated } = useAuth();
  
  return (
    <div className="fixed top-4 right-4 bg-yellow-100 border border-yellow-300 rounded-lg p-4 z-50 max-w-sm">
      <h3 className="font-bold text-yellow-800 mb-2">Debug Info</h3>
      <div className="text-xs text-yellow-700 space-y-1">
        <div>Loading: {String(loading)}</div>
        <div>Authenticated: {String(isAuthenticated)}</div>
        <div>User: {user ? user.name || 'Unknown' : 'null'}</div>
        <div>Role: {formatRoleLabel(user?.role)}</div>
        <div>Roles: {user?.roles ? 'Array with ' + user.roles.length + ' items' : 'none'}</div>
        <div>Path: {window.location.pathname}</div>
      </div>
    </div>
  );
};

export default DebugInfoSimple;
