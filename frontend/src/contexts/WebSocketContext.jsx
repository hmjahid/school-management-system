import React, { createContext, useContext, useEffect, useRef, useState } from 'react';
import PropTypes from 'prop-types';
import { webSocketService, initWebSocket } from '../services/websocketService';
import { toast } from 'react-toastify';
import { useAuth } from './AuthContext';

// Create WebSocket context
const WebSocketContext = createContext({
  isConnected: false,
  sendMessage: () => {},
  subscribe: () => () => {},
  unsubscribe: () => {},
  reconnect: () => {},
  connectionStatus: 'disconnected', // 'disconnected', 'connecting', 'connected', 'error'
  lastMessage: null,
  lastEvent: null,
});

// WebSocket provider component
export const WebSocketProvider = ({ children, autoConnect = true }) => {
  const { isAuthenticated } = useAuth();
  const [connectionStatus, setConnectionStatus] = useState('disconnected');
  const [lastMessage, setLastMessage] = useState(null);
  const [lastEvent, setLastEvent] = useState(null);
  const eventHandlers = useRef(new Map());
  const reconnectAttempts = useRef(0);
  const maxReconnectAttempts = 5;
  const reconnectTimeout = useRef(null);

  // Initialize WebSocket connection
  useEffect(() => {
    if (autoConnect && isAuthenticated) {
      connect();
    }

    return () => {
      if (reconnectTimeout.current) {
        clearTimeout(reconnectTimeout.current);
        reconnectTimeout.current = null;
      }
      disconnect();
    };
  }, [autoConnect, isAuthenticated]);

  // Handle connection status changes
  const handleConnectionChange = (status) => {
    setConnectionStatus(status);
    
    if (status === 'connected') {
      reconnectAttempts.current = 0; // Reset reconnect attempts on successful connection
      toast.dismiss('ws-connection-error'); // Clear any existing error toasts
    } else if (status === 'error') {
      // Show error toast if not already showing
      toast.error('Connection to server lost. Attempting to reconnect...', {
        toastId: 'ws-connection-error',
        autoClose: false,
        closeOnClick: false,
        closeButton: false,
        draggable: false,
      });
    }
  };

  // Connect to WebSocket
  const connect = () => {
    if (webSocketService.isConnected || connectionStatus === 'connecting') {
      return;
    }

    setConnectionStatus('connecting');
    
    try {
      // Initialize WebSocket connection
      initWebSocket();
      
      // Set up event listeners
      webSocketService.subscribe('connection', ({ connected }) => {
        if (connected) {
          handleConnectionChange('connected');
        } else {
          handleConnectionChange('disconnected');
        }
      });

      webSocketService.subscribe('error', (error) => {
        console.error('WebSocket error:', error);
        handleConnectionChange('error');
        attemptReconnect();
      });

      // Forward all messages to the appropriate handlers
      webSocketService.subscribe('*', (message) => {
        if (message && message.type) {
          setLastMessage(message);
          setLastEvent(message.type);
          
          // Call any registered event handlers
          if (eventHandlers.current.has(message.type)) {
            const handlers = eventHandlers.current.get(message.type);
            handlers.forEach(handler => {
              try {
                handler(message.data);
              } catch (error) {
                console.error(`Error in WebSocket handler for ${message.type}:`, error);
              }
            });
          }
        }
      });
      
    } catch (error) {
      console.error('Failed to initialize WebSocket:', error);
      handleConnectionChange('error');
      attemptReconnect();
    }
  };

  // Disconnect from WebSocket
  const disconnect = () => {
    if (reconnectTimeout.current) {
      clearTimeout(reconnectTimeout.current);
      reconnectTimeout.current = null;
    }
    
    if (webSocketService) {
      webSocketService.disconnect();
    }
    
    setConnectionStatus('disconnected');
  };

  // Attempt to reconnect with exponential backoff
  const attemptReconnect = () => {
    if (reconnectAttempts.current >= maxReconnectAttempts) {
      console.error('Max reconnection attempts reached');
      toast.error('Failed to connect to server. Please refresh the page.', {
        toastId: 'ws-connection-failed',
        autoClose: 5000,
      });
      return;
    }

    reconnectAttempts.current++;
    const delay = Math.min(1000 * Math.pow(2, reconnectAttempts.current - 1), 30000); // Exponential backoff with max 30s
    
    console.log(`Attempting to reconnect in ${delay}ms (attempt ${reconnectAttempts.current}/${maxReconnectAttempts})`);
    
    reconnectTimeout.current = setTimeout(() => {
      if (!webSocketService.isConnected && connectionStatus !== 'connected') {
        connect();
      }
    }, delay);
  };

  // Manually reconnect
  const reconnect = () => {
    reconnectAttempts.current = 0;
    if (reconnectTimeout.current) {
      clearTimeout(reconnectTimeout.current);
      reconnectTimeout.current = null;
    }
    connect();
  };

  // Subscribe to a specific event
  const subscribe = (eventType, handler) => {
    if (typeof handler !== 'function') {
      throw new Error('Handler must be a function');
    }

    if (!eventHandlers.current.has(eventType)) {
      eventHandlers.current.set(eventType, new Set());
    }

    const handlers = eventHandlers.current.get(eventType);
    handlers.add(handler);

    // Return unsubscribe function
    return () => {
      if (eventHandlers.current.has(eventType)) {
        const handlers = eventHandlers.current.get(eventType);
        handlers.delete(handler);
        
        if (handlers.size === 0) {
          eventHandlers.current.delete(eventType);
        }
      }
    };
  };

  // Unsubscribe from a specific event
  const unsubscribe = (eventType, handler) => {
    if (eventHandlers.current.has(eventType)) {
      const handlers = eventHandlers.current.get(eventType);
      handlers.delete(handler);
      
      if (handlers.size === 0) {
        eventHandlers.current.delete(eventType);
      }
    }
  };

  // Send a message through the WebSocket
  const sendMessage = (type, data = {}) => {
    if (!webSocketService.isConnected) {
      console.warn('WebSocket is not connected. Message not sent:', { type, data });
      return false;
    }

    return webSocketService.sendMessage(type, data);
  };

  // Context value
  const value = {
    isConnected: connectionStatus === 'connected',
    connectionStatus,
    lastMessage,
    lastEvent,
    sendMessage,
    subscribe,
    unsubscribe,
    reconnect,
    connect,
    disconnect,
  };

  return (
    <WebSocketContext.Provider value={value}>
      {children}
    </WebSocketContext.Provider>
  );
};

WebSocketProvider.propTypes = {
  children: PropTypes.node.isRequired,
  autoConnect: PropTypes.bool,
};

// Custom hook to use the WebSocket context
export const useWebSocket = () => {
  const context = useContext(WebSocketContext);
  if (context === undefined) {
    throw new Error('useWebSocket must be used within a WebSocketProvider');
  }
  return context;
};

export default WebSocketContext;
