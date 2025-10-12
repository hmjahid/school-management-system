import { useState } from 'react';
import { Link } from 'react-router-dom';

const GalleryPage = () => {
  // Sample gallery data - this will be replaced with dynamic data from the backend
  const galleryCategories = [
    { id: 'all', name: 'All' },
    { id: 'events', name: 'Events' },
    { id: 'sports', name: 'Sports' },
    { id: 'academics', name: 'Academics' },
    { id: 'cultural', name: 'Cultural' },
  ];

  const galleryItems = [
    {
      id: 1,
      title: 'Annual Day Celebration',
      category: 'events',
      image: '/images/gallery/event1.jpg',
      date: 'October 10, 2023',
      featured: true
    },
    {
      id: 2,
      title: 'Sports Day 2023',
      category: 'sports',
      image: '/images/gallery/sports1.jpg',
      date: 'September 25, 2023',
      featured: true
    },
    {
      id: 3,
      title: 'Science Exhibition',
      category: 'academics',
      image: '/images/gallery/academic1.jpg',
      date: 'September 15, 2023'
    },
    {
      id: 4,
      title: 'Cultural Fest',
      category: 'cultural',
      image: '/images/gallery/cultural1.jpg',
      date: 'August 30, 2023'
    },
    {
      id: 5,
      title: 'Independence Day',
      category: 'events',
      image: '/images/gallery/event2.jpg',
      date: 'August 15, 2023'
    },
    {
      id: 6,
      title: 'Basketball Tournament',
      category: 'sports',
      image: '/images/gallery/sports2.jpg',
      date: 'July 20, 2023'
    },
    // Add more gallery items as needed
  ];

  const [activeCategory, setActiveCategory] = useState('all');
  const [selectedImage, setSelectedImage] = useState(null);

  const filteredItems = activeCategory === 'all' 
    ? galleryItems 
    : galleryItems.filter(item => item.category === activeCategory);

  const featuredItems = galleryItems.filter(item => item.featured);

  return (
    <div className="min-h-screen bg-white">
      {/* Navigation */}
      <nav className="bg-blue-600 text-white p-4">
        <div className="container mx-auto flex justify-between items-center">
          <Link to="/" className="text-2xl font-bold">School Name</Link>
          <div className="space-x-4">
            <Link to="/" className="hover:underline">Home</Link>
            <Link to="/about" className="hover:underline">About</Link>
            <Link to="/academics" className="hover:underline">Academics</Link>
            <Link to="/admissions" className="hover:underline">Admissions</Link>
            <Link to="/news" className="hover:underline">News & Events</Link>
            <Link to="/gallery" className="font-semibold border-b-2 border-white">Gallery</Link>
            <Link to="/contact" className="hover:underline">Contact</Link>
            <Link to="/login" className="bg-white text-blue-600 px-4 py-2 rounded-md font-medium hover:bg-blue-50 transition-colors">
              Login
            </Link>
          </div>
        </div>
      </nav>

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
                    />
                  </div>
                  <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent flex items-end p-6">
                    <div>
                      <span className="inline-block px-3 py-1 bg-blue-600 text-white text-sm font-medium rounded-full mb-2">
                        {item.category.charAt(0).toUpperCase() + item.category.slice(1)}
                      </span>
                      <h3 className="text-xl font-semibold text-white">{item.title}</h3>
                      <p className="text-blue-100 text-sm">{item.date}</p>
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

      {/* Footer */}
      <footer className="bg-gray-800 text-white py-8">
        <div className="container mx-auto px-4">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="mb-4 md:mb-0">
              <h3 className="text-xl font-bold">School Name</h3>
              <p className="text-gray-400">Capturing Memories, Creating Legacies</p>
            </div>
            <div className="flex space-x-4">
              <Link to="/privacy" className="text-gray-400 hover:text-white">Privacy Policy</Link>
              <Link to="/terms" className="text-gray-400 hover:text-white">Terms of Service</Link>
              <Link to="/contact" className="text-gray-400 hover:text-white">Contact Us</Link>
            </div>
          </div>
          <div className="mt-8 text-center text-gray-400 text-sm">
            © {new Date().getFullYear()} School Name. All rights reserved.
          </div>
        </div>
      </footer>
    </div>
  );
};

export default GalleryPage;
