import { Link } from 'react-router-dom';

const AboutPage = () => {
  return (
    <div className="min-h-screen bg-white">
      {/* Navigation - Same as HomePage */}
      <nav className="bg-blue-600 text-white p-4">
        <div className="container mx-auto flex justify-between items-center">
          <Link to="/" className="text-2xl font-bold">School Name</Link>
          <div className="space-x-4">
            <Link to="/" className="hover:underline">Home</Link>
            <Link to="/about" className="font-semibold border-b-2 border-white">About</Link>
            <Link to="/academics" className="hover:underline">Academics</Link>
            <Link to="/admissions" className="hover:underline">Admissions</Link>
            <Link to="/news" className="hover:underline">News & Events</Link>
            <Link to="/gallery" className="hover:underline">Gallery</Link>
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

      {/* Main Content */}
      <main className="container mx-auto px-4 py-12">
        <h1 className="text-4xl font-bold text-center mb-8">About Our School</h1>
        
        <section className="mb-16">
          <h2 className="text-2xl font-semibold mb-4">Our History</h2>
          <p className="text-gray-700 leading-relaxed mb-4">
            [School Name] was founded in [Year] with a vision to provide quality education...
            {/* This content will be managed by admin */}
          </p>
        </section>

        <section className="mb-16">
          <h2 className="text-2xl font-semibold mb-4">Mission & Vision</h2>
          <div className="grid md:grid-cols-2 gap-8">
            <div className="bg-blue-50 p-6 rounded-lg">
              <h3 className="text-xl font-semibold mb-3">Our Mission</h3>
              <p className="text-gray-700">
                [Mission statement will be managed by admin]
              </p>
            </div>
            <div className="bg-blue-50 p-6 rounded-lg">
              <h3 className="text-xl font-semibold mb-3">Our Vision</h3>
              <p className="text-gray-700">
                [Vision statement will be managed by admin]
              </p>
            </div>
          </div>
        </section>

        <section className="mb-16">
          <h2 className="text-2xl font-semibold mb-6">Our Team</h2>
          <div className="grid md:grid-cols-3 gap-8">
            {[1, 2, 3].map((item) => (
              <div key={item} className="text-center">
                <div className="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-4"></div>
                <h3 className="text-xl font-semibold">[Staff Name]</h3>
                <p className="text-blue-600">[Position]</p>
                <p className="text-gray-600 mt-2">
                  [Brief bio or description]
                </p>
              </div>
            ))}
          </div>
        </section>
      </main>

      {/* Footer - Same as HomePage */}
      <footer className="bg-gray-800 text-white py-8">
        <div className="container mx-auto px-4">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="mb-4 md:mb-0">
              <h3 className="text-xl font-bold">School Name</h3>
              <p className="text-gray-400">Providing quality education since [Year]</p>
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

export default AboutPage;
