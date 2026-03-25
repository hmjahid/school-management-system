import React from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  List,
  ListItem,
  ListItemText,
  ListItemSecondaryAction,
  IconButton,
  TextField,
  InputAdornment,
  Paper,
  Typography,
  Divider,
  Box,
  Tooltip
} from '@mui/material';
import { Close as CloseIcon, ContentCopy as CopyIcon } from '@mui/icons-material';

const TemplateVariablesDialog = ({ open, onClose, onSelectVariable, variables = [] }) => {
  const [searchTerm, setSearchTerm] = React.useState('');
  
  const filteredVariables = variables.filter(variable => 
    variable.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    variable.description.toLowerCase().includes(searchTerm.toLowerCase())
  );
  
  const handleCopyVariable = (variable) => {
    navigator.clipboard.writeText(`{{${variable.name}}}`);
    if (onSelectVariable) {
      onSelectVariable(variable.name);
    }
  };
  
  return (
    <Dialog 
      open={open} 
      onClose={onClose}
      maxWidth="sm"
      fullWidth
    >
      <DialogTitle sx={{ m: 0, p: 2, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <span>Template Variables</span>
        <IconButton onClick={onClose} size="small">
          <CloseIcon />
        </IconButton>
      </DialogTitle>
      
      <DialogContent dividers>
        <Box sx={{ mb: 2 }}>
          <TextField
            fullWidth
            variant="outlined"
            size="small"
            placeholder="Search variables..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            InputProps={{
              startAdornment: (
                <InputAdornment position="start">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="#757575" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    <path d="M21 21L16.65 16.65" stroke="#757575" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </InputAdornment>
              ),
            }}
          />
        </Box>
        
        <Paper variant="outlined" sx={{ maxHeight: 400, overflow: 'auto' }}>
          {filteredVariables.length > 0 ? (
            <List dense>
              {filteredVariables.map((variable, index) => (
                <React.Fragment key={index}>
                  <ListItem>
                    <ListItemText
                      primary={
                        <Box sx={{ display: 'flex', alignItems: 'center' }}>
                          <Box component="code" sx={{ 
                            bgcolor: 'rgba(0, 0, 0, 0.04)', 
                            p: '2px 6px', 
                            borderRadius: 1,
                            fontFamily: 'monospace',
                            fontSize: '0.8rem'
                          }}>
                            {`{{${variable.name}}}`}
                          </Box>
                        </Box>
                      }
                      secondary={
                        <Typography variant="body2" color="text.secondary">
                          {variable.description}
                        </Typography>
                      }
                    />
                    <ListItemSecondaryAction>
                      <Tooltip title="Insert variable">
                        <IconButton 
                          edge="end" 
                          aria-label="insert variable"
                          onClick={() => handleCopyVariable(variable)}
                        >
                          <CopyIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                    </ListItemSecondaryAction>
                  </ListItem>
                  {index < filteredVariables.length - 1 && <Divider component="li" />}
                </React.Fragment>
              ))}
            </List>
          ) : (
            <Box sx={{ p: 3, textAlign: 'center' }}>
              <Typography variant="body2" color="text.secondary">
                No variables found matching "{searchTerm}"
              </Typography>
            </Box>
          )}
        </Paper>
        
        <Box sx={{ mt: 3, p: 2, bgcolor: 'rgba(0, 0, 0, 0.02)', borderRadius: 1 }}>
          <Typography variant="subtitle2" gutterBottom>
            How to use variables
          </Typography>
          <Typography variant="body2" color="text.secondary" paragraph>
            Wrap variable names in double curly braces to use them in your templates, like this: <code style={{ background: 'rgba(0,0,0,0.05)', padding: '2px 4px', borderRadius: 3 }}>{'{{user_name}}'}</code>
          </Typography>
          
          <Typography variant="subtitle2" gutterBottom>
            Special Format: Action Buttons
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Create clickable buttons in your emails using: <code style={{ background: 'rgba(0,0,0,0.05)', padding: '2px 4px', borderRadius: 3 }}>{'{{action_button|Button Text|https://example.com}}'}</code>
          </Typography>
        </Box>
      </DialogContent>
      
      <DialogActions sx={{ p: 2, borderTop: '1px solid #eee' }}>
        <Button onClick={onClose} color="primary">
          Close
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default TemplateVariablesDialog;
