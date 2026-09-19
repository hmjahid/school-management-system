import { useState, useEffect, useRef, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { 
  FiMenu, 
  FiBell, 
  FiSearch, 
  FiUser, 
  FiMail, 
  FiSettings, 
  FiLogOut,
  FiChevronDown,
  FiX,
  FiCheckCircle,
  FiAlertCircle,
  FiInfo,
  FiClock,
  FiDollarSign,
  FiBook,
  FiCalendar
} from 'react-icons/fi';
import { useAuth } from '../../contexts/AuthContext';
import { useDashboard } from '../../contexts/DashboardContext';
import { useDashboardData } from '../../hooks/useDashboardData';
import { searchContent } from '../../services/dashboardService';
import { debounce } from 'lodash';

const EnhancedHeader = () => {
  const navigate = useNavigate();
  const { user, logout } = useAuth();
  const { toggleSidebar, isMobile } = useDashboard();
  const { 
    notifications: { 
      data: notifications = [], 
      markAsRead, 
      markAllAsRead, 
      unreadCount,
      isLoading: isLoadingNotifications
    } 
  } = useDashboardData();
  
  // State for UI controls
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [isSearchFocused, setIsSearchFocused] = useState(false);
  const [isProfileOpen, setIsProfileOpen] = useState(false);
  const [isNotificationsOpen, setIsNotificationsOpen] = useState(false);
  
  // Refs for handling clicks outside
  const searchRef = useRef(null);
  const profileRef = useRef(null);
  const notificationsRef = useRef(null);
  
  // Debounced search function
  const debouncedSearch = useCallback(
    debounce(async (query) => {
      if (query.trim() === '') {
        setSearchResults([]);
        return;
      }
      try {
        const results = await searchContent(query);
        setSearchResults(results);
      } catch (error) {
        console.error('Search error:', error);
        setSearchResults([]);
      }
    }, 300),
    []
  );
  
  // Handle search input changes
  useEffect(() => {
    debouncedSearch(searchQuery);
    return () => debouncedSearch.cancel();
  }, [searchQuery, debouncedSearch]);
  
  // Close dropdowns when clicking outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (profileRef.current && !profileRef.current.contains(event.target)) {
        setIsProfileOpen(false);
      }
      if (notificationsRef.current && !notificationsRef.current.contains(event.target)) {
        setIsNotificationsOpen(false);
      }
      if (searchRef.current && !searchRef.current.contains(event.target)) {
        setIsSearchFocused(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);
  
  // Handle notification click
  const handleNotificationClick = (notification) => {
    markAsRead(notification.id);
    if (notification.link) {
      navigate(notification.link);
      setIsNotificationsOpen(false);
    }
  };
  
  // Get notification icon based on type
  const getNotificationIcon = (type) => {
    switch (type) {
      case 'assignment':
        return <FiCheckCircle className="w-5 h-5 text-blue-500" />;
      case 'payment':
        return <FiDollarSign className="w-5 h-5 text-green-500" />;
      case 'alert':
        return <FiAlertCircle className="w-5 h-5 text-yellow-500" />;
      case 'event':
        return <FiCalendar className="w-5 h-5 text-purple-500" />;
      default:
        return <FiInfo className="w-5 h-5 text-gray-500" />;
    }
  };
  
  // Handle logout
  const handleLogout = async () => {
    try {
      await logout();
      navigate('/login');
    } catch (error) {
      console.error('Failed to log out', error);
    }
  };

  return (
    <header className="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-10 transition-colors duration-200">
      <div className="flex items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <div className="flex items-center flex-1">
          {/* Mobile menu button */}
          <button
            onClick={toggleSidebar}
            className="p-2 text-gray-500 dark:text-gray-400 rounded-md hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 md:hidden"
          >
            <span className="sr-only">Open sidebar</span>
            <FiMenu className="w-6 h-6" />
          </button>

          {/* Search */}
          <div className="relative ml-4 w-full max-w-xl" ref={searchRef}>
            <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <FiSearch className="w-5 h-5 text-gray-400" />
            </div>
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onFocus={() => setIsSearchFocused(true)}
              className="block w-full py-2 pl-10 pr-3 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:placeholder-gray-400 dark:text-white transition-colors duration-200"
              placeholder="Search students, classes, assignments..."
            />
            
            {/* Clear search button */}
            {searchQuery && (
              <button
                onClick={() => setSearchQuery('')}
                className="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-500"
              >
                <FiX className="w-5 h-5" />
              </button>
            )}
            
            {/* Search results dropdown */}
            {isSearchFocused && searchQuery && (
              <div className="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 max-h-96 overflow-y-auto">
                {searchResults.length > 0 ? (
                  <div className="py-1">
                    {searchResults.map((result) => (
                      <a
                        key={result.id}
                        href={result.link}
                        className="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                        onClick={(e) => {
                          e.preventDefault();
                          navigate(result.link);
                          setIsSearchFocused(false);
                        }}
                      >
                        <div className="flex-shrink-0 mr-3">
                          {result.type === 'student' && <FiUser className="w-5 h-5 text-indigo-500" />}
                          {result.type === 'class' && <FiBook className="w-5 h-5 text-green-500" />}
                          {result.type === 'assignment' && <FiCheckCircle className="w-5 h-5 text-blue-500" />}
                          {result.type === 'event' && <FiCalendar className="w-5 h-5 text-purple-500" />}
                        </div>
                        <div>
                          <p className="font-medium">{result.title}</p>
                          <p className="text-xs text-gray-500 dark:text-gray-400">{result.subtitle}</p>
                        </div>
                      </a>
                    ))}
                  </div>
                ) : searchQuery ? (
                  <div className="px-4 py-8 text-center">
                    <FiSearch className="w-8 h-8 mx-auto text-gray-400" />
                    <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                      No results found for <span className="font-medium">"{searchQuery}"</span>
                    </p>
                  </div>
                ) : null}
              </div>
            )}
          </div>
        </div>
        
        <div className="flex items-center space-x-4 ml-4">
          {/* Notifications */}
          <div className="relative" ref={notificationsRef}>
            <button
              onClick={() => {
                setIsNotificationsOpen(!isNotificationsOpen);
                setIsProfileOpen(false);
              }}
              className="p-2 text-gray-500 dark:text-gray-300 rounded-full hover:text-gray-600 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 relative transition-colors duration-200"
              aria-label="Notifications"
            >
              <FiBell className="w-6 h-6" />
              {unreadCount > 0 && (
                <span className="absolute top-0 right-0 w-5 h-5 text-xs font-medium text-white bg-red-500 rounded-full flex items-center justify-center">
                  {unreadCount > 9 ? '9+' : unreadCount}
                </span>
              )}
            </button>
            
            {/* Notifications dropdown */}
            {isNotificationsOpen && (
              <div className="absolute right-0 w-80 mt-2 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 divide-y divide-gray-200 dark:divide-gray-700">
                <div className="px-4 py-3 flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white">Notifications</h3>
                  {unreadCount > 0 && (
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        markAllAsRead();
                      }}
                      className="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                      Mark all as read
                    </button>
                  )}
                </div>
                
                <div className="max-h-96 overflow-y-auto">
                  {isLoadingNotifications ? (
                    <div className="p-4 text-center">
                      <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500 mx-auto"></div>
                    </div>
                  ) : notifications.length > 0 ? (
                    notifications.map((notification) => (
                      <div
                        key={notification.id}
                        onClick={() => handleNotificationClick(notification)}
                        className={`p-4 cursor-pointer ${
                          !notification.read 
                            ? 'bg-blue-50 dark:bg-blue-900/30' 
                            : 'hover:bg-gray-50 dark:hover:bg-gray-700'
                        } transition-colors duration-150`}
                      >
                        <div className="flex items-start">
                          <div className="flex-shrink-0 pt-0.5">
                            {getNotificationIcon(notification.type)}
                          </div>
                          <div className="ml-3 flex-1">
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                              {notification.title}
                            </p>
                            <p className="text-sm text-gray-500 dark:text-gray-300">
                              {notification.message}
                            </p>
                            <div className="mt-1 flex items-center justify-between">
                              <p className="text-xs text-gray-400">
                                {notification.time}
                              </p>
                              {!notification.read && (
                                <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                  New
                                </span>
                              )}
                            </div>
                          </div>
                        </div>
                      </div>
                    ))
                  ) : (
                    <div className="p-6 text-center">
                      <FiBell className="w-10 h-10 mx-auto text-gray-400" />
                      <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        No notifications yet
                      </p>
                      <p className="mt-1 text-xs text-gray-400">
                        We'll notify you when something happens
                      </p>
                    </div>
                  )}
                </div>
                
                <div className="p-2 text-center bg-gray-50 dark:bg-gray-700/50">
                  <button
                    onClick={() => {
                      navigate('/notifications');
                      setIsNotificationsOpen(false);
                    }}
                    className="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                  >
                    View all notifications
                  </button>
                </div>
              </div>
            )}
          </div>

          {/* Profile dropdown */}
          <div className="relative ml-2" ref={profileRef}>
            <button
              onClick={() => {
                setIsProfileOpen(!isProfileOpen);
                setIsNotificationsOpen(false);
              }}
              className="flex items-center max-w-xs text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
              id="user-menu"
              aria-haspopup="true"
              aria-expanded={isProfileOpen}
            >
              <span className="sr-only">Open user menu</span>
              <div className="w-8 h-8 rounded-full bg-indigo-600 dark:bg-indigo-700 flex items-center justify-center text-white font-medium shadow-sm">
                {user?.name?.charAt(0)?.toUpperCase() || 'U'}
              </div>
              <div className="hidden md:flex md:items-center">
                <span className="ml-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                  {user?.name || 'User'}
                </span>
                <FiChevronDown className="ml-1 h-4 w-4 text-gray-500 dark:text-gray-400" />
              </div>
            </button>

            {/* Profile dropdown menu */}
            {isProfileOpen && (
              <div 
                className="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-10 divide-y divide-gray-100 dark:divide-gray-700"
                role="menu"
                aria-orientation="vertical"
                aria-labelledby="user-menu"
              >
                <div className="px-4 py-3" role="none">
                  <p className="text-sm text-gray-900 dark:text-white font-medium truncate">
                    {user?.name || 'User'}
                  </p>
                  <p className="text-sm text-gray-500 dark:text-gray-300 truncate">
                    {user?.email || 'user@example.com'}
                  </p>
                </div>
                
                <div className="py-1" role="none">
                  <a
                    href="/profile"
                    className="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    onClick={(e) => {
                      e.preventDefault();
                      navigate('/profile');
                      setIsProfileOpen(false);
                    }}
                  >
                    <FiUser className="mr-3 h-4 w-4 text-gray-500" />
                    Your Profile
                  </a>
                  
                  <a
                    href="/settings"
                    className="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    onClick={(e) => {
                      e.preventDefault();
                      navigate('/settings');
                      setIsProfileOpen(false);
                    }}
                  >
                    <FiSettings className="mr-3 h-4 w-4 text-gray-500" />
                    Settings
                  </a>
                </div>
                
                <div className="py-1" role="none">
                  <button
                    onClick={handleLogout}
                    className="w-full text-left flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-gray-700"
                    role="menuitem"
                  >
                    <FiLogOut className="mr-3 h-4 w-4" />
                    Sign out
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </header>
  );
};

export default EnhancedHeader;
