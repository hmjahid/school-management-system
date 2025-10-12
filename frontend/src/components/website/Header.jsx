import { Link, NavLink } from 'react-router-dom';
import { FaPhone, FaEnvelope, FaFacebookF, FaTwitter, FaInstagram, FaYoutube } from 'react-icons/fa';
import { FaLocationDot } from 'react-icons/fa6';

const Header = () => {
  return (
    <>
      {/* Top Bar */}
      <div className="bg-blue-900 text-white text-sm">
        <div className="container mx-auto px-4 py-2 flex flex-col md:flex-row justify-between items-center">
          <div className="flex items-center space-x-4 mb-2 md:mb-0">
            <div className="flex items-center">
              <FaPhone className="mr-2 text-blue-300" />
              <span>+1 234 567 8900</span>
            </div>
            <div className="flex items-center">
              <FaEnvelope className="mr-2 text-blue-300" />
              <span>info@school.edu</span>
            </div>
            <div className="flex items-center">
              <FaLocationDot className="mr-2 text-blue-300" />
              <span>123 Education St, Learning City</span>
            </div>
          </div>
          <div className="flex space-x-4">
            <a href="#" className="hover:text-blue-300 transition-colors">
              <FaFacebookF />
            </a>
            <a href="#" className="hover:text-blue-300 transition-colors">
              <FaTwitter />
            </a>
            <a href="#" className="hover:text-blue-300 transition-colors">
              <FaInstagram />
            </a>
            <a href="#" className="hover:text-blue-300 transition-colors">
              <FaYoutube />
            </a>
          </div>
        </div>
      </div>

      {/* Main Navigation */}
      <header className="bg-white shadow-md sticky top-0 z-50">
        <div className="container mx-auto px-4">
          <div className="flex justify-between items-center py-4">
            {/* Logo */}
            <Link to="/" className="flex items-center">
              <div className="text-3xl font-bold text-blue-700">EDU<span className="text-orange-500">CARE</span></div>
            </Link>

            {/* Desktop Navigation */}
            <nav className="hidden md:flex items-center space-x-1">
              <NavLink 
                to="/" 
                className={({ isActive }) => 
                  `px-4 py-2 font-medium rounded-md hover:bg-blue-50 hover:text-blue-700 transition-colors ${isActive ? 'text-blue-700' : 'text-gray-700'}`
                }
              >
                Home
              </NavLink>
              <NavLink 
                to="/about" 
                className={({ isActive }) => 
                  `px-4 py-2 font-medium rounded-md hover:bg-blue-50 hover:text-blue-700 transition-colors ${isActive ? 'text-blue-700' : 'text-gray-700'}`
                }
              >
                About Us
              </NavLink>
              <div className="group relative">
                <button className="px-4 py-2 font-medium text-gray-700 rounded-md hover:bg-blue-50 hover:text-blue-700 transition-colors flex items-center">
                  <span>Academics</span>
                  <svg className="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
                <div className="absolute left-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 z-10 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                  <Link to="/academics/curriculum" className="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Curriculum</Link>
                  <Link to="/academics/programs" className="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Programs</Link>
                  <Link to="/academics/faculty" className="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Faculty</Link>
                </div>
              </div>
              <NavLink 
                to="/admissions" 
                className={({ isActive }) => 
                  `px-4 py-2 font-medium rounded-md hover:bg-blue-50 hover:text-blue-700 transition-colors ${isActive ? 'text-blue-700' : 'text-gray-700'}`
                }
              >
                Admissions
              </NavLink>
              <NavLink 
                to="/news" 
                className={({ isActive }) => 
                  `px-4 py-2 font-medium rounded-md hover:bg-blue-50 hover:text-blue-700 transition-colors ${isActive ? 'text-blue-700' : 'text-gray-700'}`
                }
              >
                News & Events
              </NavLink>
              <NavLink 
                to="/gallery" 
                className={({ isActive }) => 
                  `px-4 py-2 font-medium rounded-md hover:bg-blue-50 hover:text-blue-700 transition-colors ${isActive ? 'text-blue-700' : 'text-gray-700'}`
                }
              >
                Gallery
              </NavLink>
              <NavLink 
                to="/contact" 
                className={({ isActive }) => 
                  `px-4 py-2 font-medium rounded-md hover:bg-blue-50 hover:text-blue-700 transition-colors ${isActive ? 'text-blue-700' : 'text-gray-700'}`
                }
              >
                Contact
              </NavLink>
              <Link 
                to="/login" 
                className="ml-4 px-6 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition-colors"
              >
                Login
              </Link>
            </nav>

            {/* Mobile menu button */}
            <button className="md:hidden text-gray-700 focus:outline-none">
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>
      </header>
    </>
  );
};

export default Header;
