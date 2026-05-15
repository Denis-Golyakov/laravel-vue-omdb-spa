export interface ApiEnvelope<T> {
    status: 'success' | 'error';
    data: T;
    message?: string;
}

export interface MovieListItem {
    imdb_id: string;
    title: string;
    year: string;
    poster: string | null;
}

export interface MovieRating {
    source: string;
    value: string;
}

export interface Movie {
    title: string;
    year: string | null;
    rated: string | null;
    released: string | null;
    runtime: string | null;
    genre: string | null;
    director: string | null;
    writer: string | null;
    actors: string | null;
    plot: string | null;
    language: string | null;
    country: string | null;
    awards: string | null;
    poster: string | null;
    ratings: MovieRating[];
    metascore: string | null;
    imdb_rating: string | null;
    imdb_votes: string | null;
    imdb_id: string | null;
    type: string | null;
    dvd: string | null;
    box_office: string | null;
    production: string | null;
    website: string | null;
}