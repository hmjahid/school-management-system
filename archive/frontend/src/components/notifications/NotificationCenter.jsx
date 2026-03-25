import React, { useState, useEffect, useRef } from 'react';
import { 
  BellIcon, 
  XMarkIcon, 
  CheckIcon,
  ArrowPathIcon,
  EnvelopeIcon,
  InboxInIcon
} from '@heroicons/react/24/outline';
import { useClickAway } from 'react-use';
import { Transition } from '@headlessui/react';
import { Link } from 'react-router-dom';
import NotificationItem from './NotificationItem';
import NotificationBadge from './NotificationBadge';
import notificationService from '../../services/notifications/NotificationService';

const NotificationCenter = ({ className = '' }) => {
  const [isOpen, setIsOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [isMarkingAllAsRead, setIsMarkingAllAsRead] = useState(false);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [hasMore, setHasMore] = useState(true);
  const [page, setPage] = useState(1);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  
  const dropdownRef = useRef(null);
  const listRef = useRef(null);
  const pageSize = 15;
  
  // Close dropdown when clicking outside
  useClickAway(dropdownRef, () => {
    if (isOpen) {
      setIsOpen(false);
    }
  });
  
  // Load notifications
  const loadNotifications = async (pageNum = 1, append = false) => {
    try {
      const isInitialLoad = pageNum === 1 && !append;
      const isLoadMore = pageNum > 1 && append;
      
      if (isInitialLoad) {
        setIsLoading(true);
      } else if (isLoadMore) {
        setIsLoadingMore(true);
      } else {
        setIsRefreshing(true);
      }
      
      // In a real app, you would fetch notifications from your API
      // const response = await notificationService.getNotifications({
      //   page: pageNum,
      //   per_page: pageSize,
      // });
      
      // Simulated API response
      const response = {
        data: [], // This would be response.data in a real app
        pagination: {
          current_page: pageNum,
          last_page: 5, // Example total pages
          total: 75, // Example total notifications
        }
      };
      
      setHasMore(pageNum < response.pagination.last_page);
      
      if (append) {
        setNotifications(prev => [...prev, ...response.data]);
      } else {
        setNotifications(response.data);
      }
      
      // Update unread count from the service
      setUnreadCount(notificationService.getUnreadCount());
    } catch (error) {
      console.error('Failed to load notifications:', error);
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
      setIsLoadingMore(false);
    }
  };
  
  // Initial load
  useEffect(() => {
    loadNotifications(1);
    
    // Subscribe to real-time updates
    const handleNewNotification = (notification) => {
      setNotifications(prev => [notification, ...prev]);
      setUnreadCount(prev => prev + 1);
    };
    
    const handleUpdate = (updatedNotifications) => {
      setNotifications(updatedNotifications);
      setUnreadCount(notificationService.getUnreadCount());
    };
    
    notificationService.on('notification:new', handleNewNotification);
    notificationService.on('notifications:updated', handleUpdate);
    
    return () => {
      notificationService.off('notification:new', handleNewNotification);
      notificationService.off('notifications:updated', handleUpdate);
    };
  }, []);
  
  // Toggle dropdown
  const toggleDropdown = () => {
    setIsOpen(!isOpen);
    
    // Mark all as read when opening the dropdown
    if (!isOpen && unreadCount > 0) {
      handleMarkAllAsRead();
    }
  };
  
  // Mark all notifications as read
  const handleMarkAllAsRead = async () => {
    if (unreadCount === 0 || isMarkingAllAsRead) return;
    
    try {
      setIsMarkingAllAsRead(true);
      await notificationService.markAllAsRead();
      setUnreadCount(0);
    } catch (error) {
      console.error('Failed to mark all as read:', error);
    } finally {
      setIsMarkingAllAsRead(false);
    }
  };
  
  // Handle notification click
  const handleNotificationClick = (notification) => {
    // Handle notification click (e.g., navigate to a specific page)
    console.log('Notification clicked:', notification);
    
    // Close the dropdown
    setIsOpen(false);
  };
  
  // Handle mark as read for a single notification
  const handleMarkAsRead = (notificationId) => {
    setNotifications(prev => 
      prev.map(n => 
        n.id === notificationId ? { ...n, read_at: new Date().toISOString() } : n
      )
    );
    setUnreadCount(prev => Math.max(0, prev - 1));
  };
  
  // Handle scroll for infinite loading
  const handleScroll = () => {
    if (!listRef.current || isLoading || isLoadingMore || !hasMore) return;
    
    const { scrollTop, scrollHeight, clientHeight } = listRef.current;
    const isNearBottom = scrollTop + clientHeight >= scrollHeight - 50;
    
    if (isNearBottom) {
      loadMore();
    }
  };
  
  // Load more notifications
  const loadMore = () => {
    if (isLoadingMore || !hasMore) return;
    
    const nextPage = page + 1;
    setPage(nextPage);
    loadNotifications(nextPage, true);
  };
  
  // Refresh notifications
  const handleRefresh = () => {
    if (isRefreshing) return;
    loadNotifications(1);
  };
  
  // Sample notifications data (replace with actual data from your API)
  const sampleNotifications = [
    {
      id: '1',
      title: 'Refund Processed',
      message: 'Your refund of $50.00 has been processed successfully.',
      type: 'success',
      created_at: new Date(Date.now() - 1000 * 60 * 5).toISOString(), // 5 minutes ago
      read_at: null,
      data: {
        amount: 50.00,
        currency: 'USD',
        payment_id: 'PAY-123456'
      },
      action: {
        label: 'View Details',
        url: '/refunds/1'
      }
    },
    {
      id: '2',
      title: 'Refund Request Received',
      message: 'Your refund request is being processed. We\'ll notify you once it\'s completed.',
      type: 'info',
      created_at: new Date(Date.now() - 1000 * 60 * 30).toISOString(), // 30 minutes ago
      read_at: new Date().toISOString(),
      data: {
        amount: 75.00,
        currency: 'USD',
        payment_id: 'PAY-123457'
      },
      action: {
        label: 'View Status',
        url: '/refunds/2'
      }
    },
    {
      id: '3',
      title: 'Refund Failed',
      message: 'There was an issue processing your refund. Please contact support for assistance.',
      type: 'error',
      created_at: new Date(Date.now() - 1000 * 60 * 60 * 2).toISOString(), // 2 hours ago
      read_at: null,
      data: {
        amount: 125.00,
        currency: 'USD',
        payment_id: 'PAY-123458',
        error: 'Insufficient funds'
      },
      action: {
        label: 'Contact Support',
        url: '/support/refund/3'
      }
    },
    {
      id: '4',
      title: 'Partial Refund Processed',
      message: 'A partial refund of $25.00 has been processed for your order.',
      type: 'success',
      created_at: new Date(Date.now() - 1000 * 60 * 60 * 5).toISOString(), // 5 hours ago
      read_at: new Date().toISOString(),
      data: {
        amount: 25.00,
        original_amount: 100.00,
        currency: 'USD',
        payment_id: 'PAY-123459',
        reason: 'Partial refund for canceled item'
      },
      action: {
        label: 'View Details',
        url: '/refunds/4'
      }
    },
    {
      id: '5',
      title: 'Refund Processing',
      message: 'Your refund is being processed and should be completed within 5-7 business days.',
      type: 'processing',
      created_at: new Date(Date.now() - 1000 * 60 * 60 * 24).toISOString(), // 1 day ago
      read_at: null,
      data: {
        amount: 60.00,
        currency: 'USD',
        payment_id: 'PAY-123460',
        estimated_completion: new Date(Date.now() + 1000 * 60 * 60 * 24 * 5).toISOString()
      },
      action: {
        label: 'Track Refund',
        url: '/refunds/5'
      }
    }
  ];
  
  // Use sample data if no notifications are loaded
  const displayNotifications = notifications.length > 0 ? notifications : sampleNotifications;
  
  return (
    <div className={`relative ${className}`} ref={dropdownRef}>
      {/* Notification Bell Button */}
      <button
        type="button"
        className="p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 relative"
        onClick={toggleDropdown}
        aria-label={`${unreadCount} unread notifications`}
        aria-expanded={isOpen}
        aria-haspopup="true"
      >
        <NotificationBadge count={unreadCount} />
      </button>
      
      {/* Notification Dropdown */}
      <Transition
        show={isOpen}
        enter="transition ease-out duration-100 transform"
        enterFrom="opacity-0 scale-95"
        enterTo="opacity-100 scale-100"
        leave="transition ease-in duration-75 transform"
        leaveFrom="opacity-100 scale-100"
        leaveTo="opacity-0 scale-95"
        className="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-50"
      >
        <div className="flex flex-col max-h-[80vh] overflow-hidden">
          {/* Header */}
          <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-t-md">
            <div className="flex items-center justify-between">
              <h3 className="text-base font-medium text-gray-900 dark:text-white">
                Notifications {unreadCount > 0 && `(${unreadCount} unread)`}
              </h3>
              <div className="flex items-center space-x-2">
                <button
                  type="button"
                  onClick={handleRefresh}
                  disabled={isRefreshing}
                  className="p-1 rounded-full text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                  title="Refresh"
                >
                  <ArrowPathIcon 
                    className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} 
                    aria-hidden="true" 
                  />
                  <span className="sr-only">Refresh</span>
                </button>
                {unreadCount > 0 && (
                  <button
                    type="button"
                    onClick={handleMarkAllAsRead}
                    disabled={isMarkingAllAsRead}
                    className="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium focus:outline-none"
                  >
                    {isMarkingAllAsRead ? 'Marking...' : 'Mark all as read'}
                  </button>
                )}
              </div>
            </div>
          </div>
          
          {/* Notifications List */}
          <div 
            ref={listRef}
            className="flex-1 overflow-y-auto divide-y divide-gray-200 dark:divide-gray-700"
            onScroll={handleScroll}
            aria-live="polite"
            aria-atomic="false"
            aria-relevant="additions text"
          >
            {isLoading ? (
              <div className="py-8 flex flex-col items-center justify-center text-gray-500">
                <ArrowPathIcon className="h-8 w-8 animate-spin mb-2" />
                <p>Loading notifications...</p>
              </div>
            ) : displayNotifications.length === 0 ? (
              <div className="py-8 text-center">
                <InboxInIcon className="mx-auto h-12 w-12 text-gray-400" />
                <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">No notifications</h3>
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  You don't have any notifications yet.
                </p>
              </div>
            ) : (
              <ul className="max-h-[60vh] overflow-y-auto">
                {displayNotifications.map((notification) => (
                  <li key={notification.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <NotificationItem
                      notification={notification}
                      onMarkAsRead={handleMarkAsRead}
                      onClick={handleNotificationClick}
                    />
                  </li>
                ))}
                
                {isLoadingMore && (
                  <li className="py-3 flex justify-center">
                    <ArrowPathIcon className="h-5 w-5 animate-spin text-gray-400" />
                  </li>
                )}
                
                {!hasMore && displayNotifications.length > 0 && (
                  <li className="py-2 text-center text-xs text-gray-500 dark:text-gray-400">
                    No more notifications
                  </li>
                )}
              </ul>
            )}
          </div>
          
          {/* Footer */}
          <div className="px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 text-center rounded-b-md">
            <Link
              to="/notifications"
              className="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
              onClick={() => setIsOpen(false)}
            >
              View all notifications
            </Link>
          </div>
        </div>
      </Transition>
    </div>
  );
};

export default NotificationCenter;
