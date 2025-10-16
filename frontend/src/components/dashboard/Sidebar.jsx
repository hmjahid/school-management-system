import { Link, useLocation } from 'react-router-dom';
import { 
  FaHome, 
  FaUsers, 
  FaChalkboardTeacher, 
  FaUserGraduate, 
  FaUserTie, 
  FaBook, 
  FaCalendarAlt, 
  FaFileInvoiceDollar, 
  FaCog, 
  FaSignOutAlt, 
  FaGlobe, 
  FaFileAlt,
  FaLayerGroup,
  FaNewspaper,
  FaImage,
  FaListUl
} from 'react-icons/fa';
import { useAuth } from '../../contexts/AuthContext';

const Sidebar = () => {
  const { logout } = useAuth();
  const location = useLocation();
  
  const navItems = [
    { 
      to: '/dashboard', 
      icon: <FaHome className="w-5 h-5" />, 
      label: 'Dashboard',
      exact: true
    },
    { 
      to: '/dashboard/students', 
      icon: <FaUserGraduate className="w-5 h-5" />, 
      label: 'Students' 
    },
    { 
      to: '/dashboard/teachers', 
      icon: <FaChalkboardTeacher className="w-5 h-5" />, 
      label: 'Teachers' 
    },
    { 
      to: '/dashboard/parents', 
      icon: <FaUserTie className="w-5 h-5" />, 
      label: 'Parents' 
    },
    { 
      to: '/dashboard/classes', 
      icon: <FaBook className="w-5 h-5" />, 
      label: 'Classes' 
    },
    { 
      to: '/dashboard/attendance', 
      icon: <FaCalendarAlt className="w-5 h-5" />, 
      label: 'Attendance' 
    },
    { 
      to: '/dashboard/exams', 
      icon: <FaFileInvoiceDollar className="w-5 h-5" />, 
      label: 'Exams' 
    },
    { 
      to: '/dashboard/fees', 
      icon: <FaFileInvoiceDollar className="w-5 h-5" />, 
      label: 'Fees' 
    },
    {
      label: 'Website CMS',
      icon: <FaGlobe className="w-5 h-5" />,
      children: [
        { to: '/dashboard/cms/pages', icon: <FaFileAlt className="w-4 h-4" />, label: 'Pages' },
        { to: '/dashboard/cms/header', icon: <FaListUl className="w-4 h-4" />, label: 'Header' },
        { to: '/dashboard/cms/footer', icon: <FaListUl className="w-4 h-4" />, label: 'Footer' },
        { to: '/dashboard/cms/menus', icon: <FaListUl className="w-4 h-4" />, label: 'Menus' },
        { to: '/dashboard/cms/media', icon: <FaImage className="w-4 h-4" />, label: 'Media Library' },
        { to: '/dashboard/cms/blocks', icon: <FaLayerGroup className="w-4 h-4" />, label: 'Content Blocks' },
        { to: '/dashboard/cms/blog', icon: <FaNewspaper className="w-4 h-4" />, label: 'Blog' },
      ]
    },
    { 
      to: '/dashboard/settings', 
      icon: <FaCog className="w-5 h-5" />, 
      label: 'Settings' 
    },
  ];

  // Check if a nav item is active
  const isActive = (path, exact = false) => {
    if (exact) {
      return location.pathname === path;
    }
    return location.pathname.startsWith(path) && path !== '/';
  };

  // Render nav item
  const renderNavItem = (item) => {
    if (item.children) {
      const isParentActive = item.children.some(child => isActive(child.to));
      return (
        <div key={item.label} className="space-y-1">
          <div className={`flex items-center justify-between px-4 py-3 text-sm font-medium rounded-md cursor-pointer ${
            isParentActive 
              ? 'bg-indigo-800 text-white' 
              : 'text-indigo-100 hover:bg-indigo-600 hover:bg-opacity-75'
          }`}>
            <div className="flex items-center">
              <span className="mr-3">{item.icon}</span>
              {item.label}
            </div>
            <svg
              className={`w-4 h-4 transition-transform duration-200 transform ${
                isParentActive ? 'rotate-90' : ''
              }`}
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
            </svg>
          </div>
          {isParentActive && (
            <div className="pl-6 space-y-1">
              {item.children.map(child => renderNavItem(child))}
            </div>
          )}
        </div>
      );
    }

    return (
      <Link
        key={item.to}
        to={item.to}
        className={`flex items-center px-4 py-3 text-sm font-medium rounded-md ${
          isActive(item.to, item.exact)
            ? 'bg-indigo-800 text-white'
            : 'text-indigo-100 hover:bg-indigo-600 hover:bg-opacity-75'
        }`}
      >
        <span className="mr-3">{item.icon}</span>
        {item.label}
      </Link>
    );
  };

  return (
    <div className="hidden md:flex md:flex-shrink-0">
      <div className="flex flex-col w-64 bg-gradient-to-b from-indigo-700 to-indigo-800 text-white">
        <div className="flex items-center justify-center h-16 bg-indigo-900 border-b border-indigo-600">
          <h1 className="text-xl font-bold text-white">SchoolEase</h1>
        </div>
        <div className="flex flex-col flex-grow overflow-y-auto">
          <nav className="flex-1 px-3 py-4 space-y-2">
            {navItems.map(item => renderNavItem(item))}
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
