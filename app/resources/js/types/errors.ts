import type { AxiosError } from 'axios';

export type ApiErrorCode = 'not_found' | 'service_unavailable' | 'network' | 'unknown';

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly code: ApiErrorCode,
        public readonly original?: AxiosError,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}