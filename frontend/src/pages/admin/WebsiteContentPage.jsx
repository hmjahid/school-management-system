import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-hot-toast';
import { FiEdit2, FiImage, FiTrash2, FiPlus, FiSave } from 'react-icons/fi';

const WebsiteContentPage = () => {
  const [activeTab, setActiveTab] = useState('pages');
  const [isEditing, setIsEditing] = useState(false);
  const [currentPage, setCurrentPage] = useState(null);
  const [pages, setPages] = useState([
    { id: 1, title: 'Home', slug: 'home', content: 'Home page content', lastUpdated: '2025-10-12' },
    { id: 2, title: 'About', slug: 'about', content: 'About page content', lastUpdated: '2025-10-12' },
    { id: 3, title: 'Academics', slug: 'academics', content: 'Academics page content', lastUpdated: '2025-10-12' },
    { id: 4, title: 'Admissions', slug: 'admissions', content: 'Admissions page content', lastUpdated: '2025-10-12' },
  ]);
  const [news, setNews] = useState([
    { id: 1, title: 'School Reopening', date: '2025-10-15', content: 'School will reopen on...', image: '' },
    { id: 2, title: 'Annual Sports Day', date: '2025-11-01', content: 'Join us for our annual sports day...', image: '' },
  ]);
  const [events, setEvents] = useState([
    { id: 1, title: 'Parent-Teacher Meeting', date: '2025-10-20', location: 'School Auditorium' },
    { id: 2, title: 'Science Fair', date: '2025-11-10', location: 'Science Block' },
  ]);
  const [gallery, setGallery] = useState([
    { id: 1, title: 'Annual Day 2025', category: 'events', images: [] },
    { id: 2, title: 'Sports Day', category: 'sports', images: [] },
  ]);
  const navigate = useNavigate();

  const handleSavePage = (e) => {
    e.preventDefault();
    // TODO: Implement API call to save page
    toast.success('Page saved successfully');
    setIsEditing(false);
    setCurrentPage(null);
  };

  const handleAddNews = () => {
    // TODO: Implement add news
    toast.success('News added successfully');
  };

  const handleAddEvent = () => {
    // TODO: Implement add event
    toast.success('Event added successfully');
  };

  const handleUploadImage = (e) => {
    // TODO: Implement image upload
    const file = e.target.files[0];
    if (file) {
      toast.success('Image uploaded successfully');
    }
  };

  const renderTabContent = () => {
    switch (activeTab) {
      case 'pages':
        return (
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <div className="p-4 border-b">
              <h3 className="text-lg font-medium">Manage Pages</h3>
            </div>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Page</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {pages.map((page) => (
                    <tr key={page.id}>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">{page.title}</div>
                        <div className="text-sm text-gray-500">/{page.slug}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {new Date(page.lastUpdated).toLocaleDateString()}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button
                          onClick={() => {
                            setCurrentPage(page);
                            setIsEditing(true);
                          }}
                          className="text-blue-600 hover:text-blue-900 mr-4"
                        >
                          <FiEdit2 className="inline-block" /> Edit
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        );
      
      case 'news':
        return (
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <div className="p-4 border-b flex justify-between items-center">
              <h3 className="text-lg font-medium">News & Announcements</h3>
              <button
                onClick={handleAddNews}
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <FiPlus className="mr-2" /> Add News
              </button>
            </div>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {news.map((item) => (
                    <tr key={item.id}>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">{item.title}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {new Date(item.date).toLocaleDateString()}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button className="text-blue-600 hover:text-blue-900 mr-4">
                          <FiEdit2 className="inline-block" /> Edit
                        </button>
                        <button className="text-red-600 hover:text-red-900">
                          <FiTrash2 className="inline-block" /> Delete
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        );
      
      case 'events':
        return (
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <div className="p-4 border-b flex justify-between items-center">
              <h3 className="text-lg font-medium">Upcoming Events</h3>
              <button
                onClick={handleAddEvent}
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <FiPlus className="mr-2" /> Add Event
              </button>
            </div>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {events.map((event) => (
                    <tr key={event.id}>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">{event.title}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {new Date(event.date).toLocaleDateString()}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {event.location}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button className="text-blue-600 hover:text-blue-900 mr-4">
                          <FiEdit2 className="inline-block" /> Edit
                        </button>
                        <button className="text-red-600 hover:text-red-900">
                          <FiTrash2 className="inline-block" /> Delete
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        );
      
      case 'gallery':
        return (
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <div className="p-4 border-b">
              <h3 className="text-lg font-medium">Gallery Management</h3>
            </div>
            <div className="p-4">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {gallery.map((album) => (
                  <div key={album.id} className="border rounded-lg overflow-hidden">
                    <div className="bg-gray-100 h-40 flex items-center justify-center">
                      <FiImage className="h-12 w-12 text-gray-400" />
                    </div>
                    <div className="p-4">
                      <h4 className="font-medium text-gray-900">{album.title}</h4>
                      <p className="text-sm text-gray-500 capitalize">{album.category}</p>
                      <div className="mt-2 flex justify-between items-center">
                        <span className="text-sm text-gray-500">{album.images.length} photos</span>
                        <div>
                          <button className="text-blue-600 hover:text-blue-900 text-sm font-medium">
                            <FiEdit2 className="inline-block mr-1" /> Edit
                          </button>
                          <button className="ml-3 text-red-600 hover:text-red-900 text-sm font-medium">
                            <FiTrash2 className="inline-block mr-1" /> Delete
                          </button>
                        </div>
                      </div>
                      <div className="mt-3">
                        <label className="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer">
                          <FiPlus className="mr-1.5 h-4 w-4" />
                          <span>Add Photos</span>
                          <input type="file" className="hidden" multiple onChange={handleUploadImage} />
                        </label>
                      </div>
                    </div>
                  </div>
                ))}
                <div className="border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center p-6 cursor-pointer hover:border-blue-500">
                  <div className="text-center">
                    <FiPlus className="mx-auto h-10 w-10 text-gray-400" />
                    <h3 className="mt-2 text-sm font-medium text-gray-900">
                      <span>Create New Album</span>
                    </h3>
                    <p className="mt-1 text-xs text-gray-500">
                      Click to create a new photo album
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        );
      
      default:
        return null;
    }
  };

  if (isEditing && currentPage) {
    return (
      <div className="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center mb-6">
          <h2 className="text-2xl font-bold text-gray-900">Edit {currentPage.title} Page</h2>
          <div>
            <button
              onClick={() => {
                setIsEditing(false);
                setCurrentPage(null);
              }}
              className="mr-2 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Cancel
            </button>
            <button
              onClick={handleSavePage}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <FiSave className="mr-2" /> Save Changes
            </button>
          </div>
        </div>
        
        <div className="bg-white shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:p-6">
            <div className="mb-6">
              <label htmlFor="page-title" className="block text-sm font-medium text-gray-700 mb-1">
                Page Title
              </label>
              <input
                type="text"
                id="page-title"
                className="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                value={currentPage.title}
                onChange={(e) => setCurrentPage({...currentPage, title: e.target.value})}
              />
            </div>
            
            <div className="mb-6">
              <label htmlFor="page-content" className="block text-sm font-medium text-gray-700 mb-1">
                Page Content
              </label>
              <textarea
                id="page-content"
                rows={15}
                className="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md"
                value={currentPage.content}
                onChange={(e) => setCurrentPage({...currentPage, content: e.target.value})}
              />
            </div>
            
            <div className="flex justify-end">
              <button
                type="button"
                onClick={() => {
                  setIsEditing(false);
                  setCurrentPage(null);
                }}
                className="mr-2 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleSavePage}
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <FiSave className="mr-2" /> Save Changes
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex">
              <div className="flex-shrink-0 flex items-center">
                <h1 className="text-xl font-bold text-gray-900">Website Content Manager</h1>
              </div>
              <nav className="hidden sm:ml-6 sm:flex sm:space-x-8">
                {['pages', 'news', 'events', 'gallery'].map((tab) => (
                  <button
                    key={tab}
                    onClick={() => setActiveTab(tab)}
                    className={`${activeTab === tab
                      ? 'border-blue-500 text-gray-900'
                      : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                    } inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium capitalize`}
                  >
                    {tab}
                  </button>
                ))}
              </nav>
            </div>
            <div className="hidden sm:ml-6 sm:flex sm:items-center">
              <button
                onClick={() => navigate('/dashboard')}
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Back to Dashboard
              </button>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div className="px-4 py-4 sm:px-0">
          {renderTabContent()}
        </div>
      </div>
    </div>
  );
};

export default WebsiteContentPage;
