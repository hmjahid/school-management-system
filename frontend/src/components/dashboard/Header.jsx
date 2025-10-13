import { useState, useEffect } from 'react';
import { FiMenu, FiBell, FiSearch, FiUser } from 'react-icons/fi';
import { useAuth } from '../../contexts/AuthContext';

const Header = ({ toggleSidebar }) => {
  const { user } = useAuth();
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [isProfileOpen, setIsProfileOpen] = useState(false);
  const [isNotificationsOpen, setIsNotificationsOpen] = useState(false);

  // Mock notifications - replace with actual API call
  useEffect(() => {
    // TODO: Fetch notifications from API
    const mockNotifications = [
      { id: 1, title: 'New assignment uploaded', message: 'Math assignment #2 has been uploaded', time: '2 hours ago', read: false },
      { id: 2, title: 'Fee payment received', message: 'Payment of $200 received', time: '1 day ago', read: true },
      { id: 3, title: 'Parent meeting', message: 'Meeting scheduled for tomorrow at 2 PM', time: '2 days ago', read: true },
    ];
    setNotifications(mockNotifications);
    setUnreadCount(mockNotifications.filter(n => !n.read).length);
  }, []);

  const markAsRead = (id) => {
    // TODO: Implement mark as read API call
    setNotifications(notifications.map(n => 
      n.id === id ? { ...n, read: true } : n
    ));
    setUnreadCount(prev => Math.max(0, prev - 1));
  };

  return (
    <header className="bg-white shadow-sm">
      <div className="flex items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <div className="flex items-center">
          <button
            onClick={toggleSidebar}
            className="p-2 text-gray-500 rounded-md hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 md:hidden"
          >
            <span className="sr-only">Open sidebar</span>
            <FiMenu className="w-6 h-6" />
          </button>
          <div className="relative ml-4">
            <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <FiSearch className="w-5 h-5 text-gray-400" />
            </div>
            <input
              type="text"
              className="w-full py-2 pl-10 pr-3 text-sm bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              placeholder="Search..."
            />
          </div>
        </div>
        
        <div className="flex items-center space-x-4">
          <div className="relative">
            <button
              onClick={() => setIsNotificationsOpen(!isNotificationsOpen)}
              className="p-2 text-gray-500 rounded-full hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 relative"
            >
              <FiBell className="w-6 h-6" />
              {unreadCount > 0 && (
                <span className="absolute top-0 right-0 w-5 h-5 text-xs font-medium text-white bg-red-500 rounded-full flex items-center justify-center">
                  {unreadCount}
                </span>
              )}
            </button>
            
            {isNotificationsOpen && (
              <div className="absolute right-0 w-80 mt-2 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5">
                <div className="p-4 border-b">
                  <h3 className="text-lg font-medium text-gray-900">Notifications</h3>
                </div>
                <div className="max-h-96 overflow-y-auto">
                  {notifications.length > 0 ? (
                    notifications.map((notification) => (
                      <div
                        key={notification.id}
                        className={`p-4 border-b hover:bg-gray-50 cursor-pointer ${!notification.read ? 'bg-blue-50' : ''}`}
                        onClick={() => markAsRead(notification.id)}
                      >
                        <div className="flex items-start">
                          <div className="flex-shrink-0 pt-0.5">
                            <div className="w-2 h-2 rounded-full bg-blue-500"></div>
                          </div>
                          <div className="ml-3 flex-1">
                            <p className="text-sm font-medium text-gray-900">{notification.title}</p>
                            <p className="text-sm text-gray-500">{notification.message}</p>
                            <p className="mt-1 text-xs text-gray-400">{notification.time}</p>
                          </div>
                        </div>
                      </div>
                    ))
                  ) : (
                    <div className="p-4 text-center text-gray-500">No new notifications</div>
                  )}
                </div>
                <div className="p-2 text-center bg-gray-50">
                  <a href="#" className="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    View all notifications
                  </a>
                </div>
              </div>
            )}
          </div>
          
          <div className="relative">
            <button
              onClick={() => setIsProfileOpen(!isProfileOpen)}
              className="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <span className="sr-only">Open user menu</span>
              <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-medium">
                {user?.name?.charAt(0) || <FiUser className="w-5 h-5" />}
              </div>
              <span className="ml-2 text-sm font-medium text-gray-700 hidden md:inline">
                {user?.name || 'User'}
              </span>
            </button>
            
            {isProfileOpen && (
              <div className="absolute right-0 w-48 mt-2 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5">
                <div className="py-1">
                  <a
                    href="#"
                    className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  >
                    Your Profile
                  </a>
                  <a
                    href="#"
                    className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  >
                    Settings
                  </a>
                  <a
                    href="#"
                    className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  >
                    Logout
                  </a>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </header>
  );
};

export default Header;
