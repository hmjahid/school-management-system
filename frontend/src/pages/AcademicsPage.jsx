import { Link } from 'react-router-dom';

const AcademicsPage = () => {
  const departments = [
    { id: 1, name: 'Science', description: 'Our science department offers...' },
    { id: 2, name: 'Arts', description: 'The arts program focuses on...' },
    { id: 3, name: 'Commerce', description: 'Commerce education for future business leaders...' },
  ];

  return (
    <div className="min-h-screen bg-white">
      {/* Main Content */}
      <main className="container mx-auto px-4 py-12">
        <h1 className="text-4xl font-bold text-center mb-8">Academics</h1>
        
        <section className="mb-16">
          <h2 className="text-2xl font-semibold mb-6">Our Academic Programs</h2>
          <p className="text-gray-700 mb-8">
            We offer a comprehensive academic program designed to prepare students for success in higher education and beyond.
          </p>
          
          <div className="grid md:grid-cols-3 gap-8">
            {departments.map((dept) => (
              <div key={dept.id} className="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                <h3 className="text-xl font-semibold mb-3 text-blue-600">{dept.name}</h3>
                <p className="text-gray-700 mb-4">{dept.description}</p>
                <Link to={`/academics/${dept.name.toLowerCase()}`} className="text-blue-600 hover:underline">
                  Learn more →
                </Link>
              </div>
            ))}
          </div>
        </section>

        <section className="mb-16">
          <h2 className="text-2xl font-semibold mb-6">Academic Calendar</h2>
          <div className="bg-gray-50 p-6 rounded-lg">
            <p className="text-gray-700">
              Our academic calendar includes important dates for the current school year, including term start/end dates, holidays, and examination periods.
            </p>
            <button className="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
              View Academic Calendar
            </button>
          </div>
        </section>
      </main>

      {/* Footer */}
      <footer className="bg-gray-800 text-white py-8">
        <div className="container mx-auto px-4">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="mb-4 md:mb-0">
              <h3 className="text-xl font-bold">School Name</h3>
              <p className="text-gray-400">Excellence in Education</p>
            </div>
            <div className="flex space-x-4">
              <Link to="/privacy" className="text-gray-400 hover:text-white">Privacy Policy</Link>
              <Link to="/terms" className="text-gray-400 hover:text-white">Terms of Service</Link>
              <Link to="/contact" className="text-gray-400 hover:text-white">Contact Us</Link>
            </div>
          </div>
          <div className="mt-8 text-center text-gray-400 text-sm">
            &copy; {new Date().getFullYear()} School Name. All rights reserved.
          </div>
        </div>
      </footer>
    </div>
  );
};

export default AcademicsPage;
