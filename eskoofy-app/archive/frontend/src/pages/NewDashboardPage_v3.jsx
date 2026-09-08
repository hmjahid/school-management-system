import { Suspense, useEffect } from 'react';
import { Outlet } from 'react-router-dom';
import DashboardContent from '../components/dashboard/DashboardContent_v3';
import { useEnhancedDashboard } from '../contexts/EnhancedDashboardContext';

export default function NewDashboardPage() {
    const { updateActivePath } = useEnhancedDashboard();
    
    // Set active path and breadcrumbs when component mounts
    useEffect(() => {
        updateActivePath('/dashboard', [
            { name: 'Dashboard', path: '/dashboard' }
        ]);
    }, [updateActivePath]);
    
    return (
        <Suspense fallback={
            <div className="flex items-center justify-center min-h-[50vh]">
                <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            </div>
        }>
            <DashboardContent />
            <Outlet />
        </Suspense>
    );
}
