import React, { useState, useEffect } from 'react';
import { 
  Box, 
  Typography, 
  Paper, 
  Grid, 
  TextField, 
  Button, 
  FormControl, 
  InputLabel, 
  Select, 
  MenuItem, 
  Divider, 
  Chip,
  CircularProgress,
  Alert,
  Snackbar,
  Tab,
  Tabs,
  IconButton,
  Tooltip
} from '@mui/material';
import { 
  Save as SaveIcon, 
  Refresh as RefreshIcon, 
  Code as CodeIcon,
  Visibility as PreviewIcon,
  Edit as EditIcon,
  Add as AddIcon
} from '@mui/icons-material';
import { useApi } from '../../../hooks/useApi';
import TemplatePreview from './TemplatePreview';
import TemplateVariablesDialog from './TemplateVariablesDialog';

const TemplateManager = () => {
  const [templates, setTemplates] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState('');
  const [activeTab, setActiveTab] = useState(0);
  const [selectedTemplate, setSelectedTemplate] = useState(null);
  const [showPreview, setShowPreview] = useState(false);
  const [showVariables, setShowVariables] = useState(false);
  const [templateTypes, setTemplateTypes] = useState([]);
  const [filters, setFilters] = useState({
    type: '',
    channel: '',
    search: ''
  });
  
  const api = useApi();
  
  // Available channels
  const channels = [
    { value: 'mail', label: 'Email' },
    { value: 'sms', label: 'SMS' },
    { value: 'push', label: 'Push Notification' },
    { value: 'in_app', label: 'In-App' }
  ];

  // Load templates and template types
  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        
        // In a real app, you would fetch these from your API
        // const [templatesRes, typesRes] = await Promise.all([
        //   api.get('/api/admin/notifications/templates', { params: filters }),
        //   api.get('/api/admin/notifications/templates/types')
        // ]);
        
        // Mock data for demonstration
        const templatesRes = {
          data: [
            {
              id: 1,
              name: 'Welcome Email',
              type: 'user.welcome',
              channel: 'mail',
              subject: 'Welcome to {{app_name}}!',
              content: 'Hello {{user_name}},\n\nThank you for registering with {{app_name}}. We\'re excited to have you on board!\n\nPlease verify your email address by clicking the button below.\n\n{{action_button|Verify Email|{{verification_url}}}}\n\nIf you did not create an account, no further action is required.\n\nThanks,\n{{app_name}} Team',
              is_active: true,
              created_at: '2023-01-01T00:00:00Z',
              updated_at: '2023-01-01T00:00:00Z'
            },
            // Add more mock templates as needed
          ]
        };
        
        const typesRes = {
          data: [
            { type: 'user.welcome', description: 'Sent when a new user registers' },
            { type: 'user.password_reset', description: 'Sent when a user requests a password reset' },
            { type: 'course.enrolled', description: 'Sent when a user enrolls in a course' },
            { type: 'payment.received', description: 'Sent when a payment is received' },
            { type: 'assignment.submitted', description: 'Sent when an assignment is submitted' },
            { type: 'exam.reminder', description: 'Reminder for upcoming exams' },
          ]
        };
        
        setTemplates(templatesRes.data);
        setTemplateTypes(typesRes.data);
        
        // Select the first template by default
        if (templatesRes.data.length > 0 && !selectedTemplate) {
          setSelectedTemplate(templatesRes.data[0]);
        }
      } catch (err) {
        setError('Failed to load templates. Please try again.');
        console.error('Error fetching templates:', err);
      } finally {
        setLoading(false);
      }
    };
    
    fetchData();
  }, [filters]);
  
  const handleSaveTemplate = async () => {
    if (!selectedTemplate) return;
    
    try {
      setSaving(true);
      
      // In a real app, you would save the template via API
      // const response = selectedTemplate.id
      //   ? await api.put(`/api/admin/notifications/templates/${selectedTemplate.id}`, selectedTemplate)
      //   : await api.post('/api/admin/notifications/templates', selectedTemplate);
      
      // Update the templates list with the saved template
      // setTemplates(prev => {
      //   const exists = prev.some(t => t.id === response.data.id);
      //   return exists
      //     ? prev.map(t => t.id === response.data.id ? response.data : t)
      //     : [...prev, response.data];
      // });
      
      setSelectedTemplate(selectedTemplate);
      setSuccess('Template saved successfully!');
    } catch (err) {
      setError('Failed to save template. Please try again.');
      console.error('Error saving template:', err);
    } finally {
      setSaving(false);
    }
  };
  
  const handleCreateNew = () => {
    setSelectedTemplate({
      name: '',
      type: '',
      channel: 'mail',
      subject: '',
      content: '',
      is_active: true
    });
  };
  
  const handleChange = (field, value) => {
    setSelectedTemplate(prev => ({
      ...prev,
      [field]: value
    }));
  };
  
  const handleFilterChange = (field, value) => {
    setFilters(prev => ({
      ...prev,
      [field]: value
    }));
  };
  
  const handleCloseSnackbar = () => {
    setError(null);
    setSuccess('');
  };
  
  const availableVariables = [
    { name: 'app_name', description: 'The name of the application' },
    { name: 'user_name', description: 'The full name of the user' },
    { name: 'user_email', description: 'The email address of the user' },
    { name: 'verification_url', description: 'URL for email verification' },
    { name: 'reset_url', description: 'URL for password reset' },
    { name: 'action_button|Text|URL', description: 'Renders a button with the specified text and URL' },
  ];
  
  if (loading && templates.length === 0) {
    return (
      <Box display="flex" justifyContent="center" p={4}>
        <CircularProgress />
      </Box>
    );
  }
  
  return (
    <Box>
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Typography variant="h5" gutterBottom>
          Notification Templates
        </Typography>
        <Button 
          variant="contained" 
          color="primary" 
          startIcon={<AddIcon />}
          onClick={handleCreateNew}
        >
          New Template
        </Button>
      </Box>
      
      <Grid container spacing={3}>
        {/* Filters */}
        <Grid item xs={12}>
          <Paper sx={{ p: 2, mb: 3 }}>
            <Grid container spacing={2} alignItems="center">
              <Grid item xs={12} sm={4}>
                <FormControl fullWidth size="small">
                  <InputLabel>Type</InputLabel>
                  <Select
                    value={filters.type}
                    onChange={(e) => handleFilterChange('type', e.target.value)}
                    label="Type"
                  >
                    <MenuItem value="">All Types</MenuItem>
                    {templateTypes.map((type) => (
                      <MenuItem key={type.type} value={type.type}>
                        {type.type}
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl>
              </Grid>
              <Grid item xs={12} sm={4}>
                <FormControl fullWidth size="small">
                  <InputLabel>Channel</InputLabel>
                  <Select
                    value={filters.channel}
                    onChange={(e) => handleFilterChange('channel', e.target.value)}
                    label="Channel"
                  >
                    <MenuItem value="">All Channels</MenuItem>
                    {channels.map((channel) => (
                      <MenuItem key={channel.value} value={channel.value}>
                        {channel.label}
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl>
              </Grid>
              <Grid item xs={12} sm={4}>
                <TextField
                  fullWidth
                  size="small"
                  label="Search templates..."
                  value={filters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                />
              </Grid>
            </Grid>
          </Paper>
        </Grid>
        
        {/* Template List */}
        <Grid item xs={12} md={4}>
          <Paper sx={{ height: 'calc(100vh - 250px)', overflow: 'auto' }}>
            {templates.length === 0 ? (
              <Box p={3} textAlign="center">
                <Typography color="textSecondary">No templates found</Typography>
              </Box>
            ) : (
              <Box>
                {templates.map((template) => (
                  <Box 
                    key={template.id} 
                    onClick={() => setSelectedTemplate(template)}
                    sx={{
                      p: 2,
                      borderBottom: '1px solid #eee',
                      cursor: 'pointer',
                      backgroundColor: selectedTemplate?.id === template.id ? '#f5f5f5' : 'transparent',
                      '&:hover': {
                        backgroundColor: '#f9f9f9'
                      }
                    }}
                  >
                    <Box display="flex" justifyContent="space-between" alignItems="center">
                      <Typography variant="subtitle2">{template.name}</Typography>
                      <Chip 
                        label={template.channel} 
                        size="small" 
                        color="primary" 
                        variant="outlined"
                      />
                    </Box>
                    <Typography variant="caption" color="textSecondary">
                      {template.type}
                    </Typography>
                    {!template.is_active && (
                      <Chip 
                        label="Inactive" 
                        size="small" 
                        color="default" 
                        variant="outlined"
                        sx={{ ml: 1 }}
                      />
                    )}
                  </Box>
                ))}
              </Box>
            )}
          </Paper>
        </Grid>
        
        {/* Template Editor */}
        <Grid item xs={12} md={8}>
          {selectedTemplate ? (
            <Paper sx={{ p: 3 }}>
              <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
                <Typography variant="h6">
                  {selectedTemplate.id ? 'Edit Template' : 'New Template'}
                </Typography>
                <Box>
                  <Tooltip title="Preview">
                    <IconButton 
                      onClick={() => setShowPreview(true)}
                      disabled={!selectedTemplate.content}
                    >
                      <PreviewIcon />
                    </IconButton>
                  </Tooltip>
                  <Tooltip title="Insert Variable">
                    <IconButton onClick={() => setShowVariables(true)}>
                      <CodeIcon />
                    </IconButton>
                  </Tooltip>
                </Box>
              </Box>
              
              <Grid container spacing={2}>
                <Grid item xs={12} sm={6}>
                  <TextField
                    fullWidth
                    label="Template Name"
                    value={selectedTemplate.name || ''}
                    onChange={(e) => handleChange('name', e.target.value)}
                    margin="normal"
                    size="small"
                  />
                </Grid>
                <Grid item xs={12} sm={6}>
                  <FormControl fullWidth margin="normal" size="small">
                    <InputLabel>Type</InputLabel>
                    <Select
                      value={selectedTemplate.type || ''}
                      onChange={(e) => handleChange('type', e.target.value)}
                      label="Type"
                    >
                      {templateTypes.map((type) => (
                        <MenuItem key={type.type} value={type.type}>
                          {type.type}
                        </MenuItem>
                      ))}
                    </Select>
                  </FormControl>
                </Grid>
                <Grid item xs={12} sm={6}>
                  <FormControl fullWidth margin="normal" size="small">
                    <InputLabel>Channel</InputLabel>
                    <Select
                      value={selectedTemplate.channel || 'mail'}
                      onChange={(e) => handleChange('channel', e.target.value)}
                      label="Channel"
                    >
                      {channels.map((channel) => (
                        <MenuItem key={channel.value} value={channel.value}>
                          {channel.label}
                        </MenuItem>
                      ))}
                    </Select>
                  </FormControl>
                </Grid>
                
                {(selectedTemplate.channel === 'mail' || selectedTemplate.channel === 'push') && (
                  <Grid item xs={12}>
                    <TextField
                      fullWidth
                      label="Subject"
                      value={selectedTemplate.subject || ''}
                      onChange={(e) => handleChange('subject', e.target.value)}
                      margin="normal"
                      size="small"
                    />
                  </Grid>
                )}
                
                <Grid item xs={12}>
                  <TextField
                    fullWidth
                    label="Content"
                    value={selectedTemplate.content || ''}
                    onChange={(e) => handleChange('content', e.target.value)}
                    margin="normal"
                    multiline
                    rows={10}
                    variant="outlined"
                    helperText="Use {{variable_name}} for variables and {{action_button|Text|URL}} for buttons"
                  />
                </Grid>
                
                <Grid item xs={12}>
                  <Box display="flex" justifyContent="space-between" alignItems="center" mt={2}>
                    <FormControlLabel
                      control={
                        <Checkbox
                          checked={selectedTemplate.is_active !== false}
                          onChange={(e) => handleChange('is_active', e.target.checked)}
                          color="primary"
                        />
                      }
                      label="Active"
                    />
                    <Box>
                      <Button 
                        variant="outlined" 
                        color="primary" 
                        startIcon={<RefreshIcon />}
                        sx={{ mr: 1 }}
                      >
                        Reset
                      </Button>
                      <Button 
                        variant="contained" 
                        color="primary" 
                        startIcon={saving ? <CircularProgress size={20} /> : <SaveIcon />}
                        onClick={handleSaveTemplate}
                        disabled={saving}
                      >
                        {saving ? 'Saving...' : 'Save Template'}
                      </Button>
                    </Box>
                  </Box>
                </Grid>
              </Grid>
            </Paper>
          ) : (
            <Paper sx={{ p: 3, textAlign: 'center' }}>
              <Typography color="textSecondary" gutterBottom>
                Select a template to edit or create a new one
              </Typography>
              <Button 
                variant="contained" 
                color="primary" 
                startIcon={<AddIcon />}
                onClick={handleCreateNew}
                sx={{ mt: 2 }}
              >
                New Template
              </Button>
            </Paper>
          )}
        </Grid>
      </Grid>
      
      {/* Preview Dialog */}
      {selectedTemplate && (
        <TemplatePreview
          open={showPreview}
          onClose={() => setShowPreview(false)}
          template={selectedTemplate}
          variables={availableVariables}
        />
      )}
      
      {/* Variables Dialog */}
      <TemplateVariablesDialog
        open={showVariables}
        onClose={() => setShowVariables(false)}
        onSelectVariable={(variable) => {
          // Insert the variable at the current cursor position
          const textarea = document.querySelector('textarea[name="content"]');
          const start = textarea.selectionStart;
          const end = textarea.selectionEnd;
          const text = textarea.value;
          const before = text.substring(0, start);
          const after = text.substring(end, text.length);
          
          handleChange('content', before + '{{' + variable + '}}' + after);
          setShowVariables(false);
          
          // Focus back on the textarea
          setTimeout(() => {
            const newPos = start + variable.length + 4; // +4 for the {{ and }}
            textarea.focus();
            textarea.setSelectionRange(newPos, newPos);
          }, 0);
        }}
        variables={availableVariables}
      />
      
      {/* Success/Error Snackbar */}
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

export default TemplateManager;
