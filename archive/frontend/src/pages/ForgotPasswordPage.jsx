import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toast } from 'react-hot-toast';

export default function ForgotPasswordPage() {
    const [email, setEmail] = useState('');
    const [loading, setLoading] = useState(false);
    const [emailSent, setEmailSent] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (!email) {
            toast.error('Please enter your email address');
            return;
        }

        setLoading(true);
        
        try {
            // TODO: Replace with actual API call
            // const response = await api.post('/auth/forgot-password', { email });
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            setEmailSent(true);
            toast.success('Password reset link sent to your email');
        } catch (error) {
            console.error('Error sending reset link:', error);
            toast.error(error.response?.data?.message || 'Failed to send reset link');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="w-full">
            <div className="min-h-screen bg-gray-50 flex">
                {/* Decorative side panel - Same as login page */}
                <div className="hidden lg:flex w-1/2 bg-gradient-to-tr from-blue-800 to-purple-700 items-center justify-center text-white p-12">
                    <div className="text-center">
                        <h1 className="font-bold text-5xl mb-4">Modern School Manager</h1>
                        <p className="text-lg opacity-80">The all-in-one solution for managing your institution.</p>
                    </div>
                </div>

                {/* Form panel */}
                <div className="w-full flex items-center justify-center p-6 sm:p-12">
                    <div className="w-full max-w-2xl space-y-8">
                        <div className="text-center mb-10">
                            <h2 className="text-3xl font-bold text-gray-800">Reset Your Password</h2>
                            <p className="text-gray-500 mt-2">
                                {emailSent 
                                    ? 'Check your email for further instructions.'
                                    : 'Enter your email and we\'ll send you a link to reset your password.'
                                }
                            </p>
                        </div>

                        {!emailSent ? (
                            <form className="space-y-6" onSubmit={handleSubmit}>
                                <div>
                                    <label htmlFor="email" className="block text-sm font-medium text-gray-700">
                                        Email address
                                    </label>
                                    <div className="mt-1">
                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            autoComplete="email"
                                            required
                                            value={email}
                                            onChange={(e) => setEmail(e.target.value)}
                                            className="block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            placeholder="you@example.com"
                                            disabled={loading}
                                        />
                                    </div>
                                </div>

                                <div>
                                    <button
                                        type="submit"
                                        disabled={loading}
                                        className="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {loading ? 'Sending...' : 'Send Reset Link'}
                                    </button>
                                </div>
                            </form>
                        ) : (
                            <div className="text-center">
                                <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                                    <svg
                                        className="h-6 w-6 text-green-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </div>
                                <p className="mt-3 text-sm text-gray-600">
                                    We've sent an email to <span className="font-medium">{email}</span> with a link to reset your password.
                                </p>
                            </div>
                        )}

                        <div className="mt-6 text-center">
                            <p className="text-sm text-gray-600">
                                Remember your password?{' '}
                                <Link to="/login" className="font-medium text-indigo-600 hover:text-indigo-500">
                                    Back to login
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
