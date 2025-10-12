import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { FaBriefcase, FaMapMarkerAlt, FaCalendarAlt, FaExternalLinkAlt } from 'react-icons/fa';
import { Link } from 'react-router-dom';
import LoadingSpinner from '../components/common/LoadingSpinner';

const CareerPage = () => {
  const [jobs, setJobs] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    // In a real application, this would fetch from your API
    const fetchJobs = async () => {
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 800));
        
        // Sample job data - in a real app, this would come from your API
        const sampleJobs = [
          {
            id: 1,
            title: 'Mathematics Teacher',
            type: 'Full-time',
            location: 'Dhaka, Bangladesh',
            department: 'High School',
            postedDate: '2023-11-01',
            deadline: '2023-12-15',
            description: 'We are looking for an experienced Mathematics teacher to join our high school department.',
            requirements: [
              'Master\'s degree in Mathematics or related field',
              'B.Ed or equivalent teaching certification',
              'Minimum 3 years of teaching experience',
              'Strong knowledge of curriculum standards'
            ],
            responsibilities: [
              'Develop and deliver engaging math lessons',
              'Assess and evaluate student progress',
              'Participate in school events and meetings',
              'Collaborate with other faculty members'
            ]
          },
          {
            id: 2,
            title: 'Science Laboratory Assistant',
            type: 'Part-time',
            location: 'Dhaka, Bangladesh',
            department: 'Science Department',
            postedDate: '2023-11-05',
            deadline: '2023-12-10',
            description: 'Assist in the preparation and maintenance of science laboratories.',
            requirements: [
              'Bachelor\'s degree in a science-related field',
              'Previous lab experience preferred',
              'Knowledge of lab safety procedures',
              'Attention to detail'
            ],
            responsibilities: [
              'Prepare laboratory equipment and materials',
              'Maintain inventory of supplies',
              'Ensure lab safety standards are met',
              'Assist teachers during lab sessions'
            ]
          },
          {
            id: 3,
            title: 'School Counselor',
            type: 'Full-time',
            location: 'Dhaka, Bangladesh',
            department: 'Student Services',
            postedDate: '2023-11-10',
            deadline: '2023-12-20',
            description: 'Provide guidance and support to students in their academic and personal development.',
            requirements: [
              'Master\'s degree in Counseling or related field',
              'Counseling certification',
              'Experience in an educational setting',
              'Strong communication skills'
            ],
            responsibilities: [
              'Provide individual and group counseling',
              'Develop student success plans',
              'Collaborate with teachers and parents',
              'Organize career guidance programs'
            ]
          }
        ];

        setJobs(sampleJobs);
        setIsLoading(false);
      } catch (err) {
        console.error('Error fetching job listings:', err);
        setError('Failed to load job listings. Please try again later.');
        setIsLoading(false);
      }
    };

    fetchJobs();
  }, []);

  const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <LoadingSpinner size="large" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-7xl mx-auto">
          <div className="text-center">
            <h1 className="text-4xl font-bold text-red-600 mb-4">Error Loading Content</h1>
            <p className="text-lg text-gray-600">{error}</p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <div className="bg-gradient-to-r from-blue-800 to-blue-600 text-white py-16">
        <div className="container mx-auto px-4 text-center">
          <motion.h1 
            className="text-4xl md:text-5xl font-bold mb-4"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
          >
            Join Our Team
          </motion.h1>
          <motion.p 
            className="text-xl md:text-2xl text-blue-100 max-w-3xl mx-auto"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5, delay: 0.2 }}
          >
            Be part of our mission to provide quality education
          </motion.p>
        </div>
      </div>

      {/* Job Listings */}
      <div className="container mx-auto px-4 py-12">
        <div className="max-w-4xl mx-auto">
          <h2 className="text-3xl font-bold text-gray-800 mb-8 text-center">
            Current Openings
          </h2>
          
          {jobs.length === 0 ? (
            <div className="text-center py-12">
              <p className="text-gray-600 text-lg">There are no current job openings. Please check back later.</p>
            </div>
          ) : (
            <div className="space-y-6">
              {jobs.map((job, index) => (
                <motion.div 
                  key={job.id}
                  className="bg-white rounded-lg shadow-md overflow-hidden"
                  initial={{ opacity: 0, y: 20 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.3, delay: index * 0.1 }}
                >
                  <div className="p-6">
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between">
                      <div>
                        <h3 className="text-xl font-bold text-gray-800">{job.title}</h3>
                        <div className="flex flex-wrap items-center mt-2 text-sm text-gray-600">
                          <span className="flex items-center mr-4">
                            <FaBriefcase className="mr-1" />
                            {job.type}
                          </span>
                          <span className="flex items-center mr-4">
                            <FaMapMarkerAlt className="mr-1" />
                            {job.location}
                          </span>
                          <span className="flex items-center">
                            <FaCalendarAlt className="mr-1" />
                            Posted: {formatDate(job.postedDate)}
                          </span>
                        </div>
                        <div className="mt-2">
                          <span className="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                            {job.department}
                          </span>
                        </div>
                      </div>
                      <div className="mt-4 md:mt-0">
                        <Link
                          to={`/careers/${job.id}`}
                          className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                          View Details <FaExternalLinkAlt className="ml-1" />
                        </Link>
                      </div>
                    </div>
                    
                    <div className="mt-4">
                      <p className="text-gray-700">{job.description}</p>
                    </div>
                    
                    <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div>
                            <h4 className="font-semibold text-gray-800 mb-2">Requirements</h4>
                            <ul className="list-disc pl-5 space-y-1 text-gray-700">
                              {job.requirements.map((req, i) => (
                                <li key={i}>{req}</li>
                              ))}
                            </ul>
                          </div>
                          <div>
                            <h4 className="font-semibold text-gray-800 mb-2">Responsibilities</h4>
                            <ul className="list-disc pl-5 space-y-1 text-gray-700">
                              {job.responsibilities.map((resp, i) => (
                                <li key={i}>{resp}</li>
                              ))}
                            </ul>
                          </div>
                        </div>
                        
                        <div className="mt-6 pt-4 border-t border-gray-200">
                          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div className="text-sm text-gray-500">
                              Application deadline: <span className="font-medium">{formatDate(job.deadline)}</span>
                            </div>
                            <button
                              className="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                              onClick={() => {
                                // In a real app, this would open an application form
                                alert(`Application for ${job.title} would open here`);
                              }}
                            >
                              Apply Now
                            </button>
                          </div>
                        </div>
                      </div>
                    </motion.div>
                  ))}
                </div>
              )}
            </div>
          </div>
          
          {/* Call to Action */}
          <div className="bg-white py-12">
            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
              <h2 className="text-2xl font-bold text-gray-900 mb-4">
                Don't see a position that matches your skills?
              </h2>
              <p className="text-gray-600 mb-6 max-w-2xl mx-auto">
                We're always looking for talented individuals to join our team. 
                Send us your resume and we'll contact you when a position becomes available.
              </p>
              <button
                className="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                onClick={() => {
                  // In a real app, this would open a contact form
                  alert('General application form would open here');
                }}
              >
                Submit General Application
              </button>
            </div>
          </div>
        </div>
      );
    };
    
    export default CareerPage;
