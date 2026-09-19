import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Formik, Form, Field, ErrorMessage, FormikProvider, useFormik } from 'formik';
import * as Yup from 'yup';
import { toast } from 'react-hot-toast';
import { FiSave, FiArrowLeft, FiEye, FiImage, FiX } from 'react-icons/fi';
import RichTextEditor from '../../../../components/editor/RichTextEditor';
import LoadingSpinner from '../../../../components/common/LoadingSpinner';
import MediaLibrary from '../../media/MediaLibrary';
import { pageSchema } from '../../../../utils/validationSchemas';
import { cmsService } from '../../../../services/cmsService';

const PageEditor = () => {
  const { id } = useParams();
  const isNew = !id;
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [showMediaLibrary, setShowMediaLibrary] = useState(false);
  const [mediaSelectionMode, setMediaSelectionMode] = useState(null);

  // Initial form values
  const initialValues = {
    title: '',
    slug: '',
    content: '',
    excerpt: '',
    status: 'draft',
    featuredImage: '',
    seoTitle: '',
    seoDescription: '',
    seoKeywords: '',
  };

  // Fetch page data if editing
  const { 
    data: pageData, 
    isLoading: isLoadingPage, 
    isError: isPageError,
    error: pageError 
  } = useQuery({
    queryKey: ['cms-page', id],
    queryFn: () => cmsService.getPage(id),
    enabled: !isNew,
    retry: 1,
  });

  // Create or update page mutation
  const savePageMutation = useMutation({
    mutationFn: (data) => 
      isNew 
        ? cmsService.createPage(data)
        : cmsService.updatePage(id, data),
    onSuccess: () => {
      const action = isNew ? 'created' : 'updated';
      toast.success(`Page ${action} successfully`);
      queryClient.invalidateQueries({ queryKey: ['cms-pages'] });
      navigate('/dashboard/cms/pages');
    },
    onError: (error) => {
      toast.error(error.message || 'Failed to save page');
    },
  });

  // Handle form submission
  const handleSubmit = async (values, { setSubmitting }) => {
    try {
      const pageData = {
        ...values,
        seo: {
          title: values.seoTitle,
          description: values.seoDescription,
          keywords: values.seoKeywords.split(',').map(k => k.trim()).filter(k => k),
        }
      };
      
      // Remove the SEO fields from the root level
      const { seoTitle, seoDescription, seoKeywords, ...cleanData } = pageData;
      
      await savePageMutation.mutateAsync(cleanData);
    } catch (error) {
      console.error('Error saving page:', error);
    } finally {
      setSubmitting(false);
    }
  };

  // Handle media selection from media library
  const handleMediaSelect = (media) => {
    if (media && media.url) {
      formik.setFieldValue('featuredImage', media.url);
    }
    setShowMediaLibrary(false);
    setMediaSelectionMode(null);
  };

  // Initialize Formik
  const formik = useFormik({
    initialValues,
    validationSchema: pageSchema,
    onSubmit: handleSubmit,
    enableReinitialize: true,
  });

  // Auto-generate slug from title
  const handleTitleChange = (e) => {
    const title = e.target.value;
    formik.setFieldValue('title', title);
    
    // Only auto-generate slug if it's a new page or if the slug hasn't been modified
    if (isNew || !formik.touched.slug) {
      const slug = title
        .toLowerCase()
        .replace(/[^\w\s-]/g, '') // Remove non-word chars
        .replace(/\s+/g, '-') // Replace spaces with -
        .replace(/--+/g, '-') // Replace multiple - with single -
        .trim();
      formik.setFieldValue('slug', slug);
    }
  };

  // Set form data when page data is loaded
  useEffect(() => {
    if (pageData) {
      formik.setValues({
        ...initialValues,
        ...pageData,
        seoTitle: pageData.seo?.title || '',
        seoDescription: pageData.seo?.description || '',
        seoKeywords: pageData.seo?.keywords?.join(', ') || '',
        featuredImage: pageData.featuredImage || '',
      });
    }
  }, [pageData]);

  if (isLoadingPage) {
    return <LoadingSpinner />;
  }

  if (isPageError) {
    return (
      <div className="bg-red-50 border-l-4 border-red-400 p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <FiX className="h-5 w-5 text-red-400" aria-hidden="true" />
          </div>
          <div className="ml-3">
            <p className="text-sm text-red-700">
              {pageError.message || 'Failed to load page. The page may not exist or you may not have permission to view it.'}
            </p>
            <div className="mt-4">
              <button
                type="button"
                onClick={() => navigate('/dashboard/cms/pages')}
                className="text-sm font-medium text-red-700 hover:text-red-600 focus:outline-none"
              >
                &larr; Back to Pages
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <FormikProvider value={formik}>
      <Form className="space-y-6">
        <div className="flex justify-between items-center mb-6">
          <h2 className="text-2xl font-bold text-gray-900">
            {isNew ? 'Create New Page' : 'Edit Page'}
          </h2>
          <div className="flex space-x-3">
            <button
              type="button"
              onClick={() => navigate('/dashboard/cms/pages')}
              className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <FiArrowLeft className="-ml-1 mr-2 h-4 w-4" />
              Cancel
            </button>
            <button
              type="submit"
              disabled={formik.isSubmitting || !formik.dirty || !formik.isValid}
              className={`inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white ${
                formik.isSubmitting || !formik.dirty || !formik.isValid
                  ? 'bg-indigo-400 cursor-not-allowed'
                  : 'bg-indigo-600 hover:bg-indigo-700'
              } focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500`}
            >
              {formik.isSubmitting ? (
                <>
                  <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Saving...
                </>
              ) : (
                <>
                  <FiSave className="-ml-1 mr-2 h-4 w-4" />
                  {isNew ? 'Publish' : 'Update'}
                </>
              )}
            </button>
            {!isNew && (
              <button
                type="button"
                onClick={() => window.open(`/pages/${formik.values.slug}`, '_blank')}
                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                title="View page"
              >
                <FiEye className="-ml-1 mr-2 h-4 w-4" />
                Preview
              </button>
            )}
          </div>
        </div>

        <div className="bg-white shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <div className="space-y-6">
              {/* Title */}
              <div>
                <label htmlFor="title" className="block text-sm font-medium text-gray-700">
                  Title <span className="text-red-500">*</span>
                </label>
                <Field
                  type="text"
                  id="title"
                  name="title"
                  value={formik.values.title}
                  onChange={(e) => {
                    handleTitleChange(e);
                    formik.handleChange(e);
                  }}
                  onBlur={formik.handleBlur}
                  className={`mt-1 block w-full border ${
                    formik.touched.title && formik.errors.title ? 'border-red-300' : 'border-gray-300'
                  } rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm`}
                />
                <ErrorMessage name="title" component="div" className="mt-1 text-sm text-red-600" />
              </div>

              {/* Slug */}
              <div>
                <label htmlFor="slug" className="block text-sm font-medium text-gray-700">
                  URL Slug <span className="text-red-500">*</span>
                </label>
                <div className="mt-1 flex rounded-md shadow-sm">
                  <span className="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                    /pages/
                  </span>
                  <Field
                    type="text"
                    id="slug"
                    name="slug"
                    className={`flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border ${
                      formik.touched.slug && formik.errors.slug ? 'border-red-300' : 'border-gray-300'
                    } focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm`}
                    placeholder="about-us"
                  />
                </div>
                <ErrorMessage name="slug" component="div" className="mt-1 text-sm text-red-600" />
                <p className="mt-1 text-xs text-gray-500">
                  Only lowercase letters, numbers, and hyphens are allowed
                </p>
              </div>

              {/* Excerpt */}
              <div>
                <label htmlFor="excerpt" className="block text-sm font-medium text-gray-700">
                  Excerpt
                </label>
                <Field
                  as="textarea"
                  id="excerpt"
                  name="excerpt"
                  rows={3}
                  className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                  placeholder="A short description of the page"
                />
              </div>

              {/* Content */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Content <span className="text-red-500">*</span>
                </label>
                <div className={formik.touched.content && formik.errors.content ? 'ring-1 ring-red-500 rounded' : ''}>
                  <RichTextEditor
                    value={formik.values.content}
                    onChange={(content) => formik.setFieldValue('content', content)}
                    onBlur={() => formik.setFieldTouched('content', true)}
                    placeholder="Start writing your page content here..."
                  />
                </div>
                {formik.touched.content && formik.errors.content && (
                  <div className="mt-1 text-sm text-red-600">{formik.errors.content}</div>
                )}
              </div>

              {/* Featured Image */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Featured Image
                </label>
                <div className="mt-1">
                  {formik.values.featuredImage ? (
                    <div className="flex items-center space-x-4">
                      <div className="relative group">
                        <img
                          src={formik.values.featuredImage}
                          alt="Featured"
                          className="h-32 w-32 object-cover rounded border border-gray-200"
                        />
                        <div className="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded">
                          <button
                            type="button"
                            onClick={() => {
                              setMediaSelectionMode('featuredImage');
                              setShowMediaLibrary(true);
                            }}
                            className="text-white bg-black bg-opacity-50 rounded-full p-2 hover:bg-opacity-75 focus:outline-none"
                            title="Change image"
                          >
                            <FiImage className="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                      <div className="space-x-2">
                        <button
                          type="button"
                          onClick={() => {
                            setMediaSelectionMode('featuredImage');
                            setShowMediaLibrary(true);
                          }}
                          className="text-sm text-indigo-600 hover:text-indigo-900 font-medium"
                        >
                          Change
                        </button>
                        <button
                          type="button"
                          onClick={() => formik.setFieldValue('featuredImage', '')}
                          className="text-sm text-red-600 hover:text-red-900 font-medium"
                        >
                          Remove
                        </button>
                      </div>
                    </div>
                  ) : (
                    <div 
                      className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md cursor-pointer hover:border-indigo-500"
                      onClick={() => {
                        setMediaSelectionMode('featuredImage');
                        setShowMediaLibrary(true);
                      }}
                    >
                      <div className="space-y-1 text-center">
                        <FiImage className="mx-auto h-12 w-12 text-gray-400" />
                        <div className="flex text-sm text-gray-600">
                          <span className="relative rounded-md bg-white font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                            Upload an image
                          </span>
                          <p className="pl-1">or drag and drop</p>
                        </div>
                        <p className="text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                      </div>
                    </div>
                  )}
                </div>
              </div>

              {/* Status */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Status
                </label>
                <div className="flex space-x-4">
                  <label className="inline-flex items-center">
                    <Field
                      type="radio"
                      name="status"
                      value="draft"
                      className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                    />
                    <span className="ml-2 text-sm text-gray-700">Draft</span>
                  </label>
                  <label className="inline-flex items-center">
                    <Field
                      type="radio"
                      name="status"
                      value="published"
                      className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                    />
                    <span className="ml-2 text-sm text-gray-700">Published</span>
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* SEO Settings */}
        <div className="bg-white shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <h3 className="text-lg font-medium text-gray-900 mb-4">SEO Settings</h3>
            <div className="space-y-4">
              {/* SEO Title */}
              <div>
                <label htmlFor="seoTitle" className="block text-sm font-medium text-gray-700">
                  SEO Title
                </label>
                <Field
                  type="text"
                  id="seoTitle"
                  name="seoTitle"
                  className={`mt-1 block w-full border ${
                    formik.touched.seoTitle && formik.errors.seoTitle ? 'border-red-300' : 'border-gray-300'
                  } rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm`}
                  placeholder="Optimized title for search engines"
                />
                <div className="mt-1 flex justify-between">
                  <ErrorMessage name="seoTitle" component="div" className="text-sm text-red-600" />
                  <span className={`text-xs ${
                    formik.values.seoTitle.length > 60 ? 'text-red-600' : 'text-gray-500'
                  }`}>
                    {formik.values.seoTitle.length}/60 characters
                  </span>
                </div>
              </div>

              {/* SEO Description */}
              <div>
                <label htmlFor="seoDescription" className="block text-sm font-medium text-gray-700">
                  SEO Description
                </label>
                <Field
                  as="textarea"
                  id="seoDescription"
                  name="seoDescription"
                  rows={3}
                  className={`mt-1 block w-full border ${
                    formik.touched.seoDescription && formik.errors.seoDescription ? 'border-red-300' : 'border-gray-300'
                  } rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm`}
                  placeholder="A description of the page for search engines"
                />
                <div className="mt-1 flex justify-between">
                  <ErrorMessage name="seoDescription" component="div" className="text-sm text-red-600" />
                  <span className={`text-xs ${
                    formik.values.seoDescription.length > 160 ? 'text-red-600' : 'text-gray-500'
                  }`}>
                    {formik.values.seoDescription.length}/160 characters
                  </span>
                </div>
              </div>

              {/* SEO Keywords */}
              <div>
                <label htmlFor="seoKeywords" className="block text-sm font-medium text-gray-700">
                  SEO Keywords
                </label>
                <Field
                  type="text"
                  id="seoKeywords"
                  name="seoKeywords"
                  className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                  placeholder="keyword1, keyword2, keyword3"
                />
                <p className="mt-1 text-xs text-gray-500">
                  Separate keywords with commas
                </p>
              </div>
            </div>
          </div>
        </div>
      </Form>

      {/* Media Library Modal */}
      {showMediaLibrary && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 transition-opacity" aria-hidden="true">
              <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div className="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
              <div className="sm:flex sm:items-start">
                <div className="mt-3 text-center sm:mt-0 sm:text-left w-full">
                  <h3 className="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Media Library
                  </h3>
                  <div className="mt-2">
                    <MediaLibrary 
                      onSelect={handleMediaSelect} 
                      onClose={() => setShowMediaLibrary(false)}
                    />
                  </div>
                </div>
              </div>
              <div className="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                <button
                  type="button"
                  onClick={() => setShowMediaLibrary(false)}
                  className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </FormikProvider>
  );
};

export default PageEditor;
