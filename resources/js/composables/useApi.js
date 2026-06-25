import axios from 'axios';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';

const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

api.interceptors.request.use((config) => {
    const auth = useAuthStore();
    if (auth.token) {
        config.headers.Authorization = `Bearer ${auth.token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const toast = useToastStore();

        if (!error.response) {
            // Network / CORS / aborted
            if (error.code !== 'ERR_CANCELED') {
                toast.error('Network error. Please check your connection.');
            }
            return Promise.reject(error);
        }

        const { status, data } = error.response;

        if (status === 401) {
            const auth = useAuthStore();
            auth.logout();
            toast.error('Session expired. Please log in again.');
            return Promise.reject(error);
        }

        // Attach structured field errors for 422 so callers can bind to inputs
        if (status === 422 && data?.errors) {
            error.fieldErrors = {};
            for (const [field, messages] of Object.entries(data.errors)) {
                error.fieldErrors[field] = Array.isArray(messages) ? messages[0] : String(messages);
            }
            // Don't toast — caller will show inline errors
            return Promise.reject(error);
        }

        if (status === 403) {
            toast.error(data?.message || 'You do not have permission to perform this action.');
            return Promise.reject(error);
        }

        if (status === 404) {
            toast.error(data?.message || 'Resource not found.');
            return Promise.reject(error);
        }

        if (status >= 500) {
            toast.error('Server error. Please try again in a moment.');
            return Promise.reject(error);
        }

        if (status >= 400) {
            toast.error(data?.message || 'Request failed.');
        }

        return Promise.reject(error);
    }
);

export function useApi() {
    return api;
}
