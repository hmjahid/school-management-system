import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { getNews, getUpcomingEvents } from '../services/newsService';
import LoadingSpinner from '../components/common/LoadingSpinner';

const NewsEventsPage = () => {
  const [newsItems, setNewsItems] = useState([]);
  const [upcomingEvents, setUpcomingEvents] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        setError(null);
        
        // Fetch news and events in parallel
        const [newsResponse, eventsResponse] = await Promise.all([
          getNews({ limit: 6 }), // Get 6 most recent news items
          getUpcomingEvents()
        ]);

        if (newsResponse.success) {
          setNewsItems(newsResponse.data);
        } else {
          setError(newsResponse.error);
          console.error('Failed to load news:', newsResponse.error);
        }

        if (eventsResponse.success) {
          setUpcomingEvents(eventsResponse.data);
        } else {
          // Only set error if news also failed to avoid overwriting the first error
          if (!newsResponse.success) {
            setError(eventsResponse.error);
          }
          console.error('Failed to load events:', eventsResponse.error);
        }
      } catch (err) {
        setError('An unexpected error occurred while loading data.');
        console.error('Error in NewsEventsPage:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  // Format date to a more readable format
  const formatDate = (dateString) => {
    if (!dateString) return 'Date not available';
    try {
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      return new Date(dateString).toLocaleDateString(undefined, options);
    } catch (error) {
      console.error('Error formatting date:', error);
      return 'Invalid date';
    }
  };

  // Loading state
  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <LoadingSpinner size="large" />
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center p-6 max-w-md mx-auto bg-red-50 rounded-lg">
          <h2 className="text-xl font-semibold text-red-700 mb-2">Error Loading Content</h2>
          <p className="text-red-600 mb-4">{error}</p>
          <button 
            onClick={() => window.location.reload()} 
            className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
          >
            Try Again
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-white">
      {/* Hero Section */}
      <div className="bg-blue-50 py-16">
        <div className="container mx-auto px-4 text-center">
          <h1 className="text-4xl font-bold text-gray-800 mb-4">News & Events</h1>
          <p className="text-xl text-gray-600">Stay updated with the latest happenings at our school</p>
        </div>
      </div>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-12">
        <div className="grid lg:grid-cols-3 gap-8">
          {/* News Section */}
          <div className="lg:col-span-2">
            <h2 className="text-2xl font-semibold mb-6 pb-2 border-b border-gray-200">Latest News</h2>
            {newsItems.length > 0 ? (
              <div className="space-y-8">
                {newsItems.map((item) => (
                  <article key={item.id} className="flex flex-col md:flex-row gap-6 group">
                    <div className="md:w-1/3">
                      <div className="bg-gray-200 rounded-lg overflow-hidden aspect-video">
                        <img 
                          src={item.image || '/images/placeholder-news.jpg'} 
                          alt={item.title}
                          className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                          onError={(e) => {
                            e.target.onerror = null; // Prevent infinite loop if placeholder also fails
                            e.target.src = '/images/placeholder-news.jpg';
                          }}
                        />
                      </div>
                    </div>
                    <div className="md:w-2/3">
                      {item.category && (
                        <span className="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full mb-2">
                          {item.category}
                        </span>
                      )}
                      <h3 className="text-xl font-semibold mb-2 group-hover:text-blue-600 transition-colors">
                        <Link to={`/news/${item.id}`} className="hover:underline">
                          {item.title || 'Untitled Article'}
                        </Link>
                      </h3>
                      <p className="text-gray-600 text-sm mb-2">{formatDate(item.publishedAt || item.date)}</p>
                      <p className="text-gray-700 mb-3">{item.excerpt || 'No excerpt available'}</p>
                      <Link 
                        to={`/news/${item.id}`} 
                        className="text-blue-600 font-medium hover:underline inline-flex items-center"
                      >
                        Read More
                        <svg className="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                        </svg>
                      </Link>
                    </div>
                  </article>
                ))}
              </div>
            ) : (
              <div className="text-center py-12 bg-gray-50 rounded-lg">
                <p className="text-gray-500">No news articles available at the moment. Please check back later.</p>
              </div>
            )}

            <div className="mt-8 flex justify-center">
              <Link 
                to="/news" 
                className="px-6 py-2 border border-blue-600 text-blue-600 rounded-md hover:bg-blue-50 transition-colors inline-block"
              >
                View All News
              </Link>
            </div>
          </div>

          {/* Upcoming Events Sidebar */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow-md p-6 sticky top-6">
              <h2 className="text-2xl font-semibold mb-6">Upcoming Events</h2>
              {upcomingEvents.length > 0 ? (
                <div className="space-y-4">
                  {upcomingEvents.map((event) => (
                    <div key={event.id} className="p-4 bg-white rounded-lg shadow-sm border border-gray-100">
                      <h3 className="font-semibold text-gray-800">{event.title || 'Untitled Event'}</h3>
                      <p className="text-sm text-gray-600 mt-1">
                        {formatDate(event.startDate || event.date)}
                        {(event.startTime || event.time) && (
                          <span> • {event.startTime || event.time}</span>
                        )}
                        {event.endTime && ` - ${event.endTime}`}
                      </p>
                      {event.location && (
                        <p className="text-sm text-gray-500 mt-1">
                          <span className="inline-flex items-center">
                            <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {event.location}
                          </span>
                        </p>
                      )}
                      <button className="mt-2 text-sm text-blue-600 hover:underline">
                        Add to Calendar
                      </button>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 bg-gray-50 rounded-lg">
                  <p className="text-gray-500">No upcoming events scheduled. Check back later!</p>
                </div>
              )}

              <div className="mt-6">
                <Link 
                  to="/events" 
                  className="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors inline-block text-center"
                >
                  View All Events
                </Link>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  );
};

export default NewsEventsPage;
