import React, { useState, useEffect } from 'react';
import { 
  Box, 
  Typography, 
  Paper, 
  FormGroup, 
  FormControlLabel, 
  Checkbox, 
  Button, 
  Divider, 
  Chip,
  Grid,
  CircularProgress,
  Alert,
  Snackbar,
  Tab,
  Tabs
} from '@mui/material';
import { useApi } from '../../hooks/useApi';

const NotificationPreferences = () => {
  const [preferences, setPreferences] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState('');
  const [activeTab, setActiveTab] = useState(0);
  const api = useApi();

  // Group preferences by category
  const categories = {
    account: preferences.filter(p => p.type.startsWith('user.')),
    courses: preferences.filter(p => p.type.startsWith('course.')),
    assignments: preferences.filter(p => p.type.startsWith('assignment.')),
    exams: preferences.filter(p => p.type.startsWith('exam.')),
    payments: preferences.filter(p => p.type.startsWith('payment.') || p.type.startsWith('refund.')),
    system: preferences.filter(p => p.type.startsWith('system.')),
  };

  const activeCategory = Object.values(categories)[activeTab] || [];
  const categoryNames = Object.keys(categories);

  useEffect(() => {
    const fetchPreferences = async () => {
      try {
        setLoading(true);
        const response = await api.get('/api/notification-preferences');
        setPreferences(response.data.data || []);
      } catch (err) {
        setError('Failed to load notification preferences. Please try again.');
        console.error('Error fetching preferences:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchPreferences();
  }, []);

  const handleChannelToggle = async (type, channel, checked) => {
    try {
      const updatedChannels = [...(preferences.find(p => p.type === type)?.channels || [])];
      
      if (checked) {
        updatedChannels.push(channel);
      } else {
        const index = updatedChannels.indexOf(channel);
        if (index > -1) {
          updatedChannels.splice(index, 1);
        }
      }

      await api.put(`/api/notification-preferences/${type}`, { channels: updatedChannels });
      
      setPreferences(prev => 
        prev.map(p => 
          p.type === type 
            ? { ...p, channels: updatedChannels, is_custom: true } 
            : p
        )
      );
      
      setSuccess('Notification preferences updated successfully!');
    } catch (err) {
      setError('Failed to update notification preferences. Please try again.');
      console.error('Error updating preference:', err);
    }
  };

  const handleResetToDefault = async (type) => {
    if (!window.confirm('Are you sure you want to reset these preferences to default?')) {
      return;
    }

    try {
      await api.delete(`/api/notification-preferences/${type}`);
      
      setPreferences(prev => 
        prev.filter(p => p.type !== type)
      );
      
      setSuccess('Notification preferences reset to default.');
    } catch (err) {
      setError('Failed to reset notification preferences. Please try again.');
      console.error('Error resetting preference:', err);
    }
  };

  const getChannelLabel = (channel) => {
    const labels = {
      database: 'In-App',
      mail: 'Email',
      sms: 'SMS',
      push: 'Push Notification'
    };
    return labels[channel] || channel;
  };

  const handleCloseSnackbar = () => {
    setError(null);
    setSuccess('');
  };

  if (loading && preferences.length === 0) {
    return (
      <Box display="flex" justifyContent="center" p={4}>
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box>
      <Typography variant="h5" gutterBottom>
        Notification Preferences
      </Typography>
      <Typography color="textSecondary" paragraph>
        Customize how and when you receive notifications
      </Typography>

      <Paper sx={{ mb: 3, p: 2 }}>
        <Tabs
          value={activeTab}
          onChange={(e, newValue) => setActiveTab(newValue)}
          variant="scrollable"
          scrollButtons="auto"
          sx={{ mb: 2 }}
        >
          {categoryNames.map((category, index) => (
            <Tab 
              key={category} 
              label={category.charAt(0).toUpperCase() + category.slice(1)} 
              id={`tab-${index}`}
              aria-controls={`tabpanel-${index}`}
            />
          ))}
        </Tabs>

        <Box sx={{ p: 2 }}>
          {activeCategory.length > 0 ? (
            <FormGroup>
              {activeCategory.map((pref) => (
                <Box key={pref.type} sx={{ mb: 3 }}>
                  <Box 
                    display="flex" 
                    justifyContent="space-between" 
                    alignItems="center"
                    mb={1}
                  >
                    <Typography variant="subtitle1">
                      {pref.type.split('.').pop().replace(/_/g, ' ')}
                      {pref.is_custom && (
                        <Chip 
                          label="Custom" 
                          size="small" 
                          color="primary" 
                          sx={{ ml: 1 }} 
                        />
                      )}
                    </Typography>
                    {pref.is_custom && (
                      <Button 
                        size="small" 
                        color="inherit"
                        onClick={() => handleResetToDefault(pref.type)}
                      >
                        Reset to Default
                      </Button>
                    )}
                  </Box>
                  
                  <Typography variant="body2" color="textSecondary" paragraph>
                    {pref.description || 'No description available'}
                  </Typography>
                  
                  <Grid container spacing={2}>
                    {pref.default_channels.map((channel) => (
                      <Grid item xs={12} sm={6} md={3} key={channel}>
                        <FormControlLabel
                          control={
                            <Checkbox
                              checked={pref.channels.includes(channel)}
                              onChange={(e) => 
                                handleChannelToggle(pref.type, channel, e.target.checked)
                              }
                              color="primary"
                            />
                          }
                          label={getChannelLabel(channel)}
                        />
                      </Grid>
                    ))}
                  </Grid>
                  
                  <Divider sx={{ mt: 2, mb: 3 }} />
                </Box>
              ))}
            </FormGroup>
          ) : (
            <Typography>No notification preferences found for this category.</Typography>
          )}
        </Box>
      </Paper>

      <Snackbar
        open={!!error || !!success}
        autoHideDuration={6000}
        onClose={handleCloseSnackbar}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
      >
        <Alert 
          onClose={handleCloseSnackbar} 
          severity={error ? 'error' : 'success'}
          sx={{ width: '100%' }}
        >
          {error || success}
        </Alert>
      </Snackbar>
    </Box>
  );
};

export default NotificationPreferences;
