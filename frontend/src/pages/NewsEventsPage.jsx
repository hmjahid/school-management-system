import { Link } from 'react-router-dom';

const NewsEventsPage = () => {
  // Sample data - this will be replaced with dynamic data from the backend
  const newsItems = [
    {
      id: 1,
      title: 'Annual Science Fair 2023',
      date: 'October 15, 2023',
      excerpt: 'Join us for our annual science fair showcasing innovative projects from our students.',
      category: 'Events',
      image: '/images/placeholder-news1.jpg'
    },
    {
      id: 2,
      title: 'School Reopening Announcement',
      date: 'September 1, 2023',
      excerpt: 'Important information about the upcoming school year and safety protocols.',
      category: 'Announcements',
      image: '/images/placeholder-news2.jpg'
    },
    // Add more sample news items as needed
  ];

  const upcomingEvents = [
    {
      id: 1,
      title: 'Parent-Teacher Conference',
      date: 'November 10, 2023',
      time: '3:00 PM - 7:00 PM',
      location: 'School Auditorium'
    },
    {
      id: 2,
      title: 'Sports Day',
      date: 'November 20, 2023',
      time: '9:00 AM - 2:00 PM',
      location: 'School Ground'
    },
    // Add more sample events as needed
  ];

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
            <Link to="/news" className="font-semibold border-b-2 border-white">News & Events</Link>
            <Link to="/gallery" className="hover:underline">Gallery</Link>
            <Link to="/contact" className="hover:underline">Contact</Link>
            <Link to="/login" className="bg-white text-blue-600 px-4 py-2 rounded-md font-medium hover:bg-blue-50 transition-colors">
              Login
            </Link>
          </div>
        </div>
      </nav>

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
            <div className="space-y-8">
              {newsItems.map((item) => (
                <article key={item.id} className="flex flex-col md:flex-row gap-6 group">
                  <div className="md:w-1/3">
                    <div className="bg-gray-200 rounded-lg overflow-hidden aspect-video">
                      <img 
                        src={item.image} 
                        alt={item.title}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                      />
                    </div>
                  </div>
                  <div className="md:w-2/3">
                    <span className="inline-block px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full mb-2">
                      {item.category}
                    </span>
                    <h3 className="text-xl font-semibold mb-2 group-hover:text-blue-600 transition-colors">
                      <Link to={`/news/${item.id}`} className="hover:underline">
                        {item.title}
                      </Link>
                    </h3>
                    <p className="text-gray-600 text-sm mb-2">{item.date}</p>
                    <p className="text-gray-700 mb-3">{item.excerpt}</p>
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

            <div className="mt-8 flex justify-center">
              <button className="px-6 py-2 border border-blue-600 text-blue-600 rounded-md hover:bg-blue-50 transition-colors">
                View All News
              </button>
            </div>
          </div>

          {/* Upcoming Events Sidebar */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow-md p-6 sticky top-6">
              <h2 className="text-xl font-semibold mb-6 pb-2 border-b border-gray-200">Upcoming Events</h2>
              <div className="space-y-6">
                {upcomingEvents.map((event) => (
                  <div key={event.id} className="pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                    <h3 className="font-medium text-gray-900">{event.title}</h3>
                    <div className="mt-1 text-sm text-gray-500">
                      <p className="flex items-center">
                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {event.date}
                      </p>
                      <p className="flex items-center mt-1">
                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {event.time}
                      </p>
                      <p className="flex items-center mt-1">
                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {event.location}
                      </p>
                    </div>
                    <button className="mt-2 text-sm text-blue-600 hover:underline">
                      Add to Calendar
                    </button>
                  </div>
                ))}
              </div>
              <div className="mt-6">
                <Link 
                  to="/events" 
                  className="block text-center w-full bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 transition-colors"
                >
                  View All Events
                </Link>
              </div>
            </div>
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="bg-gray-800 text-white py-8">
        <div className="container mx-auto px-4">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="mb-4 md:mb-0">
              <h3 className="text-xl font-bold">School Name</h3>
              <p className="text-gray-400">Stay Connected With Us</p>
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

export default NewsEventsPage;
