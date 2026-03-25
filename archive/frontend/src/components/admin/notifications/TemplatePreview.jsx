import React from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  Box,
  Tabs,
  Tab,
  Paper,
  Typography,
  Divider,
  IconButton
} from '@mui/material';
import { Close as CloseIcon } from '@mui/icons-material';

const TemplatePreview = ({ open, onClose, template, variables }) => {
  const [activeTab, setActiveTab] = React.useState(0);
  
  // Generate sample data for preview
  const sampleData = {
    app_name: 'School Management System',
    user_name: 'John Doe',
    user_email: 'john.doe@example.com',
    verification_url: 'https://schoolms.test/verify/abc123',
    reset_url: 'https://schoolms.test/reset-password/abc123',
    course_name: 'Introduction to Computer Science',
    instructor_name: 'Dr. Jane Smith',
    due_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toLocaleDateString(),
    grade: 'A',
    feedback: 'Excellent work! Your submission was well-structured and demonstrated a deep understanding of the concepts.'
  };
  
  // Render the template with sample data
  const renderTemplate = (content) => {
    if (!content) return '';
    
    // Replace variables
    let rendered = content;
    Object.entries(sampleData).forEach(([key, value]) => {
      rendered = rendered.replace(new RegExp(`\\{\\s*${key}\\s*\\}`, 'g'), value);
    });
    
    // Replace action buttons
    rendered = rendered.replace(
      /\{\{\s*action_button\|([^|]+)\|([^}]+)\s*\}\}/g,
      (match, text, url) => {
        return `<a href="${url.trim()}" style="display: inline-block; padding: 10px 20px; background-color: #4a86e8; color: white; text-decoration: none; border-radius: 4px; margin: 10px 0;">${text.trim()}</a>`;
      }
    );
    
    return rendered;
  };
  
  // Render email preview
  const renderEmailPreview = () => {
    return (
      <Box>
        <Box sx={{ p: 3, borderBottom: '1px solid #eee' }}>
          <Typography variant="subtitle2" color="textSecondary">To: {sampleData.user_email}</Typography>
          <Typography variant="subtitle2" color="textSecondary">Subject: {renderTemplate(template.subject)}</Typography>
        </Box>
        <Box 
          sx={{ 
            p: 3, 
            minHeight: '300px',
            '& a': {
              color: '#4a86e8',
              textDecoration: 'none',
              '&:hover': {
                textDecoration: 'underline'
              }
            },
            '& .button': {
              display: 'inline-block',
              padding: '10px 20px',
              backgroundColor: '#4a86e8',
              color: 'white',
              textDecoration: 'none',
              borderRadius: '4px',
              margin: '10px 0'
            }
          }}
          dangerouslySetInnerHTML={{ __html: renderTemplate(template.content) }}
        />
      </Box>
    );
  };
  
  // Render SMS preview
  const renderSmsPreview = () => {
    return (
      <Box sx={{ p: 3, bgcolor: '#f5f5f5', borderRadius: 1, fontFamily: 'Arial, sans-serif' }}>
        <Box sx={{ 
          bgcolor: 'white', 
          p: 2, 
          borderRadius: '8px',
          boxShadow: '0 1px 3px rgba(0,0,0,0.1)'
        }}>
          <Typography variant="body2" sx={{ whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>
            {renderTemplate(template.content)}
          </Typography>
          <Typography variant="caption" color="textSecondary" sx={{ mt: 1, display: 'block' }}>
            {new Date().toLocaleTimeString()}
          </Typography>
        </Box>
      </Box>
    );
  };
  
  // Render push notification preview
  const renderPushPreview = () => {
    return (
      <Box sx={{ p: 2, bgcolor: '#f5f5f5', borderRadius: 2 }}>
        <Paper sx={{ p: 2, maxWidth: 400, mx: 'auto' }}>
          <Box sx={{ display: 'flex', alignItems: 'center', mb: 1 }}>
            <Box sx={{ width: 24, height: 24, bgcolor: 'primary.main', borderRadius: '50%', mr: 1 }} />
            <Typography variant="subtitle2" color="textSecondary">SchoolMS</Typography>
            <Typography variant="caption" color="textSecondary" sx={{ ml: 'auto' }}>
              now
            </Typography>
          </Box>
          <Typography variant="subtitle1" gutterBottom>
            {renderTemplate(template.subject)}
          </Typography>
          <Typography variant="body2" color="textSecondary" sx={{ mb: 1 }}>
            {renderTemplate(template.content)}
          </Typography>
          {template.content?.includes('action_button') && (
            <Box sx={{ mt: 1, display: 'flex', gap: 1 }}>
              <Button 
                variant="outlined" 
                size="small" 
                sx={{ textTransform: 'none', fontSize: '0.75rem' }}
              >
                View
              </Button>
            </Box>
          )}
        </Paper>
      </Box>
    );
  };
  
  // Render in-app notification preview
  const renderInAppPreview = () => {
    return (
      <Box sx={{ p: 2, bgcolor: 'background.paper', borderRadius: 1 }}>
        <Box sx={{ display: 'flex', alignItems: 'flex-start' }}>
          <Box sx={{ 
            width: 40, 
            height: 40, 
            bgcolor: 'primary.main', 
            color: 'white', 
            display: 'flex', 
            alignItems: 'center', 
            justifyContent: 'center', 
            borderRadius: '50%',
            mr: 2,
            flexShrink: 0
          }}>
            <Typography variant="subtitle2">
              {template.type?.split('.')[0]?.charAt(0).toUpperCase() || 'N'}
            </Typography>
          </Box>
          <Box sx={{ flexGrow: 1 }}>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <Typography variant="subtitle2">
                {renderTemplate(template.subject || template.type?.replace(/\./g, ' '))}
              </Typography>
              <Typography variant="caption" color="textSecondary">
                Just now
              </Typography>
            </Box>
            <Typography variant="body2" color="textSecondary" sx={{ mt: 0.5 }}>
              {renderTemplate(template.content)}
            </Typography>
          </Box>
        </Box>
      </Box>
    );
  };
  
  const renderPreview = () => {
    switch (template.channel) {
      case 'mail':
        return renderEmailPreview();
      case 'sms':
        return renderSmsPreview();
      case 'push':
        return renderPushPreview();
      case 'in_app':
        return renderInAppPreview();
      default:
        return (
          <Box sx={{ p: 3, textAlign: 'center' }}>
            <Typography color="textSecondary">No preview available for this channel</Typography>
          </Box>
        );
    }
  };
  
  return (
    <Dialog 
      open={open} 
      onClose={onClose}
      maxWidth="md"
      fullWidth
      PaperProps={{
        sx: {
          height: '80vh',
          maxHeight: 800
        }
      }}
    >
      <DialogTitle sx={{ m: 0, p: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <span>Preview Notification</span>
        <IconButton onClick={onClose} size="small">
          <CloseIcon />
        </IconButton>
      </DialogTitle>
      
      <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
        <Tabs 
          value={activeTab} 
          onChange={(e, newValue) => setActiveTab(newValue)}
          variant="scrollable"
          scrollButtons="auto"
        >
          <Tab label="Preview" />
          <Tab label="Sample Data" />
        </Tabs>
      </Box>
      
      <DialogContent dividers sx={{ p: 0, overflow: 'auto' }}>
        {activeTab === 0 ? (
          renderPreview()
        ) : (
          <Box sx={{ p: 3 }}>
            <Typography variant="h6" gutterBottom>Sample Data</Typography>
            <Paper variant="outlined" sx={{ p: 2, mb: 2 }}>
              <pre style={{ margin: 0, whiteSpace: 'pre-wrap', fontFamily: 'monospace' }}>
                {JSON.stringify(sampleData, null, 2)}
              </pre>
            </Paper>
            
            <Typography variant="h6" gutterBottom>Available Variables</Typography>
            <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(250px, 1fr))', gap: 2 }}>
              {variables.map((variable, index) => (
                <Paper key={index} variant="outlined" sx={{ p: 2 }}>
                  <Typography variant="subtitle2" color="primary">
                    {`{{${variable.name}}}`}
                  </Typography>
                  <Typography variant="body2" color="textSecondary">
                    {variable.description}
                  </Typography>
                </Paper>
              ))}
            </Box>
          </Box>
        )}
      </DialogContent>
      
      <DialogActions sx={{ p: 2, borderTop: '1px solid #eee' }}>
        <Button onClick={onClose} color="primary">
          Close
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default TemplatePreview;
