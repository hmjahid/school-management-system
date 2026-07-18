const express = require('express');
const router = express.Router();
const AboutContent = require('../models/AboutContent');
const multer = require('multer');
const path = require('path');
const fs = require('fs');
const auth = require('../middleware/auth');
const admin = require('../middleware/admin');

// Configure multer for file uploads
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    const uploadDir = 'public/uploads';
    if (!fs.existsSync(uploadDir)) {
      fs.mkdirSync(uploadDir, { recursive: true });
    }
    cb(null, uploadDir);
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1e9);
    const ext = path.extname(file.originalname);
    cb(null, `${file.fieldname}-${uniqueSuffix}${ext}`);
  }
});

const upload = multer({ 
  storage: storage,
  fileFilter: (req, file, cb) => {
    if (file.mimetype.startsWith('image/')) {
      cb(null, true);
    } else {
      cb(new Error('Only image files are allowed!'), false);
    }
  },
  limits: {
    fileSize: 5 * 1024 * 1024 // 5MB limit
  }
});

// @route   GET /api/website/about
// @desc    Get about page content
// @access  Public
router.get('/', async (req, res) => {
  try {
    const content = await AboutContent.getContent();
    res.json(content);
  } catch (err) {
    console.error('Error fetching about content:', err);
    res.status(500).json({ message: 'Server error' });
  }
});

// @route   GET /api/admin/website/about
// @desc    Get about page content for admin
// @access  Private/Admin
router.get('/admin', auth, admin, async (req, res) => {
  try {
    const content = await AboutContent.getContent();
    res.json(content);
  } catch (err) {
    console.error('Error fetching about content for admin:', err);
    res.status(500).json({ message: 'Server error' });
  }
});

// @route   PUT /api/admin/website/about
// @desc    Update about page content
// @access  Private/Admin
router.put('/admin', auth, admin, async (req, res) => {
  try {
    const updates = req.body;
    
    // Find the existing content
    let content = await AboutContent.findOne();
    
    if (!content) {
      // If no content exists, create a new one
      content = new AboutContent(updates);
    } else {
      // Otherwise, update the existing content
      Object.keys(updates).forEach(key => {
        if (typeof updates[key] === 'object' && updates[key] !== null && !Array.isArray(updates[key])) {
          // Handle nested objects
          content[key] = { ...content[key], ...updates[key] };
        } else if (Array.isArray(updates[key])) {
          // Handle arrays
          content[key] = [...updates[key]];
        } else {
          // Handle primitive values
          content[key] = updates[key];
        }
      });
      content.lastUpdated = Date.now();
    }
    
    await content.save();
    res.json(content);
  } catch (err) {
    console.error('Error updating about content:', err);
    res.status(500).json({ message: 'Server error' });
  }
});

// @route   POST /api/admin/upload
// @desc    Upload an image
// @access  Private/Admin
router.post('/admin/upload', [auth, admin, upload.single('image')], async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ message: 'No file uploaded' });
    }
    
    // Construct the URL path (relative to the public directory)
    const filePath = `/uploads/${req.file.filename}`;
    
    res.json({
      success: 1,
      file: {
        url: filePath,
        // You can add more file information here if needed
      }
    });
  } catch (err) {
    console.error('Error uploading file:', err);
    res.status(500).json({ message: 'Error uploading file', error: err.message });
  }
});

// @route   DELETE /api/admin/upload
// @desc    Delete an image
// @access  Private/Admin
router.delete('/admin/upload', auth, admin, async (req, res) => {
  try {
    const { filePath } = req.body;
    
    if (!filePath) {
      return res.status(400).json({ message: 'File path is required' });
    }
    
    // Remove the leading slash if present to get the relative path
    const relativePath = filePath.startsWith('/') ? filePath.substring(1) : filePath;
    const fullPath = path.join('public', relativePath);
    
    // Check if file exists
    if (fs.existsSync(fullPath)) {
      // Delete the file
      fs.unlink(fullPath, (err) => {
        if (err) {
          console.error('Error deleting file:', err);
          return res.status(500).json({ message: 'Error deleting file' });
        }
        res.json({ message: 'File deleted successfully' });
      });
    } else {
      res.status(404).json({ message: 'File not found' });
    }
  } catch (err) {
    console.error('Error deleting file:', err);
    res.status(500).json({ message: 'Server error' });
  }
});

module.exports = router;
