import { toast } from 'react-toastify';
import { EventEmitter } from 'events';
import api from '../api';

class NotificationService extends EventEmitter {
  constructor() {
    super();
    this.notifications = [];
    this.unreadCount = 0;
    this.initialized = false;
    this.socket = null;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;
    this.reconnectDelay = 3000; // 3 seconds
    this.eventSource = null;
  }

  /**
   * Initialize the notification service
   * @param {string} userId - Current user ID
   */
  initialize(userId) {
    if (this.initialized) return;
    
    this.userId = userId;
    this.setupEventSource();
    this.loadNotifications();
    this.initialized = true;
    
    // Set up periodic sync in case of connection issues
    this.syncInterval = setInterval(() => {
      if (!this.isConnected()) {
        this.setupEventSource();
      }
      this.syncNotifications();
    }, 300000); // Sync every 5 minutes
  }

  /**
   * Set up Server-Sent Events connection
   */
  setupEventSource() {
    if (this.eventSource) {
      this.eventSource.close();
    }

    try {
      const eventSourceUrl = `${process.env.REACT_APP_API_URL}/api/notifications/stream`;
      this.eventSource = new EventSource(eventSourceUrl, {
        withCredentials: true
      });

      this.eventSource.onopen = () => {
        this.reconnectAttempts = 0;
        console.log('Notification stream connected');
      };

      this.eventSource.onmessage = (event) => {
        const notification = JSON.parse(event.data);
        this.handleNewNotification(notification);
      };

      this.eventSource.onerror = (error) => {
        console.error('Notification stream error:', error);
        this.handleConnectionError();
      };
    } catch (error) {
      console.error('Failed to create event source:', error);
      this.handleConnectionError();
    }
  }

  /**
   * Handle connection errors and attempt reconnection
   */
  handleConnectionError() {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++;
      console.log(`Attempting to reconnect (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
      
      setTimeout(() => {
        this.setupEventSource();
      }, this.reconnectDelay * this.reconnectAttempts);
    } else {
      console.error('Max reconnection attempts reached. Falling back to polling.');
      this.startPolling();
    }
  }

  /**
   * Fallback to polling if SSE fails
   */
  startPolling() {
    if (this.pollingInterval) clearInterval(this.pollingInterval);
    
    this.pollingInterval = setInterval(() => {
      this.syncNotifications();
    }, 30000); // Poll every 30 seconds
  }

  /**
   * Check if the connection is active
   */
  isConnected() {
    return this.eventSource && this.eventSource.readyState === EventSource.OPEN;
  }

  /**
   * Load initial notifications
   */
  async loadNotifications() {
    try {
      const response = await api.get('/api/notifications', {
        params: { limit: 50, unread: true }
      });
      
      this.notifications = response.data.data;
      this.updateUnreadCount();
      this.emit('notifications:updated', this.notifications);
    } catch (error) {
      console.error('Failed to load notifications:', error);
    }
  }

  /**
   * Sync notifications with the server
   */
  async syncNotifications() {
    try {
      const lastSync = this.notifications[0]?.created_at;
      const response = await api.get('/api/notifications/sync', {
        params: { since: lastSync }
      });
      
      if (response.data.data && response.data.data.length > 0) {
        response.data.data.forEach(notification => {
          this.handleNewNotification(notification);
        });
      }
    } catch (error) {
      console.error('Failed to sync notifications:', error);
    }
  }

  /**
   * Handle a new notification
   * @param {Object} notification - The new notification
   */
  handleNewNotification(notification) {
    // Check if notification already exists
    const exists = this.notifications.some(n => n.id === notification.id);
    if (exists) return;

    // Add to the beginning of the array
    this.notifications.unshift(notification);
    
    // Update unread count
    if (!notification.read_at) {
      this.unreadCount++;
    }

    // Emit events
    this.emit('notification:new', notification);
    this.emit('notifications:updated', this.notifications);
    
    // Show toast for important notifications
    if (notification.type === 'refund_status_changed' || notification.important) {
      this.showToast(notification);
    }
  }

  /**
   * Show a notification toast
   * @param {Object} notification - The notification to display
   */
  showToast(notification) {
    const { title, message, type = 'info', action } = notification;
    
    const toastOptions = {
      position: 'top-right',
      autoClose: 5000,
      hideProgressBar: false,
      closeOnClick: true,
      pauseOnHover: true,
      draggable: true,
      progress: undefined,
      type: type === 'error' ? 'error' : 
            type === 'success' ? 'success' : 
            type === 'warning' ? 'warning' : 'info',
      onClick: action ? () => {
        if (action.url) {
          window.location.href = action.url;
        } else if (action.callback) {
          action.callback();
        }
      } : undefined
    };

    toast(message || title, {
      ...toastOptions,
      toastId: notification.id // Prevent duplicate toasts
    });
  }

  /**
   * Mark a notification as read
   * @param {string} notificationId - The ID of the notification to mark as read
   */
  async markAsRead(notificationId) {
    try {
      await api.patch(`/api/notifications/${notificationId}/read`);
      
      const notification = this.notifications.find(n => n.id === notificationId);
      if (notification && !notification.read_at) {
        notification.read_at = new Date().toISOString();
        this.unreadCount = Math.max(0, this.unreadCount - 1);
        this.emit('notifications:updated', this.notifications);
      }
    } catch (error) {
      console.error('Failed to mark notification as read:', error);
      throw error;
    }
  }

  /**
   * Mark all notifications as read
   */
  async markAllAsRead() {
    try {
      await api.patch('/api/notifications/mark-all-read');
      
      const now = new Date().toISOString();
      this.notifications = this.notifications.map(notification => ({
        ...notification,
        read_at: notification.read_at || now
      }));
      
      this.unreadCount = 0;
      this.emit('notifications:updated', this.notifications);
    } catch (error) {
      console.error('Failed to mark all notifications as read:', error);
      throw error;
    }
  }

  /**
   * Get all notifications
   * @returns {Array} - Array of notifications
   */
  getNotifications() {
    return this.notifications;
  }

  /**
   * Get unread notifications
   * @returns {Array} - Array of unread notifications
   */
  getUnreadNotifications() {
    return this.notifications.filter(n => !n.read_at);
  }

  /**
   * Get the number of unread notifications
   * @returns {number} - Number of unread notifications
   */
  getUnreadCount() {
    return this.unreadCount;
  }

  /**
   * Update the unread count
   */
  updateUnreadCount() {
    this.unreadCount = this.notifications.filter(n => !n.read_at).length;
  }

  /**
   * Clear all notifications
   */
  clearNotifications() {
    this.notifications = [];
    this.unreadCount = 0;
    this.emit('notifications:updated', this.notifications);
  }

  /**
   * Clean up resources
   */
  cleanup() {
    if (this.eventSource) {
      this.eventSource.close();
      this.eventSource = null;
    }
    
    if (this.pollingInterval) {
      clearInterval(this.pollingInterval);
      this.pollingInterval = null;
    }
    
    if (this.syncInterval) {
      clearInterval(this.syncInterval);
      this.syncInterval = null;
    }
    
    this.initialized = false;
    this.removeAllListeners();
  }
}

// Export a singleton instance
export default new NotificationService();
