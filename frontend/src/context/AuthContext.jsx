import { createContext, useContext, useState, useEffect } from 'react';
import axios from '../lib/axios';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchUser = async () => {
            try {
                const { data } = await axios.get('/api/user');
                setUser(data);
            } catch (error) {
                console.error('Could not fetch user', error);
                setUser(null);
            } finally {
                setLoading(false);
            }
        };

        fetchUser();
    }, []);

    const logout = async () => {
        try {
            await axios.post('/api/logout');
            setUser(null);
            window.location.href = '/login';
        } catch (error) {
            console.error('Logout failed', error);
        }
    };

    return (
        <AuthContext.Provider value={{ user, loading, logout }}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => useContext(AuthContext);
