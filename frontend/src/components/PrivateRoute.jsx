import { Navigate } from 'react-router-dom';
import { useContext, useEffect, useState } from 'react';
import { AuthContext } from '../contexts/AuthContext';
import LoadingSpinner from './common/LoadingSpinner';

export default function PrivateRoute({ children }) {
    const { user, loading } = useContext(AuthContext);
    const [isClient, setIsClient] = useState(false);

    // This effect ensures we're on the client side before rendering
    useEffect(() => {
        setIsClient(true);
    }, []);

    // Show loading state while checking authentication
    if (loading || !isClient) {
        return (
            <div className="flex items-center justify-center min-h-screen">
                <LoadingSpinner size="lg" text="Checking authentication..." />
            </div>
        );
    }

    // If not authenticated, redirect to login
    if (!user) {
        console.log('[PrivateRoute] User not authenticated, redirecting to login');
        return <Navigate to="/login" replace />;
    }

    // User is authenticated, render the protected content
    console.log('[PrivateRoute] User is authenticated, rendering protected content');
    return children;
}
