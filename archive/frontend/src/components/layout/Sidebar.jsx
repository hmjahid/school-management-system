import React from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import {
  HomeIcon,
  UserGroupIcon,
  BookOpenIcon,
  CurrencyDollarIcon,
  CalendarIcon,
  ChartBarIcon,
  CogIcon,
  BellIcon,
  UsersIcon,
  ClipboardDocumentListIcon
} from '@heroicons/react/24/outline';

const Sidebar = () => {
  const location = useLocation();
  
  const navigation = [
    { name: 'Dashboard', href: '/dashboard', icon: HomeIcon },
    { name: 'Students', href: '/students', icon: UserGroupIcon },
    { name: 'Classes', href: '/classes', icon: BookOpenIcon },
    { name: 'Fees', href: '/fees', icon: CurrencyDollarIcon },
    { name: 'Exams', href: '/exams', icon: ClipboardDocumentListIcon },
    { name: 'Attendance', href: '/attendance', icon: CalendarIcon },
    { name: 'Reports', href: '/reports', icon: ChartBarIcon },
    { name: 'Staff', href: '/staff', icon: UsersIcon },
    { name: 'Notifications', href: '/notifications', icon: BellIcon },
    { name: 'Settings', href: '/settings', icon: CogIcon },
  ];

  return (
    <div className="hidden md:flex md:flex-shrink-0">
      <div className="flex flex-col w-64 border-r border-gray-200 bg-white">
        <div className="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
          <div className="flex items-center flex-shrink-0 px-4">
            <h1 className="text-xl font-bold text-indigo-600">School Portal</h1>
          </div>
          <div className="mt-5 flex-grow flex flex-col">
            <nav className="flex-1 px-2 space-y-1 bg-white">
              {navigation.map((item) => {
                const isActive = location.pathname.startsWith(item.href);
                return (
                  <NavLink
                    key={item.name}
                    to={item.href}
                    className={`group flex items-center px-2 py-2 text-sm font-medium rounded-md ${
                      isActive
                        ? 'bg-indigo-50 text-indigo-700'
                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                    }`}
                  >
                    <item.icon
                      className={`mr-3 flex-shrink-0 h-6 w-6 ${
                        isActive ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500'
                      }`}
                      aria-hidden="true"
                    />
                    {item.name}
                  </NavLink>
                );
              })}
            </nav>
          </div>
        </div>
        <div className="flex-shrink-0 flex border-t border-gray-200 p-4">
          <div className="flex-shrink-0 w-full group block">
            <div className="flex items-center">
              <div>
                <div className="h-9 w-9 rounded-full bg-indigo-100 flex items-center justify-center">
                  <UserIcon className="h-5 w-5 text-indigo-600" />
                </div>
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-gray-700 group-hover:text-gray-900">
                  Admin User
                </p>
                <p className="text-xs font-medium text-gray-500 group-hover:text-gray-700">
                  View profile
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Sidebar;
