import { Link } from 'react-router-dom';
import { FaFacebookF, FaTwitter, FaInstagram, FaYoutube, FaPhone, FaEnvelope, FaMapMarkerAlt, FaClock } from 'react-icons/fa';

const Footer = () => {
  const currentYear = new Date().getFullYear();
  
  return (
    <footer className="bg-gray-900 text-white pt-12 pb-6">
      <div className="container mx-auto px-4">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 mb-8">
          {/* About School */}
          <div>
            <h3 className="text-xl font-bold mb-4 text-orange-400">About Us</h3>
            <p className="text-gray-300 mb-4">
              EDUCare is a premier educational institution committed to excellence in teaching, research, and community service.
            </p>
            <div className="flex space-x-4">
              <a href="#" className="text-gray-400 hover:text-white transition-colors">
                <FaFacebookF />
              </a>
              <a href="#" className="text-gray-400 hover:text-white transition-colors">
                <FaTwitter />
              </a>
              <a href="#" className="text-gray-400 hover:text-white transition-colors">
                <FaInstagram />
              </a>
              <a href="#" className="text-gray-400 hover:text-white transition-colors">
                <FaYoutube />
              </a>
            </div>
          </div>

          {/* Quick Links */}
          <div>
            <h3 className="text-xl font-bold mb-4 text-orange-400">Quick Links</h3>
            <ul className="space-y-2">
              <li><Link to="/about" className="text-gray-300 hover:text-white transition-colors">About School</Link></li>
              <li><Link to="/academics" className="text-gray-300 hover:text-white transition-colors">Academics</Link></li>
              <li><Link to="/admissions" className="text-gray-300 hover:text-white transition-colors">Admissions</Link></li>
              <li><Link to="/news" className="text-gray-300 hover:text-white transition-colors">News & Events</Link></li>
              <li><Link to="/gallery" className="text-gray-300 hover:text-white transition-colors">Photo Gallery</Link></li>
              <li><Link to="/contact" className="text-gray-300 hover:text-white transition-colors">Contact Us</Link></li>
            </ul>
          </div>

          {/* Legal Links */}
          <div>
            <h3 className="text-xl font-bold mb-4 text-orange-400">Legal</h3>
            <ul className="space-y-2">
              <li><Link to="/terms" className="text-gray-300 hover:text-white transition-colors">Terms of Service</Link></li>
              <li><Link to="/privacy" className="text-gray-300 hover:text-white transition-colors">Privacy Policy</Link></li>
              <li><Link to="/sitemap" className="text-gray-300 hover:text-white transition-colors">Sitemap</Link></li>
              <li><Link to="/accessibility" className="text-gray-300 hover:text-white transition-colors">Accessibility</Link></li>
            </ul>
          </div>

          {/* Contact Info */}
          <div>
            <h3 className="text-xl font-bold mb-4 text-orange-400">Contact Us</h3>
            <ul className="space-y-3">
              <li className="flex items-start">
                <FaMapMarkerAlt className="mt-1 mr-3 text-orange-400" />
                <span className="text-gray-300">123 Education Street, Learning City, 10001</span>
              </li>
              <li className="flex items-center">
                <FaPhone className="mr-3 text-orange-400" />
                <span className="text-gray-300">+1 234 567 8900</span>
              </li>
              <li className="flex items-center">
                <FaEnvelope className="mr-3 text-orange-400" />
                <span className="text-gray-300">info@school.edu</span>
              </li>
              <li className="flex items-center">
                <FaClock className="mr-3 text-orange-400" />
                <span className="text-gray-300">Mon - Fri: 8:00 AM - 4:00 PM</span>
              </li>
            </ul>
          </div>

          {/* Newsletter */}
          <div>
            <h3 className="text-xl font-bold mb-4 text-orange-400">Newsletter</h3>
            <p className="text-gray-300 mb-4">Subscribe to our newsletter for the latest updates and news.</p>
            <form className="flex">
              <input 
                type="email" 
                placeholder="Your email address" 
                className="px-4 py-2 w-full rounded-l-md focus:outline-none text-gray-800"
                required
              />
              <button 
                type="submit" 
                className="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-r-md transition-colors"
              >
                Subscribe
              </button>
            </form>
          </div>
        </div>

        {/* Copyright */}
        <div className="border-t border-gray-800 pt-6">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <p className="text-gray-400 text-sm mb-4 md:mb-0">
              &copy; {currentYear} EDUCare School. All rights reserved.
            </p>
            <div className="flex space-x-6">
              <Link to="/privacy" className="text-gray-400 hover:text-white text-sm transition-colors">
                Privacy Policy
              </Link>
              <Link to="/terms" className="text-gray-400 hover:text-white text-sm transition-colors">
                Terms of Service
              </Link>
              <Link to="/sitemap" className="text-gray-400 hover:text-white text-sm transition-colors">
                Sitemap
              </Link>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
