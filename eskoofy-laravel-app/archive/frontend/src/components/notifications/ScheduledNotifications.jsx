import React, { useState, useEffect } from 'react';
import { 
  Box, 
  Typography, 
  Paper, 
  Button, 
  Table, 
  TableBody, 
  TableCell, 
  TableContainer, 
  TableHead, 
  TableRow, 
  TablePagination,
  Chip,
  IconButton,
  Tooltip,
  Menu,
  MenuItem,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  DialogContentText,
  TextField,
  FormControl,
  InputLabel,
  Select,
  Checkbox,
  ListItemText,
  FormGroup,
  FormControlLabel,
  Grid,
  Divider,
  useTheme,
  useMediaQuery,
  CircularProgress,
  Snackbar,
  Alert,
  Tabs,
  Tab,
  Badge
} from '@mui/material';
import { 
  Add as AddIcon, 
  MoreVert as MoreVertIcon, 
  Edit as EditIcon, 
  Delete as DeleteIcon, 
  Schedule as ScheduleIcon,
  Refresh as RefreshIcon,
  CheckCircle as CheckCircleIcon,
  Error as ErrorIcon,
  Pending as PendingIcon,
  Cancel as CancelIcon,
  Notifications as NotificationsIcon
} from '@mui/icons-material';
import { format, parseISO, isPast, isToday, isTomorrow } from 'date-fns';
import { useApi } from '../../hooks/useApi';
import ScheduleNotificationDialog from './ScheduleNotificationDialog';

const ScheduledNotifications = () => {
  const [notifications, setNotifications] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [total, setTotal] = useState(0);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState('');
  const [dialogOpen, setDialogOpen] = useState(false);
  const [selectedNotification, setSelectedNotification] = useState(null);
  const [anchorEl, setAnchorEl] = useState(null);
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [notificationToDelete, setNotificationToDelete] = useState(null);
  const [filters, setFilters] = useState({
    status: '',
    type: '',
    search: ''
  });
  const [tabValue, setTabValue] = useState('all');
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('sm'));
  const api = useApi();

  // Status options for filtering
  const statusOptions = [
    { value: 'pending', label: 'Pending', icon: <PendingIcon fontSize="small" />, color: 'warning' },
    { value: 'sent', label: 'Sent', icon: <CheckCircleIcon fontSize="small" />, color: 'success' },
    { value: 'failed', label: 'Failed', icon: <ErrorIcon fontSize="small" />, color: 'error' },
    { value: 'cancelled', label: 'Cancelled', icon: <CancelIcon fontSize="small" />, color: 'default' },
  ];

  // Fetch notifications
  const fetchNotifications = async () => {
    try {
      setLoading(true);
      const params = {
        page: page + 1,
        per_page: rowsPerPage,
        ...filters,
        status: tabValue === 'all' ? '' : tabValue
      };
      
      const response = await api.get('/api/notifications/scheduled', { params });
      setNotifications(response.data.data);
      setTotal(response.data.total || 0);
    } catch (err) {
      setError('Failed to load scheduled notifications');
      console.error('Error fetching scheduled notifications:', err);
    } finally {
      setLoading(false);
    }
  };

  // Initial fetch
  useEffect(() => {
    fetchNotifications();
  }, [page, rowsPerPage, filters, tabValue]);

  // Handle pagination
  const handleChangePage = (event, newPage) => {
    setPage(newPage);
  };

  const handleChangeRowsPerPage = (event) => {
    setRowsPerPage(parseInt(event.target.value, 10));
    setPage(0);
  };

  // Handle menu actions
  const handleMenuOpen = (event, notification) => {
    setAnchorEl(event.currentTarget);
    setSelectedNotification(notification);
  };

  const handleMenuClose = () => {
    setAnchorEl(null);
    setSelectedNotification(null);
  };

  // Handle delete confirmation
  const handleDeleteClick = (notification) => {
    setNotificationToDelete(notification);
    setDeleteConfirmOpen(true);
    handleMenuClose();
  };

  const handleDeleteConfirm = async () => {
    if (!notificationToDelete) return;
    
    try {
      await api.delete(`/api/notifications/scheduled/${notificationToDelete.id}`);
      setSuccess('Scheduled notification cancelled successfully');
      fetchNotifications();
    } catch (err) {
      setError('Failed to cancel scheduled notification');
      console.error('Error cancelling scheduled notification:', err);
    } finally {
      setDeleteConfirmOpen(false);
      setNotificationToDelete(null);
    }
  };

  // Handle edit
  const handleEdit = () => {
    if (!selectedNotification) return;
    
    // Only allow editing pending notifications
    if (selectedNotification.status !== 'pending') {
      setError('Only pending notifications can be edited');
      return;
    }
    
    setDialogOpen(true);
    handleMenuClose();
  };

  // Handle dialog close
  const handleDialogClose = (refresh = false) => {
    setDialogOpen(false);
    setSelectedNotification(null);
    
    if (refresh) {
      fetchNotifications();
    }
  };

  // Format date for display
  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    
    const date = parseISO(dateString);
    
    if (isToday(date)) {
      return `Today at ${format(date, 'h:mm a')}`;
    } else if (isTomorrow(date)) {
      return `Tomorrow at ${format(date, 'h:mm a')}`;
    } else if (isPast(date)) {
      return format(date, 'MMM d, yyyy h:mm a');
    } else {
      return format(date, 'EEE, MMM d, yyyy h:mm a');
    }
  };

  // Get status chip
  const getStatusChip = (status) => {
    const statusOption = statusOptions.find(opt => opt.value === status) || {};
    
    return (
      <Chip
        icon={statusOption.icon}
        label={statusOption.label || status}
        color={statusOption.color || 'default'}
        size="small"
        variant="outlined"
      />
    );
  };

  // Handle tab change
  const handleTabChange = (event, newValue) => {
    setTabValue(newValue);
    setPage(0);
  };

  // Handle filter change
  const handleFilterChange = (filter, value) => {
    setFilters(prev => ({
      ...prev,
      [filter]: value
    }));
    setPage(0);
  };

  // Handle refresh
  const handleRefresh = () => {
    fetchNotifications();
  };

  // Handle close snackbar
  const handleCloseSnackbar = () => {
    setError(null);
    setSuccess('');
  };

  return (
    <Box>
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Box display="flex" alignItems="center">
          <NotificationsIcon sx={{ mr: 1 }} />
          <Typography variant="h5" component="h1">
            Scheduled Notifications
          </Typography>
        </Box>
        
        <Box>
          <Button
            variant="contained"
            color="primary"
            startIcon={<AddIcon />}
            onClick={() => setDialogOpen(true)}
            sx={{ ml: 1 }}
          >
            {isMobile ? 'New' : 'Schedule Notification'}
          </Button>
        </Box>
      </Box>

      {/* Filters */}
      <Paper sx={{ mb: 3, p: 2 }}>
        <Box display="flex" flexWrap="wrap" gap={2} alignItems="center">
          <TextField
            label="Search"
            variant="outlined"
            size="small"
            value={filters.search}
            onChange={(e) => handleFilterChange('search', e.target.value)}
            sx={{ minWidth: 200, flex: 1 }}
            placeholder="Search by name or type..."
          />
          
          <FormControl variant="outlined" size="small" sx={{ minWidth: 200 }}>
            <InputLabel>Type</InputLabel>
            <Select
              value={filters.type}
              onChange={(e) => handleFilterChange('type', e.target.value)}
              label="Type"
            >
              <MenuItem value="">All Types</MenuItem>
              {/* Add your notification types here */}
              <MenuItem value="announcement">Announcement</MenuItem>
              <MenuItem value="reminder">Reminder</MenuItem>
              <MenuItem value="alert">Alert</MenuItem>
            </Select>
          </FormControl>
          
          <Button
            variant="outlined"
            startIcon={<RefreshIcon />}
            onClick={handleRefresh}
            disabled={loading}
          >
            Refresh
          </Button>
        </Box>
        
        {/* Status Tabs */}
        <Tabs
          value={tabValue}
          onChange={handleTabChange}
          indicatorColor="primary"
          textColor="primary"
          variant="scrollable"
          scrollButtons="auto"
          sx={{ mt: 2 }}
        >
          <Tab 
            label={
              <Box display="flex" alignItems="center">
                <Box component="span" mr={1}>All</Box>
                <Chip 
                  label={total} 
                  size="small" 
                  color="default" 
                  variant="outlined"
                  sx={{ height: 20 }}
                />
              </Box>
            } 
            value="all" 
          />
          {statusOptions.map((status) => (
            <Tab 
              key={status.value}
              icon={status.icon}
              iconPosition="start"
              label={
                <Box display="flex" alignItems="center">
                  <Box component="span" mr={1}>{status.label}</Box>
                  <Chip 
                    label={notifications.filter(n => n.status === status.value).length}
                    size="small"
                    color={status.color}
                    variant="outlined"
                    sx={{ height: 20 }}
                  />
                </Box>
              }
              value={status.value}
              sx={{ minHeight: 48 }}
            />
          ))}
        </Tabs>
      </Paper>

      {/* Notifications Table */}
      <Paper sx={{ width: '100%', overflow: 'hidden' }}>
        <TableContainer sx={{ maxHeight: 'calc(100vh - 300px)' }}>
          <Table stickyHeader size="small">
            <TableHead>
              <TableRow>
                <TableCell>Name</TableCell>
                <TableCell>Type</TableCell>
                <TableCell>Channels</TableCell>
                <TableCell>Recipients</TableCell>
                <TableCell>Scheduled For</TableCell>
                <TableCell>Status</TableCell>
                <TableCell align="right">Actions</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {loading ? (
                <TableRow>
                  <TableCell colSpan={7} align="center" sx={{ py: 4 }}>
                    <CircularProgress />
                  </TableCell>
                </TableRow>
              ) : notifications.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={7} align="center" sx={{ py: 4 }}>
                    <Typography variant="body2" color="textSecondary">
                      No scheduled notifications found
                    </Typography>
                  </TableCell>
                </TableRow>
              ) : (
                notifications.map((notification) => (
                  <TableRow 
                    key={notification.id}
                    hover
                    sx={{ '&:last-child td, &:last-child th': { border: 0 } }}
                  >
                    <TableCell>
                      <Typography variant="body2" fontWeight="medium">
                        {notification.name}
                      </Typography>
                      <Typography variant="caption" color="textSecondary">
                        {notification.type}
                      </Typography>
                    </TableCell>
                    <TableCell>
                      <Chip 
                        label={notification.type} 
                        size="small" 
                        variant="outlined" 
                      />
                    </TableCell>
                    <TableCell>
                      <Box display="flex" flexWrap="wrap" gap={0.5}>
                        {notification.channels?.map((channel) => (
                          <Chip 
                            key={channel} 
                            label={channel} 
                            size="small" 
                            variant="outlined"
                            color="primary"
                          />
                        ))}
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Tooltip title={`${notification.recipients?.length || 0} recipients`}>
                        <Chip 
                          label={`${notification.recipients?.length || 0} ${notification.recipients?.length === 1 ? 'recipient' : 'recipients'}`}
                          size="small"
                          variant="outlined"
                        />
                      </Tooltip>
                    </TableCell>
                    <TableCell>
                      <Box display="flex" alignItems="center">
                        <ScheduleIcon fontSize="small" color="action" sx={{ mr: 1 }} />
                        <Box>
                          <Typography variant="body2">
                            {formatDate(notification.scheduled_at)}
                          </Typography>
                          {notification.sent_at && (
                            <Typography variant="caption" color="textSecondary">
                              Sent: {formatDate(notification.sent_at)}
                            </Typography>
                          )}
                        </Box>
                      </Box>
                    </TableCell>
                    <TableCell>
                      {getStatusChip(notification.status)}
                    </TableCell>
                    <TableCell align="right">
                      <IconButton
                        size="small"
                        onClick={(e) => handleMenuOpen(e, notification)}
                        aria-label="more"
                      >
                        <MoreVertIcon />
                      </IconButton>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </TableContainer>
        
        <TablePagination
          rowsPerPageOptions={[5, 10, 25, 50]}
          component="div"
          count={total}
          rowsPerPage={rowsPerPage}
          page={page}
          onPageChange={handleChangePage}
          onRowsPerPageChange={handleChangeRowsPerPage}
        />
      </Paper>

      {/* Action Menu */}
      <Menu
        anchorEl={anchorEl}
        open={Boolean(anchorEl)}
        onClose={handleMenuClose}
        onClick={handleMenuClose}
        transformOrigin={{ horizontal: 'right', vertical: 'top' }}
        anchorOrigin={{ horizontal: 'right', vertical: 'bottom' }}
      >
        <MenuItem onClick={handleEdit}>
          <EditIcon fontSize="small" sx={{ mr: 1 }} />
          Edit
        </MenuItem>
        <MenuItem 
          onClick={() => handleDeleteClick(selectedNotification)}
          disabled={!['pending', 'failed'].includes(selectedNotification?.status)}
        >
          <DeleteIcon fontSize="small" sx={{ mr: 1 }} />
          {selectedNotification?.status === 'pending' ? 'Cancel' : 'Delete'}
        </MenuItem>
      </Menu>

      {/* Delete Confirmation Dialog */}
      <Dialog
        open={deleteConfirmOpen}
        onClose={() => setDeleteConfirmOpen(false)}
        aria-labelledby="delete-dialog-title"
        aria-describedby="delete-dialog-description"
      >
        <DialogTitle id="delete-dialog-title">
          {notificationToDelete?.status === 'pending' ? 'Cancel Scheduled Notification' : 'Delete Notification'}
        </DialogTitle>
        <DialogContent>
          <DialogContentText id="delete-dialog-description">
            Are you sure you want to {notificationToDelete?.status === 'pending' ? 'cancel' : 'delete'} the notification "{notificationToDelete?.name}"?
            {notificationToDelete?.status === 'pending' && ' This action cannot be undone.'}
          </DialogContentText>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDeleteConfirmOpen(false)} color="primary">
            No, Keep It
          </Button>
          <Button onClick={handleDeleteConfirm} color="primary" autoFocus>
            Yes, {notificationToDelete?.status === 'pending' ? 'Cancel' : 'Delete'}
          </Button>
        </DialogActions>
      </Dialog>

      {/* Schedule Notification Dialog */}
      <ScheduleNotificationDialog
        open={dialogOpen}
        onClose={handleDialogClose}
        notification={selectedNotification}
      />

      {/* Snackbars */}
      <Snackbar
        open={!!error}
        autoHideDuration={6000}
        onClose={handleCloseSnackbar}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
      >
        <Alert onClose={handleCloseSnackbar} severity="error" sx={{ width: '100%' }}>
          {error}
        </Alert>
      </Snackbar>
      
      <Snackbar
        open={!!success}
        autoHideDuration={6000}
        onClose={handleCloseSnackbar}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
      >
        <Alert onClose={handleCloseSnackbar} severity="success" sx={{ width: '100%' }}>
          {success}
        </Alert>
      </Snackbar>
    </Box>
  );
};

export default ScheduledNotifications;
