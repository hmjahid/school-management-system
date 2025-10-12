import { useState, useEffect } from 'react';
import { websiteContentService } from '../services/websiteContentService';

export const useWebsiteContent = (page, initialContent = {}) => {
  const [content, setContent] = useState(initialContent);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchContent = async () => {
    try {
      console.log(`[useWebsiteContent] Fetching content for page: ${page}`);
      setLoading(true);
      setError(null);
      
      // If we have initial content, use it immediately for better UX
      if (initialContent && Object.keys(initialContent).length > 0) {
        console.log('[useWebsiteContent] Using initial content while loading');
        setContent(initialContent);
      }
      
      // Try to fetch fresh content from the server
      console.log(`[useWebsiteContent] Making API call for ${page}`);
      const data = await websiteContentService.getPageContent(page);
      console.log(`[useWebsiteContent] Received response for ${page}:`, data);
      
      // Check if we got valid data back
      if (data) {
        // If data is an object with content, use it
        if (typeof data === 'object' && Object.keys(data).length > 0) {
          console.log(`[useWebsiteContent] Updating content for ${page} with fresh data`);
          setContent(data);
          setError(null);
          return; // Exit early on success
        } 
        // If data is an empty object but we have initial content, use that
        else if (initialContent && Object.keys(initialContent).length > 0) {
          console.log(`[useWebsiteContent] Empty response, using initial content for ${page}`);
          setContent(initialContent);
          setError('Using default content (no data from server)');
          return;
        }
      }
      
      // If we get here, we don't have valid data or initial content
      console.warn(`[useWebsiteContent] No valid content available for ${page}`);
      setError('No content available');
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
