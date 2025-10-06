import Axios from 'axios';

const axios = Axios.create({
    baseURL: 'http://localhost:8080/api',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
});

export default axios;
