import { toast } from 'react-toastify';
import { getAuthToken } from '../utils/auth';

class WebSocketService {
  #isConnected = false;
  
  constructor() {
    this.socket = null;
    this.subscribers = new Map();
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 5;
    this.reconnectDelay = 1000; // Start with 1 second delay
  }
  
  get isConnected() {
    return this.#isConnected;
  }
  
  set isConnected(value) {
    this.#isConnected = value;
  }

  /**
   * Initialize WebSocket connection
   * @param {string} url - WebSocket server URL
   */
  connect(url) {
    if (this.socket && (this.socket.readyState === WebSocket.OPEN || this.socket.readyState === WebSocket.CONNECTING)) {
      console.log('WebSocket already connected or connecting');
      return;
    }

    try {
      // Add authentication token to the WebSocket URL
      const token = getAuthToken();
      const wsUrl = new URL(url);
      wsUrl.searchParams.append('token', token);
      
      this.socket = new WebSocket(wsUrl.toString());
      this.setupEventHandlers();
    } catch (error) {
      console.error('Error initializing WebSocket:', error);
      this.attemptReconnect(url);
    }
  }

  /**
   * Set up WebSocket event handlers
   */
  setupEventHandlers() {
    if (!this.socket) return;

    this.socket.onopen = () => {
      console.log('WebSocket connected');
      this.isConnected = true;
      this.reconnectAttempts = 0; // Reset reconnect attempts on successful connection
      this.notifySubscribers('connection', { connected: true });
    };

    this.socket.onmessage = (event) => {
      try {
        const message = JSON.parse(event.data);
        this.handleMessage(message);
      } catch (error) {
        console.error('Error parsing WebSocket message:', error);
      }
    };

    this.socket.onclose = (event) => {
      console.log('WebSocket disconnected:', event.code, event.reason);
      this.isConnected = false;
      this.notifySubscribers('connection', { connected: false });
      
      // Attempt to reconnect if the connection was closed unexpectedly
      if (event.code !== 1000) { // Don't reconnect if closed normally
        this.attemptReconnect();
      }
    };

    this.socket.onerror = (error) => {
      console.error('WebSocket error:', error);
      this.notifySubscribers('error', { error });
    };
  }

  /**
   * Attempt to reconnect to the WebSocket server
   * @param {string} [url] - WebSocket server URL (optional, uses previous URL if not provided)
   */
  attemptReconnect(url) {
    if (this.reconnectAttempts >= this.maxReconnectAttempts) {
      console.error('Max reconnection attempts reached');
      this.notifySubscribers('reconnect_failed', { 
        message: 'Failed to reconnect to server. Please refresh the page.' 
      });
      return;
    }

    this.reconnectAttempts++;
    const delay = Math.min(this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1), 30000); // Exponential backoff with max 30s
    
    console.log(`Attempting to reconnect in ${delay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
    
    setTimeout(() => {
      if (!this.isConnected) {
        this.connect(url || this.socket?.url);
      }
    }, delay);
  }

  /**
   * Handle incoming WebSocket messages
   * @param {Object} message - The parsed WebSocket message
   */
  handleMessage(message) {
    if (!message || !message.type) {
      console.warn('Received invalid WebSocket message:', message);
      return;
    }

    // Notify all subscribers for this message type
    this.notifySubscribers(message.type, message.data);
    
    // Also notify wildcard subscribers
    this.notifySubscribers('*', { type: message.type, data: message.data });
  }

  /**
   * Subscribe to WebSocket events
   * @param {string} eventType - The event type to subscribe to (or '*' for all events)
   * @param {Function} callback - The callback function to call when the event is received
   * @returns {Function} Unsubscribe function
   */
  subscribe(eventType, callback) {
    if (typeof callback !== 'function') {
      throw new Error('Callback must be a function');
    }

    if (!this.subscribers.has(eventType)) {
      this.subscribers.set(eventType, new Set());
    }

    const subscribers = this.subscribers.get(eventType);
    subscribers.add(callback);

    // Return unsubscribe function
    return () => {
      if (this.subscribers.has(eventType)) {
        const subscribers = this.subscribers.get(eventType);
        subscribers.delete(callback);
        
        if (subscribers.size === 0) {
          this.subscribers.delete(eventType);
        }
      }
    };
  }

  /**
   * Notify all subscribers of an event
   * @param {string} eventType - The event type
   * @param {Object} data - The event data
   */
  notifySubscribers(eventType, data) {
    // Notify specific subscribers
    if (this.subscribers.has(eventType)) {
      const subscribers = this.subscribers.get(eventType);
      subscribers.forEach(callback => {
        try {
          callback(data);
        } catch (error) {
          console.error(`Error in ${eventType} subscriber:`, error);
        }
      });
    }
  }

  /**
   * Send a message through the WebSocket
   * @param {string} type - The message type
   * @param {Object} data - The message data
   */
  sendMessage(type, data = {}) {
    if (!this.isConnected || !this.socket || this.socket.readyState !== WebSocket.OPEN) {
      console.warn('WebSocket is not connected. Message not sent:', { type, data });
      return false;
    }

    try {
      const message = JSON.stringify({ type, data });
      this.socket.send(message);
      return true;
    } catch (error) {
      console.error('Error sending WebSocket message:', error);
      return false;
    }
  }

  /**
   * Close the WebSocket connection
   * @param {number} [code] - Close code
   * @param {string} [reason] - Close reason
   */
  disconnect(code = 1000, reason = 'User disconnected') {
    if (this.socket) {
      this.socket.close(code, reason);
      this.socket = null;
      this.isConnected = false;
    }
  }

  /**
   * Get the current connection status
   * @returns {boolean} True if connected, false otherwise
   */
  get isConnected() {
    return this.socket && this.socket.readyState === WebSocket.OPEN;
  }
}

// Create a singleton instance
export const webSocketService = new WebSocketService();

/**
 * Initialize WebSocket connection with default settings
 * @param {string} [url] - WebSocket server URL (defaults to VITE_WS_URL from environment)
 */
export const initWebSocket = (url) => {
  const wsUrl = url || import.meta.env.VITE_WS_URL || `ws://${window.location.host}/ws`;
  webSocketService.connect(wsUrl);
  return webSocketService;
};

/**
 * Hook for using WebSocket in React components
 * @param {string} eventType - The event type to subscribe to
 * @param {Function} callback - The callback function to call when the event is received
 * @param {Array} deps - Dependencies array for the callback (similar to useEffect)
 * @returns {Object} WebSocket service instance and connection status
 */
export const useWebSocket = (eventType, callback, deps = []) => {
  const [isConnected, setIsConnected] = useState(webSocketService.isConnected);

  useEffect(() => {
    // Subscribe to the specified event
    const unsubscribe = webSocketService.subscribe(eventType, (data) => {
      if (typeof callback === 'function') {
        callback(data);
      }
    });

    // Subscribe to connection status changes
    const unsubscribeConnection = webSocketService.subscribe('connection', ({ connected }) => {
      setIsConnected(connected);
    });

    // Clean up subscriptions on unmount
    return () => {
      unsubscribe();
      unsubscribeConnection();
    };
  }, [eventType, ...deps]);

  return {
    isConnected,
    sendMessage: webSocketService.sendMessage.bind(webSocketService),
    disconnect: webSocketService.disconnect.bind(webSocketService),
    connect: webSocketService.connect.bind(webSocketService)
  };
};

export default webSocketService;
