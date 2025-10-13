import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { useAuth } from './AuthContext';

export const WidgetContext = createContext();

// Default widget configurations
const defaultWidgets = {
  quick_stats: {
    id: 'quick_stats',
    title: 'Quick Stats',
    enabled: true,
    position: 1,
    settings: {
      show_revenue: true,
      show_students: true,
      show_attendance: true,
    },
  },
  revenue_chart: {
    id: 'revenue_chart',
    title: 'Revenue Overview',
    enabled: true,
    position: 2,
    settings: {
      timeframe: 'monthly',
      show_average: true,
    },
  },
  recent_activity: {
    id: 'recent_activity',
    title: 'Recent Activity',
    enabled: true,
    position: 3,
    settings: {
      limit: 10,
      show_timestamps: true,
    },
  },
  upcoming_events: {
    id: 'upcoming_events',
    title: 'Upcoming Events',
    enabled: true,
    position: 4,
    settings: {
      limit: 5,
      show_location: true,
    },
  },
  class_distribution: {
    id: 'class_distribution',
    title: 'Class Distribution',
    enabled: true,
    position: 5,
    settings: {
      show_legend: true,
      max_items: 10,
    },
  },
  performance_metrics: {
    id: 'performance_metrics',
    title: 'Performance Metrics',
    enabled: true,
    position: 6,
    settings: {
      show_growth: true,
      compare_period: 'previous_period',
    },
  },
};

export const WidgetProvider = ({ children }) => {
  const { isAuthenticated, token } = useAuth();
  const [widgets, setWidgets] = useState({});
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // API client with auth headers
  const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': token ? `Bearer ${token}` : '',
    },
  });

  // Load widget configuration from API
  const loadWidgets = useCallback(async () => {
    if (!isAuthenticated) return;
    
    setIsLoading(true);
    setError(null);
    
    try {
      const response = await api.get('/admin/widgets');
      const { widgets: savedWidgets, defaults } = response.data.data;
      
      // Merge with defaults to ensure all widgets are present
      const mergedWidgets = { ...defaults };
      
      savedWidgets.forEach(widget => {
        if (mergedWidgets[widget.id]) {
          mergedWidgets[widget.id] = {
            ...mergedWidgets[widget.id],
            ...widget,
            settings: {
              ...mergedWidgets[widget.id].settings,
              ...(widget.settings || {})
            }
          };
        }
      });
      
      setWidgets(mergedWidgets);
    } catch (err) {
      console.error('Error loading widgets:', err);
      setError('Failed to load widget configuration');
      // Fallback to default widgets if API fails
      setWidgets(defaultWidgets);
    } finally {
      setIsLoading(false);
    }
  }, [isAuthenticated, token]);

  // Save widget configuration to API
  const saveWidgets = async (updatedWidgets) => {
    if (!isAuthenticated) return false;
    
    setIsLoading(true);
    setError(null);
    
    try {
      const widgetsArray = Object.values(updatedWidgets).map(widget => ({
        id: widget.id,
        enabled: widget.enabled,
        position: widget.position,
        settings: widget.settings || {},
      }));
      
      await api.post('/admin/widgets', { widgets: widgetsArray });
      setWidgets(updatedWidgets);
      return true;
    } catch (err) {
      console.error('Error saving widgets:', err);
      setError('Failed to save widget configuration');
      return false;
    } finally {
      setIsLoading(false);
    }
  };

  // Reset widgets to default configuration
  const resetWidgets = async () => {
    if (!isAuthenticated) return false;
    
    setIsLoading(true);
    setError(null);
    
    try {
      await api.post('/admin/widgets/reset');
      await loadWidgets(); // Reload to get the default configuration
      return true;
    } catch (err) {
      console.error('Error resetting widgets:', err);
      setError('Failed to reset widget configuration');
      return false;
    } finally {
      setIsLoading(false);
    }
  };

  // Update a single widget's settings
  const updateWidget = async (widgetId, updates) => {
    const updatedWidgets = {
      ...widgets,
      [widgetId]: {
        ...widgets[widgetId],
        ...updates,
        settings: {
          ...widgets[widgetId]?.settings,
          ...(updates.settings || {})
        }
      }
    };
    
    return await saveWidgets(updatedWidgets);
  };

  // Toggle widget visibility
  const toggleWidget = async (widgetId) => {
    if (!widgets[widgetId]) return false;
    
    const updatedWidgets = {
      ...widgets,
      [widgetId]: {
        ...widgets[widgetId],
        enabled: !widgets[widgetId].enabled
      }
    };
    
    return await saveWidgets(updatedWidgets);
  };

  // Reorder widgets
  const reorderWidgets = async (newOrder) => {
    const updatedWidgets = { ...widgets };
    
    newOrder.forEach((widgetId, index) => {
      if (updatedWidgets[widgetId]) {
        updatedWidgets[widgetId] = {
          ...updatedWidgets[widgetId],
          position: index + 1
        };
      }
    });
    
    return await saveWidgets(updatedWidgets);
  };

  // Load widgets when authenticated
  useEffect(() => {
    if (isAuthenticated) {
      loadWidgets();
    }
  }, [isAuthenticated, loadWidgets]);

  // Get enabled widgets in the correct order
  const enabledWidgets = Object.values(widgets)
    .filter(widget => widget.enabled)
    .sort((a, b) => a.position - b.position);

  // Get all widgets in the correct order
  const allWidgets = Object.values(widgets)
    .sort((a, b) => a.position - b.position);

  return (
    <WidgetContext.Provider
      value={{
        widgets: widgets,
        enabledWidgets,
        allWidgets,
        isLoading,
        error,
        loadWidgets,
        saveWidgets,
        resetWidgets,
        updateWidget,
        toggleWidget,
        reorderWidgets,
      }}
    >
      {children}
    </WidgetContext.Provider>
  );
};

export const useWidgets = () => {
  const context = useContext(WidgetContext);
  if (!context) {
    throw new Error('useWidgets must be used within a WidgetProvider');
  }
  return context;
};

export default WidgetContext;
