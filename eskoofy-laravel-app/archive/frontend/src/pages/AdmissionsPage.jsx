import { Link } from 'react-router-dom';

const AdmissionsPage = () => {
  const admissionSteps = [
    { step: 1, title: 'Inquiry', description: 'Submit an online inquiry form or visit our campus.' },
    { step: 2, title: 'Application', description: 'Complete the online application form.' },
    { step: 3, title: 'Documentation', description: 'Submit required documents.' },
    { step: 4, title: 'Assessment', description: 'Complete any required testing or interviews.' },
    { step: 5, title: 'Admission Decision', description: 'Receive admission decision.' },
    { step: 6, title: 'Enrollment', description: 'Complete enrollment and pay fees.' },
  ];

  return (
    <div className="min-h-screen bg-white">
      {/* Hero Section */}
      <div className="bg-blue-50 py-16">
        <div className="container mx-auto px-4 text-center">
          <h1 className="text-4xl font-bold text-gray-800 mb-4">Admissions</h1>
          <p className="text-xl text-gray-600 max-w-3xl mx-auto">
            Begin your journey with us. We're excited to welcome new students to our school community.
          </p>
        </div>
      </div>

      {/* Main Content */}
      <main className="container mx-auto px-4 py-12">
        <section className="mb-16">
          <h2 className="text-2xl font-semibold mb-6">Admission Process</h2>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {admissionSteps.map((item) => (
              <div key={item.step} className="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                <div className="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xl font-bold mb-4">
                  {item.step}
                </div>
                <h3 className="text-xl font-semibold mb-2">{item.title}</h3>
                <p className="text-gray-600">{item.description}</p>
              </div>
            ))}
          </div>
        </section>

        <section className="mb-16 bg-blue-50 p-8 rounded-lg">
          <h2 className="text-2xl font-semibold mb-6">Ready to Apply?</h2>
          <div className="grid md:grid-cols-2 gap-8">
            <div>
              <h3 className="text-xl font-semibold mb-4">Application Requirements</h3>
              <ul className="list-disc pl-5 space-y-2 text-gray-700">
                <li>Completed application form</li>
                <li>Birth certificate (copy)</li>
                <li>Previous school records</li>
                <li>Passport-sized photographs</li>
                <li>Medical records (if applicable)</li>
              </ul>
            </div>
            <div className="bg-white p-6 rounded-lg shadow-md">
              <h3 className="text-xl font-semibold mb-4">Important Dates</h3>
              <ul className="space-y-3">
                <li className="flex justify-between">
                  <span>Application Deadline:</span>
                  <span className="font-medium">[Date]</span>
                </li>
                <li className="flex justify-between">
                  <span>Entrance Exam:</span>
                  <span className="font-medium">[Date]</span>
                </li>
                <li className="flex justify-between">
                  <span>Interviews:</span>
                  <span className="font-medium">[Date Range]</span>
                </li>
                <li className="flex justify-between">
                  <span>Decision Notification:</span>
                  <span className="font-medium">[Date]</span>
                </li>
              </ul>
              <button className="mt-6 w-full bg-blue-600 text-white py-3 rounded-md font-medium hover:bg-blue-700 transition-colors">
                Start Application
              </button>
            </div>
          </div>
        </section>

        <section className="mb-16">
          <h2 className="text-2xl font-semibold mb-6">Frequently Asked Questions</h2>
          <div className="space-y-4">
            {[
              { question: 'What is the age requirement for admission?', answer: 'Answer will be provided by admin.' },
              { question: 'Is there an application fee?', answer: 'Answer will be provided by admin.' },
              { question: 'Do you offer scholarships or financial aid?', answer: 'Answer will be provided by admin.' },
            ].map((faq, index) => (
              <div key={index} className="border-b border-gray-200 pb-4">
                <h3 className="text-lg font-medium text-gray-900">{faq.question}</h3>
                <p className="mt-1 text-gray-600">{faq.answer}</p>
              </div>
            ))}
          </div>
        </section>
      </main>


    </div>
  );
};

export default AdmissionsPage;
