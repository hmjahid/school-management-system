import React from 'react';
import { motion } from 'framer-motion';
import { FaAward, FaGraduationCap, FaUsers, FaBookOpen, FaSchool, FaEnvelope, FaPhone, FaMapMarkerAlt } from 'react-icons/fa';
import { GiTeacher } from 'react-icons/gi';
import LoadingSpinner from '../components/common/LoadingSpinner';
import useWebsiteContent from '../hooks/useWebsiteContent';

const AboutPage = () => {
  // Default content structure that matches the backend model
  const defaultContent = {
    school_name: 'Our School',
    tagline: 'Nurturing minds, building character',
    about_summary: 'A leading educational institution committed to excellence in education and character development.',
    mission: 'To empower students to achieve their full potential through a balanced education that fosters intellectual growth, character development, and a lifelong love of learning.',
    vision: 'To be a leading educational institution that inspires and prepares students to become responsible global citizens and future leaders.',
    core_values: [
      'Excellence in Education',
      'Integrity and Respect',
      'Diversity and Inclusion',
      'Innovation and Creativity',
      'Community Engagement'
    ],
    contact_info: {
      address: '123 Education Street, City, Country',
      phone: '+1 (555) 123-4567',
      email: 'info@school.edu',
      website: 'www.school.edu'
    },
    social_links: {
      facebook: '#',
      twitter: '#',
      instagram: '#',
      linkedin: '#'
    }
  };

  // Use the useWebsiteContent hook to fetch and manage about page content
  const { content, loading, error } = useWebsiteContent('about', defaultContent);

  // Destructure content with default values to prevent errors
  const {
    school_name = 'Our School',
    tagline = 'Nurturing minds, building character',
    about_summary = 'A leading educational institution committed to excellence in education and character development.',
    mission: missionText = 'To empower students to achieve their full potential through a balanced education that fosters intellectual growth, character development, and a lifelong love of learning.',
    vision: visionText = 'To be a leading educational institution that inspires and prepares students to become responsible global citizens and future leaders.',
    core_values = [
      'Excellence in Education',
      'Integrity and Respect',
      'Diversity and Inclusion',
      'Innovation and Creativity'
    ],
    contact_info: contactInfo = {},
    social_links: socialLinks = {}
  } = content || defaultContent;
  
  const { 
    address = '123 Education Street, City, Country',
    phone = '+1 (555) 123-4567',
    email = 'info@school.edu',
    website = 'www.school.edu'
  } = contactInfo;

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <LoadingSpinner />
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="text-red-600">{error}</div>
      </div>
    );
  }

  // Default leadership team data
  const leadershipTeam = [
    {
      name: 'Dr. Sarah Johnson',
      role: 'Principal',
      bio: 'With over 20 years of experience in education, Dr. Johnson leads our school with vision and dedication to academic excellence.',
      image: '/images/principal.jpg'
    },
    {
      name: 'Michael Chen',
      role: 'Vice Principal',
      bio: 'A passionate educator with expertise in curriculum development and student engagement strategies.',
      image: '/images/vice-principal.jpg'
    },
    {
      name: 'Priya Patel',
      role: 'Head of Academics',
      bio: 'Committed to fostering innovative teaching methods and supporting our teaching staff in delivering exceptional education.',
      image: '/images/head-academics.jpg'
    }
  ];
  
  // Helper function to render line breaks in text
  const renderTextWithLineBreaks = (text) => {
    return text.split('\n').map((paragraph, index) => (
      <p key={index} className="mb-4">{paragraph}</p>
    ));
  };

  return (
    <div className="bg-white">
      {/* Hero Section */}
      <div className="relative bg-gray-900 text-white">
        <div className="absolute inset-0 bg-gray-800">
          <div className="absolute inset-0 bg-gradient-to-r from-blue-900 to-transparent opacity-75"></div>
        </div>
        <div className="relative max-w-7xl mx-auto py-24 px-4 sm:py-32 sm:px-6 lg:px-8">
          <h1 className="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
            About {school_name}
          </h1>
          <p className="mt-6 text-xl text-blue-100 max-w-3xl">
            {tagline}
          </p>
        </div>
      </div>

      {/* About Section */}
      <div className="py-16 bg-white overflow-hidden">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="lg:grid lg:grid-cols-2 lg:gap-8 items-center">
            <div className="relative lg:row-start-1 lg:col-start-2">
              <div className="relative text-base mx-auto max-w-prose lg:max-w-none">
                <figure>
                  <div className="aspect-w-12 aspect-h-7 lg:aspect-none">
                    <img
                      className="rounded-lg shadow-lg object-cover object-center"
                      src={"/images/school-building.jpg"}
                      alt={school_name}
                      width={1184}
                      height={1376}
                    />
                  </div>
                </figure>
              </div>
            </div>
            <div className="mt-8 lg:mt-0">
              <div className="text-base max-w-prose mx-auto lg:max-w-none">
                <h2 className="text-3xl font-extrabold text-gray-900 mb-4">
                  Our Story
                </h2>
                <p className="text-lg text-gray-600">
                  {about_summary}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Mission & Vision */}
      <section className="py-16 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="lg:text-center mb-12">
            <h2 className="text-3xl font-bold text-gray-900 mb-4">Our Mission & Vision</h2>
            <div className="w-20 h-1 bg-blue-600 mx-auto"></div>
          </div>
          
          <div className="grid md:grid-cols-2 gap-8">
            <motion.div 
              className="bg-white p-8 rounded-lg shadow-md"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5 }}
            >
              <h2 className="text-3xl font-bold text-gray-900 mb-6">Our Mission</h2>
              <div className="text-lg text-gray-600 space-y-4">
                {missionText}
              </div>
            </motion.div>
            <motion.div 
              className="bg-white p-8 rounded-lg shadow-md"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, delay: 0.2 }}
            >
              <h2 className="text-3xl font-bold text-gray-900 mb-6">Our Vision</h2>
              <div className="text-lg text-gray-600">
                {visionText}
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Core Values */}
      <section className="py-16 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h2 className="text-base text-blue-600 font-semibold tracking-wide uppercase">What We Believe In</h2>
            <p className="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
              Our Core Values
            </p>
            <p className="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
              The foundation of our educational philosophy
            </p>
          </div>

          <div className="mt-10">
            <div className="space-y-10 md:space-y-0 md:grid md:grid-cols-2 lg:grid-cols-3 md:gap-x-8 md:gap-y-10">
              {core_values.map((value, index) => {
                // Map value names to corresponding icons
                const valueIcons = [
                  FaAward,          // Excellence in Education
                  FaUsers,          // Integrity and Respect
                  FaBookOpen,       // Diversity and Inclusion
                  GiTeacher,        // Innovation and Creativity
                  FaGraduationCap,  // Community Engagement
                  FaSchool          // Default
                ];
                
                const Icon = valueIcons[index] || valueIcons[valueIcons.length - 1];
                
                return (
                  <motion.div
                    key={index}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: index * 0.1 }}
                    className="bg-gray-50 p-6 rounded-lg shadow hover:shadow-md transition-shadow duration-300 border border-gray-100"
                  >
                    <div className="flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white mb-4">
                      <Icon className="h-6 w-6" />
                    </div>
                    <h3 className="text-xl font-semibold text-gray-900">{value}</h3>
                  </motion.div>
                );
              })}
            </div>
          </div>
        </div>
      </section>

      {/* Leadership */}
      <section className="py-16 bg-blue-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-gray-900 mb-4">Our Leadership</h2>
            <div className="w-20 h-1 bg-blue-600 mx-auto"></div>
            <p className="mt-4 text-lg text-gray-600 max-w-3xl mx-auto">
              Meet the dedicated individuals who guide our school towards excellence
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {leadershipTeam.map((leader, index) => (
              <motion.div 
                key={index}
                className="bg-white rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow duration-300"
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: index * 0.1 }}
              >
                <div className="h-64 bg-gray-200 overflow-hidden">
                  <img 
                    src={leader.image || '/images/placeholder-avatar.png'} 
                    alt={leader.name}
                    className="w-full h-full object-cover"
                    loading="lazy"
                    onError={(e) => {
                      e.target.onerror = null; // Prevent infinite loop if placeholder also fails
                      e.target.src = '/images/placeholder-avatar.png';
                    }}
                  />
                </div>
                <div className="p-6">
                  <h3 className="text-xl font-semibold text-gray-900">{leader.name}</h3>
                  <p className="text-blue-600 font-medium mb-3">{leader.role}</p>
                  <p className="text-gray-600">{leader.bio}</p>
                </div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Contact CTA */}
      <section className="py-16 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-3xl font-bold text-gray-900 mb-6">Want to Learn More?</h2>
          <p className="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
            We'd love to hear from you. Contact us to schedule a tour or ask any questions about our programs.
          </p>
          <div className="flex flex-col sm:flex-row justify-center gap-4">
            <a
              href="/contact"
              className="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-3 rounded-md text-lg transition-colors duration-300 inline-flex items-center justify-center"
            >
              <FaEnvelope className="mr-2" /> Contact Us
            </a>
            <a
              href="/admissions"
              className="bg-white hover:bg-gray-50 text-blue-600 border-2 border-blue-600 font-semibold px-8 py-3 rounded-md text-lg transition-colors duration-300 inline-flex items-center justify-center"
            >
              <FaGraduationCap className="mr-2" /> Apply Now
            </a>
          </div>
          <div className="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8 text-left max-w-5xl mx-auto">
            <div className="flex items-start">
              <div className="bg-blue-100 p-3 rounded-full mr-4">
                <FaMapMarkerAlt className="w-6 h-6 text-blue-600" />
              </div>
              <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-1">Visit Us</h3>
                <p className="text-gray-600 whitespace-pre-line">123 Education Street
City, State 12345</p>
              </div>
            </div>
            <div className="flex items-start">
              <div className="bg-blue-100 p-3 rounded-full mr-4">
                <FaPhone className="w-6 h-6 text-blue-600" />
              </div>
              <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-1">Call Us</h3>
                <p className="text-gray-600">
                  +1 (555) 123-4567
                  <br />
                  Mon-Fri, 8:00 AM - 4:00 PM
                </p>
              </div>
            </div>
            <div className="flex items-start">
              <div className="bg-blue-100 p-3 rounded-full mr-4">
                <FaEnvelope className="w-6 h-6 text-blue-600" />
              </div>
              <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-1">Email Us</h3>
                <p className="text-gray-600">
                  info@schoolname.edu
                  <br />
                  admissions@schoolname.edu
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default AboutPage;
