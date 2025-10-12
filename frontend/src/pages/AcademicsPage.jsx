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


    </div>
  );
};

export default AcademicsPage;
