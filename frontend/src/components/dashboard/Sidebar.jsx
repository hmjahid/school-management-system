import { Link, useLocation } from 'react-router-dom';
import { FaHome, FaUsers, FaChalkboardTeacher, FaUserGraduate, FaUserTie, FaBook, FaCalendarAlt, FaFileInvoiceDollar, FaCog, FaSignOutAlt } from 'react-icons/fa';
import { useAuth } from '../../contexts/AuthContext';

const Sidebar = () => {
  const { logout } = useAuth();
  const location = useLocation();
  
  const navItems = [
    { to: '/dashboard', icon: <FaHome className="w-5 h-5" />, label: 'Dashboard' },
    { to: '/dashboard/students', icon: <FaUserGraduate className="w-5 h-5" />, label: 'Students' },
    { to: '/dashboard/teachers', icon: <FaChalkboardTeacher className="w-5 h-5" />, label: 'Teachers' },
    { to: '/dashboard/parents', icon: <FaUserTie className="w-5 h-5" />, label: 'Parents' },
    { to: '/dashboard/classes', icon: <FaBook className="w-5 h-5" />, label: 'Classes' },
    { to: '/dashboard/attendance', icon: <FaCalendarAlt className="w-5 h-5" />, label: 'Attendance' },
    { to: '/dashboard/exams', icon: <FaFileInvoiceDollar className="w-5 h-5" />, label: 'Exams' },
    { to: '/dashboard/fees', icon: <FaFileInvoiceDollar className="w-5 h-5" />, label: 'Fees' },
    { to: '/dashboard/settings', icon: <FaCog className="w-5 h-5" />, label: 'Settings' },
  ];

  return (
    <div className="hidden md:flex md:flex-shrink-0">
      <div className="flex flex-col w-64 bg-indigo-700 text-white">
        <div className="flex items-center justify-center h-16 bg-indigo-800">
          <h1 className="text-xl font-bold">SchoolEase</h1>
        </div>
        <div className="flex flex-col flex-grow mt-5 overflow-y-auto">
          <nav className="flex-1 px-2 space-y-1">
            {navItems.map((item) => (
              <Link
                key={item.to}
                to={item.to}
                className={`flex items-center px-4 py-3 text-sm font-medium rounded-md ${
                  location.pathname === item.to
                    ? 'bg-indigo-800 text-white'
                    : 'text-indigo-100 hover:bg-indigo-600 hover:bg-opacity-75'
                }`}
              >
                <span className="mr-3">{item.icon}</span>
                {item.label}
              </Link>
            ))}
          </nav>
        </div>
        <div className="p-4 border-t border-indigo-800">
          <button
            onClick={logout}
            className="flex items-center w-full px-4 py-2 text-sm font-medium text-red-100 bg-red-600 rounded-md hover:bg-red-700"
          >
            <FaSignOutAlt className="w-5 h-5 mr-3" />
            Logout
          </button>
        </div>
      </div>
    </div>
  );
};

export default Sidebar;
