import { useState, useEffect } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { useDashboard } from '../../contexts/DashboardContext';
import Sidebar from './Sidebar';
import EnhancedHeader from './EnhancedHeader';

const EnhancedDashboardLayout = () => {
  const { isSidebarOpen, closeSidebar } = useDashboard();
  const location = useLocation();
  const isMobile = window.innerWidth < 768;

  // Close sidebar when route changes on mobile
  useEffect(() => {
    if (isMobile) {
      closeSidebar();
    }
  }, [location, isMobile, closeSidebar]);

  // Handle window resize
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth >= 768) {
        closeSidebar();
      }
    };

    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, [closeSidebar]);

  return (
    <div className="flex h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
      {/* Sidebar */}
      <div
        className={`fixed inset-y-0 left-0 z-30 w-64 transform ${
          isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
        } bg-white dark:bg-gray-800 shadow-lg transition-transform duration-300 ease-in-out md:relative md:translate-x-0`}
      >
        <Sidebar />
      </div>

      {/* Overlay for mobile */}
      {isSidebarOpen && isMobile && (
        <div
          className="fixed inset-0 z-20 bg-black bg-opacity-50 transition-opacity duration-300"
          onClick={closeSidebar}
          aria-hidden="true"
        />
      )}

      {/* Main content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        <EnhancedHeader />
        
        <main 
          className={`flex-1 overflow-x-hidden overflow-y-auto transition-all duration-300 ${
            isSidebarOpen && isMobile ? 'opacity-30' : 'opacity-100'
          }`}
        >
          <div className="p-4 md:p-6">
            <Toaster 
              position="top-right" 
              toastOptions={{
                style: {
                  background: 'var(--color-bg-elevated)',
                  color: 'var(--color-text)',
                  border: '1px solid var(--color-border)'
                },
                success: {
                  iconTheme: {
                    primary: '#10B981',
                    secondary: 'white',
                  },
                },
                error: {
                  iconTheme: {
                    primary: '#EF4444',
                    secondary: 'white',
                  },
                },
              }}
            />
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
};

export default EnhancedDashboardLayout;
