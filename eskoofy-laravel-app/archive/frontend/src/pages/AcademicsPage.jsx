import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { FaCalendarAlt, FaBook, FaGraduationCap, FaFlask, FaPalette, FaChartLine } from 'react-icons/fa';
import LoadingSpinner from '../components/common/LoadingSpinner';
import useWebsiteContent from '../hooks/useWebsiteContent';

const AcademicsPage = () => {
  // Default content in case the API is not available
  const defaultContent = {
    pageTitle: 'Academics',
    heroTitle: 'Excellence in Education',
    heroSubtitle: 'Nurturing curious minds and fostering lifelong learning',
    introText: 'We offer a comprehensive academic program designed to prepare students for success in higher education and beyond.',
    departments: [
      {
        id: 1,
        name: 'Science',
        description: 'Our science department offers a rigorous curriculum that emphasizes critical thinking, problem-solving, and hands-on experimentation in physics, chemistry, biology, and environmental science.',
        icon: 'FaFlask'
      },
      {
        id: 2,
        name: 'Arts',
        description: 'The arts program focuses on developing creativity, self-expression, and cultural appreciation through visual arts, music, theater, and dance.',
        icon: 'FaPalette'
      },
      {
        id: 3,
        name: 'Commerce',
        description: 'Commerce education for future business leaders, covering accounting, economics, business studies, and entrepreneurship.',
        icon: 'FaChartLine'
      }
    ],
    calendar: {
      title: 'Academic Calendar',
      description: 'Our academic calendar includes important dates for the current school year, including term start/end dates, holidays, and examination periods.',
      buttonText: 'View Academic Calendar',
      fileUrl: '/documents/academic-calendar.pdf'
    },
    curriculum: {
      title: 'Our Curriculum',
      description: 'We follow a comprehensive curriculum that meets national education standards while incorporating innovative teaching methods and 21st-century learning skills.',
      features: [
        'Interactive and engaging learning experiences',
        'Technology integration across all subjects',
        'Focus on critical thinking and problem-solving',
        'Project-based and experiential learning',
        'Personalized learning paths',
        'Regular assessments and feedback'
      ]
    }
  };

  // Fetch content from the backend
  const { content, loading, error } = useWebsiteContent('academics', defaultContent);

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
          <p className="text-gray-600 mb-6">Failed to load academic programs. Please try again later.</p>
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

  // Icon mapping
  const iconComponents = {
    FaFlask: FaFlask,
    FaPalette: FaPalette,
    FaChartLine: FaChartLine,
    FaBook: FaBook,
    FaGraduationCap: FaGraduationCap,
    FaCalendarAlt: FaCalendarAlt
  };

  return (
    <div className="min-h-screen bg-white">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-blue-800 to-blue-600 text-white py-16">
        <div className="container mx-auto px-4 text-center">
          <motion.h1 
            className="text-4xl md:text-5xl font-bold mb-4"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
          >
            {content.heroTitle || content.pageTitle}
          </motion.h1>
          <motion.p 
            className="text-xl md:text-2xl text-blue-100 max-w-3xl mx-auto"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5, delay: 0.2 }}
          >
            {content.heroSubtitle}
          </motion.p>
        </div>
      </div>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-12">
        {/* Academic Programs */}
        <section className="mb-16">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5, delay: 0.3 }}
          >
            <h2 className="text-3xl font-bold text-center mb-4">Our Academic Programs</h2>
            <div className="w-24 h-1 bg-blue-600 mx-auto mb-8"></div>
            <p className="text-lg text-gray-700 mb-12 text-center max-w-4xl mx-auto">
              {content.introText}
            </p>
            
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
              {content.departments?.map((dept, index) => {
                const Icon = iconComponents[dept.icon] || FaBook;
                return (
                  <motion.div 
                    key={dept.id || index}
                    className="bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow border border-gray-100"
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true }}
                    transition={{ duration: 0.4, delay: index * 0.1 }}
                  >
                    <div className="text-blue-600 text-4xl mb-4">
                      <Icon className="inline-block" />
                    </div>
                    <h3 className="text-xl font-semibold mb-3 text-gray-900">{dept.name}</h3>
                    <p className="text-gray-600 mb-4">{dept.description}</p>
                    <Link 
                      to={`/academics/${dept.name.toLowerCase().replace(/\s+/g, '-')}`} 
                      className="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center transition-colors"
                    >
                      Learn more <span className="ml-1">→</span>
                    </Link>
                  </motion.div>
                );
              })}
            </div>
          </motion.div>
        </section>

        {/* Curriculum Section */}
        {content.curriculum && (
          <section className="mb-16 bg-gray-50 rounded-xl p-8">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5 }}
            >
              <h2 className="text-3xl font-bold mb-6 text-gray-900">{content.curriculum.title}</h2>
              <p className="text-lg text-gray-700 mb-6">
                {content.curriculum.description}
              </p>
              <div className="grid md:grid-cols-2 gap-4">
                {content.curriculum.features?.map((feature, index) => (
                  <div key={index} className="flex items-start">
                    <div className="flex-shrink-0 h-6 w-6 text-green-500 mr-3 mt-0.5">
                      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                      </svg>
                    </div>
                    <p className="text-gray-700">{feature}</p>
                  </div>
                ))}
              </div>
            </motion.div>
          </section>
        )}

        {/* Academic Calendar */}
        <section className="mb-16">
          <motion.div
            className="bg-white p-8 rounded-xl shadow-md border border-gray-100"
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5, delay: 0.2 }}
          >
            <div className="flex flex-col md:flex-row md:items-center justify-between">
              <div className="mb-6 md:mb-0 md:mr-8">
                <h2 className="text-2xl font-semibold text-gray-900 mb-3">
                  {content.calendar?.title || 'Academic Calendar'}
                </h2>
                <p className="text-gray-600">
                  {content.calendar?.description || 'View important dates for the current academic year.'}
                </p>
              </div>
              <a
                href={content.calendar?.fileUrl || '#'}
                target="_blank"
                rel="noopener noreferrer"
                className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors inline-flex items-center justify-center whitespace-nowrap"
              >
                <FaCalendarAlt className="mr-2" />
                {content.calendar?.buttonText || 'View Calendar'}
              </a>
            </div>
          </motion.div>
        </section>
      </main>
    </div>
  );
};

export default AcademicsPage;
