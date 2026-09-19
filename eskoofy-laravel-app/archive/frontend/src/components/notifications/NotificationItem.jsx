import React, { useState, useEffect } from 'react';
import { 
  CheckCircleIcon, 
  XCircleIcon, 
  InformationCircleIcon, 
  ExclamationTriangleIcon,
  ClockIcon,
  ArrowPathIcon
} from '@heroicons/react/24/outline';
import { formatDistanceToNow, parseISO } from 'date-fns';
import { Link } from 'react-router-dom';
import notificationService from '../../services/notifications/NotificationService';

// Icon mapping for different notification types
const iconMap = {
  success: {
    icon: CheckCircleIcon,
    className: 'text-green-500',
    bgClassName: 'bg-green-50 dark:bg-green-900/30',
  },
  error: {
    icon: XCircleIcon,
    className: 'text-red-500',
    bgClassName: 'bg-red-50 dark:bg-red-900/30',
  },
  warning: {
    icon: ExclamationTriangleIcon,
    className: 'text-yellow-500',
    bgClassName: 'bg-yellow-50 dark:bg-yellow-900/30',
  },
  info: {
    icon: InformationCircleIcon,
    className: 'text-blue-500',
    bgClassName: 'bg-blue-50 dark:bg-blue-900/30',
  },
  processing: {
    icon: ArrowPathIcon,
    className: 'text-indigo-500 animate-spin',
    bgClassName: 'bg-indigo-50 dark:bg-indigo-900/30',
  },
  pending: {
    icon: ClockIcon,
    className: 'text-gray-500',
    bgClassName: 'bg-gray-50 dark:bg-gray-800',
  },
};

const NotificationItem = ({ 
  notification, 
  onMarkAsRead, 
  className = '' 
}) => {
  const [isRead, setIsRead] = useState(!!notification.read_at);
  const [isHovering, setIsHovering] = useState(false);
  const [isMarkingAsRead, setIsMarkingAsRead] = useState(false);
  const [timeAgo, setTimeAgo] = useState('');
  
  const { 
    id, 
    title, 
    message, 
    type = 'info', 
    created_at, 
    data = {}, 
    action 
  } = notification;
  
  const Icon = (iconMap[type] || iconMap.info).icon;
  const iconClassName = (iconMap[type] || iconMap.info).className;
  const bgClassName = (iconMap[type] || iconMap.info).bgClassName;
  
  // Update time ago at regular intervals
  useEffect(() => {
    const updateTimeAgo = () => {
      if (created_at) {
        setTimeAgo(formatDistanceToNow(parseISO(created_at), { addSuffix: true }));
      }
    };
    
    // Initial update
    updateTimeAgo();
    
    // Update every minute
    const interval = setInterval(updateTimeAgo, 60000);
    
    return () => clearInterval(interval);
  }, [created_at]);
  
  // Handle mark as read
  const handleMarkAsRead = async (e) => {
    e.preventDefault();
    e.stopPropagation();
    
    if (isRead || isMarkingAsRead) return;
    
    try {
      setIsMarkingAsRead(true);
      await notificationService.markAsRead(id);
      setIsRead(true);
      if (onMarkAsRead) {
        onMarkAsRead(id);
      }
    } catch (error) {
      console.error('Failed to mark notification as read:', error);
    } finally {
      setIsMarkingAsRead(false);
    }
  };
  
  // Handle click on notification
  const handleClick = (e) => {
    if (action?.url) {
      // Let the link handle the navigation
      return;
    }
    
    // Mark as read when clicked if not already read
    if (!isRead) {
      handleMarkAsRead(e);
    }
    
    // Call custom action if provided
    if (action?.callback) {
      e.preventDefault();
      action.callback();
    }
  };
  
  // Determine the component to render based on the action
  const ActionComponent = action?.url ? Link : 'div';
  const actionProps = action?.url ? { to: action.url } : {};
  
  return (
    <div 
      className={`relative p-4 border-b border-gray-100 dark:border-gray-700 transition-colors duration-150 ${
        isRead 
          ? 'bg-white dark:bg-gray-800' 
          : `${bgClassName} border-l-4 border-l-indigo-500`
      } ${className}`}
      onMouseEnter={() => setIsHovering(true)}
      onMouseLeave={() => setIsHovering(false)}
      onClick={handleClick}
      role="button"
      tabIndex={0}
      aria-label={`${title}. ${message}. ${timeAgo}. ${isRead ? 'Read' : 'Unread'}`}
    >
      <div className="flex items-start">
        {/* Notification Icon */}
        <div className="flex-shrink-0 mt-0.5">
          <Icon className={`h-5 w-5 ${iconClassName}`} aria-hidden="true" />
        </div>
        
        {/* Notification Content */}
        <div className="ml-3 flex-1 overflow-hidden">
          <div className="flex justify-between items-start">
            <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
              {title}
            </p>
            <div className="flex items-center">
              <span 
                className={`text-xs ${isRead ? 'text-gray-400' : 'text-indigo-600 font-medium'}`}
                title={new Date(created_at).toLocaleString()}
              >
                {timeAgo}
              </span>
              {!isRead && (
                <button
                  type="button"
                  onClick={handleMarkAsRead}
                  className="ml-2 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                  title="Mark as read"
                  disabled={isMarkingAsRead}
                >
                  <span className="sr-only">Mark as read</span>
                  <CheckCircleIcon className="h-4 w-4" />
                </button>
              )}
            </div>
          </div>
          
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-300 break-words">
            {message}
          </p>
          
          {/* Additional data */}
          {data && Object.keys(data).length > 0 && (
            <div className="mt-2 text-xs text-gray-500 dark:text-gray-400">
              {Object.entries(data).map(([key, value]) => (
                <div key={key} className="truncate">
                  <span className="font-medium capitalize">{key.replace(/_/g, ' ')}:</span>{' '}
                  <span className="font-mono">{String(value)}</span>
                </div>
              ))}
            </div>
          )}
          
          {/* Action buttons */}
          {action && (
            <div className="mt-3 flex space-x-3">
              <ActionComponent
                {...actionProps}
                className={`inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ${
                  isMarkingAsRead ? 'opacity-75 cursor-not-allowed' : ''
                }`}
                onClick={action.callback ? handleClick : undefined}
              >
                {action.label || 'View'}
              </ActionComponent>
              
              {action.secondaryAction && (
                <button
                  type="button"
                  className="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  onClick={(e) => {
                    e.stopPropagation();
                    action.secondaryAction.callback();
                  }}
                >
                  {action.secondaryAction.label}
                </button>
              )}
            </div>
          )}
        </div>
      </div>
      
      {/* Hover effect */}
      <div 
        className={`absolute inset-0 bg-black bg-opacity-5 dark:bg-opacity-10 transition-opacity duration-200 ${
          isHovering ? 'opacity-100' : 'opacity-0'
        }`}
      />
    </div>
  );
};

export default NotificationItem;
