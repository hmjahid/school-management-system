import { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { 
  FaBriefcase, 
  FaMapMarkerAlt, 
  FaCalendarAlt, 
  FaExternalLinkAlt,
  FaClock,
  FaGraduationCap,
  FaMoneyBillWave
} from 'react-icons/fa';
import { Link } from 'react-router-dom';
import LoadingSpinner from '../components/common/LoadingSpinner';
import careerService from '../services/careerService';
import { toast } from 'react-hot-toast';

const CareerPage = () => {
  const [jobs, setJobs] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filters, setFilters] = useState({
    status: 'active',
    department: ''
  });
  const [categories, setCategories] = useState([]);

  // Fetch job listings and categories
  useEffect(() => {
    const fetchData = async () => {
      try {
        setIsLoading(true);
        
        // Fetch active job listings
        const jobsResponse = await careerService.getJobListings({ 
          status: 'active',
          ...filters
        });
        
        // Fetch job categories
        const categoriesResponse = await careerService.getJobCategories();

        if (jobsResponse.success) {
          setJobs(jobsResponse.data);
        } else {
          // Fallback to sample data if API fails
          const fallbackData = careerService.getFallbackData();
          setJobs(fallbackData.jobs);
          setCategories(fallbackData.categories);
          toast.error('Using sample data. Could not connect to server.');
        }

        if (categoriesResponse.success) {
          setCategories(categoriesResponse.data);
        }
        
      } catch (err) {
        console.error('Error fetching career data:', err);
        setError('Failed to load career information. Please try again later.');
        
        // Fallback to sample data
        const fallbackData = careerService.getFallbackData();
        setJobs(fallbackData.jobs);
        setCategories(fallbackData.categories);
      } finally {
        setIsLoading(false);
      }
    };

    fetchData();
  }, [filters]);

  const handleFilterChange = (e) => {
    const { name, value } = e.target;
    setFilters(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
  };

  // Get unique departments from jobs
  const departmentOptions = [
    { id: '', name: 'All Departments' },
    ...categories.map(cat => ({
      id: cat.id,
      name: cat.name
    }))
  ];

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
        <div className="max-w-6xl mx-auto">
          <motion.div 
            className="text-center mb-12"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
          >
            <h2 className="text-3xl font-bold text-gray-800 mb-2">
              Current Openings
            </h2>
            <p className="text-gray-600 max-w-2xl mx-auto">
              Join our team of dedicated educators and staff members committed to excellence in education.
            </p>
          </motion.div>

          {/* Filters */}
          <motion.div 
            className="bg-white p-4 rounded-lg shadow-sm mb-8"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5, delay: 0.1 }}
          >
            <div className="flex flex-col md:flex-row md:items-center md:space-x-4 space-y-4 md:space-y-0">
              <div className="flex-1">
                <label htmlFor="department" className="block text-sm font-medium text-gray-700 mb-1">
                  Filter by Department
                </label>
                <select
                  id="department"
                  name="department"
                  value={filters.department}
                  onChange={handleFilterChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                >
                  {departmentOptions.map(option => (
                    <option key={option.id} value={option.id}>
                      {option.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>
          </motion.div>
          
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
                      <div className="flex-1">
                        <h3 className="text-xl font-bold text-gray-800">{job.title}</h3>
                        <div className="flex flex-wrap items-center mt-2 text-sm text-gray-600">
                          <span className="flex items-center mr-4 mb-1">
                            <FaBriefcase className="mr-1 text-blue-500" />
                            {job.type}
                          </span>
                          <span className="flex items-center mr-4 mb-1">
                            <FaMapMarkerAlt className="mr-1 text-blue-500" />
                            {job.location || 'Not specified'}
                          </span>
                          <span className="flex items-center mr-4 mb-1">
                            <FaCalendarAlt className="mr-1 text-blue-500" />
                            Posted: {formatDate(job.postedDate)}
                          </span>
                          {job.deadline && (
                            <span className="flex items-center mr-4 mb-1">
                              <FaClock className="mr-1 text-red-500" />
                              Apply by: {formatDate(job.deadline)}
                            </span>
                          )}
                        </div>
                        <div className="mt-3 flex flex-wrap gap-2">
                          <span className="inline-flex items-center bg-blue-50 text-blue-800 text-xs px-3 py-1 rounded-full">
                            <FaGraduationCap className="mr-1" />
                            {job.education || 'Education not specified'}
                          </span>
                          {job.experience && (
                            <span className="inline-flex items-center bg-green-50 text-green-800 text-xs px-3 py-1 rounded-full">
                              <FaBriefcase className="mr-1" />
                              {job.experience}
                            </span>
                          )}
                          {job.salary && (
                            <span className="inline-flex items-center bg-purple-50 text-purple-800 text-xs px-3 py-1 rounded-full">
                              <FaMoneyBillWave className="mr-1" />
                              {job.salary}
                            </span>
                          )}
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
