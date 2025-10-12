import { Link } from 'react-router-dom';

const HomePage = () => {
  return (
    <div className="min-h-screen bg-white">
      {/* Navigation */}
      <nav className="bg-blue-600 text-white p-4">
        <div className="container mx-auto flex justify-between items-center">
          <h1 className="text-2xl font-bold">School Name</h1>
          <div className="space-x-4">
            <Link to="/" className="hover:underline">Home</Link>
            <Link to="/about" className="hover:underline">About</Link>
            <Link to="/contact" className="hover:underline">Contact</Link>
            <Link 
              to="/login" 
              className="bg-white text-blue-600 px-4 py-2 rounded-md font-medium hover:bg-blue-50 transition-colors"
            >
              Login
            </Link>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <div className="bg-blue-50 py-20">
        <div className="container mx-auto text-center px-4">
          <h1 className="text-5xl font-bold text-gray-800 mb-6">Welcome to Our School</h1>
          <p className="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
            Providing quality education and fostering academic excellence for a better tomorrow.
          </p>
          <div className="space-x-4">
            <Link 
              to="/admissions" 
              className="bg-blue-600 text-white px-8 py-3 rounded-md font-medium text-lg hover:bg-blue-700 transition-colors"
            >
              Apply Now
            </Link>
            <Link 
              to="/about" 
              className="bg-white text-blue-600 border border-blue-600 px-8 py-3 rounded-md font-medium text-lg hover:bg-blue-50 transition-colors"
            >
              Learn More
            </Link>
          </div>
        </div>
      </div>

      {/* Quick Links Section */}
      <div className="py-16 bg-white">
        <div className="container mx-auto px-4">
          <h2 className="text-3xl font-bold text-center mb-12">Our School</h2>
          <div className="grid md:grid-cols-3 gap-8">
            <div className="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
              <h3 className="text-xl font-semibold mb-3">Academics</h3>
              <p className="text-gray-600 mb-4">Explore our comprehensive academic programs and curriculum.</p>
              <Link to="/academics" className="text-blue-600 hover:underline">Learn more →</Link>
            </div>
            <div className="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
              <h3 className="text-xl font-semibold mb-3">Admissions</h3>
              <p className="text-gray-600 mb-4">Start your journey with us. Apply online today.</p>
              <Link to="/admissions" className="text-blue-600 hover:underline">Apply now →</Link>
            </div>
            <div className="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
              <h3 className="text-xl font-semibold mb-3">Contact Us</h3>
              <p className="text-gray-600 mb-4">Get in touch with our administration team.</p>
              <Link to="/contact" className="text-blue-600 hover:underline">Contact us →</Link>
            </div>
          </div>
        </div>
      </div>

      {/* Footer */}
      <footer className="bg-gray-800 text-white py-8">
        <div className="container mx-auto px-4">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="mb-4 md:mb-0">
              <h3 className="text-xl font-bold">School Name</h3>
              <p className="text-gray-400">Providing quality education since 1990</p>
            </div>
            <div className="flex space-x-4">
              <Link to="/privacy" className="text-gray-400 hover:text-white">Privacy Policy</Link>
              <Link to="/terms" className="text-gray-400 hover:text-white">Terms of Service</Link>
              <Link to="/contact" className="text-gray-400 hover:text-white">Contact Us</Link>
            </div>
          </div>
          <div className="mt-8 text-center text-gray-400 text-sm">
            © {new Date().getFullYear()} School Name. All rights reserved.
          </div>
        </div>
      </footer>
    </div>
  );
};

export default HomePage;
