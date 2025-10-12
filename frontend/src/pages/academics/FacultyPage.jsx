import { motion } from 'framer-motion';
import { FaChalkboardTeacher, FaUserTie, FaGraduationCap, FaBook, FaAward } from 'react-icons/fa';
import useWebsiteContent from '../../hooks/useWebsiteContent';
import LoadingSpinner from '../../components/common/LoadingSpinner';

const FacultyPage = () => {
  // Default content in case the API is not available
  const defaultContent = {
    pageTitle: 'Our Faculty',
    heroTitle: 'Meet Our Dedicated Faculty',
    heroSubtitle: 'Passionate educators committed to student success',
    intro: 'Our distinguished faculty members bring a wealth of knowledge, experience, and dedication to the classroom. They are not just teachers but mentors who inspire and guide students to achieve their full potential.',
    departments: [
      {
        id: 1,
        name: 'Science Department',
        faculty: [
          {
            id: 1,
            name: 'Dr. Sarah Johnson',
            position: 'Head of Science',
            qualification: 'Ph.D. in Physics, MIT',
            bio: 'With over 15 years of teaching experience, Dr. Johnson specializes in quantum mechanics and has published numerous research papers.',
            image: '/images/faculty/sarah-johnson.jpg',
            subjects: ['Physics', 'Advanced Physics', 'Research Methods'],
            yearsAtSchool: 8
          },
          {
            id: 2,
            name: 'Michael Chen',
            position: 'Chemistry Teacher',
            qualification: 'M.Sc. in Chemistry, Stanford',
            bio: 'Mr. Chen brings innovative teaching methods to the chemistry lab, making complex concepts accessible to all students.',
            image: '/images/faculty/michael-chen.jpg',
            subjects: ['Chemistry', 'AP Chemistry', 'Forensic Science'],
            yearsAtSchool: 5
          },
          {
            id: 3,
            name: 'Dr. Priya Patel',
            position: 'Biology Professor',
            qualification: 'Ph.D. in Molecular Biology, Harvard',
            bio: 'Dr. Patel leads our advanced biology program and mentors students in cutting-edge research projects.',
            image: '/images/faculty/priya-patel.jpg',
            subjects: ['Biology', 'AP Biology', 'Genetics', 'Research Seminar'],
            yearsAtSchool: 6
          }
        ]
      },
      {
        id: 2,
        name: 'Mathematics Department',
        faculty: [
          {
            id: 4,
            name: 'Robert Williams',
            position: 'Mathematics Chair',
            qualification: 'M.A. in Mathematics, Cambridge',
            bio: 'With over 20 years of experience, Mr. Williams has a talent for making complex mathematical concepts understandable and engaging.',
            image: '/images/faculty/robert-williams.jpg',
            subjects: ['Calculus', 'Advanced Mathematics', 'Mathematical Theory'],
            yearsAtSchool: 12
          },
          {
            id: 5,
            name: 'Emily Zhang',
            position: 'Mathematics Teacher',
            qualification: 'M.Ed. in Mathematics Education, Columbia',
            bio: 'Ms. Zhang specializes in developing students\' problem-solving skills and mathematical reasoning abilities.',
            image: '/images/faculty/emily-zhang.jpg',
            subjects: ['Algebra', 'Geometry', 'Pre-Calculus'],
            yearsAtSchool: 4
          }
        ]
      },
      {
        id: 3,
        name: 'Humanities Department',
        faculty: [
          {
            id: 6,
            name: 'James Wilson',
            position: 'English Department Head',
            qualification: 'Ph.D. in English Literature, Oxford',
            bio: 'Dr. Wilson is an expert in 19th-century literature and creative writing, inspiring students to develop their voice.',
            image: '/images/faculty/james-wilson.jpg',
            subjects: ['British Literature', 'Creative Writing', 'AP English'],
            yearsAtSchool: 10
          },
          {
            id: 7,
            name: 'Maria Garcia',
            position: 'History Teacher',
            qualification: 'M.A. in History, Yale',
            bio: 'Ms. Garcia brings history to life with engaging discussions and primary source analysis.',
            image: '/images/faculty/maria-garcia.jpg',
            subjects: ['World History', 'AP European History', 'Modern World'],
            yearsAtSchool: 7
          }
        ]
      }
    ],
    stats: [
      { value: '95%', label: 'Hold Advanced Degrees' },
      { value: '15+', label: 'Average Years of Experience' },
      { value: '8:1', label: 'Student to Faculty Ratio' },
      { value: '100%', label: 'Committed to Professional Development' }
    ]
  };

  // Fetch content from the backend
  const { content, loading, error } = useWebsiteContent('academics/faculty', defaultContent);

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
          <p className="text-gray-600 mb-6">Failed to load faculty information. Please try again later.</p>
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
            <h2 className="text-3xl font-bold text-gray-900 mb-6">Excellence in Education</h2>
            <div className="w-24 h-1 bg-blue-600 mx-auto mb-8"></div>
            <p className="text-lg text-gray-700 mb-12">
              {content.intro}
            </p>
          </motion.div>
        </section>

        {/* Stats */}
        <section className="mb-16 bg-gray-50 rounded-xl p-8">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            {content.stats?.map((stat, index) => (
              <motion.div 
                key={index}
                className="p-4"
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: index * 0.1 }}
              >
                <div className="text-3xl font-bold text-blue-700 mb-2">{stat.value}</div>
                <div className="text-gray-600">{stat.label}</div>
              </motion.div>
            ))}
          </div>
        </section>

        {/* Faculty by Department */}
        <section className="mb-16">
          {content.departments?.map((department, deptIndex) => (
            <motion.div 
              key={department.id || deptIndex}
              className="mb-12"
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5 }}
            >
              <h2 className="text-2xl font-bold text-gray-900 mb-6 border-b-2 border-blue-200 pb-2">
                {department.name}
              </h2>
              
              <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                {department.faculty?.map((teacher, teacherIndex) => (
                  <motion.div 
                    key={teacher.id || teacherIndex}
                    className="bg-white rounded-lg shadow-md overflow-hidden border border-gray-100 hover:shadow-lg transition-shadow"
                    initial={{ opacity: 0, y: 20 }}
                    whileInView={{ opacity: 1, y: 0 }}
                    viewport={{ once: true }}
                    transition={{ duration: 0.3, delay: teacherIndex * 0.1 }}
                  >
                    <div className="h-48 bg-gray-200 overflow-hidden">
                      <img 
                        src={teacher.image || '/images/placeholder-faculty.jpg'} 
                        alt={teacher.name}
                        className="w-full h-full object-cover"
                        onError={(e) => {
                          e.target.onerror = null;
                          e.target.src = '/images/placeholder-faculty.jpg';
                        }}
                      />
                    </div>
                    <div className="p-6">
                      <h3 className="text-xl font-bold text-gray-900 mb-1">{teacher.name}</h3>
                      <p className="text-blue-600 font-medium mb-2">{teacher.position}</p>
                      <p className="text-sm text-gray-500 mb-4 flex items-center">
                        <FaGraduationCap className="mr-2" />
                        {teacher.qualification}
                      </p>
                      <p className="text-gray-700 text-sm mb-4">{teacher.bio}</p>
                      
                      <div className="mt-4 pt-4 border-t border-gray-100">
                        <h4 className="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                          <FaBook className="mr-2 text-blue-500" />
                          Teaches:
                        </h4>
                        <div className="flex flex-wrap gap-2">
                          {teacher.subjects?.map((subject, i) => (
                            <span key={i} className="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded">
                              {subject}
                            </span>
                          ))}
                        </div>
                      </div>
                      
                      <div className="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                        <span className="text-sm text-gray-500">
                          <FaAward className="inline mr-1 text-yellow-500" />
                          {teacher.yearsAtSchool || 'Several'} years at our school
                        </span>
                      </div>
                    </div>
                  </motion.div>
                ))}
              </div>
            </motion.div>
          ))}
        </section>

        {/* Join Our Team */}
        <section className="bg-blue-50 rounded-xl p-8 text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5 }}
          >
            <h2 className="text-2xl font-bold text-gray-900 mb-4">Join Our Exceptional Faculty</h2>
            <p className="text-gray-700 mb-6 max-w-2xl mx-auto">
              We're always looking for passionate educators to join our team. If you're dedicated to excellence in education and want to make a difference in students' lives, we'd love to hear from you.
            </p>
            <a 
              href="/careers" 
              className="inline-block px-6 py-3 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition-colors"
            >
              View Current Openings
            </a>
          </motion.div>
        </section>
      </main>
    </div>
  );
};

export default FacultyPage;
