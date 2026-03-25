import React, { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { FiSave, FiUpload, FiLink, FiPlus, FiTrash2, FiChevronDown, FiChevronRight, FiImage } from 'react-icons/fi';
import { cmsService } from '../../../../services/cmsService';
import LoadingSpinner from '../../../../components/common/LoadingSpinner';
import ConfirmationModal from '../../../../components/common/ConfirmationModal';

const HeaderFooterEditor = () => {
  const [activeTab, setActiveTab] = useState('header');
  const [isSaving, setIsSaving] = useState(false);
  const [formData, setFormData] = useState({
    // Header
    logo: {
      url: '',
      width: 150,
      height: 50,
      alt: 'Logo',
    },
    topBar: {
      enabled: true,
      text: 'Welcome to our school',
      contactInfo: [
        { type: 'phone', label: '+1 234 567 890', value: 'tel:+1234567890' },
        { type: 'email', label: 'info@school.edu', value: 'mailto:info@school.edu' },
      ],
    },
    // Footer
    footer: {
      layout: '4-columns',
      backgroundColor: '#1a202c',
      textColor: '#e2e8f0',
      linkColor: '#e2e8f0',
      copyright: '© 2023 School Name. All rights reserved.',
    },
  });

  const handleInputChange = (section, field, value) => {
    setFormData(prev => ({
      ...prev,
      [section]: {
        ...prev[section],
        [field]: value
      }
    }));
  };

  const handleNestedChange = (section, subSection, field, value) => {
    setFormData(prev => ({
      ...prev,
      [section]: {
        ...prev[section],
        [subSection]: {
          ...prev[section][subSection],
          [field]: value
        }
      }
    }));
  };

  const saveSettings = async () => {
    setIsSaving(true);
    try {
      await new Promise(resolve => setTimeout(resolve, 1000));
      toast.success(`${activeTab === 'header' ? 'Header' : 'Footer'} settings saved`);
    } catch (error) {
      toast.error(`Failed to save ${activeTab} settings`);
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex border-b border-gray-200">
        <button
          onClick={() => setActiveTab('header')}
          className={`py-4 px-6 font-medium text-sm border-b-2 ${
            activeTab === 'header' 
              ? 'border-indigo-500 text-indigo-600' 
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          }`}
        >
          Header Settings
        </button>
        <button
          onClick={() => setActiveTab('footer')}
          className={`py-4 px-6 font-medium text-sm border-b-2 ${
            activeTab === 'footer' 
              ? 'border-indigo-500 text-indigo-600' 
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          }`}
        >
          Footer Settings
        </button>
      </div>

      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:px-6 flex justify-between items-center">
          <h3 className="text-lg leading-6 font-medium text-gray-900">
            {activeTab === 'header' ? 'Header' : 'Footer'} Configuration
          </h3>
          <button
            type="button"
            onClick={saveSettings}
            disabled={isSaving}
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            {isSaving ? 'Saving...' : 'Save Changes'}
          </button>
        </div>

        <div className="border-t border-gray-200 px-4 py-5 sm:p-6">
          {activeTab === 'header' ? (
            <HeaderSettings 
              formData={formData} 
              onInputChange={handleInputChange}
              onNestedChange={handleNestedChange}
            />
          ) : (
            <FooterSettings 
              formData={formData} 
              onInputChange={handleInputChange}
              onNestedChange={handleNestedChange}
            />
          )}
        </div>
      </div>
    </div>
  );
};

// Header Settings Component
const HeaderSettings = ({ formData, onInputChange, onNestedChange }) => {
  const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const fileUrl = URL.createObjectURL(file);
    onNestedChange('logo', 'logo', 'url', fileUrl);
  };

  return (
    <div className="space-y-6">
      <div>
        <h4 className="text-md font-medium text-gray-900 mb-4">Logo Settings</h4>
        <div className="flex items-center space-x-4">
          <div className="flex-shrink-0 h-20 w-20 rounded-md overflow-hidden border border-gray-300">
            {formData.logo.url ? (
              <img
                src={formData.logo.url}
                alt={formData.logo.alt}
                className="h-full w-full object-contain"
              />
            ) : (
              <div className="h-full w-full bg-gray-100 flex items-center justify-center text-gray-400">
                <FiImage className="h-8 w-8" />
              </div>
            )}
          </div>
          <div>
            <label
              htmlFor="logo-upload"
              className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer"
            >
              <FiUpload className="-ml-1 mr-2 h-4 w-4" />
              {formData.logo.url ? 'Change' : 'Upload'} Logo
            </label>
            <input
              id="logo-upload"
              type="file"
              className="sr-only"
              onChange={handleFileUpload}
              accept="image/*"
            />
          </div>
        </div>
      </div>

      <div className="pt-4">
        <div className="flex items-start">
          <div className="flex items-center h-5">
            <input
              id="topbar-enabled"
              type="checkbox"
              checked={formData.topBar.enabled}
              onChange={(e) => onNestedChange('topBar', 'topBar', 'enabled', e.target.checked)}
              className="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
            />
          </div>
          <div className="ml-3 text-sm">
            <label htmlFor="topbar-enabled" className="font-medium text-gray-700">
              Show Top Bar
            </label>
            <p className="text-gray-500">Display a top bar above the main header</p>
          </div>
        </div>
      </div>
    </div>
  );
};

// Footer Settings Component
const FooterSettings = ({ formData, onInputChange, onNestedChange }) => {
  return (
    <div className="space-y-6">
      <div>
        <label htmlFor="footer-layout" className="block text-sm font-medium text-gray-700">
          Layout
        </label>
        <select
          id="footer-layout"
          value={formData.footer.layout}
          onChange={(e) => onNestedChange('footer', 'footer', 'layout', e.target.value)}
          className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
        >
          <option value="1-column">1 Column</option>
          <option value="2-columns">2 Columns</option>
          <option value="3-columns">3 Columns</option>
          <option value="4-columns">4 Columns</option>
        </select>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label htmlFor="footer-bg-color" className="block text-sm font-medium text-gray-700">
            Background Color
          </label>
          <div className="mt-1 flex rounded-md shadow-sm">
            <input
              type="text"
              id="footer-bg-color"
              value={formData.footer.backgroundColor}
              onChange={(e) => onNestedChange('footer', 'footer', 'backgroundColor', e.target.value)}
              className="focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md sm:text-sm border-gray-300"
              placeholder="#1a202c"
            />
            <input
              type="color"
              value={formData.footer.backgroundColor}
              onChange={(e) => onNestedChange('footer', 'footer', 'backgroundColor', e.target.value)}
              className="h-10 w-10 p-1 border border-l-0 border-gray-300 rounded-r-md focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
        </div>

        <div>
          <label htmlFor="footer-text-color" className="block text-sm font-medium text-gray-700">
            Text Color
          </label>
          <div className="mt-1 flex rounded-md shadow-sm">
            <input
              type="text"
              id="footer-text-color"
              value={formData.footer.textColor}
              onChange={(e) => onNestedChange('footer', 'footer', 'textColor', e.target.value)}
              className="focus:ring-indigo-500 focus:border-indigo-500 block w-full rounded-md sm:text-sm border-gray-300"
              placeholder="#e2e8f0"
            />
            <input
              type="color"
              value={formData.footer.textColor}
              onChange={(e) => onNestedChange('footer', 'footer', 'textColor', e.target.value)}
              className="h-10 w-10 p-1 border border-l-0 border-gray-300 rounded-r-md focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
        </div>
      </div>

      <div>
        <label htmlFor="copyright" className="block text-sm font-medium text-gray-700">
          Copyright Text
        </label>
        <input
          type="text"
          id="copyright"
          value={formData.footer.copyright}
          onChange={(e) => onNestedChange('footer', 'footer', 'copyright', e.target.value)}
          className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          placeholder="© 2023 School Name. All rights reserved."
        />
      </div>
    </div>
  );
};

export default HeaderFooterEditor;
