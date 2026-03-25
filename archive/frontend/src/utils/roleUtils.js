/**
 * Get the dashboard route based on user role
 * @param {Object} user - The user object
 * @returns {string} The dashboard route
 */
export const getDashboardRoute = (user) => {
    if (!user || !user.role) return '/login';

    const role = user.role.toLowerCase();
    
    // Handle both string role and role object with name property
    const roleName = typeof role === 'object' ? role.name || role.role : role;
    
    switch (roleName) {
        case 'admin':
            return '/admin/dashboard';
        case 'teacher':
            return '/teacher/dashboard';
        case 'student':
            return '/student/dashboard';
        case 'parent':
            return '/parent/dashboard';
        case 'accountant':
            return '/accountant/dashboard';
        case 'librarian':
            return '/librarian/dashboard';
        case 'receptionist':
            return '/receptionist/dashboard';
        case 'superadmin':
            return '/super-admin/dashboard';
        default:
            return '/dashboard'; // Fallback to a default dashboard
    }
};

/**
 * Check if user has required role
 * @param {Object} user - The user object
 * @param {string|Array} requiredRole - The required role(s)
 * @returns {boolean} Whether the user has the required role
 */
export const hasRole = (user, requiredRole) => {
    if (!user || !user.role) return false;
    
    const userRole = user.role.toLowerCase();
    const userRoleName = typeof userRole === 'object' ? userRole.name || userRole.role : userRole;
    
    if (Array.isArray(requiredRole)) {
        return requiredRole.some(role => 
            role.toLowerCase() === userRoleName
        );
    }
    
    return userRoleName === requiredRole.toLowerCase();
};
