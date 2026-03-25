import { Link } from 'react-router-dom';

export default function Unauthorized() {
  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8 text-center">
        <div>
          <h2 className="mt-6 text-3xl font-extrabold text-gray-900">
            403 - Unauthorized Access
          </h2>
          <p className="mt-2 text-sm text-gray-600">
            You don't have permission to access this page. Please contact the administrator if you believe this is an error.
          </p>
        </div>
        <div className="mt-8">
          <Link
            to="/"
            className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Go back to home
          </Link>
          <div className="mt-4">
            <Link
              to="/dashboard"
              className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
            >
              Or go to your dashboard
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
