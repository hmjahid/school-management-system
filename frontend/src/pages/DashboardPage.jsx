import { useEffect, useState } from 'react';
import axios from 'axios';

export default function DashboardPage() {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchUser = async () => {
            try {
                const response = await axios.get('/api/user');
                setUser(response.data);
            } catch (error) {
                console.error(error);
            } finally {
                setLoading(false);
            }
        };

        fetchUser();
    }, []);

    const handleLogout = async () => {
        await axios.post('/api/logout');
        window.location.href = '/login';
    };

    if (loading) {
        return <div>Loading...</div>;
    }

    return (
        <div className="min-h-screen bg-gray-100">
            <div className="py-10">
                <header>
                    <div className="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <h1 className="text-3xl font-bold leading-tight text-gray-900">Dashboard</h1>
                    </div>
                </header>
                <main>
                    <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                        <div className="px-4 py-8 sm:px-0">
                            <div className="p-6 bg-white border-4 border-gray-200 border-dashed rounded-lg">
                                <h2 className="text-xl font-bold">Welcome, {user?.name}</h2>
                                <p>Your role is: {user?.roles.map(role => role.name).join(', ')}</p>
                                <button
                                    onClick={handleLogout}
                                    className="px-4 py-2 mt-4 font-bold text-white bg-red-500 rounded hover:bg-red-700"
                                >
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    );
}
