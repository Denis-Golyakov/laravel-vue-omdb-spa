import axios, { type AxiosInstance, AxiosError } from 'axios'
import type { ApiEnvelope, Movie, MovieListItem } from '@/types/api';
import { ApiError, type ApiErrorCode } from '@/types/errors';

const http: AxiosInstance = axios.create({
  baseURL: '/api',
  timeout: 10000,
  headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

// Error handling
http.interceptors.response.use(
  response => response,
  (error: AxiosError<ApiEnvelope<unknown>>) => {
    // No response = network failure or timeout
    if (!error.response) {
      return Promise.reject(
        new ApiError('Network or timeout error', 0, 'network', error)
      );
    }

    const status = error.response.status;
    const message = error.response.data?.message ?? error.message;

    const code: ApiErrorCode =
      status === 404 ? 'not_found'
        : status === 503 ? 'service_unavailable'
          : 'unknown';

    return Promise.reject(new ApiError(message, status, code, error));
  }
);

export async function searchMovies(query: string): Promise<MovieListItem[]> {
  const { data } = await http.get<ApiEnvelope<MovieListItem[]>>('/movies/search', {
    params: { query },
  })
  return data.data
}

export async function findMovie(imdbId: string): Promise<Movie> {
  const { data } = await http.get<ApiEnvelope<Movie>>(`/movies/${imdbId}`)
  return data.data
}

export async function getHistory(): Promise<string[]> {
  const { data } = await http.get<ApiEnvelope<string[]>>('/searches')
  return data.data
}
