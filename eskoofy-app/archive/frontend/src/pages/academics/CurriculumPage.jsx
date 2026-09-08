import { motion } from 'framer-motion';
import { FaBookOpen, FaLaptopCode, FaUserGraduate, FaFlask, FaChartLine, FaPalette } from 'react-icons/fa';
import { useEffect, useState } from 'react';
import LoadingSpinner from '../../components/common/LoadingSpinner';
import academicService from '../../services/academicService';

const CurriculumPage = () => {
  const [pageData, setPageData] = useState({
    content: null,
    isLoading: true,
    error: null
  });

  useEffect(() => {
    const fetchCurriculum = async () => {
      try {
        const { success, data, error } = await academicService.getAcademicContent('academics/curriculum');
        
        if (success) {
          setPageData(prev => ({
            ...prev,
            content: data,
            isLoading: false,
            error: null
          }));
        } else {
          throw new Error(error || 'Failed to load curriculum data');
        }
      } catch (err) {
        console.error('Error fetching curriculum:', err);
        setPageData(prev => ({
          ...prev,
          isLoading: false,
          error: err.message || 'An unexpected error occurred'
        }));
      }
    };

    fetchCurriculum();
  }, []);

  // Default content in case the API is not available and no sample data is found
  const defaultContent = pageData.content || {
    pageTitle: 'Our Curriculum',
    heroTitle: 'Comprehensive Learning Experience',
    heroSubtitle: 'A well-rounded curriculum designed to foster academic excellence and personal growth',
    overview: 'Our curriculum is carefully designed to provide students with a balanced education that combines academic rigor with practical skills and character development.',
    programs: [
      {
        id: 1,
        title: 'Core Subjects',
        description: 'Strong foundation in Mathematics, Science, Languages, and Social Studies',
        icon: 'FaBookOpen',
        features: [
          'Mathematics & Statistics',
          'Sciences (Physics, Chemistry, Biology)',
          'Languages & Literature',
          'Social Studies & Humanities'
        ]
      },
      {
        id: 2,
        title: 'STEM Program',
        description: 'Focused on Science, Technology, Engineering, and Mathematics',
        icon: 'FaLaptopCode',
        features: [
          'Robotics & Coding',
          'Advanced Mathematics',
          'Engineering Principles',
          'Scientific Research Methods'
        ]
      },
      {
        id: 3,
        title: 'Arts & Humanities',
        description: 'Nurturing creativity and cultural appreciation',
        icon: 'FaPalette',
        features: [
          'Visual & Performing Arts',
          'Music & Theater',
          'World Languages',
          'Cultural Studies'
        ]
      }
    ],
    grading: {
      title: 'Assessment & Grading',
      description: 'We use a comprehensive assessment system to track student progress and ensure learning outcomes are met.',
      components: [
        'Continuous assessments and projects (40%)',
        'Mid-term examinations (20%)',
        'Final examinations (40%)',
        'Participation and classwork (20% of total grade)'
      ]
    }
  };

  // Use pageData for content and state management
  const content = pageData.content || defaultContent;

  // Icon mapping
  const iconComponents = {
    FaBookOpen,
    FaLaptopCode,
    FaUserGraduate,
    FaFlask,
    FaChartLine,
    FaPalette
  };

  // Show loading state
  if (pageData.isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <LoadingSpinner size="large" />
      </div>
    );
  }

  if (pageData.error) {
    return (
      <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-7xl mx-auto">
          <div className="text-center">
            <h1 className="text-4xl font-bold text-red-600 mb-4">Error Loading Content</h1>
            <p className="text-lg text-gray-600">We're having trouble loading the curriculum information. Using sample data instead.</p>
            <p className="text-sm text-gray-500 mt-2">Error: {pageData.error}</p>
          </div>
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
        {/* Overview */}
        <section className="mb-16">
          <motion.div
            className="max-w-4xl mx-auto text-center"
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5 }}
          >
            <h2 className="text-3xl font-bold text-gray-900 mb-6">Our Educational Approach</h2>
            <div className="w-24 h-1 bg-blue-600 mx-auto mb-8"></div>
            <p className="text-lg text-gray-700 mb-12">
              {content.overview}
            </p>
          </motion.div>
        </section>

        {/* Programs */}
        <section className="mb-16">
          <h2 className="text-3xl font-bold text-center text-gray-900 mb-12">Academic Programs</h2>
          
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {content.programs?.map((program, index) => {
              const Icon = iconComponents[program.icon] || FaBookOpen;
              return (
                <motion.div 
                  key={program.id || index}
                  className="bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow border border-gray-100"
                  initial={{ opacity: 0, y: 20 }}
                  whileInView={{ opacity: 1, y: 0 }}
                  viewport={{ once: true }}
                  transition={{ duration: 0.4, delay: index * 0.1 }}
                >
                  <div className="text-blue-600 text-4xl mb-4">
                    <Icon className="inline-block" />
                  </div>
                  <h3 className="text-xl font-semibold mb-3 text-gray-900">{program.title}</h3>
                  <p className="text-gray-600 mb-4">{program.description}</p>
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
                </motion.div>
              );
            })}
          </div>
        </section>

        {/* Grading System */}
        {content.grading && (
          <section className="bg-gray-50 rounded-xl p-8 mb-16">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5 }}
              className="max-w-4xl mx-auto"
            >
              <h2 className="text-3xl font-bold text-center text-gray-900 mb-6">
                {content.grading.title}
              </h2>
              <p className="text-lg text-gray-700 mb-8 text-center">
                {content.grading.description}
              </p>
              <div className="bg-white p-6 rounded-lg shadow">
                <ul className="space-y-3">
                  {content.grading.components.map((item, index) => (
                    <li key={index} className="flex items-start">
                      <div className="bg-blue-100 text-blue-600 rounded-full w-6 h-6 flex items-center justify-center flex-shrink-0 mr-3 mt-0.5">
                        {index + 1}
                      </div>
                      <p className="text-gray-700">{item}</p>
                    </li>
                  ))}
                </ul>
              </div>
            </motion.div>
          </section>
        )}
      </main>
    </div>
  );
};

export default CurriculumPage;
