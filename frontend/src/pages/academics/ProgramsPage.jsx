import { motion } from 'framer-motion';
import { FaCalendarAlt, FaBook, FaGraduationCap, FaFlask, FaPalette, FaChartLine } from 'react-icons/fa';
import useWebsiteContent from '../../hooks/useWebsiteContent';
import LoadingSpinner from '../../components/common/LoadingSpinner';

const ProgramsPage = () => {
  // Default content in case the API is not available
  const defaultContent = {
    pageTitle: 'Academic Programs',
    heroTitle: 'Explore Our Programs',
    heroSubtitle: 'Comprehensive educational pathways to nurture every student\'s potential',
    intro: 'Our school offers a diverse range of academic programs designed to meet the needs and interests of all students, from early childhood through high school graduation.',
    programs: [
      {
        id: 1,
        name: 'Elementary School',
        description: 'A strong foundation for lifelong learning with a focus on literacy, numeracy, and social development.',
        icon: 'FaGraduationCap',
        grades: 'Grades K-5',
        features: [
          'Literacy & Language Arts',
          'Mathematics & Problem Solving',
          'Science Exploration',
          'Social Studies',
          'Arts & Music',
          'Physical Education'
        ]
      },
      {
        id: 2,
        name: 'Middle School',
        description: 'A transitional program that builds on elementary foundations while preparing students for high school.',
        icon: 'FaFlask',
        grades: 'Grades 6-8',
        features: [
          'Advanced Mathematics',
          'Laboratory Sciences',
          'Literature & Composition',
          'World Languages',
          'Computer Science',
          'Elective Courses'
        ]
      },
      {
        id: 3,
        name: 'High School',
        description: 'College-preparatory curriculum with Advanced Placement and honors courses across all disciplines.',
        icon: 'FaLaptopCode',
        grades: 'Grades 9-12',
        features: [
          'Advanced Placement (AP) Courses',
          'STEM Electives',
          'Fine & Performing Arts',
          'World Languages',
          'College & Career Counseling',
          'Dual Enrollment Options'
        ]
      },
      {
        id: 4,
        name: 'Special Programs',
        description: 'Specialized programs to meet diverse learning needs and interests.',
        icon: 'FaPalette',
        grades: 'All Grades',
        features: [
          'Gifted & Talented',
          'Special Education',
          'ESL/ELL Support',
          'Performing Arts Academy',
          'Athletic Programs',
          'Clubs & Organizations'
        ]
      }
    ]
  };

  // Fetch content from the backend
  const { content, loading, error } = useWebsiteContent('academics/programs', defaultContent);

  // Icon mapping
  const iconComponents = {
    FaGraduationCap,
    FaFlask,
    FaLaptopCode,
    FaPalette,
    FaGlobe,
    FaChartLine
  };

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
          <p className="text-gray-600 mb-6">Failed to load program information. Please try again later.</p>
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
        {/* Introduction */}
        <section className="mb-16">
          <motion.div
            className="max-w-4xl mx-auto text-center"
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5 }}
          >
            <h2 className="text-3xl font-bold text-gray-900 mb-6">Our Academic Offerings</h2>
            <div className="w-24 h-1 bg-blue-600 mx-auto mb-8"></div>
            <p className="text-lg text-gray-700 mb-12">
              {content.intro}
            </p>
          </motion.div>
        </section>

        {/* Programs Grid */}
        <section className="mb-16">
          <div className="grid md:grid-cols-2 gap-8">
            {content.programs?.map((program, index) => {
              const Icon = iconComponents[program.icon] || FaGraduationCap;
              return (
                <motion.div 
                  key={program.id || index}
                  className="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-100 hover:shadow-xl transition-shadow"
                  initial={{ opacity: 0, y: 20 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.4, delay: index * 0.1 }}
                >
                  <div className="bg-blue-700 text-white p-4 flex items-center">
                    <div className="bg-white bg-opacity-20 p-3 rounded-full mr-4">
                      <Icon className="h-6 w-6 text-white" />
                    </div>
                    <div>
                      <h3 className="text-xl font-bold">{program.name}</h3>
                      <p className="text-blue-100 text-sm">{program.grades}</p>
                    </div>
                  </div>
                  <div className="p-6">
                    <p className="text-gray-700 mb-4">{program.description}</p>
                    <h4 className="font-semibold text-gray-800 mb-2">Program Highlights:</h4>
                    <ul className="space-y-2">
                      {program.features.map((feature, i) => (
                        <li key={i} className="flex items-start">
                          <svg className="h-5 w-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                          </svg>
                          <span className="text-gray-700">{feature}</span>
                        </li>
                      ))}
                    </ul>
                    <div className="mt-6">
                      <button className="w-full py-2 px-4 border border-blue-600 text-blue-600 rounded-md hover:bg-blue-50 transition-colors">
                        Learn More About {program.name}
                      </button>
                    </div>
                  </div>
                </motion.div>
              );
            })}
          </div>
        </section>

        {/* Additional Information */}
        <section className="bg-blue-50 rounded-xl p-8 mb-16">
          <motion.div
            className="max-w-4xl mx-auto text-center"
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5 }}
          >
            <h2 className="text-3xl font-bold text-gray-900 mb-6">Ready to Learn More?</h2>
            <p className="text-lg text-gray-700 mb-8">
              Discover how our academic programs can help your child reach their full potential. 
              Contact our admissions office to schedule a tour or request more information.
            </p>
            <div className="flex flex-col sm:flex-row justify-center gap-4">
              <a 
                href="/admissions" 
                className="px-6 py-3 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition-colors text-center"
              >
                Apply Now
              </a>
              <a 
                href="/contact" 
                className="px-6 py-3 border border-blue-600 text-blue-600 font-medium rounded-md hover:bg-blue-50 transition-colors text-center"
              >
                Contact Us
              </a>
            </div>
          </motion.div>
        </section>
      </main>
    </div>
  );
};

export default ProgramsPage;
