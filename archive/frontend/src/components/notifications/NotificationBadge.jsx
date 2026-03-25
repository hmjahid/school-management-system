import React, { useState, useEffect, useRef } from 'react';
import { BellIcon } from '@heroicons/react/24/outline';
import notificationService from '../../services/notifications/NotificationService';

const NotificationBadge = ({ className = '', showCount = true, onClick }) => {
  const [unreadCount, setUnreadCount] = useState(0);
  const [isHovering, setIsHovering] = useState(false);
  const badgeRef = useRef(null);

  // Subscribe to notification updates
  useEffect(() => {
    const handleUpdate = () => {
      setUnreadCount(notificationService.getUnreadCount());
    };

    // Initial load
    handleUpdate();

    // Subscribe to updates
    notificationService.on('notifications:updated', handleUpdate);

    // Cleanup
    return () => {
      notificationService.off('notifications:updated', handleUpdate);
    };
  }, []);

  // Handle click events
  const handleClick = (e) => {
    e.stopPropagation();
    if (onClick) {
      onClick(e);
    }
  };

  // Show pulse animation when there are unread notifications
  const showPulse = unreadCount > 0 && !isHovering;

  return (
    <div 
      ref={badgeRef}
      className={`relative inline-flex items-center justify-center p-1.5 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 ${className}`}
      onClick={handleClick}
      onMouseEnter={() => setIsHovering(true)}
      onMouseLeave={() => setIsHovering(false)}
      aria-label={`${unreadCount} unread notifications`}
      role="button"
      tabIndex={0}
    >
      <BellIcon className="h-6 w-6 text-gray-500 dark:text-gray-400" />
      
      {/* Unread count badge */}
      {showCount && unreadCount > 0 && (
        <span 
          className={`absolute top-0 right-0 flex items-center justify-center h-5 w-5 rounded-full bg-red-500 text-white text-xs font-medium transform translate-x-1 -translate-y-1 transition-all duration-200 ${showPulse ? 'animate-pulse' : ''}`}
          aria-hidden="true"
        >
          {unreadCount > 9 ? '9+' : unreadCount}
        </span>
      )}
      
      {/* Hover effect */}
      <span className="sr-only">
        {unreadCount === 0 ? 'No new notifications' : `${unreadCount} unread notifications`}
      </span>
    </div>
  );
};

export default NotificationBadge;
