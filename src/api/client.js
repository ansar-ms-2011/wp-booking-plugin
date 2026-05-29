import axios from 'axios';

const apiClient = axios.create({
    baseURL: mevp_ajax.rest_url,
    headers: {
        'X-WP-Nonce': mevp_ajax.rest_nonce,
        'Content-Type': 'application/json'
    }
});

apiClient.interceptors.request.use(
    config => {
        config.headers['X-WP-Nonce'] = mevp_ajax.rest_nonce;
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

let isRefreshing = false;
let refreshSubscribers = [];

function subscribeTokenRefresh(cb) {
    refreshSubscribers.push(cb);
}

function onRefreshed(nonce) {
    refreshSubscribers.map(cb => cb(nonce));
}

apiClient.interceptors.response.use(
    response => response,
    async error => {
        const {config, response} = error;
        const originalRequest = config;

        if (response && (response.status === 403 ||
            (response.data && response.data.code === 'rest_cookie_invalid_nonce'))) {
            if (!isRefreshing) {
                isRefreshing = true;
                console.log('Refreshing nonce...');
                try {
                    let timestamp = new Date().getTime();
                    const {data} = await axios.get(`${mevp_ajax.rest_url}refresh-nonce?t=${timestamp}`);
                    const newNonce = data.nonce;
                    mevp_ajax.rest_nonce = newNonce;
                    isRefreshing = false;
                    onRefreshed(newNonce);
                    refreshSubscribers = [];
                } catch (e) {
                    isRefreshing = false;
                    return Promise.reject(e);
                }
            }

            const retryOrigRequest = new Promise((resolve) => {
                subscribeTokenRefresh(nonce => {
                    originalRequest.headers['X-WP-Nonce'] = nonce;
                    resolve(axios(originalRequest));
                });
            });
            return retryOrigRequest;
        }

        if (response) {
            console.error('API Error:', response.data);
            if (response.status === 401) {
                window.location.reload();
            }
        }
        return Promise.reject(error);
    }
);

export default apiClient;
