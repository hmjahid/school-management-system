import React, { useState, useEffect } from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  FormGroup,
  FormControlLabel,
  Checkbox,
  Grid,
  Typography,
  Divider,
  Box,
  Chip,
  IconButton,
  FormHelperText,
  Stepper,
  Step,
  StepLabel,
  StepContent,
  Paper,
  useTheme,
  useMediaQuery,
  CircularProgress,
  Alert,
  Tabs,
  Tab,
  List,
  ListItem,
  ListItemText,
  ListItemSecondaryAction,
  Avatar,
  InputAdornment,
  Collapse,
  Fade
} from '@mui/material';
import {
  Close as CloseIcon,
  Check as CheckIcon,
  Add as AddIcon,
  Person as PersonIcon,
  Group as GroupIcon,
  Class as ClassIcon,
  Public as PublicIcon,
  Schedule as ScheduleIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  ExpandMore as ExpandMoreIcon,
  ExpandLess as ExpandLessIcon,
  Info as InfoIcon,
  Warning as WarningIcon
} from '@mui/icons-material';
import { DateTimePicker } from '@mui/x-date-pickers/DateTimePicker';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { AdapterDateFns } from '@mui/x-date-pickers/AdapterDateFns';
import { format, addDays, addWeeks, addMonths, isBefore, isAfter, isToday, isTomorrow } from 'date-fns';
import { useApi } from '../../hooks/useApi';

const RECIPIENT_TYPES = [
  { id: 'user', label: 'Specific Users', icon: <PersonIcon /> },
  { id: 'role', label: 'User Roles', icon: <GroupIcon /> },
  { id: 'class', label: 'Classes', icon: <ClassIcon /> },
  { id: 'all', label: 'All Users', icon: <PublicIcon /> },
];

const CHANNELS = [
  { id: 'mail', label: 'Email' },
  { id: 'sms', label: 'SMS' },
  { id: 'push', label: 'Push Notification' },
  { id: 'in_app', label: 'In-App' },
];

const SCHEDULE_TYPES = [
  { id: 'once', label: 'Send once' },
  { id: 'daily', label: 'Daily' },
  { id: 'weekly', label: 'Weekly' },
  { id: 'monthly', label: 'Monthly' },
  { id: 'custom', label: 'Custom' },
];

const CUSTOM_INTERVALS = [
  { value: 1, label: '1' },
  { value: 2, label: '2' },
  { value: 3, label: '3' },
  { value: 4, label: '4' },
  { value: 5, label: '5' },
  { value: 6, label: '6' },
  { value: 7, label: '7' },
  { value: 14, label: '14' },
  { value: 21, label: '21' },
  { value: 30, label: '30' },
];

const INTERVAL_UNITS = [
  { value: 'minutes', label: 'Minutes' },
  { value: 'hours', label: 'Hours' },
  { value: 'days', label: 'Days' },
  { value: 'weeks', label: 'Weeks' },
  { value: 'months', label: 'Months' },
];

// Mock data - in a real app, this would come from your API
const MOCK_USERS = [
  { id: 1, name: 'John Doe', email: 'john@example.com', avatar: 'JD' },
  { id: 2, name: 'Jane Smith', email: 'jane@example.com', avatar: 'JS' },
  { id: 3, name: 'Bob Johnson', email: 'bob@example.com', avatar: 'BJ' },
];

const MOCK_ROLES = [
  { id: 'admin', name: 'Administrator' },
  { id: 'teacher', name: 'Teacher' },
  { id: 'student', name: 'Student' },
  { id: 'parent', name: 'Parent' },
];

const MOCK_CLASSES = [
  { id: 1, name: 'Class A', code: 'MATH-101' },
  { id: 2, name: 'Class B', code: 'SCI-201' },
  { id: 3, name: 'Class C', code: 'ENG-301' },
];

const ScheduleNotificationDialog = ({ open, onClose, notification }) => {
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const api = useApi();
  
  // Form state
  const [activeStep, setActiveStep] = useState(0);
  const [formData, setFormData] = useState({
    name: '',
    type: 'announcement',
    message: '',
    channels: ['mail', 'in_app'],
    recipientType: 'all',
    selectedUsers: [],
    selectedRoles: [],
    selectedClasses: [],
    scheduleType: 'once',
    scheduledAt: new Date(),
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    customInterval: 1,
    customUnit: 'days',
    endDate: null,
    endAfterOccurrences: null,
    endCondition: 'never',
  });
  
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [preview, setPreview] = useState(null);
  const [expandedRecipientType, setExpandedRecipientType] = useState(null);
  
  // Initialize form with notification data if editing
  useEffect(() => {
    if (notification) {
      setFormData(prev => ({
        ...prev,
        name: notification.name || '',
        type: notification.type || 'announcement',
        message: notification.message || '',
        channels: notification.channels || ['mail', 'in_app'],
        scheduledAt: notification.scheduled_at ? new Date(notification.scheduled_at) : new Date(),
        // ... other fields from notification
      }));
      
      // Parse recipients
      if (notification.recipients && notification.recipients.length > 0) {
        const firstRecipient = notification.recipients[0];
        if (firstRecipient.type === 'all') {
          setFormData(prev => ({
            ...prev,
            recipientType: 'all',
            selectedUsers: [],
            selectedRoles: [],
            selectedClasses: [],
          }));
        } else if (firstRecipient.type === 'user') {
          setFormData(prev => ({
            ...prev,
            recipientType: 'user',
            selectedUsers: notification.recipients.map(r => r.id) || [],
            selectedRoles: [],
            selectedClasses: [],
          }));
        }
        // Add other recipient types as needed
      }
      
      // Parse schedule
      if (notification.schedule) {
        setFormData(prev => ({
          ...prev,
          scheduleType: notification.schedule.type || 'once',
          customInterval: notification.schedule.interval || 1,
          customUnit: notification.schedule.unit || 'days',
          endDate: notification.schedule.end_date ? new Date(notification.schedule.end_date) : null,
          endAfterOccurrences: notification.schedule.end_after_occurrences || null,
          endCondition: notification.schedule.end_condition || 'never',
        }));
      }
    } else {
      // Reset form for new notification
      setFormData({
        name: '',
        type: 'announcement',
        message: '',
        channels: ['mail', 'in_app'],
        recipientType: 'all',
        selectedUsers: [],
        selectedRoles: [],
        selectedClasses: [],
        scheduleType: 'once',
        scheduledAt: new Date(),
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        customInterval: 1,
        customUnit: 'days',
        endDate: null,
        endAfterOccurrences: null,
        endCondition: 'never',
      });
    }
    
    // Reset step when opening/closing dialog
    if (open) {
      setActiveStep(0);
      setError(null);
      setPreview(null);
    }
  }, [notification, open]);
  
  // Handle form input changes
  const handleChange = (field) => (event) => {
    setFormData({
      ...formData,
      [field]: event.target.value
    });
  };
  
  const handleCheckboxChange = (field) => (event) => {
    setFormData({
      ...formData,
      [field]: event.target.checked
    });
  };
  
  const handleChannelsChange = (event) => {
    const value = event.target.value;
    setFormData({
      ...formData,
      channels: typeof value === 'string' ? value.split(',') : value,
    });
  };
  
  const handleDateChange = (field) => (date) => {
    setFormData({
      ...formData,
      [field]: date
    });
  };
  
  const handleRecipientTypeChange = (type) => {
    setFormData({
      ...formData,
      recipientType: type,
      selectedUsers: [],
      selectedRoles: [],
      selectedClasses: [],
    });
    setExpandedRecipientType(type === expandedRecipientType ? null : type);
  };
  
  const toggleRecipient = (type, id) => {
    const field = `selected${type.charAt(0).toUpperCase() + type.slice(1)}s`;
    const currentSelection = formData[field] || [];
    
    setFormData({
      ...formData,
      [field]: currentSelection.includes(id)
        ? currentSelection.filter(item => item !== id)
        : [...currentSelection, id]
    });
  };
  
  const handleSelectAll = (type) => {
    const field = `selected${type.charAt(0).toUpperCase() + type.slice(1)}s`;
    const allIds = [];
    
    // Get all possible IDs for the type
    switch (type) {
      case 'user':
        allIds.push(...MOCK_USERS.map(user => user.id));
        break;
      case 'role':
        allIds.push(...MOCK_ROLES.map(role => role.id));
        break;
      case 'class':
        allIds.push(...MOCK_CLASSES.map(cls => cls.id));
        break;
    }
    
    setFormData({
      ...formData,
      [field]: formData[field].length === allIds.length ? [] : allIds
    });
  };
  
  // Navigation
  const handleNext = () => {
    if (activeStep === steps.length - 1) {
      handleSubmit();
    } else {
      setActiveStep(prevStep => prevStep + 1);
    }
  };
  
  const handleBack = () => {
    setActiveStep(prevStep => prevStep - 1);
  };
  
  // Form submission
  const handleSubmit = async () => {
    setLoading(true);
    setError(null);
    
    try {
      // Prepare recipients based on recipient type
      let recipients = [];
      
      switch (formData.recipientType) {
        case 'all':
          recipients = [{ type: 'all' }];
          break;
        case 'user':
          recipients = formData.selectedUsers.map(id => ({
            type: 'user',
            id: id,
            name: MOCK_USERS.find(u => u.id === id)?.name || ''
          }));
          break;
        case 'role':
          recipients = formData.selectedRoles.map(id => ({
            type: 'role',
            id: id,
            name: MOCK_ROLES.find(r => r.id === id)?.name || ''
          }));
          break;
        case 'class':
          recipients = formData.selectedClasses.map(id => ({
            type: 'class',
            id: id,
            name: MOCK_CLASSES.find(c => c.id === id)?.name || ''
          }));
          break;
      }
      
      // Prepare schedule
      const schedule = {
        type: formData.scheduleType,
      };
      
      if (formData.scheduleType === 'once') {
        schedule.datetime = formData.scheduledAt.toISOString();
        schedule.timezone = formData.timezone;
      } else if (formData.scheduleType === 'custom') {
        schedule.interval = formData.customInterval;
        schedule.unit = formData.customUnit;
      }
      
      if (formData.endCondition === 'date' && formData.endDate) {
        schedule.end_date = formData.endDate.toISOString();
      } else if (formData.endCondition === 'occurrences' && formData.endAfterOccurrences) {
        schedule.end_after_occurrences = formData.endAfterOccurrences;
      }
      
      // Prepare notification data
      const notificationData = {
        name: formData.name,
        type: formData.type,
        message: formData.message,
        channels: formData.channels,
        recipients: recipients,
        schedule: schedule,
      };
      
      // In a real app, you would make an API call here
      console.log('Submitting notification:', notificationData);
      
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      // Close dialog and refresh notifications list
      onClose(true);
      
    } catch (err) {
      console.error('Error scheduling notification:', err);
      setError(err.message || 'Failed to schedule notification. Please try again.');
    } finally {
      setLoading(false);
    }
  };
  
  // Generate preview
  const generatePreview = () => {
    setPreview({
      name: formData.name || 'Notification Preview',
      type: formData.type || 'announcement',
      message: formData.message || 'This is a preview of your notification message.',
      scheduledAt: formData.scheduledAt,
      channels: formData.channels,
      recipients: getRecipientsCount(),
    });
  };
  
  // Get count of recipients for preview
  const getRecipientsCount = () => {
    switch (formData.recipientType) {
      case 'all':
        return 'All users';
      case 'user':
        return `${formData.selectedUsers.length} user${formData.selectedUsers.length !== 1 ? 's' : ''}`;
      case 'role':
        return `${formData.selectedRoles.length} role${formData.selectedRoles.length !== 1 ? 's' : ''}`;
      case 'class':
        return `${formData.selectedClasses.length} class${formData.selectedClasses.length !== 1 ? 'es' : ''}`;
      default:
        return 'No recipients selected';
    }
  };
  
  // Format date for display
  const formatDate = (date) => {
    if (!date) return 'Not set';
    
    if (isToday(date)) {
      return `Today at ${format(date, 'h:mm a')}`;
    } else if (isTomorrow(date)) {
      return `Tomorrow at ${format(date, 'h:mm a')}`;
    } else {
      return format(date, 'EEE, MMM d, yyyy h:mm a');
    }
  };
  
  // Steps for the stepper
  const steps = [
    { label: 'Notification Details', description: 'Enter the notification content and type' },
    { label: 'Recipients', description: 'Select who will receive this notification' },
    { label: 'Channels', description: 'Choose how to deliver the notification' },
    { label: 'Schedule', description: 'Set when to send the notification' },
    { label: 'Review', description: 'Review and confirm your notification' },
  ];
  
  // Get the next scheduled date based on the current settings
  const getNextScheduleDate = () => {
    const now = new Date();
    let nextDate = new Date(formData.scheduledAt);
    
    // If the scheduled time is in the past and it's a one-time notification, return null
    if (formData.scheduleType === 'once' && isBefore(nextDate, now)) {
      return null;
    }
    
    // For recurring notifications, find the next occurrence
    while (isBefore(nextDate, now)) {
      switch (formData.scheduleType) {
        case 'daily':
          nextDate = addDays(nextDate, 1);
          break;
        case 'weekly':
          nextDate = addWeeks(nextDate, 1);
          break;
        case 'monthly':
          nextDate = addMonths(nextDate, 1);
          break;
        case 'custom':
          const unit = formData.customUnit;
          const interval = formData.customInterval || 1;
          
          if (unit === 'days') {
            nextDate = addDays(nextDate, interval);
          } else if (unit === 'weeks') {
            nextDate = addWeeks(nextDate, interval);
          } else if (unit === 'months') {
            nextDate = addMonths(nextDate, interval);
          } else if (unit === 'hours') {
            nextDate.setHours(nextDate.getHours() + interval);
          } else if (unit === 'minutes') {
            nextDate.setMinutes(nextDate.getMinutes() + interval);
          }
          break;
        default:
          return nextDate;
      }
    }
    
    return nextDate;
  };
  
  // Check if the form is valid for the current step
  const isStepValid = (step) => {
    switch (step) {
      case 0: // Notification Details
        return formData.name.trim() !== '' && formData.message.trim() !== '';
      case 1: // Recipients
        if (formData.recipientType === 'user' && formData.selectedUsers.length === 0) return false;
        if (formData.recipientType === 'role' && formData.selectedRoles.length === 0) return false;
        if (formData.recipientType === 'class' && formData.selectedClasses.length === 0) return false;
        return true;
      case 2: // Channels
        return formData.channels.length > 0;
      case 3: // Schedule
        return formData.scheduledAt && isAfter(formData.scheduledAt, new Date());
      default:
        return true;
    }
  };
  
  // Render step content based on the current step
  const renderStepContent = (step) => {
    switch (step) {
      case 0: // Notification Details
        return (
          <Grid container spacing={3}>
            <Grid item xs={12}>
              <TextField
                fullWidth
                label="Notification Name"
                value={formData.name}
                onChange={handleChange('name')}
                required
                helperText="A name to identify this notification"
              />
            </Grid>
            <Grid item xs={12} md={6}>
              <FormControl fullWidth required>
                <InputLabel>Notification Type</InputLabel>
                <Select
                  value={formData.type}
                  onChange={handleChange('type')}
                  label="Notification Type"
                >
                  <MenuItem value="announcement">Announcement</MenuItem>
                  <MenuItem value="reminder">Reminder</MenuItem>
                  <MenuItem value="alert">Alert</MenuItem>
                  <MenuItem value="update">Update</MenuItem>
                  <MenuItem value="event">Event</MenuItem>
                </Select>
              </FormControl>
            </Grid>
            <Grid item xs={12}>
              <TextField
                fullWidth
                label="Message"
                value={formData.message}
                onChange={handleChange('message')}
                multiline
                rows={6}
                required
                helperText="Enter the notification message. You can use markdown for formatting."
              />
            </Grid>
            <Grid item xs={12}>
              <Box mt={2} p={2} bgcolor="action.hover" borderRadius={1}>
                <Typography variant="subtitle2" gutterBottom>
                  Preview
                </Typography>
                <Paper variant="outlined" sx={{ p: 2, bgcolor: 'background.paper' }}>
                  <Typography variant="h6" gutterBottom>
                    {formData.name || 'Notification Title'}
                  </Typography>
                  <Typography variant="body1" paragraph>
                    {formData.message || 'Your notification message will appear here.'}
                  </Typography>
                  <Typography variant="caption" color="text.secondary">
                    {formatDate(new Date())}
                  </Typography>
                </Paper>
              </Box>
            </Grid>
          </Grid>
        );
        
      case 1: // Recipients
        return (
          <Grid container spacing={3}>
            <Grid item xs={12}>
              <Typography variant="subtitle1" gutterBottom>
                Who should receive this notification?
              </Typography>
              <Grid container spacing={2} sx={{ mb: 3 }}>
                {RECIPIENT_TYPES.map((type) => (
                  <Grid item key={type.id} xs={12} sm={6} md={3}>
                    <Paper
                      elevation={formData.recipientType === type.id ? 3 : 1}
                      sx={{
                        p: 2,
                        cursor: 'pointer',
                        border: `1px solid ${
                          formData.recipientType === type.id 
                            ? theme.palette.primary.main 
                            : theme.palette.divider
                        }`,
                        '&:hover': {
                          borderColor: theme.palette.primary.main,
                        },
                      }}
                      onClick={() => handleRecipientTypeChange(type.id)}
                    >
                      <Box display="flex" alignItems="center">
                        <Box mr={1} color={formData.recipientType === type.id ? 'primary.main' : 'text.secondary'}>
                          {type.icon}
                        </Box>
                        <Typography variant="body2">{type.label}</Typography>
                        {formData.recipientType === type.id && (
                          <Box ml="auto" color="primary.main">
                            <CheckIcon fontSize="small" />
                          </Box>
                        )}
                      </Box>
                    </Paper>
                  </Grid>
                ))}
              </Grid>
              
              {/* Recipient Selection */}
              <Box mt={3}>
                {formData.recipientType === 'all' && (
                  <Alert severity="info">
                    This notification will be sent to all users in the system.
                  </Alert>
                )}
                
                {formData.recipientType === 'user' && (
                  <Box>
                    <Box display="flex" justifyContent="space-between" alignItems="center" mb={1}>
                      <Typography variant="subtitle2">Select Users</Typography>
                      <Button 
                        size="small" 
                        onClick={() => handleSelectAll('user')}
                        disabled={MOCK_USERS.length === 0}
                      >
                        {formData.selectedUsers.length === MOCK_USERS.length ? 'Deselect All' : 'Select All'}
                      </Button>
                    </Box>
                    <Paper variant="outlined" sx={{ maxHeight: 200, overflow: 'auto' }}>
                      <List dense>
                        {MOCK_USERS.map((user) => (
                          <ListItem 
                            key={user.id}
                            button
                            onClick={() => toggleRecipient('user', user.id)}
                            selected={formData.selectedUsers.includes(user.id)}
                          >
                            <Avatar sx={{ width: 32, height: 32, mr: 1, bgcolor: 'primary.main' }}>
                              {user.avatar}
                            </Avatar>
                            <ListItemText 
                              primary={user.name} 
                              secondary={user.email} 
                            />
                            {formData.selectedUsers.includes(user.id) && (
                              <CheckIcon color="primary" />
                            )}
                          </ListItem>
                        ))}
                        {MOCK_USERS.length === 0 && (
                          <ListItem>
                            <ListItemText 
                              primary="No users found" 
                              primaryTypographyProps={{ color: 'text.secondary', align: 'center' }}
                            />
                          </ListItem>
                        )}
                      </List>
                    </Paper>
                    <FormHelperText>
                      {formData.selectedUsers.length} user{formData.selectedUsers.length !== 1 ? 's' : ''} selected
                    </FormHelperText>
                  </Box>
                )}
                
                {formData.recipientType === 'role' && (
                  <Box>
                    <Box display="flex" justifyContent="space-between" alignItems="center" mb={1}>
                      <Typography variant="subtitle2">Select Roles</Typography>
                      <Button 
                        size="small" 
                        onClick={() => handleSelectAll('role')}
                        disabled={MOCK_ROLES.length === 0}
                      >
                        {formData.selectedRoles.length === MOCK_ROLES.length ? 'Deselect All' : 'Select All'}
                      </Button>
                    </Box>
                    <Grid container spacing={1}>
                      {MOCK_ROLES.map((role) => (
                        <Grid item xs={12} sm={6} key={role.id}>
                          <Paper
                            variant="outlined"
                            sx={{
                              p: 1.5,
                              cursor: 'pointer',
                              borderColor: formData.selectedRoles.includes(role.id) 
                                ? 'primary.main' 
                                : 'divider',
                              bgcolor: formData.selectedRoles.includes(role.id) 
                                ? 'rgba(25, 118, 210, 0.08)' 
                                : 'background.paper',
                              '&:hover': {
                                borderColor: 'primary.main',
                              },
                            }}
                            onClick={() => toggleRecipient('role', role.id)}
                          >
                            <Box display="flex" alignItems="center">
                              <Checkbox
                                checked={formData.selectedRoles.includes(role.id)}
                                color="primary"
                                size="small"
                                sx={{ p: 0.5, mr: 1 }}
                              />
                              <Typography variant="body2">{role.name}</Typography>
                            </Box>
                          </Paper>
                        </Grid>
                      ))}
                      {MOCK_ROLES.length === 0 && (
                        <Grid item xs={12}>
                          <Typography color="text.secondary" align="center">
                            No roles found
                          </Typography>
                        </Grid>
                      )}
                    </Grid>
                  </Box>
                )}
                
                {formData.recipientType === 'class' && (
                  <Box>
                    <Box display="flex" justifyContent="space-between" alignItems="center" mb={1}>
                      <Typography variant="subtitle2">Select Classes</Typography>
                      <Button 
                        size="small" 
                        onClick={() => handleSelectAll('class')}
                        disabled={MOCK_CLASSES.length === 0}
                      >
                        {formData.selectedClasses.length === MOCK_CLASSES.length ? 'Deselect All' : 'Select All'}
                      </Button>
                    </Box>
                    <Grid container spacing={1}>
                      {MOCK_CLASSES.map((cls) => (
                        <Grid item xs={12} sm={6} md={4} key={cls.id}>
                          <Paper
                            variant="outlined"
                            sx={{
                              p: 1.5,
                              cursor: 'pointer',
                              borderColor: formData.selectedClasses.includes(cls.id) 
                                ? 'primary.main' 
                                : 'divider',
                              bgcolor: formData.selectedClasses.includes(cls.id) 
                                ? 'rgba(25, 118, 210, 0.08)' 
                                : 'background.paper',
                              '&:hover': {
                                borderColor: 'primary.main',
                              },
                            }}
                            onClick={() => toggleRecipient('class', cls.id)}
                          >
                            <Box display="flex" alignItems="center">
                              <Checkbox
                                checked={formData.selectedClasses.includes(cls.id)}
                                color="primary"
                                size="small"
                                sx={{ p: 0.5, mr: 1 }}
                              />
                              <Box>
                                <Typography variant="body2">{cls.name}</Typography>
                                <Typography variant="caption" color="text.secondary">
                                  {cls.code}
                                </Typography>
                              </Box>
                            </Box>
                          </Paper>
                        </Grid>
                      ))}
                      {MOCK_CLASSES.length === 0 && (
                        <Grid item xs={12}>
                          <Typography color="text.secondary" align="center">
                            No classes found
                          </Typography>
                        </Grid>
                      )}
                    </Grid>
                  </Box>
                )}
              </Box>
            </Grid>
          </Grid>
        );
        
      case 2: // Channels
        return (
          <Grid container spacing={3}>
            <Grid item xs={12}>
              <Typography variant="subtitle1" gutterBottom>
                How should we deliver this notification?
              </Typography>
              <Typography variant="body2" color="text.secondary" paragraph>
                Select one or more delivery channels. The notification will be sent through all selected channels.
              </Typography>
              
              <FormControl component="fieldset" fullWidth>
                <FormGroup row>
                  {CHANNELS.map((channel) => (
                    <Grid item xs={12} sm={6} md={3} key={channel.id}>
                      <Paper
                        variant="outlined"
                        sx={{
                          p: 2,
                          mb: 2,
                          cursor: 'pointer',
                          borderColor: formData.channels.includes(channel.id)
                            ? 'primary.main'
                            : 'divider',
                          bgcolor: formData.channels.includes(channel.id)
                            ? 'rgba(25, 118, 210, 0.08)'
                            : 'background.paper',
                          '&:hover': {
                            borderColor: 'primary.main',
                          },
                        }}
                        onClick={() => {
                          const newChannels = formData.channels.includes(channel.id)
                            ? formData.channels.filter(c => c !== channel.id)
                            : [...formData.channels, channel.id];
                          setFormData({ ...formData, channels: newChannels });
                        }}
                      >
                        <FormControlLabel
                          control={
                            <Checkbox
                              checked={formData.channels.includes(channel.id)}
                              onChange={() => {}}
                              color="primary"
                            />
                          }
                          label={
                            <Box ml={1}>
                              <Typography variant="subtitle2">{channel.label}</Typography>
                              <Typography variant="caption" color="text.secondary">
                                {channel.id === 'mail' && 'Send as email'}
                                {channel.id === 'sms' && 'Send as text message'}
                                {channel.id === 'push' && 'Send as mobile push notification'}
                                {channel.id === 'in_app' && 'Show in the notification center'}
                              </Typography>
                            </Box>
                          }
                          sx={{ m: 0, width: '100%' }}
                        />
                      </Paper>
                    </Grid>
                  ))}
                </FormGroup>
              </FormControl>
              
              {formData.channels.length === 0 && (
                <Alert severity="warning" sx={{ mt: 2 }}>
                  Please select at least one delivery channel.
                </Alert>
              )}
              
              <Box mt={3}>
                <Typography variant="subtitle2" gutterBottom>
                  Notification Preview
                </Typography>
                <Paper variant="outlined" sx={{ p: 2 }}>
                  <Typography variant="body1" paragraph>
                    {formData.message || 'Your notification message will appear here.'}
                  </Typography>
                  <Box display="flex" flexWrap="wrap" gap={1} mt={2}>
                    {formData.channels.map(channelId => {
                      const channel = CHANNELS.find(c => c.id === channelId);
                      return channel ? (
                        <Chip
                          key={channel.id}
                          label={channel.label}
                          size="small"
                          color="primary"
                          variant="outlined"
                        />
                      ) : null;
                    })}
                    {formData.channels.length === 0 && (
                      <Typography variant="caption" color="text.secondary">
                        No channels selected
                      </Typography>
                    )}
                  </Box>
                </Paper>
              </Box>
            </Grid>
          </Grid>
        );
        
      case 3: // Schedule
        const nextDate = getNextScheduleDate();
        
        return (
          <Grid container spacing={3}>
            <Grid item xs={12}>
              <Typography variant="subtitle1" gutterBottom>
                When should we send this notification?
              </Typography>
              
              <Grid container spacing={2} sx={{ mb: 3 }}>
                {SCHEDULE_TYPES.map((type) => (
                  <Grid item key={type.id} xs={12} sm={6} md={4} lg={2.4}>
                    <Paper
                      elevation={formData.scheduleType === type.id ? 3 : 1}
                      sx={{
                        p: 2,
                        height: '100%',
                        cursor: 'pointer',
                        border: `1px solid ${
                          formData.scheduleType === type.id 
                            ? theme.palette.primary.main 
                            : theme.palette.divider
                        }`,
                        '&:hover': {
                          borderColor: theme.palette.primary.main,
                        },
                      }}
                      onClick={() => setFormData({ ...formData, scheduleType: type.id })}
                    >
                      <Box display="flex" alignItems="center">
                        <Box mr={1} color={formData.scheduleType === type.id ? 'primary.main' : 'text.secondary'}>
                          <ScheduleIcon />
                        </Box>
                        <Typography variant="body2">{type.label}</Typography>
                        {formData.scheduleType === type.id && (
                          <Box ml="auto" color="primary.main">
                            <CheckIcon fontSize="small" />
                          </Box>
                        )}
                      </Box>
                    </Paper>
                  </Grid>
                ))}
              </Grid>
              
              {/* Date/Time Picker */}
              <Box mb={3}>
                <LocalizationProvider dateAdapter={AdapterDateFns}>
                  <DateTimePicker
                    label="Scheduled Date & Time"
                    value={formData.scheduledAt}
                    onChange={handleDateChange('scheduledAt')}
                    minDateTime={new Date()}
                    renderInput={(params) => <TextField {...params} fullWidth />}
                  />
                </LocalizationProvider>
                <FormHelperText>
                  {formData.scheduleType === 'once' 
                    ? 'The date and time when the notification will be sent.'
                    : 'The date and time when the first notification will be sent.'}
                </FormHelperText>
              </Box>
              
              {/* Recurring Options */}
              {formData.scheduleType !== 'once' && (
                <Box mb={3}>
                  <Typography variant="subtitle2" gutterBottom>
                    Recurrence Settings
                  </Typography>
                  
                  {formData.scheduleType === 'custom' && (
                    <Box display="flex" alignItems="center" flexWrap="wrap" gap={2} mb={2}>
                      <Typography>Repeat every</Typography>
                      
                      <FormControl sx={{ minWidth: 80 }}>
                        <Select
                          value={formData.customInterval}
                          onChange={(e) => setFormData({ ...formData, customInterval: parseInt(e.target.value) })}
                          size="small"
                        >
                          {CUSTOM_INTERVALS.map((interval) => (
                            <MenuItem key={interval.value} value={interval.value}>
                              {interval.label}
                            </MenuItem>
                          ))}
                        </Select>
                      </FormControl>
                      
                      <FormControl sx={{ minWidth: 120 }}>
                        <Select
                          value={formData.customUnit}
                          onChange={(e) => setFormData({ ...formData, customUnit: e.target.value })}
                          size="small"
                        >
                          {INTERVAL_UNITS.map((unit) => (
                            <MenuItem key={unit.value} value={unit.value}>
                              {unit.label}
                            </MenuItem>
                          ))}
                        </Select>
                      </FormControl>
                    </Box>
                  )}
                  
                  {/* End Condition */}
                  <Box mt={3}>
                    <Typography variant="subtitle2" gutterBottom>
                      End Condition
                    </Typography>
                    
                    <FormControl component="fieldset">
                      <FormGroup>
                        <FormControlLabel
                          control={
                            <Checkbox
                              checked={formData.endCondition === 'never'}
                              onChange={() => setFormData({ ...formData, endCondition: 'never' })}
                              color="primary"
                            />
                          }
                          label="Never end"
                        />
                        
                        <Box display="flex" alignItems="center" ml={4} mb={1}>
                          <FormControlLabel
                            control={
                              <Checkbox
                                checked={formData.endCondition === 'date'}
                                onChange={() => setFormData({ 
                                  ...formData, 
                                  endCondition: 'date',
                                  endDate: formData.endDate || addMonths(new Date(), 1)
                                })}
                                color="primary"
                              />
                            }
                            label="End on"
                          />
                          
                          <LocalizationProvider dateAdapter={AdapterDateFns}>
                            <DateTimePicker
                              value={formData.endDate}
                              onChange={(date) => setFormData({ ...formData, endDate: date })}
                              minDateTime={formData.scheduledAt}
                              disabled={formData.endCondition !== 'date'}
                              renderInput={(params) => (
                                <TextField 
                                  {...params} 
                                  size="small" 
                                  sx={{ width: 200, ml: 1 }}
                                  disabled={formData.endCondition !== 'date'}
                                />
                              )}
                            />
                          </LocalizationProvider>
                        </Box>
                        
                        <Box display="flex" alignItems="center" ml={4}>
                          <FormControlLabel
                            control={
                              <Checkbox
                                checked={formData.endCondition === 'occurrences'}
                                onChange={() => setFormData({ 
                                  ...formData, 
                                  endCondition: 'occurrences',
                                  endAfterOccurrences: formData.endAfterOccurrences || 5
                                })}
                                color="primary"
                              />
                            }
                            label="End after"
                          />
                          
                          <TextField
                            type="number"
                            value={formData.endAfterOccurrences || ''}
                            onChange={(e) => setFormData({ 
                              ...formData, 
                              endAfterOccurrences: parseInt(e.target.value) || 1 
                            })}
                            size="small"
                            disabled={formData.endCondition !== 'occurrences'}
                            inputProps={{ min: 1, max: 100 }}
                            sx={{ width: 80, mx: 1 }}
                          />
                          
                          <Typography>occurrences</Typography>
                        </Box>
                      </FormGroup>
                    </FormControl>
                  </Box>
                </Box>
              )}
              
              {/* Next Occurrence Preview */}
              <Box mt={3} p={2} bgcolor="action.hover" borderRadius={1}>
                <Typography variant="subtitle2" gutterBottom>
                  Next Scheduled Occurrence
                </Typography>
                
                {nextDate ? (
                  <Box display="flex" alignItems="center">
                    <ScheduleIcon color="primary" sx={{ mr: 1 }} />
                    <Typography variant="body1">
                      {formatDate(nextDate)}
                    </Typography>
                  </Box>
                ) : (
                  <Typography variant="body2" color="text.secondary">
                    No upcoming occurrences. The scheduled time may be in the past.
                  </Typography>
                )}
                
                {formData.scheduleType !== 'once' && (
                  <Box mt={1}>
                    <Typography variant="caption" color="text.secondary">
                      {formData.scheduleType === 'daily' && 'This notification will be sent every day.'}
                      {formData.scheduleType === 'weekly' && 'This notification will be sent every week on the same day.'}
                      {formData.scheduleType === 'monthly' && 'This notification will be sent every month on the same date.'}
                      {formData.scheduleType === 'custom' && `This notification will be sent every ${formData.customInterval} ${formData.customUnit}${formData.customInterval > 1 ? 's' : ''}.`}
                      
                      {formData.endCondition === 'date' && formData.endDate && (
                        <span> It will end on {format(new Date(formData.endDate), 'MMM d, yyyy')}.</span>
                      )}
                      
                      {formData.endCondition === 'occurrences' && formData.endAfterOccurrences && (
                        <span> It will end after {formData.endAfterOccurrences} occurrence{formData.endAfterOccurrences !== 1 ? 's' : ''}.</span>
                      )}
                    </Typography>
                  </Box>
                )}
              </Box>
            </Grid>
          </Grid>
        );
        
      case 4: // Review
        return (
          <Grid container spacing={3}>
            <Grid item xs={12} md={8}>
              <Typography variant="h6" gutterBottom>
                Review Your Notification
              </Typography>
              
              <Paper variant="outlined" sx={{ p: 3, mb: 3 }}>
                <Typography variant="subtitle1" gutterBottom>
                  {formData.name || 'Untitled Notification'}
                </Typography>
                
                <Box mb={3}>
                  <Typography variant="body2" color="text.secondary" gutterBottom>
                    MESSAGE
                  </Typography>
                  <Paper variant="outlined" sx={{ p: 2, bgcolor: 'background.default' }}>
                    <Typography variant="body1" whiteSpace="pre-line">
                      {formData.message || 'No message provided.'}
                    </Typography>
                  </Paper>
                </Box>
                
                <Grid container spacing={3}>
                  <Grid item xs={12} sm={6}>
                    <Typography variant="body2" color="text.secondary" gutterBottom>
                      RECIPIENTS
                    </Typography>
                    <Box display="flex" alignItems="center" flexWrap="wrap" gap={1}>
                      {formData.recipientType === 'all' && (
                        <Chip 
                          icon={<PublicIcon />} 
                          label="All Users" 
                          size="small" 
                          variant="outlined" 
                        />
                      )}
                      
                      {formData.recipientType === 'user' && formData.selectedUsers.length > 0 && (
                        <Chip 
                          icon={<PersonIcon />} 
                          label={`${formData.selectedUsers.length} user${formData.selectedUsers.length !== 1 ? 's' : ''}`} 
                          size="small" 
                          variant="outlined" 
                        />
                      )}
                      
                      {formData.recipientType === 'role' && formData.selectedRoles.length > 0 && (
                        <Chip 
                          icon={<GroupIcon />} 
                          label={`${formData.selectedRoles.length} role${formData.selectedRoles.length !== 1 ? 's' : ''}`} 
                          size="small" 
                          variant="outlined" 
                        />
                      )}
                      
                      {formData.recipientType === 'class' && formData.selectedClasses.length > 0 && (
                        <Chip 
                          icon={<ClassIcon />} 
                          label={`${formData.selectedClasses.length} class${formData.selectedClasses.length !== 1 ? 'es' : ''}`} 
                          size="small" 
                          variant="outlined" 
                        />
                      )}
                      
                      {(formData.recipientType !== 'all' && 
                        ((formData.recipientType === 'user' && formData.selectedUsers.length === 0) ||
                         (formData.recipientType === 'role' && formData.selectedRoles.length === 0) ||
                         (formData.recipientType === 'class' && formData.selectedClasses.length === 0))) && (
                        <Typography variant="body2" color="error">
                          No recipients selected
                        </Typography>
                      )}
                    </Box>
                  </Grid>
                  
                  <Grid item xs={12} sm={6}>
                    <Typography variant="body2" color="text.secondary" gutterBottom>
                      CHANNELS
                    </Typography>
                    <Box display="flex" flexWrap="wrap" gap={1}>
                      {formData.channels.length > 0 ? (
                        formData.channels.map(channelId => {
                          const channel = CHANNELS.find(c => c.id === channelId);
                          return channel ? (
                            <Chip
                              key={channel.id}
                              label={channel.label}
                              size="small"
                              color="primary"
                              variant="outlined"
                            />
                          ) : null;
                        })
                      ) : (
                        <Typography variant="body2" color="error">
                          No channels selected
                        </Typography>
                      )}
                    </Box>
                  </Grid>
                  
                  <Grid item xs={12}>
                    <Typography variant="body2" color="text.secondary" gutterBottom>
                      SCHEDULE
                    </Typography>
                    <Box>
                      <Typography variant="body2">
                        {formData.scheduleType === 'once' ? (
                          <>
                            Send once on <strong>{formatDate(formData.scheduledAt)}</strong>
                          </>
                        ) : (
                          <>
                            Send <strong>{formData.scheduleType}</strong> starting from{' '}
                            <strong>{formatDate(formData.scheduledAt)}</strong>
                            
                            {formData.scheduleType === 'custom' && (
                              <>, every <strong>{formData.customInterval} {formData.customUnit}{formData.customInterval !== 1 ? 's' : ''}</strong></>
                            )}
                            
                            {formData.endCondition === 'date' && formData.endDate && (
                              <>, until <strong>{format(new Date(formData.endDate), 'MMM d, yyyy')}</strong></>
                            )}
                            
                            {formData.endCondition === 'occurrences' && formData.endAfterOccurrences && (
                              <>, for <strong>{formData.endAfterOccurrences} occurrence{formData.endAfterOccurrences !== 1 ? 's' : ''}</strong></>
                            )}
                            
                            {formData.endCondition === 'never' && (
                              <>, with no end date</>
                            )}
                          </>
                        )}
                      </Typography>
                      
                      {formData.scheduleType !== 'once' && (
                        <Box mt={1}>
                          <Typography variant="body2">
                            Next occurrence: <strong>{formatDate(getNextScheduleDate()) || 'None'}</strong>
                          </Typography>
                        </Box>
                      )}
                    </Box>
                  </Grid>
                </Grid>
              </Paper>
              
              {notification && (
                <Box mb={3}>
                  <FormControlLabel
                    control={
                      <Checkbox
                        checked={formData.notifyRecipients}
                        onChange={handleCheckboxChange('notifyRecipients')}
                        color="primary"
                      />
                    }
                    label="Notify recipients when this scheduled notification is created or updated"
                  />
                </Box>
              )}
              
              <Box display="flex" justifyContent="space-between" mt={4}>
                <Button 
                  onClick={handleBack}
                  disabled={loading}
                >
                  Back
                </Button>
                
                <Box>
                  <Button 
                    variant="outlined" 
                    onClick={onClose}
                    disabled={loading}
                    sx={{ mr: 1 }}
                  >
                    Cancel
                  </Button>
                  
                  <Button 
                    variant="contained" 
                    color="primary"
                    onClick={handleSubmit}
                    disabled={loading || !isStepValid(activeStep)}
                    startIcon={loading ? <CircularProgress size={20} /> : null}
                  >
                    {notification ? 'Update' : 'Schedule'} Notification
                  </Button>
                </Box>
              </Box>
            </Grid>
            
            <Grid item xs={12} md={4}>
              <Paper variant="outlined" sx={{ p: 2, position: 'sticky', top: 16 }}>
                <Typography variant="subtitle2" gutterBottom>
                  Notification Summary
                </Typography>
                
                <List dense>
                  <ListItem>
                    <ListItemText 
                      primary="Type" 
                      secondary={formData.type.charAt(0).toUpperCase() + formData.type.slice(1)} 
                    />
                  </ListItem>
                  
                  <ListItem>
                    <ListItemText 
                      primary="Recipients" 
                      secondary={
                        formData.recipientType === 'all' ? 'All Users' :
                        formData.recipientType === 'user' ? `${formData.selectedUsers.length} user${formData.selectedUsers.length !== 1 ? 's' : ''}` :
                        formData.recipientType === 'role' ? `${formData.selectedRoles.length} role${formData.selectedRoles.length !== 1 ? 's' : ''}` :
                        formData.recipientType === 'class' ? `${formData.selectedClasses.length} class${formData.selectedClasses.length !== 1 ? 'es' : ''}` :
                        'None'
                      } 
                    />
                  </ListItem>
                  
                  <ListItem>
                    <ListItemText 
                      primary="Channels" 
                      secondary={formData.channels.length > 0 
                        ? formData.channels.map(id => 
                            CHANNELS.find(c => c.id === id)?.label
                          ).join(', ')
                        : 'None'
                      } 
                    />
                  </ListItem>
                  
                  <ListItem>
                    <ListItemText 
                      primary="Schedule" 
                      secondary={
                        formData.scheduleType === 'once' 
                          ? `Once on ${formatDate(formData.scheduledAt)}`
                          : `Recurring (${formData.scheduleType})`
                      } 
                    />
                  </ListItem>
                  
                  {formData.scheduleType !== 'once' && (
                    <ListItem>
                      <ListItemText 
                        primary="Next Occurrence" 
                        secondary={formatDate(getNextScheduleDate()) || 'None'} 
                      />
                    </ListItem>
                  )}
                </List>
                
                <Divider sx={{ my: 2 }} />
                
                <Box mt={2}>
                  <Typography variant="body2" color="text.secondary" gutterBottom>
                    Ready to {notification ? 'update' : 'schedule'} this notification?
                  </Typography>
                  
                  <Button
                    fullWidth
                    variant="contained"
                    color="primary"
                    onClick={handleSubmit}
                    disabled={loading || !isStepValid(activeStep)}
                    startIcon={loading ? <CircularProgress size={20} /> : null}
                    size="large"
                    sx={{ mt: 1 }}
                  >
                    {notification ? 'Update' : 'Schedule'} Notification
                  </Button>
                </Box>
              </Paper>
            </Grid>
          </Grid>
        );
        
      default:
        return 'Unknown step';
    }
  };
  
  return (
    <Dialog
      open={open}
      onClose={() => onClose(false)}
      maxWidth="md"
      fullWidth
      fullScreen={isMobile}
      aria-labelledby="schedule-notification-dialog-title"
    >
      <DialogTitle id="schedule-notification-dialog-title">
        <Box display="flex" justifyContent="space-between" alignItems="center">
          <span>
            {notification ? 'Edit Scheduled Notification' : 'Schedule New Notification'}
          </span>
          <IconButton 
            edge="end" 
            onClick={() => onClose(false)}
            aria-label="close"
          >
            <CloseIcon />
          </IconButton>
        </Box>
        
        <Stepper 
          activeStep={activeStep} 
          orientation={isMobile ? 'vertical' : 'horizontal'}
          sx={{ mt: 2 }}
        >
          {steps.map((step, index) => (
            <Step key={step.label}>
              <StepLabel
                optional={
                  index === steps.length - 1 ? (
                    <Typography variant="caption">Last step</Typography>
                  ) : null
                }
              >
                {step.label}
              </StepLabel>
            </Step>
          ))}
        </Stepper>
      </DialogTitle>
      
      <DialogContent dividers>
        {error && (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        )}
        
        {renderStepContent(activeStep)}
      </DialogContent>
      
      {activeStep !== 4 && ( // Hide default actions on the review step
        <DialogActions sx={{ p: 2, borderTop: '1px solid rgba(0, 0, 0, 0.12)' }}>
          <Button 
            onClick={activeStep === 0 ? () => onClose(false) : handleBack}
            disabled={loading}
          >
            {activeStep === 0 ? 'Cancel' : 'Back'}
          </Button>
          
          <Button
            variant="contained"
            color="primary"
            onClick={handleNext}
            disabled={loading || !isStepValid(activeStep)}
            endIcon={loading ? <CircularProgress size={20} /> : null}
          >
            {activeStep === steps.length - 1 
              ? (notification ? 'Update' : 'Schedule') + ' Notification' 
              : 'Next'}
          </Button>
        </DialogActions>
      )}
    </Dialog>
  );
};

export default ScheduleNotificationDialog;
