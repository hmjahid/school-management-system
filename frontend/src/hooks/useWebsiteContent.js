import { useState, useEffect } from 'react';
import { websiteContentService } from '../services/websiteContentService';

export const useWebsiteContent = (page, initialContent = {}) => {
  const [content, setContent] = useState(initialContent);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchContent = async () => {
    try {
      setLoading(true);
      // If we have initial content, use it immediately for better UX
      if (Object.keys(initialContent).length > 0) {
        setContent(initialContent);
      }
      
      // Try to fetch fresh content from the server
      const data = await websiteContentService.getPageContent(page);
      
      // Only update if we got valid data back
      if (data && typeof data === 'object' && Object.keys(data).length > 0) {
        setContent(data);
        setError(null);
      } else if (Object.keys(initialContent).length === 0) {
        // Only show error if we don't have initial content
        setError('No content available');
      }
    } catch (err) {
      console.warn(`Using fallback content for ${page}:`, err.message);
      // Don't show error if we have initial content to display
      if (Object.keys(initialContent).length === 0) {
        setError('Failed to load content. Please check your connection and try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchContent();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page]);

  const updateContent = async (updatedContent) => {
    try {
      setLoading(true);
      const data = await websiteContentService.updatePageContent(page, updatedContent);
      setContent(data);
      setError(null);
      return data;
    } catch (err) {
      console.error(`Error updating ${page} content:`, err);
      setError('Failed to update content. Please try again.');
      throw err;
    } finally {
      setLoading(false);
    }
  };

  const uploadImage = async (file, fieldName) => {
    try {
      setLoading(true);
      const data = await websiteContentService.uploadImage(page, file, fieldName);
      // Update the content with the new image URL
      setContent(prev => ({
        ...prev,
        images: {
          ...prev.images,
          [fieldName]: data.path
        }
      }));
      return data;
    } catch (err) {
      console.error('Error uploading image:', err);
      setError('Failed to upload image. Please try again.');
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return {
    content,
    loading,
    error,
    updateContent,
    uploadImage,
    refresh: fetchContent,
  };
};

export default useWebsiteContent;
