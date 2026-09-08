import React from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { FaGraduationCap, FaChalkboardTeacher, FaBook, FaUsers, FaQuoteLeft, FaStar, FaArrowRight } from 'react-icons/fa';
import useWebsiteContent from '../hooks/useWebsiteContent';
import LoadingSpinner from '../components/common/LoadingSpinner';

// Default content that will be used if API call fails or content is not available
const defaultContent = {
  hero: {
    title: 'Welcome to Our School',
    subtitle: 'Nurturing young minds for a brighter future',
    ctaText: 'Learn More',
    ctaLink: '/about',
    backgroundImage: '/images/hero-bg.jpg'
  },
  features: [
    {
      title: 'Experienced Faculty',
      description: 'Our dedicated team of educators brings years of experience and expertise to the classroom.',
      icon: 'FaGraduationCap'
    },
    {
      title: 'Modern Facilities',
      description: 'State-of-the-art classrooms and laboratories to support innovative learning.',
      icon: 'FaChalkboardTeacher'
    },
    {
      title: 'Comprehensive Curriculum',
      description: 'A well-rounded curriculum that prepares students for future challenges.',
      icon: 'FaBook'
    },
    {
      title: 'Inclusive Community',
      description: 'A diverse and welcoming environment that celebrates every student.',
      icon: 'FaUsers'
    }
  ],
  about: {
    title: 'About Our School',
    description: 'We are committed to providing a nurturing environment where students can grow academically, socially, and emotionally. Our experienced faculty and comprehensive programs ensure that every student reaches their full potential.',
    image: '/images/school-building.jpg'
  },
  testimonials: [
    {
      name: 'Sarah Johnson',
      role: 'Parent',
      content: 'The school has provided an excellent learning environment for my child. The teachers are dedicated and caring.',
      rating: 5
    },
    {
      name: 'Michael Chen',
      role: 'Alumnus',
      content: 'The education I received here laid a strong foundation for my university studies and career.',
      rating: 5
    },
    {
      name: 'Priya Patel',
      role: 'Student',
      content: 'I love the extracurricular activities and the supportive community at this school.',
      rating: 4
    }
  ]
};

// Icon mapping for dynamic icon rendering
const iconComponents = {
  FaGraduationCap: FaGraduationCap,
  FaChalkboardTeacher: FaChalkboardTeacher,
  FaBook: FaBook,
  FaUsers: FaUsers
};

const latestNews = [
  {
    id: 1,
    title: 'Annual Science Fair 2025',
    date: 'October 15, 2025',
    excerpt: 'Join us for our annual science fair showcasing innovative student projects.',
    image: '/images/science-fair.jpg'
  },
  {
    id: 2,
    title: 'New Sports Complex Inauguration',
    date: 'November 1, 2025',
    excerpt: 'Our new state-of-the-art sports complex is now open for students and staff.',
    image: '/images/sports-complex.jpg'
  },
  {
    id: 3,
    title: 'Scholarship Program 2025',
    date: 'December 10, 2025',
    excerpt: 'Applications are now open for our merit-based scholarship program.',
    image: '/images/scholarship.jpg'
  }
];

const statistics = [
  { number: '95%', label: 'Graduation Rate' },
  { number: '50+', label: 'Qualified Teachers' },
  { number: '1000+', label: 'Students Enrolled' },
  { number: '25', label: 'Years of Excellence' }
];

const HomePage = () => {
  // Use the useWebsiteContent hook to fetch and manage home page content
  const { content, loading, error } = useWebsiteContent('home', defaultContent);
  const [currentTestimonial, setCurrentTestimonial] = React.useState(0);

  // Auto-rotate testimonials
  React.useEffect(() => {
    const interval = setInterval(() => {
      setCurrentTestimonial(prev => 
        prev === (content?.testimonials?.length - 1) ? 0 : prev + 1
      );
    }, 5000);
    return () => clearInterval(interval);
  }, [content?.testimonials?.length]);

  // Show loading state
  if (loading && !content) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <LoadingSpinner size="large" />
      </div>
    );
  }

  // Show error state
  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center p-6 max-w-md mx-auto">
          <div className="text-red-500 text-2xl mb-4">Error Loading Content</div>
          <p className="text-gray-600 mb-6">{error.message || 'Failed to load page content. Please try again later.'}</p>
          <button
            onClick={() => window.location.reload()}
            className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
          >
            Retry
          </button>
        </div>
      </div>
    );
  }

  // Destructure content with default values to prevent errors
  const {
    hero = {},
    features = [],
    about = {},
    testimonials = []
  } = content?.content || {};

  return (
    <div className="bg-white">
      {/* Hero Section */}
      <section className="relative bg-blue-800 text-white overflow-hidden">
        <div className="absolute inset-0">
          <div className="absolute inset-0 bg-black opacity-50"></div>
          <img 
            src="/images/hero-bg.jpg" 
            alt="Students in classroom" 
            className="w-full h-full object-cover"
            loading="lazy"
          />
        </div>
        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
          <div className="text-center">
            <motion.h1 
              className="text-4xl md:text-6xl font-bold mb-6"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8 }}
            >
              Shaping Future Leaders Through Excellence in Education
            </motion.h1>
            <motion.p 
              className="text-xl md:text-2xl mb-8 max-w-3xl mx-auto"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8, delay: 0.2 }}
            >
              Empowering students with knowledge, skills, and values to succeed in a rapidly changing world.
            </motion.p>
            <div className="flex flex-col sm:flex-row justify-center gap-4">
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.8, delay: 0.4 }}
              >
                <Link
                  to="/admissions"
                  className="inline-block bg-orange-500 hover:bg-orange-600 text-white font-semibold px-8 py-4 rounded-md text-lg transition-colors duration-300"
                >
                  Apply for Admission
                </Link>
              </motion.div>
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.8, delay: 0.6 }}
              >
                <Link
                  to="/about"
                  className="inline-block bg-transparent hover:bg-white/10 text-white border-2 border-white font-semibold px-8 py-3.5 rounded-md text-lg transition-colors duration-300"
                >
                  Learn More
                </Link>
              </motion.div>
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section className="py-16 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-gray-900 mb-4">Why Choose Our School?</h2>
            <div className="w-20 h-1 bg-orange-500 mx-auto"></div>
            <p className="mt-4 text-lg text-gray-600 max-w-3xl mx-auto">
              We are committed to providing a nurturing environment that fosters academic excellence and personal growth.
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            {features.map((feature, index) => (
              <motion.div 
                key={index}
                className="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300"
                whileHover={{ y: -5 }}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: index * 0.1 }}
              >
                <div className="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mb-4 mx-auto">
                  {feature.icon}
                </div>
                <h3 className="text-xl font-semibold text-center mb-2">{feature.title}</h3>
                <p className="text-gray-600 text-center">{feature.description}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Statistics Section */}
      <section className="bg-blue-900 text-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            {statistics.map((stat, index) => (
              <motion.div 
                key={index}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: index * 0.1 }}
              >
                <div className="text-4xl font-bold mb-2">{stat.number}</div>
                <div className="text-blue-200">{stat.label}</div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Testimonials Section */}
      <section className="py-16 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-gray-900 mb-4">What Parents & Students Say</h2>
            <div className="w-20 h-1 bg-orange-500 mx-auto"></div>
          </div>
          
          <div className="max-w-4xl mx-auto">
            {testimonials.map((testimonial, index) => (
              <motion.div 
                key={testimonial.id}
                className={`bg-gray-50 p-8 rounded-lg shadow-md ${currentTestimonial === index ? 'block' : 'hidden'}`}
                initial={{ opacity: 0 }}
                animate={{ 
                  opacity: currentTestimonial === index ? 1 : 0,
                  y: currentTestimonial === index ? 0 : 20
                }}
                transition={{ duration: 0.5 }}
              >
                <div className="text-orange-500 text-4xl mb-4">
                  <FaQuoteLeft />
                </div>
                <p className="text-lg text-gray-700 mb-6">{testimonial.content}</p>
                <div className="flex items-center">
                  <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-xl mr-4">
                    {testimonial.name.charAt(0)}
                  </div>
                  <div>
                    <h4 className="font-semibold text-gray-900">{testimonial.name}</h4>
                    <p className="text-gray-600">{testimonial.role}</p>
                  </div>
                  <div className="ml-auto flex">
                    {[...Array(5)].map((_, i) => (
                      <FaStar 
                        key={i} 
                        className={`w-5 h-5 ${i < testimonial.rating ? 'text-yellow-400' : 'text-gray-300'}`}
                      />
                    ))}
                  </div>
                </div>
              </motion.div>
            ))}
            <div className="flex justify-center mt-8 space-x-2">
              {testimonials.map((_, index) => (
                <button
                  key={index}
                  onClick={() => setCurrentTestimonial(index)}
                  className={`w-3 h-3 rounded-full ${currentTestimonial === index ? 'bg-blue-600' : 'bg-gray-300'}`}
                  aria-label={`Go to testimonial ${index + 1}`}
                ></button>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* Latest News Section */}
      <section className="py-16 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center mb-8">
            <div>
              <h2 className="text-3xl font-bold text-gray-900">Latest News & Events</h2>
              <div className="w-20 h-1 bg-orange-500 mt-2"></div>
            </div>
            <Link 
              to="/news" 
              className="text-blue-600 hover:text-blue-800 font-medium flex items-center"
            >
              View All News
              <svg className="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
              </svg>
            </Link>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {latestNews.map((news) => (
              <motion.div 
                key={news.id}
                className="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow duration-300"
                whileHover={{ y: -5 }}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5 }}
              >
                <div className="h-48 bg-gray-200 overflow-hidden">
                  <img 
                    src={news.image} 
                    alt={news.title} 
                    className="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
                    loading="lazy"
                  />
                </div>
                <div className="p-6">
                  <div className="text-sm text-gray-500 mb-2">{news.date}</div>
                  <h3 className="text-xl font-semibold text-gray-900 mb-2">{news.title}</h3>
                  <p className="text-gray-600 mb-4">{news.excerpt}</p>
                  <Link 
                    to={`/news/${news.id}`} 
                    className="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center"
                  >
                    Read More
                    <svg className="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                    </svg>
                  </Link>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Call to Action */}
      <section className="bg-blue-700 text-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-3xl font-bold mb-6">Ready to Join Our Community?</h2>
          <p className="text-xl mb-8 max-w-3xl mx-auto">
            Take the first step towards an exceptional educational journey for your child.
          </p>
          <div className="flex flex-col sm:flex-row justify-center gap-4">
            <Link
              to="/admissions"
              className="bg-white text-blue-700 hover:bg-gray-100 font-semibold px-8 py-3 rounded-md text-lg transition-colors duration-300 inline-block"
            >
              Apply Now
            </Link>
            <Link
              to="/contact"
              className="bg-transparent hover:bg-white/10 border-2 border-white font-semibold px-8 py-3 rounded-md text-lg transition-colors duration-300 inline-block"
            >
              Contact Us
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
};

export default HomePage;
