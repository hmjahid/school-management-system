import { motion } from 'framer-motion';
import { Link } from 'react-router-dom';
import useWebsiteContent from '../hooks/useWebsiteContent';
import LoadingSpinner from '../components/common/LoadingSpinner';

const SitemapPage = () => {
  const defaultContent = {
    pageTitle: 'Sitemap',
    heroTitle: 'Website Sitemap',
    lastUpdated: 'October 12, 2025',
    description: 'Browse our complete website structure to find what you\'re looking for.',
    sections: [
      {
        title: 'Main Pages',
        links: [
          { text: 'Home', url: '/', description: 'Welcome to our school' },
          { text: 'About Us', url: '/about', description: 'Learn about our history, mission, and values' },
          { text: 'Academics', url: '/academics', description: 'Explore our academic programs and curriculum' },
          { text: 'Admissions', url: '/admissions', description: 'Information about enrollment and requirements' },
          { text: 'News & Events', url: '/news', description: 'Latest updates and school events' },
          { text: 'Gallery', url: '/gallery', description: 'Photos and videos of school life' },
          { text: 'Contact Us', url: '/contact', description: 'Get in touch with our team' }
        ]
      },
      {
        title: 'Academic Programs',
        links: [
          { text: 'Elementary School', url: '/academics/programs/elementary', description: 'Grades K-5 curriculum' },
          { text: 'Middle School', url: '/academics/programs/middle', description: 'Grades 6-8 curriculum' },
          { text: 'High School', url: '/academics/programs/high', description: 'Grades 9-12 curriculum' },
          { text: 'Advanced Placement', url: '/academics/programs/ap', description: 'College-level courses' },
          { text: 'Extracurriculars', url: '/academics/programs/extracurriculars', description: 'Clubs and activities' }
        ]
      },
      {
        title: 'Resources',
        links: [
          { text: 'School Calendar', url: '/calendar', description: 'Academic calendar and important dates' },
          { text: 'Parent Portal', url: '/portal', description: 'Access to grades and attendance' },
          { text: 'Lunch Menu', url: '/lunch', description: 'Monthly meal plans' },
          { text: 'School Supplies', url: '/supplies', description: 'Required materials by grade' },
          { text: 'Transportation', url: '/transportation', description: 'Bus routes and information' }
        ]
      },
      {
        title: 'Legal & Information',
        links: [
          { text: 'Terms of Service', url: '/terms', description: 'Website terms and conditions' },
          { text: 'Privacy Policy', url: '/privacy', description: 'How we handle your information' },
          { text: 'Accessibility', url: '/accessibility', description: 'Accessibility information' },
          { text: 'Careers', url: '/careers', description: 'Job opportunities' },
          { text: 'Staff Directory', url: '/staff', description: 'Contact information for faculty and staff' }
        ]
      }
    ]
  };

  const { content, loading, error } = useWebsiteContent('sitemap', defaultContent);

  if (loading && !content) return <LoadingSpinner size="large" />;
  if (error) return <div className="min-h-screen flex items-center justify-center">Error loading content. Please try again later.</div>;

  return (
    <div className="min-h-screen bg-white">
      <div className="bg-blue-700 text-white py-16">
        <div className="container mx-auto px-4">
          <motion.h1 
            className="text-4xl font-bold mb-2"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
          >
            {content.heroTitle}
          </motion.h1>
          <p className="text-blue-100">Last updated: {content.lastUpdated}</p>
        </div>
      </div>

      <main className="container mx-auto px-4 py-12">
        <div className="max-w-5xl mx-auto">
          <motion.p 
            className="text-lg text-gray-700 mb-12 text-center max-w-3xl mx-auto"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
          >
            {content.description}
          </motion.p>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
            {content.sections?.map((section, sectionIndex) => (
              <motion.div 
                key={sectionIndex}
                className="bg-white rounded-lg shadow-sm p-6 border border-gray-100"
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.5, delay: sectionIndex * 0.1 }}
              >
                <h2 className="text-xl font-bold mb-4 text-gray-800 border-b pb-2">{section.title}</h2>
                <ul className="space-y-3">
                  {section.links?.map((link, linkIndex) => (
                    <motion.li 
                      key={linkIndex}
                      initial={{ opacity: 0, x: -10 }}
                      animate={{ opacity: 1, x: 0 }}
                      transition={{ duration: 0.3, delay: sectionIndex * 0.1 + linkIndex * 0.03 }}
                      className="group"
                    >
                      <Link 
                        to={link.url} 
                        className="flex flex-col p-3 rounded-lg hover:bg-blue-50 transition-colors"
                      >
                        <span className="text-blue-600 font-medium group-hover:text-blue-800 transition-colors">
                          {link.text}
                        </span>
                        <span className="text-sm text-gray-500 mt-1">
                          {link.description}
                        </span>
                      </Link>
                    </motion.li>
                  ))}
                </ul>
              </motion.div>
            ))}
          </div>
          
          <motion.div 
            className="mt-12 bg-blue-50 rounded-lg p-6 text-center"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5, delay: 0.3 }}
          >
            <h3 className="text-xl font-semibold mb-2 text-gray-800">Can't find what you're looking for?</h3>
            <p className="text-gray-700 mb-4">Try our search function or contact our support team for assistance.</p>
            <div className="max-w-md mx-auto">
              <div className="relative">
                <input 
                  type="text" 
                  placeholder="Search our website..." 
                  className="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <button className="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-blue-600">
                  <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </button>
              </div>
            </div>
          </motion.div>
        </div>
      </main>
    </div>
  );
};

export default SitemapPage;
