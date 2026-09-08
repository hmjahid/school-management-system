import { useState, useEffect } from 'react';
import { Outlet, useNavigate, useLocation, NavLink } from 'react-router-dom';
import { useAuth } from '../../../contexts/AuthContext';
import { 
  FiMenu, FiX, FiLogOut, FiUser, FiSettings, FiBell, FiHome, 
  FiUsers, FiCalendar, FiBook, FiDollarSign, FiFileText, 
  FiPieChart, FiLayers, FiGrid, FiUserCheck, FiCreditCard,
  FiAlertCircle, FiMessageSquare, FiDatabase, FiMail, FiShield
} from 'react-icons/fi';
import { motion, AnimatePresence } from 'framer-motion';
import { toast } from 'react-hot-toast';

const DashboardLayout = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [activeNav, setActiveNav] = useState('dashboard');
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();

  // Update active nav based on URL
  useEffect(() => {
    const path = location.pathname.split('/')[2] || 'dashboard';
    setActiveNav(path);
  }, [location]);

  const handleLogout = async () => {
    try {
      await logout();
      navigate('/login');
      toast.success('Successfully logged out');
    } catch (error) {
      console.error('Logout error:', error);
      toast.error('Failed to log out');
    }
  };

  // Navigation items based on user role
  const getNavItems = () => {
    const commonItems = [
      { name: 'Dashboard', icon: <FiHome />, path: 'dashboard' },
      { name: 'Profile', icon: <FiUser />, path: 'profile' },
    ];

    if (!user) return commonItems;

    // Settings submenu items
    const settingsItems = [
      { name: 'General', icon: <FiSettings />, path: 'settings/general' },
      { name: 'Security', icon: <FiShield />, path: 'settings/security' },
      { name: 'Email', icon: <FiMail />, path: 'settings/email' },
      { name: 'Notifications', icon: <FiBell />, path: 'settings/notifications' },
      { name: 'Payments', icon: <FiCreditCard />, path: 'settings/payments' },
      { name: 'Backup', icon: <FiDatabase />, path: 'settings/backup' },
    ];

    const roleSpecificItems = {
      admin: [
        { 
          name: 'Management', 
          icon: <FiGrid />, 
          path: '#', 
          submenu: [
            { name: 'Users', icon: <FiUsers />, path: 'users' },
            { name: 'Students', icon: <FiUserCheck />, path: 'users/students' },
            { name: 'Teachers', icon: <FiUserCheck />, path: 'users/teachers' },
            { name: 'Parents', icon: <FiUsers />, path: 'users/parents' },
            { name: 'Classes', icon: <FiBook />, path: 'classes' },
            { name: 'Sections', icon: <FiLayers />, path: 'classes/sections' },
          ]
        },
        { 
          name: 'Finance', 
          icon: <FiDollarSign />, 
          path: 'finance',
          submenu: [
            { name: 'Overview', icon: <FiPieChart />, path: 'finance' },
            { name: 'Fees', icon: <FiCreditCard />, path: 'finance/fees' },
            { name: 'Payments', icon: <FiDollarSign />, path: 'finance/payments' },
            { name: 'Expenses', icon: <FiFileText />, path: 'finance/expenses' },
          ]
        },
        { 
          name: 'Reports', 
          icon: <FiPieChart />, 
          path: 'reports',
          submenu: [
            { name: 'Overview', icon: <FiPieChart />, path: 'reports' },
            { name: 'Attendance', icon: <FiUserCheck />, path: 'reports/attendance' },
            { name: 'Exams', icon: <FiFileText />, path: 'reports/exams' },
            { name: 'Finance', icon: <FiDollarSign />, path: 'reports/finance' },
          ]
        },
        { 
          name: 'Settings', 
          icon: <FiSettings />, 
          path: 'settings',
          submenu: settingsItems
        },
        { 
          name: 'CMS', 
          icon: <FiLayers />, 
          path: 'cms',
          submenu: [
            { name: 'Pages', icon: <FiFileText />, path: 'cms/pages' },
            { name: 'News', icon: <FiMessageSquare />, path: 'cms/news' },
            { name: 'Events', icon: <FiCalendar />, path: 'cms/events' },
            { name: 'Gallery', icon: <FiGrid />, path: 'cms/gallery' },
          ]
        },
      ],
      teacher: [
        { name: 'My Classes', icon: <FiBook />, path: 'classes' },
        { name: 'Attendance', icon: <FiCalendar />, path: 'attendance' },
        { name: 'Grades', icon: <FiFileText />, path: 'grades' },
      ],
      student: [
        { name: 'Schedule', icon: <FiCalendar />, path: 'schedule' },
        { name: 'Grades', icon: <FiFileText />, path: 'grades' },
        { name: 'Assignments', icon: <FiBook />, path: 'assignments' },
      ],
      parent: [
        { name: 'Children', icon: <FiUsers />, path: 'children' },
        { name: 'Attendance', icon: <FiCalendar />, path: 'attendance' },
        { name: 'Payments', icon: <FiDollarSign />, path: 'payments' },
      ],
    };

    return [...commonItems, ...(roleSpecificItems[user.role] || [])];
  };

  const navItems = getNavItems();

  // Check if current path is a submenu item
  const isSubmenuActive = (parentPath, currentPath) => {
    return currentPath.startsWith(`/${parentPath}/`);
  };

  // Toggle submenu
  const toggleSubmenu = (path) => {
    if (activeNav === path) {
      setActiveNav('');
    } else {
      setActiveNav(path);
    }
  };

  // Render navigation items with support for submenus
  const renderNavItems = (items) => {
    return items.map((item) => (
      <div key={item.name} className="mb-1">
        <button
          onClick={() => {
            if (item.submenu) {
              toggleSubmenu(item.path);
            } else {
              navigate(item.path);
              setSidebarOpen(false);
            }
          }}
          className={`flex items-center justify-between w-full px-4 py-3 text-sm font-medium rounded-md ${
            activeNav === item.path.split('/')[0] || 
            (item.submenu && isSubmenuActive(item.path, location.pathname))
              ? 'bg-indigo-50 text-indigo-700'
              : 'text-gray-600 hover:bg-gray-100'
          }`}
        >
          <div className="flex items-center">
            <span className="mr-3">{item.icon}</span>
            {item.name}
          </div>
          {item.submenu && (
            <svg
              className={`w-4 h-4 transition-transform duration-200 ${
                activeNav === item.path ? 'transform rotate-90' : ''
              }`}
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          )}
        </button>

        {/* Submenu */}
        {item.submenu && (
          <motion.div
            initial={{ height: 0, opacity: 0 }}
            animate={{
              height: activeNav === item.path ? 'auto' : 0,
              opacity: activeNav === item.path ? 1 : 0,
            }}
            className="overflow-hidden"
          >
            <div className="pl-8 py-1 space-y-1">
              {item.submenu.map((subItem) => (
                <NavLink
                  key={subItem.path}
                  to={subItem.path}
                  onClick={() => setSidebarOpen(false)}
                  className={({ isActive }) =>
                    `flex items-center px-4 py-2 text-sm rounded-md ${
                      isActive
                        ? 'bg-indigo-50 text-indigo-700 font-medium'
                        : 'text-gray-600 hover:bg-gray-100'
                    }`
                  }
                >
                  <span className="mr-2">{subItem.icon}</span>
                  {subItem.name}
                </NavLink>
              ))}
            </div>
          </motion.div>
        )}
      </div>
    ));
  };

  return (
    <div className="flex h-screen bg-gray-50">
      {/* Mobile sidebar backdrop */}
      <AnimatePresence>
        {sidebarOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
            onClick={() => setSidebarOpen(false)}
          />
        )}
      </AnimatePresence>

      {/* Sidebar */}
      <motion.div
        initial={{ x: -300 }}
        animate={{ x: sidebarOpen ? 0 : -300 }}
        className="fixed inset-y-0 left-0 z-30 w-64 bg-white shadow-lg lg:static lg:translate-x-0 overflow-y-auto"
      >
        <div className="flex flex-col h-full">
          {/* Logo */}
          <div className="flex items-center justify-between h-16 px-4 bg-indigo-600">
            <h1 className="text-xl font-bold text-white">School Portal</h1>
            <button 
              onClick={() => setSidebarOpen(false)}
              className="p-1 text-white rounded-md lg:hidden hover:bg-indigo-500"
            >
              <FiX size={24} />
            </button>
          </div>

          {/* Navigation */}
          <nav className="flex-1 px-2 py-4 overflow-y-auto">
            {renderNavItems(getNavItems())}
          </nav>

          {/* User profile */}
          <div className="p-4 border-t border-gray-200">
            <div className="flex items-center">
              <div className="flex-shrink-0">
                <div className="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                  <FiUser size={20} />
                </div>
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-gray-700">
                  {user?.name || 'User'}
                </p>
                <div className="flex items-center">
                  <span className="text-xs text-gray-500">{user?.role || 'User'}</span>
                  <span className="mx-2 text-gray-300">|</span>
                  <button
                    onClick={handleLogout}
                    className="text-xs text-gray-500 hover:text-gray-700 flex items-center"
                  >
                    <FiLogOut className="mr-1" size={14} /> Sign out
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </motion.div>

      {/* Main content */}
      <div className="flex flex-1 flex-col overflow-hidden">
        {/* Top navigation */}
        <header className="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-6">
          <div className="flex items-center">
            <button
              onClick={() => setSidebarOpen(!sidebarOpen)}
              className="mr-4 text-gray-500 hover:text-gray-700 lg:hidden"
            >
              {sidebarOpen ? <FiX size={24} /> : <FiMenu size={24} />}
            </button>
            <h1 className="text-xl font-semibold text-gray-800">
              {navItems.find((item) => item.path === activeNav)?.name || 'Dashboard'}
            </h1>
          </div>
          
          <div className="flex items-center space-x-4">
            <button className="relative rounded-full p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
              <FiBell size={20} />
              <span className="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500"></span>
            </button>
            
            <div className="hidden md:flex items-center space-x-3">
              <div className="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                <FiUser size={16} />
              </div>
              <div className="text-right">
                <p className="text-sm font-medium text-gray-900">
                  {user?.name || 'User'}
                </p>
                <p className="text-xs text-gray-500 capitalize">
                  {user?.role || 'User'}
                </p>
              </div>
            </div>
          </div>
        </header>

        {/* Page content */}
        <main className="flex-1 overflow-y-auto bg-gray-50 p-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default DashboardLayout;
