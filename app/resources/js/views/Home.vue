<template>
  <div>
    <div class="search-input-ctn">
      <input type="text" :disabled="isLoading" :placeholder="t('home.search_placeholder')"
        v-model="searchQuery" @keyup.enter="doSearch" />
      <button :disabled="isLoading" @click="doSearch">{{ t('buttons.search') }}</button>
    </div>
    <div class="search-history-ctn">
      <span class="label">{{ t('home.search_history') }}</span>
      <template v-if="pastQueries.length">
        <span class="badge" v-for="query in pastQueries" :key="query" @click="setQuery(query)">{{
          query }}</span>
      </template>
      <span v-else> &mdash;</span>
    </div>
    <ul class="search-results-ctn">
      <li v-if="isLoading">
        {{ t('loading') }}
      </li>
      <template v-else>
        <li v-if="isNoResults">{{ t('home.search_no_results') }}</li>
        <li v-else v-for="listItem in searchResults" :key="listItem.imdb_id"
          @click="viewMovieDetails(listItem.imdb_id)">
          <div class="movie-poster">
            <img :src="listItem.poster ?? POSTER_FALLBACK_URL" @error="handlePosterError"
              :alt="listItem.title" />
          </div>
          <div class="movie-description">
            <h3>{{ listItem.title }}</h3>
            <h4>{{ listItem.year }}</h4>
          </div>
        </li>
      </template>
    </ul>
    <div v-show="errorMessage" class="search-error-ctn">{{ errorMessage }}</div>
  </div>
</template>

<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { onMounted, ref, watch } from 'vue';
import type { MovieListItem } from '@/types/api';
import { ApiError } from '@/types/errors';
import { POSTER_FALLBACK_URL } from '@/constants';
import { getHistory, searchMovies } from '@/api';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const errorMessage = ref<string | null>(null);
const isLoading = ref(false);
const isNoResults = ref(false);
const pastQueries = ref<string[]>([])
const searchResults = ref<MovieListItem[]>([])
const searchQuery = ref('');

const setQuery = (query: string) => {
  if (isLoading.value) {
    return;
  }

  searchQuery.value = query;
  doSearch();
}

const doSearch = async () => {
  if (isLoading.value || !searchQuery.value) {
    return;
  }

  isLoading.value = true;
  isNoResults.value = false;
  errorMessage.value = null;

  router.replace({ name: 'Home', query: { q: searchQuery.value } })

  try {
    const results = await searchMovies(searchQuery.value);
    searchResults.value = results;
    isNoResults.value = (results.length === 0);
  } catch (err) {
    searchResults.value = [];
    isNoResults.value = false;
    if (err instanceof ApiError) {
      errorMessage.value = t(`errors.${err.code}`);
    } else {
      errorMessage.value = t('errors.unknown');
    }
  } finally {
    await loadSearchHistory();
    isLoading.value = false;
  }
}

const loadSearchHistory = async () => {
  try {
    const history = await getHistory();
    pastQueries.value = history;
  } catch {
    // History is non-critical, fail silently
    pastQueries.value = [];
  }
}

const handlePosterError = (event: Event) => {
  const imageElement = event.currentTarget as HTMLImageElement;
  imageElement.src = POSTER_FALLBACK_URL;
}

const viewMovieDetails = (imdbId: string) => {
  router.push({ name: 'MovieDetails', params: { imdbId } });
}

onMounted(() => {
  const q = route.query.q
  if (typeof q === 'string' && q.trim()) {
    searchQuery.value = q
    doSearch()
  } else {
    loadSearchHistory()
  }
});

watch(() => route.query.q, (newQ) => {
  if (typeof newQ === 'string' && newQ !== searchQuery.value) {
    searchQuery.value = newQ
    doSearch()
  }
});
</script>

<style scoped>
.search-input-ctn {
  display: flex;
  justify-items: start;
}

.search-input-ctn input {
  border: 1px solid #999999;
  border-radius: .5rem;
  flex: 1;
  padding: .5rem;
}

.search-input-ctn input:disabled {
  background-color: #ddd;
  border-color: #ddd;
  color: #666;
  cursor: not-allowed;
}

.search-input-ctn button {
  background-color: var(--color-primary);
  border: none;
  border-radius: .5rem;
  cursor: pointer;
  margin-left: .5rem;
  padding: .5rem;
}

.search-input-ctn button:hover {
  background-color: var(--color-primary-focus);
  color: white;
}

.search-input-ctn button:disabled {
  background-color: #ddd;
  cursor: not-allowed;
  color: #666;
}

.search-input-ctn input:focus {
  outline-color: var(--color-primary-focus);
}

.search-history-ctn {
  font-size: .75rem;
  line-height: 2rem;
  padding-left: .5rem;
}

.search-history-ctn .label {
  font-weight: bold;
}

.search-history-ctn .badge {
  background-color: var(--color-primary);
  border-radius: .3rem;
  color: black;
  font-size: .6rem;
  margin-left: .25rem;
  padding: .15rem .5rem;
}

.search-history-ctn .badge:hover {
  background-color: var(--color-primary-focus);
  cursor: pointer;
  color: white;
}

.search-results-ctn {
  list-style: none;
  margin: 0;
  padding: 0 .5rem;
}

.search-results-ctn li {
  border: 1px solid #ccc;
  border-radius: .5rem;
  display: flex;
  margin: 0 0 .15rem 0;
  padding: .5rem;
}

.search-results-ctn li:hover {
  background-color: var(--color-primary);
  border-color: var(--color-primary-focus);
  cursor: pointer;
}

.search-results-ctn li .movie-poster {
  margin-right: .5rem;
  width: 50px;
}

.search-results-ctn li .movie-poster img {
  border-radius: .5rem;
  width: 100%;
}

.search-results-ctn li .movie-description {
  flex: 1;
  margin-left: .5rem;
}

.search-results-ctn li .movie-description h3 {
  font-size: 2rem;
  line-height: 1em;
  margin: 0;
  padding: .5rem 0 0;
}

.search-results-ctn li .movie-description h4 {
  color: #666;
  font-size: .75rem;
  line-height: 1em;
  margin: 0;
  padding: 0 0 0 .15rem;
}

.search-error-ctn {
  background-color: #fee;
  border: 1px solid #977;
  border-radius: .5rem;
  color: #d77;
  font-size: .75rem;
  font-weight: bold;
  line-height: 1em;
  margin: 0;
  padding: .5rem 1rem;
}
</style>