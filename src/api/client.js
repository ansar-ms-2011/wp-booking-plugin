import axios from 'axios';

const apiClient = axios.create({
    baseURL: mevp_ajax.rest_url,
    headers: {
        'X-WP-Nonce': mevp_ajax.rest_nonce,
        'Content-Type': 'application/json'
    }
});

apiClient.interceptors.response.use(
    response => response,
    error => {
        if (error.response) {
            console.error('API Error:', error.response.data);
            if (error.response.status === 401) {
                window.location.reload();
            }
        }
        return Promise.reject(error);
    }
);

export default apiClient;
