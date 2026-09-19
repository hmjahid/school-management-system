import React, { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { useSnackbar } from 'notistack';
import { 
  Box, 
  Typography, 
  Paper, 
  TextField, 
  Button, 
  Grid, 
  Divider, 
  FormControlLabel, 
  Switch,
  Card,
  CardContent,
  CardHeader,
  Avatar,
  IconButton,
  InputAdornment
} from '@mui/material';
import { 
  Save as SaveIcon, 
  Upload as UploadIcon,
  Link as LinkIcon,
  Email as EmailIcon,
  Phone as PhoneIcon,
  Language as WebsiteIcon,
  Facebook as FacebookIcon,
  Twitter as TwitterIcon,
  Instagram as InstagramIcon,
  LinkedIn as LinkedInIcon,
  YouTube as YouTubeIcon,
  AccessTime as TimeIcon,
  CalendarToday as CalendarIcon,
  Schedule as ClockIcon,
  LocationOn as LocationIcon,
  Public as PublicIcon
} from '@mui/icons-material';
import axios from '../../lib/axios';
import LoadingSpinner from '../../components/common/LoadingSpinner';

const WebsiteSettingsPage = () => {
  const { enqueueSnackbar } = useSnackbar();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [logoPreview, setLogoPreview] = useState('');
  const [faviconPreview, setFaviconPreview] = useState('');
  
  const { 
    register, 
    handleSubmit, 
    reset, 
    setValue, 
    watch, 
    formState: { errors } 
  } = useForm({
    defaultValues: {
      school_name: '',
      tagline: '',
      established_year: new Date().getFullYear(),
      address: '',
      city: '',
      state: '',
      country: '',
      postal_code: '',
      phone: '',
      email: '',
      website: '',
      facebook_url: '',
      twitter_url: '',
      instagram_url: '',
      linkedin_url: '',
      youtube_url: '',
      meta_title: '',
      meta_description: '',
      meta_keywords: '',
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
      date_format: 'F j, Y',
      time_format: 'g:i A',
      maintenance_mode: false,
      maintenance_message: ''
    }
  });

  // Watch for changes in maintenance mode
  const maintenanceMode = watch('maintenance_mode');

  // Fetch website settings
  useEffect(() => {
    const fetchSettings = async () => {
      try {
        setLoading(true);
        const response = await axios.get('/api/admin/website/settings');
        const settings = response.data.data;
        
        // Set form values
        Object.keys(settings).forEach(key => {
          if (key !== 'logo_url' && key !== 'favicon_url') {
            setValue(key, settings[key]);
          }
        });
        
        // Set preview images if they exist
        if (settings.logo_url) {
          setLogoPreview(settings.logo_url);
        }
        
        if (settings.favicon_url) {
          setFaviconPreview(settings.favicon_url);
        }
        
      } catch (error) {
        console.error('Error fetching website settings:', error);
        enqueueSnackbar('Failed to load website settings', { variant: 'error' });
      } finally {
        setLoading(false);
      }
    };
    
    fetchSettings();
  }, [setValue, enqueueSnackbar]);

  // Handle file upload
  const handleFileUpload = async (file, type) => {
    const formData = new FormData();
    formData.append('file', file);
    
    try {
      const response = await axios.post(`/api/admin/website/${type}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      
      // Update the preview and form value
      if (type === 'logo') {
        setLogoPreview(response.data.url);
        setValue('logo_path', response.data.path);
      } else {
        setFaviconPreview(response.data.url);
        setValue('favicon_path', response.data.path);
      }
      
      enqueueSnackbar(`${type === 'logo' ? 'Logo' : 'Favicon'} uploaded successfully`, { variant: 'success' });
      return response.data.path;
    } catch (error) {
      console.error(`Error uploading ${type}:`, error);
      enqueueSnackbar(`Failed to upload ${type}`, { variant: 'error' });
      return null;
    }
  };

  // Handle form submission
  const onSubmit = async (data) => {
    try {
      setSaving(true);
      
      // Handle file uploads if new files are selected
      if (data.logo instanceof File) {
        const logoPath = await handleFileUpload(data.logo, 'logo');
        if (!logoPath) return; // Stop if upload fails
        data.logo_path = logoPath;
      }
      
      if (data.favicon instanceof File) {
        const faviconPath = await handleFileUpload(data.favicon, 'favicon');
        if (!faviconPath) return; // Stop if upload fails
        data.favicon_path = faviconPath;
      }
      
      // Remove the file objects before sending
      const { logo, favicon, ...settingsData } = data;
      
      // Send the update request
      await axios.put('/api/admin/website/settings', settingsData);
      
      enqueueSnackbar('Settings saved successfully', { variant: 'success' });
    } catch (error) {
      console.error('Error saving settings:', error);
      enqueueSnackbar('Failed to save settings', { variant: 'error' });
    } finally {
      setSaving(false);
    }
  };

  // Render a form field with icon
  const renderField = (name, label, type = 'text', icon, options = {}) => (
    <Grid item xs={12} md={options.fullWidth ? 12 : 6}>
      <TextField
        fullWidth
        variant="outlined"
        label={label}
        type={type}
        {...register(name, options)}
        error={!!errors[name]}
        helperText={errors[name]?.message}
        InputProps={{
          startAdornment: icon && (
            <InputAdornment position="start">
              {icon}
            </InputAdornment>
          ),
        }}
      />
    </Grid>
  );

  if (loading) {
    return <LoadingSpinner />;
  }

  return (
    <Box>
      <Typography variant="h4" gutterBottom>
        Website Settings
      </Typography>
      
      <form onSubmit={handleSubmit(onSubmit)}>
        <Grid container spacing={3}>
          {/* School Information Card */}
          <Grid item xs={12}>
            <Card>
              <CardHeader 
                title="School Information" 
                subheader="Update your school's basic information"
                avatar={<Avatar><LocationIcon /></Avatar>}
              />
              <CardContent>
                <Grid container spacing={2}>
                  {/* Logo Upload */}
                  <Grid item xs={12} md={4}>
                    <Box display="flex" flexDirection="column" alignItems="center" mb={2}>
                      <Avatar 
                        src={logoPreview} 
                        alt="School Logo" 
                        sx={{ width: 150, height: 150, mb: 2 }}
                        variant="rounded"
                      />
                      <input
                        accept="image/*"
                        style={{ display: 'none' }}
                        id="logo-upload"
                        type="file"
                        onChange={(e) => {
                          const file = e.target.files[0];
                          if (file) {
                            setLogoPreview(URL.createObjectURL(file));
                            setValue('logo', file);
                          }
                        }}
                      />
                      <label htmlFor="logo-upload">
                        <Button
                          variant="outlined"
                          color="primary"
                          component="span"
                          startIcon={<UploadIcon />}
                        >
                          Upload Logo
                        </Button>
                      </label>
                      <Typography variant="caption" color="textSecondary" mt={1}>
                        Recommended size: 300x300px
                      </Typography>
                    </Box>
                  </Grid>
                  
                  {/* Favicon Upload */}
                  <Grid item xs={12} md={4}>
                    <Box display="flex" flexDirection="column" alignItems="center" mb={2}>
                      <Avatar 
                        src={faviconPreview || '/favicon.ico'} 
                        alt="Favicon" 
                        sx={{ width: 64, height: 64, mb: 2 }}
                        variant="rounded"
                      />
                      <input
                        accept="image/x-icon,image/png"
                        style={{ display: 'none' }}
                        id="favicon-upload"
                        type="file"
                        onChange={(e) => {
                          const file = e.target.files[0];
                          if (file) {
                            setFaviconPreview(URL.createObjectURL(file));
                            setValue('favicon', file);
                          }
                        }}
                      />
                      <label htmlFor="favicon-upload">
                        <Button
                          variant="outlined"
                          color="primary"
                          component="span"
                          startIcon={<UploadIcon />}
                        >
                          Upload Favicon
                        </Button>
                      </label>
                      <Typography variant="caption" color="textSecondary" mt={1}>
                        Recommended size: 32x32px or 64x64px
                      </Typography>
                    </Box>
                  </Grid>
                  
                  {/* School Info Fields */}
                  <Grid item xs={12} md={4}>
                    {renderField('school_name', 'School Name', 'text', null, { required: 'School name is required' })}
                    {renderField('tagline', 'Tagline', 'text', null)}
                    {renderField('established_year', 'Established Year', 'number', <CalendarIcon />, {
                      required: 'Established year is required',
                      min: { value: 1800, message: 'Invalid year' },
                      max: { value: new Date().getFullYear() + 1, message: 'Year cannot be in the future' }
                    })}
                  </Grid>
                </Grid>
              </CardContent>
            </Card>
          </Grid>
          
          {/* Contact Information Card */}
          <Grid item xs={12}>
            <Card>
              <CardHeader 
                title="Contact Information" 
                subheader="Update your school's contact details"
                avatar={<Avatar><EmailIcon /></Avatar>}
              />
              <CardContent>
                <Grid container spacing={2}>
                  {renderField('address', 'Address', 'text', <LocationIcon />, { required: 'Address is required' })}
                  {renderField('city', 'City', 'text', null, { required: 'City is required' })}
                  {renderField('state', 'State/Province', 'text', null, { required: 'State is required' })}
                  {renderField('country', 'Country', 'text', <PublicIcon />, { required: 'Country is required' })}
                  {renderField('postal_code', 'Postal Code', 'text', null, { required: 'Postal code is required' })}
                  {renderField('phone', 'Phone Number', 'tel', <PhoneIcon />, { required: 'Phone number is required' })}
                  {renderField('email', 'Email', 'email', <EmailIcon />, { 
                    required: 'Email is required',
                    pattern: {
                      value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                      message: 'Invalid email address'
                    }
                  })}
                  {renderField('website', 'Website', 'url', <WebsiteIcon />, {
                    pattern: {
                      value: /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([/\w .-]*)*\/?$/,
                      message: 'Please enter a valid URL'
                    }
                  })}
                </Grid>
              </CardContent>
            </Card>
          </Grid>
          
          {/* Social Media Card */}
          <Grid item xs={12}>
            <Card>
              <CardHeader 
                title="Social Media" 
                subheader="Update your social media links"
                avatar={<Avatar><LinkIcon /></Avatar>}
              />
              <CardContent>
                <Grid container spacing={2}>
                  {renderField('facebook_url', 'Facebook URL', 'url', <FacebookIcon color="primary" />)}
                  {renderField('twitter_url', 'Twitter URL', 'url', <TwitterIcon color="info" />)}
                  {renderField('instagram_url', 'Instagram URL', 'url', <InstagramIcon color="secondary" />)}
                  {renderField('linkedin_url', 'LinkedIn URL', 'url', <LinkedInIcon color="primary" />)}
                  {renderField('youtube_url', 'YouTube URL', 'url', <YouTubeIcon color="error" />)}
                </Grid>
              </CardContent>
            </Card>
          </Grid>
          
          {/* SEO Settings Card */}
          <Grid item xs={12}>
            <Card>
              <CardHeader 
                title="SEO Settings" 
                subheader="Configure search engine optimization settings"
                avatar={<Avatar>SEO</Avatar>}
              />
              <CardContent>
                <Grid container spacing={2}>
                  {renderField('meta_title', 'Meta Title', 'text', null, {
                    maxLength: { value: 60, message: 'Title should be 60 characters or less' }
                  })}
                  <Grid item xs={12}>
                    <TextField
                      fullWidth
                      multiline
                      rows={3}
                      variant="outlined"
                      label="Meta Description"
                      {...register('meta_description', {
                        maxLength: { value: 160, message: 'Description should be 160 characters or less' }
                      })}
                      error={!!errors.meta_description}
                      helperText={errors.meta_description?.message}
                    />
                  </Grid>
                  <Grid item xs={12}>
                    <TextField
                      fullWidth
                      variant="outlined"
                      label="Meta Keywords"
                      {...register('meta_keywords')}
                      helperText="Separate keywords with commas"
                    />
                  </Grid>
                </Grid>
              </CardContent>
            </Card>
          </Grid>
          
          {/* System Settings Card */}
          <Grid item xs={12}>
            <Card>
              <CardHeader 
                title="System Settings" 
                subheader="Configure system preferences"
                avatar={<Avatar><TimeIcon /></Avatar>}
              />
              <CardContent>
                <Grid container spacing={2}>
                  {renderField('timezone', 'Timezone', 'text', <PublicIcon />, { required: 'Timezone is required' })}
                  {renderField('date_format', 'Date Format', 'text', <CalendarIcon />, { required: 'Date format is required' })}
                  {renderField('time_format', 'Time Format', 'text', <ClockIcon />, { required: 'Time format is required' })}
                  
                  <Grid item xs={12}>
                    <FormControlLabel
                      control={
                        <Switch
                          checked={maintenanceMode}
                          onChange={(e) => setValue('maintenance_mode', e.target.checked)}
                          color="primary"
                        />
                      }
                      label="Maintenance Mode"
                    />
                    {maintenanceMode && (
                      <TextField
                        fullWidth
                        multiline
                        rows={3}
                        variant="outlined"
                        label="Maintenance Message"
                        {...register('maintenance_message')}
                        sx={{ mt: 2 }}
                      />
                    )}
                  </Grid>
                </Grid>
              </CardContent>
            </Card>
          </Grid>
          
          {/* Save Button */}
          <Grid item xs={12}>
            <Box display="flex" justifyContent="flex-end" mt={2}>
              <Button
                type="submit"
                variant="contained"
                color="primary"
                size="large"
                startIcon={<SaveIcon />}
                disabled={saving}
              >
                {saving ? 'Saving...' : 'Save Settings'}
              </Button>
            </Box>
          </Grid>
        </Grid>
      </form>
    </Box>
  );
};

export default WebsiteSettingsPage;
