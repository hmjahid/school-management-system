import { useState, useEffect } from 'react';
import { Outlet, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../../contexts/AuthContext';
import { FiMenu, FiX, FiLogOut, FiUser, FiSettings, FiBell, FiHome, FiUsers, FiCalendar, FiBook, FiDollarSign, FiFileText, FiPieChart } from 'react-icons/fi';
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

    const roleSpecificItems = {
      admin: [
        { name: 'Users', icon: <FiUsers />, path: 'users' },
        { name: 'Classes', icon: <FiBook />, path: 'classes' },
        { name: 'Finance', icon: <FiDollarSign />, path: 'finance' },
        { name: 'Reports', icon: <FiPieChart />, path: 'reports' },
        { name: 'Settings', icon: <FiSettings />, path: 'settings' },
      ],
      teacher: [
        { name: 'Classes', icon: <FiBook />, path: 'classes' },
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

  return (
    <div className="flex h-screen bg-gray-50">
      {/* Mobile sidebar backdrop */}
      <AnimatePresence>
        {sidebarOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={() => setSidebarOpen(false)}
            className="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
          />
        )}
      </AnimatePresence>

      {/* Sidebar */}
      <motion.aside
        initial={{ x: -300 }}
        animate={{ x: sidebarOpen ? 0 : -300 }}
        transition={{ type: 'spring', stiffness: 300, damping: 30 }}
        className="fixed inset-y-0 left-0 z-30 w-64 transform bg-white shadow-xl lg:relative lg:translate-x-0"
      >
        <div className="flex h-full flex-col">
          {/* Logo */}
          <div className="flex h-16 items-center justify-center border-b border-gray-200 px-4">
            <h1 className="text-xl font-bold text-indigo-600">SchoolEdu</h1>
          </div>

          {/* Navigation */}
          <nav className="flex-1 overflow-y-auto p-4">
            <ul className="space-y-2">
              {navItems.map((item) => (
                <li key={item.path}>
                  <button
                    onClick={() => {
                      navigate(item.path);
                      setSidebarOpen(false);
                    }}
                    className={`flex w-full items-center space-x-3 rounded-lg px-4 py-3 text-left transition-colors ${
                      activeNav === item.path
                        ? 'bg-indigo-50 text-indigo-600'
                        : 'text-gray-700 hover:bg-gray-100'
                    }`}
                  >
                    <span className="text-lg">{item.icon}</span>
                    <span>{item.name}</span>
                  </button>
                </li>
              ))}
            </ul>
          </nav>

          {/* User profile and logout */}
          <div className="border-t border-gray-200 p-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-3">
                <div className="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                  <FiUser size={20} />
                </div>
                <div>
                  <p className="text-sm font-medium text-gray-900">
                    {user?.name || 'User'}
                  </p>
                  <p className="text-xs text-gray-500 capitalize">
                    {user?.role || 'User'}
                  </p>
                </div>
              </div>
              <button
                onClick={handleLogout}
                className="rounded-full p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                title="Logout"
              >
                <FiLogOut size={20} />
              </button>
            </div>
          </div>
        </div>
      </motion.aside>

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
