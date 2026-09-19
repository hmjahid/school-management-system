import React from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { 
  FaFileAlt, 
  FaListUl, 
  FaImage, 
  FaLayerGroup, 
  FaNewspaper,
  FaChevronRight
} from 'react-icons/fa';

const CMSLayout = () => {
  const location = useLocation();
  
  const navItems = [
    { 
      to: '/dashboard/cms/pages', 
      icon: <FaFileAlt className="w-4 h-4" />, 
      label: 'Pages',
      description: 'Manage website pages and their content'
    },
    { 
      to: '/dashboard/cms/header', 
      icon: <FaListUl className="w-4 h-4" />, 
      label: 'Header',
      description: 'Customize header content and navigation'
    },
    { 
      to: '/dashboard/cms/footer', 
      icon: <FaListUl className="w-4 h-4" />, 
      label: 'Footer',
      description: 'Customize footer content and links'
    },
    { 
      to: '/dashboard/cms/menus', 
      icon: <FaListUl className="w-4 h-4" />, 
      label: 'Menus',
      description: 'Manage website navigation menus'
    },
    { 
      to: '/dashboard/cms/media', 
      icon: <FaImage className="w-4 h-4" />, 
      label: 'Media Library',
      description: 'Upload and manage media files'
    },
    { 
      to: '/dashboard/cms/blocks', 
      icon: <FaLayerGroup className="w-4 h-4" />, 
      label: 'Content Blocks',
      description: 'Create and manage reusable content blocks'
    },
    { 
      to: '/dashboard/cms/blog', 
      icon: <FaNewspaper className="w-4 h-4" />, 
      label: 'Blog',
      description: 'Manage blog posts and categories'
    },
  ];

  const isActive = (path) => {
    return location.pathname === path || location.pathname.startsWith(`${path}/`);
  };

  return (
    <div className="flex flex-col h-full">
      <div className="bg-white shadow-sm">
        <div className="px-6 py-4 border-b border-gray-200">
          <h1 className="text-2xl font-semibold text-gray-900">Website CMS</h1>
          <p className="mt-1 text-sm text-gray-500">
            Manage your website content, pages, and media
          </p>
        </div>
      </div>
      
      <div className="flex flex-1 overflow-hidden">
        {/* Sidebar */}
        <div className="w-64 bg-white border-r border-gray-200 overflow-y-auto">
          <nav className="p-4 space-y-1">
            {navItems.map((item) => (
              <a
                key={item.to}
                href={item.to}
                className={`group flex items-center px-3 py-3 text-sm font-medium rounded-md ${
                  isActive(item.to)
                    ? 'bg-indigo-50 text-indigo-700 border-l-4 border-indigo-500'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-4 border-transparent'
                }`}
              >
                <span className={`mr-3 ${isActive(item.to) ? 'text-indigo-500' : 'text-gray-400 group-hover:text-gray-500'}`}>
                  {item.icon}
                </span>
                <div className="flex-1">
                  <div className="font-medium">{item.label}</div>
                  <div className="text-xs text-gray-500">{item.description}</div>
                </div>
                <FaChevronRight 
                  className={`w-3 h-3 ${isActive(item.to) ? 'text-indigo-500' : 'text-gray-400'}`} 
                />
              </a>
            ))}
          </nav>
        </div>
        
        {/* Main Content */}
        <div className="flex-1 overflow-auto bg-gray-50 p-6">
          <Outlet />
        </div>
      </div>
    </div>
  );
};

export default CMSLayout;
