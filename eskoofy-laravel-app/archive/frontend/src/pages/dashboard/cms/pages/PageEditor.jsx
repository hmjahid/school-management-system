import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
// Standard Formik imports
import { FormikProvider, Form, Field, ErrorMessage, useFormik } from 'formik';
import * as Yup from 'yup';
import { toast } from 'react-hot-toast';
import { FiSave, FiArrowLeft, FiEye, FiImage, FiX } from 'react-icons/fi';
import RichTextEditor from '../../../../components/editor/RichTextEditor';
import LoadingSpinner from '../../../../components/common/LoadingSpinner';
import MediaLibrary from '../../media/MediaLibrary';
import { pageSchema } from '../../../../utils/validationSchemas';
import cmsService from '../../../../services/cmsService';

const PageEditor = () => {
  const { id } = useParams();
  const isNew = !id;
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const [showMediaLibrary, setShowMediaLibrary] = useState(false);
  const [mediaSelectionMode, setMediaSelectionMode] = useState(null);

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

  const { data: pageData, isLoading: isLoadingPage, isError: isPageError, error: pageError } = useQuery({
    queryKey: ['cms-page', id],
    queryFn: () => cmsService.getPage(id),
    enabled: !isNew,
    retry: 1,
  });

  const savePageMutation = useMutation({
    mutationFn: (data) => (isNew ? cmsService.createPage(data) : cmsService.updatePage(id, data)),
    onSuccess: () => {
      toast.success(`Page ${isNew ? 'created' : 'updated'} successfully`);
      queryClient.invalidateQueries({ queryKey: ['cms-pages'] });
      navigate('/dashboard/cms/pages');
    },
    onError: (error) => {
      toast.error(error?.message || 'Failed to save page');
    },
  });

  const handleSubmit = async (values, { setSubmitting }) => {
    try {
      const payload = {
        ...values,
        seo: {
          title: values.seoTitle || '',
          description: values.seoDescription || '',
          keywords: values.seoKeywords ? values.seoKeywords.split(',').map(k => k.trim()).filter(Boolean) : [],
        }
      };

      // Remove top-level SEO fields
      delete payload.seoTitle;
      delete payload.seoDescription;
      delete payload.seoKeywords;

      await savePageMutation.mutateAsync(payload);
    } catch (err) {
      console.error('Error saving page:', err);
    } finally {
      setSubmitting(false);
    }
  };

  // Create formik early so it can be referenced in effects
  const formik = useFormik({
    initialValues,
    validationSchema: pageSchema,
    onSubmit: handleSubmit,
    enableReinitialize: true,
  });

  const { values, errors, touched, isSubmitting, dirty, isValid, setFieldValue, setFieldTouched, setValues } = formik;

  useEffect(() => {
    if (pageData) {
      const formData = {
        ...initialValues,
        ...pageData,
        seoTitle: pageData.seo?.title || '',
        seoDescription: pageData.seo?.description || '',
        seoKeywords: pageData.seo?.keywords ? pageData.seo.keywords.join(', ') : pageData.seo?.keywords || '',
        featuredImage: pageData.featuredImage || '',
      };
      setValues(formData);
    }
  }, [pageData, setValues]);

  const handleMediaSelect = (media) => {
    if (media?.url) {
      setFieldValue('featuredImage', media.url);
    }
    setShowMediaLibrary(false);
    setMediaSelectionMode(null);
  };

  const generateSlug = (title) =>
    title
      .toLowerCase()
      .replace(/[^^\w\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/--+/g, '-')
      .trim();

  const handleTitleChange = (e) => {
    const title = e.target.value;
    setFieldValue('title', title);
    // auto slug only if blank or untouched
    if (isNew || !touched?.slug) {
      setFieldValue('slug', generateSlug(title));
    }
  };

  if (isLoadingPage) return <LoadingSpinner />;

  if (isPageError) {
    return (
      <div className="bg-red-50 border-l-4 border-red-400 p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <FiX className="h-5 w-5 text-red-400" aria-hidden="true" />
          </div>
          <div className="ml-3">
            <p className="text-sm text-red-700">
              {pageError?.message || 'Failed to load page. The page may not exist or you may not have permission to view it.'}
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
      <Form id="pageForm" className="space-y-6" onSubmit={formik.handleSubmit}>
        {/* header and actions */}
        <div className="flex justify-between items-center mb-6">
          <div>
            <h2 className="text-2xl font-bold text-gray-900">{isNew ? 'Create New Page' : 'Edit Page'}</h2>
            <p className="mt-1 text-gray-500">{isNew ? 'Create a new page for your website' : 'Edit the page content and settings'}</p>
          </div>
          <div className="flex space-x-3">
            <button
              type="button"
              onClick={() => navigate('/dashboard/cms/pages')}
              className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none"
            >
              <FiArrowLeft className="-ml-1 mr-2 h-4 w-4" />
              Back to Pages
            </button>

            <button
              type="submit"
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
              disabled={isSubmitting || savePageMutation.isLoading || !dirty || !isValid}
            >
              {isSubmitting || savePageMutation.isLoading ? (
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
                onClick={() => window.open(`/pages/${values.slug}`, '_blank')}
                className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                title="View page"
              >
                <FiEye className="-ml-1 mr-2 h-4 w-4" />
                Preview
              </button>
            )}
          </div>
        </div>

        {/* main form panel */}
        <div className="bg-white shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <div className="space-y-6">
              {/* Title */}
              <div>
                <label htmlFor="title" className="block text-sm font-medium text-gray-700">Title <span className="text-red-500">*</span></label>
                <input
                  id="title"
                  name="title"
                  type="text"
                  value={values.title}
                  onChange={handleTitleChange}
                  className={`mt-1 block w-full border ${errors.title && touched.title ? 'border-red-300' : 'border-gray-300'} rounded-md shadow-sm py-2 px-3`}
                />
                <ErrorMessage name="title" component="div" className="mt-1 text-sm text-red-600" />
              </div>

              {/* Slug */}
              <div>
                <label htmlFor="slug" className="block text-sm font-medium text-gray-700">URL Slug <span className="text-red-500">*</span></label>
                <div className="mt-1 flex rounded-md shadow-sm">
                  <span className="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">/pages/</span>
                  <input
                    id="slug"
                    name="slug"
                    type="text"
                    value={values.slug}
                    onChange={(e) => setFieldValue('slug', e.target.value)}
                    className={`flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border ${errors.slug && touched.slug ? 'border-red-300' : 'border-gray-300'}`}
                    placeholder="about-us"
                  />
                </div>
                <ErrorMessage name="slug" component="div" className="mt-1 text-sm text-red-600" />
                <p className="mt-1 text-xs text-gray-500">Only lowercase letters, numbers, and hyphens are allowed</p>
              </div>

              {/* Excerpt */}
              <div>
                <label htmlFor="excerpt" className="block text-sm font-medium text-gray-700">Excerpt</label>
                <textarea
                  id="excerpt"
                  name="excerpt"
                  rows={3}
                  value={values.excerpt}
                  onChange={(e) => setFieldValue('excerpt', e.target.value)}
                  className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3"
                  placeholder="A short description of the page"
                />
              </div>

              {/* Content */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Content <span className="text-red-500">*</span></label>
                <div className={errors.content && touched.content ? 'ring-1 ring-red-500 rounded' : ''}>
                  <RichTextEditor
                    value={values.content}
                    onChange={(content) => setFieldValue('content', content)}
                    onBlur={() => setFieldTouched('content', true)}
                    placeholder="Start writing your page content here..."
                  />
                </div>
                {errors.content && touched.content && (<div className="mt-1 text-sm text-red-600">{errors.content}</div>)}
              </div>

              {/* Featured Image */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
                <div className="mt-1">
                  {values.featuredImage ? (
                    <div className="flex items-center space-x-4">
                      <div className="relative group">
                        <img src={values.featuredImage} alt="Featured" className="h-32 w-32 object-cover rounded border" />
                        <div className="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity rounded">
                          <button type="button" onClick={() => { setMediaSelectionMode('featuredImage'); setShowMediaLibrary(true); }} className="text-white bg-black bg-opacity-50 rounded-full p-2">
                            <FiImage className="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                      <div className="space-x-2">
                        <button type="button" onClick={() => { setMediaSelectionMode('featuredImage'); setShowMediaLibrary(true); }} className="text-sm text-indigo-600">Change</button>
                        <button type="button" onClick={() => setFieldValue('featuredImage', '')} className="text-sm text-red-600">Remove</button>
                      </div>
                    </div>
                  ) : (
                    <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md cursor-pointer" onClick={() => { setMediaSelectionMode('featuredImage'); setShowMediaLibrary(true); }}>
                      <div className="space-y-1 text-center">
                        <FiImage className="mx-auto h-12 w-12 text-gray-400" />
                        <div className="flex text-sm text-gray-600"><span className="relative rounded-md bg-white font-medium text-indigo-600">Upload an image</span><p className="pl-1">or drag and drop</p></div>
                        <p className="text-xs text-gray-500">PNG, JPG, GIF up to 5MB</p>
                      </div>
                    </div>
                  )}
                </div>
                <ErrorMessage name="featuredImage" component="div" className="mt-1 text-sm text-red-600" />
              </div>

              {/* Status */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <div className="flex space-x-4">
                  <label className="inline-flex items-center"><input type="radio" name="status" value="draft" checked={values.status === 'draft'} onChange={() => setFieldValue('status','draft')} className="h-4 w-4" /> <span className="ml-2 text-sm">Draft</span></label>
                  <label className="inline-flex items-center"><input type="radio" name="status" value="published" checked={values.status === 'published'} onChange={() => setFieldValue('status','published')} className="h-4 w-4" /> <span className="ml-2 text-sm">Published</span></label>
                  <label className="inline-flex items-center"><input type="radio" name="status" value="archived" checked={values.status === 'archived'} onChange={() => setFieldValue('status','archived')} className="h-4 w-4" /> <span className="ml-2 text-sm">Archived</span></label>
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
              <div>
                <label htmlFor="seoTitle" className="block text-sm font-medium text-gray-700">SEO Title</label>
                <input id="seoTitle" name="seoTitle" type="text" value={values.seoTitle} onChange={(e)=> setFieldValue('seoTitle', e.target.value)} className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" placeholder="Optimized title for search engines" />
                <div className="mt-1 flex justify-between"><ErrorMessage name="seoTitle" component="div" className="text-sm text-red-600" /><span className={`text-xs ${values.seoTitle.length > 60 ? 'text-red-600' : 'text-gray-500'}`}>{values.seoTitle.length}/60 characters</span></div>
              </div>

              <div>
                <label htmlFor="seoDescription" className="block text-sm font-medium text-gray-700">SEO Description</label>
                <textarea id="seoDescription" name="seoDescription" rows={3} value={values.seoDescription} onChange={(e)=> setFieldValue('seoDescription', e.target.value)} className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" placeholder="A description of the page for search engines" />
              </div>

              <div>
                <label htmlFor="seoKeywords" className="block text-sm font-medium text-gray-700">SEO Keywords</label>
                <input id="seoKeywords" name="seoKeywords" type="text" value={values.seoKeywords} onChange={(e)=> setFieldValue('seoKeywords', e.target.value)} className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" placeholder="keyword1, keyword2, keyword3" />
                <p className="mt-1 text-xs text-gray-500">Separate keywords with commas</p>
              </div>
            </div>
          </div>
        </div>

        {/* Form Actions */}
        <div className="px-4 py-3 bg-gray-50 text-right sm:px-6">
          <div className="flex justify-end space-x-3">
            <button type="button" onClick={() => navigate('/dashboard/cms/pages')} className="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
            <button type="submit" disabled={savePageMutation.isLoading} className="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">{savePageMutation.isLoading ? 'Saving...' : 'Save Changes'}</button>
          </div>
        </div>

        {showMediaLibrary && (
          <MediaLibrary
            mode={mediaSelectionMode}
            onClose={() => setShowMediaLibrary(false)}
            onSelect={handleMediaSelect}
          />
        )}
      </Form>
    </FormikProvider>
  );
};

export default PageEditor;
