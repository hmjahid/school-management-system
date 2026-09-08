import React, { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { FaSave, FaUpload, FaSpinner } from 'react-icons/fa';
import { toast } from 'react-toastify';
import axios from 'axios';

const AboutContentPage = () => {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [currentTab, setCurrentTab] = useState('hero');
  const [imagePreview, setImagePreview] = useState({
    hero: '',
    history: '',
    principal: '',
    vicePrincipal: '',
    headAcademics: ''
  });

  const { register, handleSubmit, reset, formState: { errors } } = useForm({
    defaultValues: {
      heroTitle: '',
      heroSubtitle: '',
      mission: '',
      vision: '',
      history: '',
      values: [
        { title: 'Excellence', description: '' },
        { title: 'Community', description: '' },
        { title: 'Lifelong Learning', description: '' }
      ],
      stats: [
        { number: '25+', label: 'Years of Excellence' },
        { number: '1000+', label: 'Students Enrolled' },
        { number: '80+', label: 'Qualified Staff' },
        { number: '95%', label: 'Graduation Rate' }
      ],
      leadership: [
        {
          name: 'Dr. Sarah Johnson',
          role: 'Principal',
          bio: 'With over 20 years of experience in education...',
          image: ''
        },
        {
          name: 'Michael Chen',
          role: 'Vice Principal',
          bio: 'A passionate educator with expertise in...',
          image: ''
        },
        {
          name: 'Priya Patel',
          role: 'Head of Academics',
          bio: 'Committed to fostering innovative teaching methods...',
          image: ''
        }
      ],
      contact: {
        address: '123 Education Street\nCity, State 12345',
        phone: '+1 (555) 123-4567',
        email: 'info@schoolname.edu',
        admissionEmail: 'admissions@schoolname.edu',
        hours: 'Mon-Fri, 8:00 AM - 4:00 PM'
      }
    }
  });

  // Fetch existing content on component mount
  useEffect(() => {
    const fetchContent = async () => {
      try {
        const response = await axios.get('/api/admin/website/about');
        if (response.data) {
          reset(response.data);
          // Set image previews if they exist
          if (response.data.images) {
            setImagePreview(response.data.images);
          }
        }
      } catch (error) {
        console.error('Error fetching content:', error);
        toast.error('Failed to load content');
      } finally {
        setLoading(false);
      }
    };

    fetchContent();
  }, [reset]);

  const handleImageUpload = async (e, field) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);
    formData.append('field', field);

    setUploading(true);
    try {
      const response = await axios.post('/api/admin/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });

      // Update image preview
      setImagePreview(prev => ({
        ...prev,
        [field]: URL.createObjectURL(file)
      }));

      toast.success('Image uploaded successfully');
      return response.data.path;
    } catch (error) {
      console.error('Error uploading image:', error);
      toast.error('Failed to upload image');
      return null;
    } finally {
      setUploading(false);
    }
  };

  const onSubmit = async (data) => {
    setSaving(true);
    try {
      // Include image URLs in the data
      const contentWithImages = {
        ...data,
        images: imagePreview
      };

      await axios.put('/api/admin/website/about', contentWithImages);
      toast.success('Content saved successfully');
    } catch (error) {
      console.error('Error saving content:', error);
      toast.error('Failed to save content');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <FaSpinner className="animate-spin text-4xl text-blue-600" />
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-8">Manage About Page</h1>
      
      {/* Navigation Tabs */}
      <div className="border-b border-gray-200 mb-6">
        <nav className="flex space-x-4" aria-label="Tabs">
          {['Hero', 'Mission & Vision', 'History', 'Values', 'Statistics', 'Leadership', 'Contact'].map((tab) => (
            <button
              key={tab}
              onClick={() => setCurrentTab(tab.toLowerCase().replace(/\s+/g, ''))}
              className={`px-4 py-2 font-medium text-sm rounded-t-lg ${
                currentTab === tab.toLowerCase().replace(/\s+/g, '')
                  ? 'bg-blue-50 text-blue-700 border-t-2 border-blue-500'
                  : 'text-gray-500 hover:text-gray-700'
              }`}
            >
              {tab}
            </button>
          ))}
        </nav>
      </div>

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-8">
        {/* Hero Section */}
        {currentTab === 'hero' && (
          <div className="bg-white p-6 rounded-lg shadow">
            <h2 className="text-xl font-semibold mb-4">Hero Section</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <div className="mb-4">
                  <label className="block text-sm font-medium text-gray-700 mb-1">Hero Image</label>
                  <div className="mt-1 flex items-center">
                    <input
                      type="file"
                      onChange={(e) => handleImageUpload(e, 'hero')}
                      className="sr-only"
                      id="hero-upload"
                      accept="image/*"
                    />
                    <label
                      htmlFor="hero-upload"
                      className="bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer"
                    >
                      {uploading ? 'Uploading...' : 'Upload Image'}
                    </label>
                    {imagePreview.hero && (
                      <span className="ml-3 text-sm text-gray-500">Image uploaded</span>
                    )}
                  </div>
                  {imagePreview.hero && (
                    <div className="mt-2">
                      <img
                        src={imagePreview.hero}
                        alt="Hero preview"
                        className="h-40 object-cover rounded"
                      />
                    </div>
                  )}
                </div>
              </div>
              <div>
                <div className="mb-4">
                  <label className="block text-sm font-medium text-gray-700 mb-1">Title</label>
                  <input
                    type="text"
                    {...register('heroTitle', { required: 'Title is required' })}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  />
                  {errors.heroTitle && (
                    <p className="mt-1 text-sm text-red-600">{errors.heroTitle.message}</p>
                  )}
                </div>
                <div className="mb-4">
                  <label className="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                  <textarea
                    {...register('heroSubtitle', { required: 'Subtitle is required' })}
                    rows={3}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  />
                  {errors.heroSubtitle && (
                    <p className="mt-1 text-sm text-red-600">{errors.heroSubtitle.message}</p>
                  )}
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Mission & Vision */}
        {currentTab === 'mission&vision' && (
          <div className="bg-white p-6 rounded-lg shadow">
            <h2 className="text-xl font-semibold mb-4">Mission & Vision</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 className="text-lg font-medium mb-2">Mission</h3>
                <textarea
                  {...register('mission', { required: 'Mission is required' })}
                  rows={6}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                />
                {errors.mission && (
                  <p className="mt-1 text-sm text-red-600">{errors.mission.message}</p>
                )}
              </div>
              <div>
                <h3 className="text-lg font-medium mb-2">Vision</h3>
                <textarea
                  {...register('vision', { required: 'Vision is required' })}
                  rows={6}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                />
                {errors.vision && (
                  <p className="mt-1 text-sm text-red-600">{errors.vision.message}</p>
                )}
              </div>
            </div>
          </div>
        )}

        {/* History */}
        {currentTab === 'history' && (
          <div className="bg-white p-6 rounded-lg shadow">
            <h2 className="text-xl font-semibold mb-4">School History</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">History Image</label>
                <div className="mt-1 flex items-center">
                  <input
                    type="file"
                    onChange={(e) => handleImageUpload(e, 'history')}
                    className="sr-only"
                    id="history-upload"
                    accept="image/*"
                  />
                  <label
                    htmlFor="history-upload"
                    className="bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer"
                  >
                    {uploading ? 'Uploading...' : 'Upload Image'}
                  </label>
                  {imagePreview.history && (
                    <span className="ml-3 text-sm text-gray-500">Image uploaded</span>
                  )}
                </div>
                {imagePreview.history && (
                  <div className="mt-2">
                    <img
                      src={imagePreview.history}
                      alt="History preview"
                      className="h-40 w-full object-cover rounded"
                    />
                  </div>
                )}
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">History Text</label>
                <textarea
                  {...register('history', { required: 'History text is required' })}
                  rows={8}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                />
                {errors.history && (
                  <p className="mt-1 text-sm text-red-600">{errors.history.message}</p>
                )}
              </div>
            </div>
          </div>
        )}

        {/* Values */}
        {currentTab === 'values' && (
          <div className="bg-white p-6 rounded-lg shadow">
            <h2 className="text-xl font-semibold mb-4">Core Values</h2>
            <div className="space-y-6">
              {[0, 1, 2].map((index) => (
                <div key={index} className="border-b border-gray-200 pb-6 last:border-0 last:pb-0">
                  <h3 className="text-lg font-medium mb-2">Value {index + 1}</h3>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Title</label>
                      <input
                        type="text"
                        {...register(`values.${index}.title`, { required: 'Title is required' })}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                      />
                      {errors.values?.[index]?.title && (
                        <p className="mt-1 text-sm text-red-600">{errors.values[index].title.message}</p>
                      )}
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                      <textarea
                        {...register(`values.${index}.description`, { required: 'Description is required' })}
                        rows={3}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                      />
                      {errors.values?.[index]?.description && (
                        <p className="mt-1 text-sm text-red-600">{errors.values[index].description.message}</p>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Statistics */}
        {currentTab === 'statistics' && (
          <div className="bg-white p-6 rounded-lg shadow">
            <h2 className="text-xl font-semibold mb-4">Statistics</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {[0, 1, 2, 3].map((index) => (
                <div key={index} className="border border-gray-200 rounded-lg p-4">
                  <h3 className="text-lg font-medium mb-3">Statistic {index + 1}</h3>
                  <div className="space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Number/Value</label>
                      <input
                        type="text"
                        {...register(`stats.${index}.number`, { required: 'Number is required' })}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                      />
                      {errors.stats?.[index]?.number && (
                        <p className="mt-1 text-sm text-red-600">{errors.stats[index].number.message}</p>
                      )}
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Label</label>
                      <input
                        type="text"
                        {...register(`stats.${index}.label`, { required: 'Label is required' })}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                      />
                      {errors.stats?.[index]?.label && (
                        <p className="mt-1 text-sm text-red-600">{errors.stats[index].label.message}</p>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Leadership */}
        {currentTab === 'leadership' && (
          <div className="bg-white p-6 rounded-lg shadow">
            <h2 className="text-xl font-semibold mb-4">Leadership Team</h2>
            <div className="space-y-8">
              {[0, 1, 2].map((index) => (
                <div key={index} className="border-b border-gray-200 pb-8 last:border-0 last:pb-0">
                  <h3 className="text-lg font-medium mb-4">Team Member {index + 1}</h3>
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-1">Profile Image</label>
                      <div className="mt-1">
                        <input
                          type="file"
                          onChange={(e) => handleImageUpload(e, index === 0 ? 'principal' : index === 1 ? 'vicePrincipal' : 'headAcademics')}
                          className="sr-only"
                          id={`leader-${index}-upload`}
                          accept="image/*"
                        />
                        <label
                          htmlFor={`leader-${index}-upload`}
                          className="block w-full bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer text-center"
                        >
                          {uploading ? 'Uploading...' : 'Upload Image'}
                        </label>
                        {imagePreview[index === 0 ? 'principal' : index === 1 ? 'vicePrincipal' : 'headAcademics'] && (
                          <div className="mt-2">
                            <img
                              src={imagePreview[index === 0 ? 'principal' : index === 1 ? 'vicePrincipal' : 'headAcademics']}
                              alt={`Leader ${index + 1} preview`}
                              className="h-32 w-32 object-cover rounded-full mx-auto"
                            />
                          </div>
                        )}
                      </div>
                    </div>
                    <div className="md:col-span-2 space-y-4">
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Name</label>
                        <input
                          type="text"
                          {...register(`leadership.${index}.name`, { required: 'Name is required' })}
                          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        />
                        {errors.leadership?.[index]?.name && (
                          <p className="mt-1 text-sm text-red-600">{errors.leadership[index].name.message}</p>
                        )}
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Role/Position</label>
                        <input
                          type="text"
                          {...register(`leadership.${index}.role`, { required: 'Role is required' })}
                          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        />
                        {errors.leadership?.[index]?.role && (
                          <p className="mt-1 text-sm text-red-600">{errors.leadership[index].role.message}</p>
                        )}
                      </div>
                      <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                        <textarea
                          {...register(`leadership.${index}.bio`, { required: 'Bio is required' })}
                          rows={3}
                          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        />
                        {errors.leadership?.[index]?.bio && (
                          <p className="mt-1 text-sm text-red-600">{errors.leadership[index].bio.message}</p>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Contact */}
        {currentTab === 'contact' && (
          <div className="bg-white p-6 rounded-lg shadow">
            <h2 className="text-xl font-semibold mb-4">Contact Information</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">School Address</label>
                <textarea
                  {...register('contact.address', { required: 'Address is required' })}
                  rows={3}
                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                />
                {errors.contact?.address && (
                  <p className="mt-1 text-sm text-red-600">{errors.contact.address.message}</p>
                )}
              </div>
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                  <input
                    type="text"
                    {...register('contact.phone', { required: 'Phone number is required' })}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  />
                  {errors.contact?.phone && (
                    <p className="mt-1 text-sm text-red-600">{errors.contact.phone.message}</p>
                  )}
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                  <input
                    type="email"
                    {...register('contact.email', { 
                      required: 'Email is required',
                      pattern: {
                        value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                        message: 'Invalid email address'
                      }
                    })}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  />
                  {errors.contact?.email && (
                    <p className="mt-1 text-sm text-red-600">{errors.contact.email.message}</p>
                  )}
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Admissions Email</label>
                  <input
                    type="email"
                    {...register('contact.admissionEmail', { 
                      required: 'Admissions email is required',
                      pattern: {
                        value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                        message: 'Invalid email address'
                      }
                    })}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  />
                  {errors.contact?.admissionEmail && (
                    <p className="mt-1 text-sm text-red-600">{errors.contact.admissionEmail.message}</p>
                  )}
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Office Hours</label>
                  <input
                    type="text"
                    {...register('contact.hours', { required: 'Office hours are required' })}
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  />
                  {errors.contact?.hours && (
                    <p className="mt-1 text-sm text-red-600">{errors.contact.hours.message}</p>
                  )}
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Save Button */}
        <div className="flex justify-end">
          <button
            type="submit"
            disabled={saving}
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {saving ? (
              <>
                <FaSpinner className="animate-spin -ml-1 mr-2 h-4 w-4" />
                Saving...
              </>
            ) : (
              <>
                <FaSave className="-ml-1 mr-2 h-4 w-4" />
                Save Changes
              </>
            )}
          </button>
        </div>
      </form>
    </div>
  );
};

export default AboutContentPage;
