import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import galleryService from '../services/galleryService';
import LoadingSpinner from '../components/common/LoadingSpinner';
import { toast } from 'react-hot-toast';

const GalleryPage = () => {
  const [galleryItems, setGalleryItems] = useState([]);
  const [galleryCategories, setGalleryCategories] = useState([{ id: 'all', name: 'All' }]);
  const [activeCategory, setActiveCategory] = useState('all');
  const [selectedImage, setSelectedImage] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchGalleryData = async () => {
      try {
        setIsLoading(true);
        
        // Fetch gallery items
        const itemsResponse = await galleryService.getGalleryItems();
        
        // Fetch categories
        const categoriesResponse = await galleryService.getGalleryCategories();
        
        if (itemsResponse.success && categoriesResponse.success) {
          setGalleryItems(itemsResponse.data);
          // Add 'all' category to the beginning of categories
          setGalleryCategories([
            { id: 'all', name: 'All' },
            ...categoriesResponse.data
          ]);
        } else {
          // Fallback to sample data if API fails
          const fallbackData = galleryService.getFallbackData();
          setGalleryItems(fallbackData.items);
          setGalleryCategories(fallbackData.categories);
          toast.error('Using sample data. Could not connect to server.');
        }
      } catch (err) {
        console.error('Error fetching gallery data:', err);
        setError('Failed to load gallery. Please try again later.');
        // Fallback to sample data
        const fallbackData = galleryService.getFallbackData();
        setGalleryItems(fallbackData.items);
        setGalleryCategories(fallbackData.categories);
      } finally {
        setIsLoading(false);
      }
    };

    fetchGalleryData();
  }, []);

  // Filter items based on selected category
  const filteredItems = activeCategory === 'all' 
    ? galleryItems 
    : galleryItems.filter(item => item.category === activeCategory);

  // Get featured items (first 2 items for the featured section)
  const featuredItems = galleryItems.filter(item => item.featured).slice(0, 2);

  // Format date for display
  const formatDate = (dateString) => {
    if (!dateString) return '';
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
  };

  // Loading and error states are already handled above

  return (
    <div className="min-h-screen bg-white">
      {/* Hero Section */}
      <div className="relative bg-blue-50 py-20">
        <div className="absolute inset-0 bg-black opacity-40"></div>
        <div className="container mx-auto px-4 text-center relative z-10">
          <h1 className="text-4xl md:text-5xl font-bold text-white mb-4">Our Gallery</h1>
          <p className="text-xl text-blue-100 max-w-3xl mx-auto">
            Capturing the vibrant life and memorable moments at our school
          </p>
        </div>
      </div>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-12">
        {isLoading ? (
          <div className="flex justify-center items-center py-20">
            <LoadingSpinner size="large" />
          </div>
        ) : error ? (
          <div className="text-center py-12 text-red-500">
            <p>{error}</p>
          </div>
        ) : (
          <>
            {/* Featured Gallery */}
            {featuredItems.length > 0 && (
              <section className="mb-16">
            <h2 className="text-2xl font-semibold mb-6">Featured</h2>
            <div className="grid md:grid-cols-2 gap-6">
              {featuredItems.map((item) => (
                <div 
                  key={`featured-${item.id}`} 
                  className="group relative rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow"
                  onClick={() => setSelectedImage(item)}
                >
                  <div className="aspect-video bg-gray-200 overflow-hidden">
                    <img 
                      src={item.image} 
                      alt={item.title}
                      className="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500"
                      loading="lazy"
                      onError={(e) => {
                        e.target.onerror = null;
                        e.target.src = '/images/placeholder-gallery.jpg';
                      }}
                    />
                  </div>
                  <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex items-end p-6">
                    <div>
                      <span className="inline-block px-3 py-1 bg-blue-600 text-white text-sm font-medium rounded-full mb-2">
                        {item.category.charAt(0).toUpperCase() + item.category.slice(1)}
                      </span>
                      <h3 className="text-xl font-semibold text-white">{item.title}</h3>
                      <p className="text-blue-100 text-sm">{formatDate(item.date)}</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
              </section>
            )}

        {/* Gallery Categories */}
        <section className="mb-12">
          <div className="flex flex-wrap justify-center gap-2 mb-8">
            {galleryCategories.map((category) => (
              <button
                key={category.id}
                onClick={() => setActiveCategory(category.id)}
                className={`px-4 py-2 rounded-full text-sm font-medium transition-colors ${
                  activeCategory === category.id
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                {category.name}
              </button>
            ))}
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredItems.map((item) => (
              <div 
                key={item.id} 
                className="group relative rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-all cursor-pointer"
                onClick={() => setSelectedImage(item)}
              >
                <div className="aspect-square bg-gray-200 overflow-hidden">
                  <img 
                    src={item.image} 
                    alt={item.title}
                    className="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500"
                  />
                </div>
                <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent opacity-0 group-hover:opacity-100 flex items-end p-4 transition-opacity">
                  <div>
                    <h3 className="text-white font-medium">{item.title}</h3>
                    <p className="text-blue-100 text-sm">{item.date}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {filteredItems.length === 0 && (
            <div className="text-center py-12">
              <p className="text-gray-500">No images found in this category.</p>
            </div>
          )}
        </section>
          </>
        )}
      </main>

      {/* Image Modal */}
      {selectedImage && (
        <div className="fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-4" onClick={() => setSelectedImage(null)}>
          <div className="relative max-w-4xl w-full" onClick={e => e.stopPropagation()}>
            <button 
              className="absolute -top-10 right-0 text-white hover:text-gray-300"
              onClick={() => setSelectedImage(null)}
            >
              <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            <div className="bg-white rounded-lg overflow-hidden">
              <img 
                src={selectedImage.image} 
                alt={selectedImage.title}
                className="w-full max-h-[70vh] object-contain"
              />
              <div className="p-4 bg-white">
                <div className="flex justify-between items-start">
                  <div>
                    <h3 className="text-xl font-semibold">{selectedImage.title}</h3>
                    <p className="text-gray-600">{selectedImage.date}</p>
                  </div>
                  <span className="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                    {selectedImage.category.charAt(0).toUpperCase() + selectedImage.category.slice(1)}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default GalleryPage;
