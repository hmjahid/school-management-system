import { motion } from 'framer-motion';
import useWebsiteContent from '../hooks/useWebsiteContent';
import LoadingSpinner from '../components/common/LoadingSpinner';

const TermsOfServicePage = () => {
  const defaultContent = {
    pageTitle: 'Terms of Service',
    heroTitle: 'Terms of Service',
    lastUpdated: 'October 12, 2025',
    sections: [
      {
        title: '1. Introduction',
        content: 'Welcome to our school website. These terms of service outline the rules and regulations for the use of our website.'
      },
      {
        title: '2. Intellectual Property Rights',
        content: 'All content published and made available on our website is the property of the school and its content creators.'
      },
      {
        title: '3. User Responsibilities',
        content: 'As a user of our website, you agree to use our website legally, not to use our website for illegal purposes, and not to harass or cause distress to other users.'
      },
      {
        title: '4. Limitation of Liability',
        content: 'The school will not be liable for any actions, claims, losses, damages, or expenses arising from your use of the website.'
      },
      {
        title: '5. Changes to Terms',
        content: 'We reserve the right to modify these terms at any time. Your continued use of the website after any changes constitutes your acceptance of the new terms.'
      }
    ]
  };

  const { content, loading, error } = useWebsiteContent('legal/terms', defaultContent);

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
        <div className="max-w-4xl mx-auto bg-white rounded-lg shadow-sm p-6">
          {content.sections?.map((section, index) => (
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
        </div>
      </main>
    </div>
  );
};

export default TermsOfServicePage;
