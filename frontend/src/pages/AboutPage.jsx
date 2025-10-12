import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { FaAward, FaGraduationCap, FaUsers, FaBookOpen, FaSchool, FaEnvelope, FaPhone, FaMapMarkerAlt } from 'react-icons/fa';
import { GiTeacher } from 'react-icons/gi';
import axios from 'axios';
import LoadingSpinner from '../components/common/LoadingSpinner';

const AboutPage = () => {
  const [content, setContent] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchContent = async () => {
      try {
        const response = await axios.get('/api/website/about');
        setContent(response.data);
      } catch (err) {
        console.error('Error fetching about content:', err);
        setError('Failed to load content. Please try again later.');
      } finally {
        setLoading(false);
      }
    };

    fetchContent();
  }, []);

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

  // Destructure content with default values to prevent errors if content is null
  const {
    hero = { title: 'About Our School', subtitle: 'Nurturing minds, building character, and shaping futures since 1995' },
    mission = 'To empower students to become compassionate, confident, and capable individuals who are prepared to meet the challenges of a rapidly changing world with integrity and purpose.',
    vision = 'To be a beacon of educational excellence, innovation, and character development, where every student discovers their unique potential and is inspired to make a positive impact in their community and beyond.',
    history = { text: 'Founded in 1995, our school began as a small educational institution with just 50 students and a handful of dedicated teachers. Over the years, we have grown into a thriving learning community that serves over 1,000 students from diverse backgrounds.' },
    values = [
      {
        title: 'Excellence',
        description: 'We strive for the highest standards in academics, character development, and personal growth.'
      },
      {
        title: 'Community',
        description: 'We foster a supportive and inclusive environment that values diversity and collaboration.'
      },
      {
        title: 'Lifelong Learning',
        description: 'We cultivate curiosity and a love for learning that extends beyond the classroom.'
      }
    ],
    stats = [
      { number: '25+', label: 'Years of Excellence' },
      { number: '1000+', label: 'Students Enrolled' },
      { number: '80+', label: 'Qualified Staff' },
      { number: '95%', label: 'Graduation Rate' }
    ],
    leadership = [
      {
        name: 'Dr. Sarah Johnson',
        role: 'Principal',
        bio: 'With over 20 years of experience in education, Dr. Johnson leads our school with vision and dedication to academic excellence.'
      },
      {
        name: 'Michael Chen',
        role: 'Vice Principal',
        bio: 'A passionate educator with expertise in curriculum development and student engagement strategies.'
      },
      {
        name: 'Priya Patel',
        role: 'Head of Academics',
        bio: 'Committed to fostering innovative teaching methods and supporting our teaching staff in delivering exceptional education.'
      }
    ],
    contact = {
      address: '123 Education Street\nCity, State 12345',
      phone: '+1 (555) 123-4567',
      email: 'info@schoolname.edu',
      admissionEmail: 'admissions@schoolname.edu',
      hours: 'Mon-Fri, 8:00 AM - 4:00 PM'
    }
  } = content || {};
  
  // Helper function to render line breaks in text
  const renderTextWithLineBreaks = (text) => {
    return text.split('\n').map((paragraph, index) => (
      <p key={index} className="mb-4">{paragraph}</p>
    ));
  };

  return (
    <div className="bg-white">
      {/* Hero Section */}
      <section className="relative bg-blue-800 text-white overflow-hidden">
        <div className="absolute inset-0">
          <div className="absolute inset-0 bg-black opacity-60"></div>
          <div className="absolute inset-0 bg-gradient-to-b from-blue-600 to-blue-900 opacity-70"></div>
        </div>
        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
          <div className="text-center">
            <motion.h1 
              className="text-4xl md:text-6xl font-bold mb-6"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8 }}
            >
              {hero.title}
            </motion.h1>
            <motion.p 
              className="text-xl md:text-2xl max-w-3xl mx-auto"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.8, delay: 0.2 }}
            >
              {hero.subtitle}
            </motion.p>
          </div>
        </div>
      </section>

      {/* Stats Section */}
      <section className="py-16 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            {stats.map((stat, index) => (
              <motion.div 
                key={index}
                className="bg-white p-6 rounded-lg shadow-md"
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: index * 0.1 }}
              >
                <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                  {stat.label === 'Years of Excellence' ? (
                    <FaSchool className="w-8 h-8 text-blue-600" />
                  ) : stat.label === 'Students Enrolled' ? (
                    <FaUsers className="w-8 h-8 text-blue-600" />
                  ) : stat.label === 'Qualified Staff' ? (
                    <GiTeacher className="w-8 h-8 text-blue-600" />
                  ) : (
                    <FaGraduationCap className="w-8 h-8 text-blue-600" />
                  )}
                </div>
                <div className="text-3xl font-bold text-gray-900 mb-1">{stat.number}</div>
                <div className="text-gray-600">{stat.label}</div>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Mission & Vision */}
      <section className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="lg:grid lg:grid-cols-2 lg:gap-16 items-center">
            <motion.div
              initial={{ opacity: 0, x: -50 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.6 }}
              className="mb-12 lg:mb-0"
            >
              <h2 className="text-3xl font-bold text-gray-900 mb-6">Our Mission</h2>
              <div className="text-lg text-gray-600 space-y-4">
                {renderTextWithLineBreaks(mission)}
              </div>
            </motion.div>
            <motion.div
              initial={{ opacity: 0, x: 50 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.6, delay: 0.2 }}
              className="bg-blue-50 p-8 rounded-lg"
            >
              <h2 className="text-3xl font-bold text-gray-900 mb-6">Our Vision</h2>
              <div className="text-lg text-gray-600">
                {renderTextWithLineBreaks(vision)}
              </div>
            </motion.div>
          </div>
        </div>
      </section>

      {/* Our Values */}
      <section className="py-16 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-gray-900 mb-4">Our Core Values</h2>
            <div className="w-20 h-1 bg-blue-600 mx-auto"></div>
          </div>
          
          <div className="grid md:grid-cols-3 gap-8">
            {values.map((value, index) => (
              <motion.div 
                key={index}
                className="bg-white p-8 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300"
                whileHover={{ y: -5 }}
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.5, delay: index * 0.1 }}
              >
                <div className="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mb-6 mx-auto">
                  {value.title === 'Excellence' ? (
                    <FaAward className="w-6 h-6 text-white" />
                  ) : value.title === 'Community' ? (
                    <FaUsers className="w-6 h-6 text-white" />
                  ) : (
                    <FaBookOpen className="w-6 h-6 text-white" />
                  )}
                </div>
                <h3 className="text-xl font-semibold text-center mb-3">{value.title}</h3>
                <p className="text-gray-600 text-center">{value.description}</p>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* History */}
      <section className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <motion.div 
            className="bg-white rounded-lg shadow-lg overflow-hidden"
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <div className="md:flex">
              <div className="md:flex-shrink-0 md:w-1/3 bg-gray-100">
                <div className="h-full bg-cover bg-center" style={{ backgroundImage: 'url(/images/school-history.jpg)', minHeight: '300px' }}></div>
              </div>
              <div className="p-8 md:w-2/3">
                <h2 className="text-3xl font-bold text-gray-900 mb-6">Our History</h2>
                <div className="text-lg text-gray-600">
                  {renderTextWithLineBreaks(history.text)}
                </div>
              </div>
            </div>
          </motion.div>
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
                <p className="text-gray-600 whitespace-pre-line">{contact.address}</p>
              </div>
            </div>
            <div className="flex items-start">
              <div className="bg-blue-100 p-3 rounded-full mr-4">
                <FaPhone className="w-6 h-6 text-blue-600" />
              </div>
              <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-1">Call Us</h3>
                <p className="text-gray-600">
                  {contact.phone}<br />
                  {contact.hours}
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
                  {contact.email}<br />
                  {contact.admissionEmail}
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
