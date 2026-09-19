import React, { useState, useRef, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { 
  FiUpload, 
  FiImage, 
  FiVideo, 
  FiFile, 
  FiTrash2, 
  FiCopy, 
  FiSearch, 
  FiFilter,
  FiX,
  FiChevronLeft,
  FiChevronRight,
  FiGrid,
  FiList,
  FiDownload,
  FiEye
} from 'react-icons/fi';
import { cmsService } from '../../../../services/cmsService';
import LoadingSpinner from '../../../../components/common/LoadingSpinner';
import ConfirmationModal from '../../../../components/common/ConfirmationModal';

const MediaManager = () => {
  const queryClient = useQueryClient();
  const fileInputRef = useRef(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedMedia, setSelectedMedia] = useState([]);
  const [viewMode, setViewMode] = useState('grid'); // 'grid' or 'list'
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [mediaToDelete, setMediaToDelete] = useState(null);
  const [activeFilter, setActiveFilter] = useState('all'); // 'all', 'images', 'videos', 'documents'
  const [previewItem, setPreviewItem] = useState(null);

  // Fetch media items
  const { data: mediaItems = [], isLoading, error } = useQuery(
    ['cms-media'],
    cmsService.getMedia,
    {
      refetchOnWindowFocus: false,
    }
  );

  // Upload media mutation
  const uploadMediaMutation = useMutation(cmsService.uploadMedia, {
    onSuccess: () => {
      queryClient.invalidateQueries(['cms-media']);
      toast.success('File uploaded successfully');
    },
    onError: (error) => {
      toast.error(error.message || 'Failed to upload file');
    },
  });

  // Delete media mutation
  const deleteMediaMutation = useMutation(cmsService.deleteMedia, {
    onSuccess: () => {
      queryClient.invalidateQueries(['cms-media']);
      toast.success('File deleted successfully');
      setDeleteModalOpen(false);
    },
    onError: (error) => {
      toast.error(error.message || 'Failed to delete file');
    },
  });

  // Handle file selection
  const handleFileChange = (e) => {
    const files = Array.from(e.target.files);
    if (files.length === 0) return;

    // Filter by file type if needed
    const validFiles = files.filter(file => {
      const fileType = file.type.split('/')[0];
      return ['image', 'video', 'application'].includes(fileType);
    });

    if (validFiles.length === 0) {
      toast.error('Please select valid image, video, or document files');
      return;
    }

    // Upload each file
    validFiles.forEach(file => {
      uploadMediaMutation.mutate(file);
    });
  };

  // Handle delete confirmation
  const handleDeleteClick = (media) => {
    setMediaToDelete(media);
    setDeleteModalOpen(true);
  };

  // Confirm delete
  const confirmDelete = () => {
    if (mediaToDelete) {
      deleteMediaMutation.mutate(mediaToDelete.id);
    }
  };

  // Filter media items
  const filteredMedia = mediaItems.filter(item => {
    // Apply search filter
    const matchesSearch = item.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         item.type.toLowerCase().includes(searchTerm.toLowerCase());
    
    // Apply type filter
    const matchesType = activeFilter === 'all' || 
                       (activeFilter === 'images' && item.type.startsWith('image/')) ||
                       (activeFilter === 'videos' && item.type.startsWith('video/')) ||
                       (activeFilter === 'documents' && !item.type.startsWith('image/') && !item.type.startsWith('video/'));
    
    return matchesSearch && matchesType;
  });

  // Get file icon based on file type
  const getFileIcon = (fileType) => {
    if (fileType.startsWith('image/')) {
      return <FiImage className="w-6 h-6 text-blue-500" />;
    } else if (fileType.startsWith('video/')) {
      return <FiVideo className="w-6 h-6 text-red-500" />;
    } else {
      return <FiFile className="w-6 h-6 text-gray-500" />;
    }
  };

  // Format file size
  const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  // Copy URL to clipboard
  const copyToClipboard = (url) => {
    navigator.clipboard.writeText(url);
    toast.success('URL copied to clipboard');
  };

  // Handle preview
  const handlePreview = (item) => {
    setPreviewItem(item);
  };

  if (isLoading) {
    return <LoadingSpinner />;
  }

  if (error) {
    return (
      <div className="bg-red-50 border-l-4 border-red-400 p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <svg className="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="ml-3">
            <p className="text-sm text-red-700">
              Failed to load media. {error.message}
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
        <div>
          <h2 className="text-lg font-medium text-gray-900">Media Library</h2>
          <p className="mt-1 text-sm text-gray-500">
            Upload and manage your media files
          </p>
        </div>
        <div className="flex space-x-3">
          <div className="flex rounded-md shadow-sm">
            <button
              type="button"
              onClick={() => setViewMode('list')}
              className={`relative inline-flex items-center px-3 py-2 rounded-l-md border border-gray-300 text-sm font-medium ${
                viewMode === 'list' ? 'bg-gray-100 text-gray-800' : 'bg-white text-gray-700 hover:bg-gray-50'
              }`}
            >
              <FiList className="h-4 w-4" />
            </button>
            <button
              type="button"
              onClick={() => setViewMode('grid')}
              className={`-ml-px relative inline-flex items-center px-3 py-2 rounded-r-md border border-l-0 border-gray-300 text-sm font-medium ${
                viewMode === 'grid' ? 'bg-gray-100 text-gray-800' : 'bg-white text-gray-700 hover:bg-gray-50'
              }`}
            >
              <FiGrid className="h-4 w-4" />
            </button>
          </div>
          <button
            type="button"
            onClick={() => fileInputRef.current.click()}
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            disabled={uploadMediaMutation.isLoading}
          >
            {uploadMediaMutation.isLoading ? (
              <>
                <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Uploading...
              </>
            ) : (
              <>
                <FiUpload className="-ml-1 mr-2 h-4 w-4" />
                Upload
              </>
            )}
          </button>
          <input
            type="file"
            ref={fileInputRef}
            onChange={handleFileChange}
            className="hidden"
            multiple
            accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt"
          />
        </div>
      </div>

      {/* Search and filter */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
          <div className="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
            <div className="flex-1">
              <div className="relative rounded-md shadow-sm">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <FiSearch className="h-4 w-4 text-gray-400" />
                </div>
                <input
                  type="text"
                  className="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                  placeholder="Search media..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>
            </div>
            <div className="flex space-x-2 overflow-x-auto pb-2 sm:pb-0">
              <button
                type="button"
                onClick={() => setActiveFilter('all')}
                className={`px-3 py-1 text-sm font-medium rounded-full ${
                  activeFilter === 'all'
                    ? 'bg-indigo-100 text-indigo-800'
                    : 'text-gray-600 hover:bg-gray-100'
                }`}
              >
                All Files
              </button>
              <button
                type="button"
                onClick={() => setActiveFilter('images')}
                className={`px-3 py-1 text-sm font-medium rounded-full flex items-center ${
                  activeFilter === 'images'
                    ? 'bg-blue-100 text-blue-800'
                    : 'text-gray-600 hover:bg-gray-100'
                }`}
              >
                <FiImage className="mr-1 h-4 w-4" />
                Images
              </button>
              <button
                type="button"
                onClick={() => setActiveFilter('videos')}
                className={`px-3 py-1 text-sm font-medium rounded-full flex items-center ${
                  activeFilter === 'videos'
                    ? 'bg-red-100 text-red-800'
                    : 'text-gray-600 hover:bg-gray-100'
                }`}
              >
                <FiVideo className="mr-1 h-4 w-4" />
                Videos
              </button>
              <button
                type="button"
                onClick={() => setActiveFilter('documents')}
                className={`px-3 py-1 text-sm font-medium rounded-full flex items-center ${
                  activeFilter === 'documents'
                    ? 'bg-green-100 text-green-800'
                    : 'text-gray-600 hover:bg-gray-100'
                }`}
              >
                <FiFile className="mr-1 h-4 w-4" />
                Documents
              </button>
            </div>
          </div>
        </div>

        {/* Media items */}
        {filteredMedia.length === 0 ? (
          <div className="p-12 text-center">
            <FiImage className="mx-auto h-12 w-12 text-gray-400" />
            <h3 className="mt-2 text-sm font-medium text-gray-900">No media files found</h3>
            <p className="mt-1 text-sm text-gray-500">
              Get started by uploading a new file.
            </p>
            <div className="mt-6">
              <button
                type="button"
                onClick={() => fileInputRef.current.click()}
                className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <FiUpload className="-ml-1 mr-2 h-4 w-4" />
                Upload Files
              </button>
            </div>
          </div>
        ) : viewMode === 'grid' ? (
          // Grid View
          <div className="p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            {filteredMedia.map((item) => (
              <div
                key={item.id}
                className={`group relative rounded-lg border border-gray-200 bg-white overflow-hidden hover:shadow-md transition-shadow duration-200 ${
                  selectedMedia.includes(item.id) ? 'ring-2 ring-indigo-500' : ''
                }`}
              >
                <div className="aspect-w-1 aspect-h-1 bg-gray-100 overflow-hidden">
                  {item.type.startsWith('image/') ? (
                    <img
                      src={item.url}
                      alt={item.name}
                      className="w-full h-32 object-cover"
                    />
                  ) : item.type.startsWith('video/') ? (
                    <div className="w-full h-32 flex items-center justify-center bg-gray-100">
                      <FiVideo className="h-12 w-12 text-gray-400" />
                    </div>
                  ) : (
                    <div className="w-full h-32 flex items-center justify-center bg-gray-50">
                      <FiFile className="h-12 w-12 text-gray-400" />
                    </div>
                  )}
                </div>
                <div className="p-2">
                  <p className="text-xs font-medium text-gray-900 truncate">
                    {item.name}
                  </p>
                  <p className="text-xs text-gray-500">
                    {formatFileSize(item.size)}
                  </p>
                </div>
                <div className="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100 space-x-2">
                  <button
                    onClick={() => handlePreview(item)}
                    className="p-2 rounded-full bg-white bg-opacity-80 hover:bg-opacity-100 text-gray-800 hover:text-indigo-600"
                    title="Preview"
                  >
                    <FiEye className="h-4 w-4" />
                  </button>
                  <button
                    onClick={() => copyToClipboard(item.url)}
                    className="p-2 rounded-full bg-white bg-opacity-80 hover:bg-opacity-100 text-gray-800 hover:text-indigo-600"
                    title="Copy URL"
                  >
                    <FiCopy className="h-4 w-4" />
                  </button>
                  <a
                    href={item.url}
                    download
                    className="p-2 rounded-full bg-white bg-opacity-80 hover:bg-opacity-100 text-gray-800 hover:text-indigo-600"
                    title="Download"
                  >
                    <FiDownload className="h-4 w-4" />
                  </a>
                  <button
                    onClick={() => handleDeleteClick(item)}
                    className="p-2 rounded-full bg-white bg-opacity-80 hover:bg-opacity-100 text-gray-800 hover:text-red-600"
                    title="Delete"
                  >
                    <FiTrash2 className="h-4 w-4" />
                  </button>
                </div>
              </div>
            ))}
          </div>
        ) : (
          // List View
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Name
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Type
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Size
                  </th>
                  <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Uploaded
                  </th>
                  <th scope="col" className="relative px-6 py-3">
                    <span className="sr-only">Actions</span>
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredMedia.map((item) => (
                  <tr key={item.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center">
                        <div className="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-md bg-gray-100">
                          {getFileIcon(item.type)}
                        </div>
                        <div className="ml-4">
                          <div className="text-sm font-medium text-gray-900">{item.name}</div>
                          <div className="text-xs text-gray-500 truncate w-40">
                            {item.url.split('/').pop()}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-900 capitalize">
                        {item.type.split('/')[0]}
                      </div>
                      <div className="text-xs text-gray-500">
                        {item.type.split('/')[1]?.toUpperCase() || 'FILE'}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {formatFileSize(item.size)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {new Date(item.uploadedAt).toLocaleDateString()}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div className="flex justify-end space-x-2">
                        <button
                          onClick={() => handlePreview(item)}
                          className="text-indigo-600 hover:text-indigo-900"
                          title="Preview"
                        >
                          <FiEye className="h-4 w-4" />
                        </button>
                        <button
                          onClick={() => copyToClipboard(item.url)}
                          className="text-indigo-600 hover:text-indigo-900"
                          title="Copy URL"
                        >
                          <FiCopy className="h-4 w-4" />
                        </button>
                        <a
                          href={item.url}
                          download
                          className="text-indigo-600 hover:text-indigo-900"
                          title="Download"
                        >
                          <FiDownload className="h-4 w-4" />
                        </a>
                        <button
                          onClick={() => handleDeleteClick(item)}
                          className="text-red-600 hover:text-red-900"
                          title="Delete"
                        >
                          <FiTrash2 className="h-4 w-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Preview Modal */}
      {previewItem && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 transition-opacity" aria-hidden="true">
              <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div className="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
              <div className="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div className="sm:flex sm:items-start">
                  <div className="mt-3 text-center sm:mt-0 sm:text-left w-full">
                    <div className="flex justify-between items-center">
                      <h3 className="text-lg leading-6 font-medium text-gray-900">
                        {previewItem.name}
                      </h3>
                      <button
                        type="button"
                        onClick={() => setPreviewItem(null)}
                        className="text-gray-400 hover:text-gray-500"
                      >
                        <FiX className="h-6 w-6" />
                      </button>
                    </div>
                    <div className="mt-4">
                      {previewItem.type.startsWith('image/') ? (
                        <div className="flex justify-center">
                          <img
                            src={previewItem.url}
                            alt={previewItem.name}
                            className="max-h-[70vh] max-w-full object-contain"
                          />
                        </div>
                      ) : previewItem.type.startsWith('video/') ? (
                        <div className="flex justify-center">
                          <video
                            controls
                            className="max-h-[70vh] max-w-full"
                            src={previewItem.url}
                          >
                            Your browser does not support the video tag.
                          </video>
                        </div>
                      ) : (
                        <div className="flex flex-col items-center justify-center p-12 bg-gray-50 rounded-lg">
                          <FiFile className="h-16 w-16 text-gray-400" />
                          <p className="mt-2 text-sm text-gray-500">
                            Preview not available for this file type.
                          </p>
                          <a
                            href={previewItem.url}
                            download
                            className="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
                          >
                            <FiDownload className="-ml-1 mr-2 h-4 w-4" />
                            Download File
                          </a>
                        </div>
                      )}
                    </div>
                    <div className="mt-4 grid grid-cols-2 gap-4 text-sm text-gray-500">
                      <div>
                        <p className="font-medium text-gray-700">File Name:</p>
                        <p>{previewItem.name}</p>
                      </div>
                      <div>
                        <p className="font-medium text-gray-700">File Type:</p>
                        <p>{previewItem.type}</p>
                      </div>
                      <div>
                        <p className="font-medium text-gray-700">File Size:</p>
                        <p>{formatFileSize(previewItem.size)}</p>
                      </div>
                      <div>
                        <p className="font-medium text-gray-700">Uploaded:</p>
                        <p>{new Date(previewItem.uploadedAt).toLocaleString()}</p>
                      </div>
                      <div className="col-span-2">
                        <p className="font-medium text-gray-700">URL:</p>
                        <div className="flex mt-1">
                          <input
                            type="text"
                            readOnly
                            value={previewItem.url}
                            className="flex-1 min-w-0 block w-full px-3 py-2 rounded-l-md border border-r-0 border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                          />
                          <button
                            type="button"
                            onClick={() => copyToClipboard(previewItem.url)}
                            className="inline-flex items-center px-3 py-2 border border-l-0 border-gray-300 bg-gray-50 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 rounded-r-md"
                          >
                            <FiCopy className="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div className="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a
                  href={previewItem.url}
                  download
                  className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm"
                >
                  <FiDownload className="-ml-1 mr-2 h-4 w-4" />
                  Download
                </a>
                <button
                  type="button"
                  onClick={() => setPreviewItem(null)}
                  className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Delete Confirmation Modal */}
      <ConfirmationModal
        isOpen={deleteModalOpen}
        onClose={() => setDeleteModalOpen(false)}
        onConfirm={confirmDelete}
        title="Delete File"
        message={`Are you sure you want to delete "${mediaToDelete?.name}"? This action cannot be undone.`}
        confirmText="Delete"
        cancelText="Cancel"
        isDanger={true}
        isLoading={deleteMediaMutation.isLoading}
      />
    </div>
  );
};

export default MediaManager;
