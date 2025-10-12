import { motion } from 'framer-motion';
import useWebsiteContent from '../hooks/useWebsiteContent';
import LoadingSpinner from '../components/common/LoadingSpinner';

const PrivacyPolicyPage = () => {
  const defaultContent = {
    pageTitle: 'Privacy Policy',
    heroTitle: 'Privacy Policy',
    lastUpdated: new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }),
    introduction: 'We are committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website.',
    sections: [
      {
        title: '1. Information We Collect',
        content: 'We may collect personal information such as your name, email address, phone number, and other information you provide when you register, subscribe to our newsletter, or fill out a form on our website.'
      },
      {
        title: '2. How We Use Your Information',
        content: 'We may use the information we collect to provide and maintain our services, notify you about changes, allow you to participate in interactive features, provide customer support, and gather analysis to improve our website.'
      },
      {
        title: '3. Information Sharing and Disclosure',
        content: 'We do not sell, trade, or rent your personal information to others. We may share generic aggregated demographic information not linked to any personal identification information regarding visitors and users with our business partners and advertisers.'
      },
      {
        title: '4. Data Security',
        content: 'We implement appropriate data collection, storage, and processing practices and security measures to protect against unauthorized access, alteration, disclosure, or destruction of your personal information.'
      },
      {
        title: '5. Your Rights',
        content: 'You have the right to access, correct, or delete your personal information. You may also have the right to object to or restrict certain processing of your data.'
      },
      {
        title: '6. Changes to This Privacy Policy',
        content: 'We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page.'
      }
    ],
    contact: {
      email: 'privacy@school.edu',
      phone: '(123) 456-7890',
      address: '123 School Street, City, Country'
    }
  };

  const { content, loading, error } = useWebsiteContent('legal/privacy', defaultContent);
  
  // Use default content if API content is not available
  const pageContent = content || defaultContent;
  
  // Show loading spinner only if we don't have any content yet
  if (loading && !content) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <LoadingSpinner size="large" />
      </div>
    );
  }

  // If there's an error but we have default content, show the default content with a warning
  if (error) {
    console.error('Error loading privacy policy:', error);
    if (!defaultContent) {
      return <div className="min-h-screen flex items-center justify-center">Error loading content. Please try again later.</div>;
    }
    // Continue with default content if available
  }

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
            {pageContent.heroTitle || pageContent.pageTitle || 'Privacy Policy'}
          </motion.h1>
          <p className="text-blue-100">Last updated: {pageContent.lastUpdated}</p>
        </div>
      </div>

      <main className="container mx-auto px-4 py-12">
        <div className="max-w-4xl mx-auto bg-white rounded-lg shadow-sm p-6">
          <motion.p 
            className="text-lg text-gray-700 mb-8 leading-relaxed"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5 }}
          >
            {pageContent.introduction}
          </motion.p>
          
          {pageContent.sections?.map((section, index) => (
            <motion.section 
              key={index}
              className="mb-8 pb-6 border-b border-gray-100 last:border-0 last:mb-0 last:pb-0"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, delay: index * 0.1 }}
            >
              <h2 className="text-2xl font-bold mb-4 text-gray-800">{section.title}</h2>
              <p className="text-gray-700 leading-relaxed">{section.content}</p>
            </motion.section>
          ))}
          
          <motion.div 
            className="mt-12 p-6 bg-blue-50 rounded-lg"
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.5, delay: 0.3 }}
          >
            <h3 className="text-xl font-semibold mb-2">Contact Us</h3>
            <p className="text-gray-700 mb-4">
              If you have any questions about this Privacy Policy, please contact us at:
            </p>
            <ul className="space-y-2">
              <li>Email: {pageContent.contact?.email || 'privacy@school.edu'}</li>
              <li>Phone: {pageContent.contact?.phone || '(123) 456-7890'}</li>
              <li>Address: {pageContent.contact?.address || '123 School Street, City, Country'}</li>
            </ul>
          </motion.div>
        </div>
      </main>
    </div>
  );
};

export default PrivacyPolicyPage;
