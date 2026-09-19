import { createContext, useContext, useState, useEffect, useCallback } from 'react';

const EnhancedDashboardContext = createContext();

export const EnhancedDashboardProvider = ({ children }) => {
  const [isSidebarOpen, setIsSidebarOpen] = useState(() => {
    // Check for saved sidebar state (default to true for desktop, false for mobile)
    const savedState = localStorage.getItem('sidebarState');
    if (savedState !== null) return JSON.parse(savedState);
    return window.innerWidth >= 768; // Open by default on desktop
  });
  
  const [isMobile, setIsMobile] = useState(window.innerWidth < 768);
  const [theme, setTheme] = useState(() => {
    // Check for saved theme preference or use system preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) return savedTheme;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  });
  
  const [activePath, setActivePath] = useState('/dashboard');
  const [breadcrumbs, setBreadcrumbs] = useState([{ name: 'Dashboard', path: '/dashboard' }]);

  // Handle window resize for mobile detection and responsive behavior
  useEffect(() => {
    const checkMobile = () => {
      const isMobileView = window.innerWidth < 768;
      setIsMobile(isMobileView);
      
      // Auto-close sidebar when switching to mobile view
      if (isMobileView && isSidebarOpen) {
        setIsSidebarOpen(false);
      }
      
      // Auto-open sidebar when switching to desktop view if it was previously open
      if (!isMobileView && !isSidebarOpen && localStorage.getItem('sidebarState') === 'true') {
        setIsSidebarOpen(true);
      }
    };

    window.addEventListener('resize', checkMobile);
    checkMobile(); // Initial check
    
    return () => window.removeEventListener('resize', checkMobile);
  }, [isSidebarOpen]);

  // Save sidebar state to localStorage
  useEffect(() => {
    localStorage.setItem('sidebarState', JSON.stringify(isSidebarOpen));
  }, [isSidebarOpen]);

  // Apply theme class to document element and update CSS variables
  useEffect(() => {
    const root = window.document.documentElement;
    
    // Remove all theme classes
    root.classList.remove('light', 'dark');
    
    // Add current theme class
    root.classList.add(theme);
    
    // Update CSS variables based on theme
    if (theme === 'dark') {
      root.style.setProperty('--color-bg-primary', '#1F2937');
      root.style.setProperty('--color-bg-secondary', '#111827');
      root.style.setProperty('--color-bg-elevated', '#1F2937');
      root.style.setProperty('--color-text', '#F9FAFB');
      root.style.setProperty('--color-text-secondary', '#9CA3AF');
      root.style.setProperty('--color-border', '#374151');
    } else {
      root.style.setProperty('--color-bg-primary', '#FFFFFF');
      root.style.setProperty('--color-bg-secondary', '#F9FAFB');
      root.style.setProperty('--color-bg-elevated', '#FFFFFF');
      root.style.setProperty('--color-text', '#111827');
      root.style.setProperty('--color-text-secondary', '#6B7280');
      root.style.setProperty('--color-border', '#E5E7EB');
    }
    
    // Save to localStorage
    localStorage.setItem('theme', theme);
  }, [theme]);

  // Toggle sidebar open/closed
  const toggleSidebar = useCallback(() => {
    setIsSidebarOpen(prev => {
      const newState = !prev;
      localStorage.setItem('sidebarState', JSON.stringify(newState));
      return newState;
    });
  }, []);

  // Close sidebar (useful for mobile)
  const closeSidebar = useCallback(() => {
    if (isMobile) {
      setIsSidebarOpen(false);
    }
  }, [isMobile]);

  // Toggle between light and dark theme
  const toggleTheme = useCallback(() => {
    setTheme(prevTheme => (prevTheme === 'dark' ? 'light' : 'dark'));
  }, []);

  // Update active path and breadcrumbs
  const updateActivePath = useCallback((path, breadcrumbItems) => {
    setActivePath(path);
    if (breadcrumbItems) {
      setBreadcrumbs(breadcrumbItems);
    }
  }, []);

  return (
    <EnhancedDashboardContext.Provider
      value={{
        isSidebarOpen,
        isMobile,
        theme,
        activePath,
        breadcrumbs,
        toggleSidebar,
        toggleTheme,
        closeSidebar,
        updateActivePath,
      }}
    >
      {children}
    </EnhancedDashboardContext.Provider>
  );
};

export const useEnhancedDashboard = () => {
  const context = useContext(EnhancedDashboardContext);
  if (!context) {
    throw new Error('useEnhancedDashboard must be used within an EnhancedDashboardProvider');
  }
  return context;
};

export default EnhancedDashboardContext;
