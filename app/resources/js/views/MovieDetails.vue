<template>
  <div class="movie-details-ctn">
    <div class="loader" v-if="isLoading">{{ t('loading') }}</div>
    <div v-else-if="errorMessage" class="movie-details-error-ctn">{{ errorMessage }}</div>
    <template v-else>
      <div class="header">
        <div class="action">
          <button @click="goBack">&laquo; {{ t('buttons.back') }}</button>
        </div>
        <div class="description">
          <h2 class="title">{{ movieInfo?.title }}</h2>
          <ul class="subtitle">
            <li v-for="elementName in subtitleElementList" :key="elementName">{{
              getSubtitleElementValue(elementName) }}</li>
          </ul>
        </div>
      </div>
      <div class="movie-info">
        <div class="poster">
          <img :src="movieInfo?.poster ?? POSTER_FALLBACK_URL" alt="" @error="handlePosterError" />
        </div>
        <div class="description">
          <p class="plot">{{ movieInfo?.plot }}</p>
          <template v-for="elementName in descriptionElementList" :key="elementName">
            <h4>{{ t('movie.' + elementName) }}</h4>
            <p>{{ getDescriptionElementValue(elementName) }}</p>
          </template>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { computed, onMounted, ref, watch } from 'vue';
import { findMovie } from '@/api';
import { Movie } from '@/types/api';
import { ApiError } from '@/types/errors';
import { POSTER_FALLBACK_URL } from '@/constants';

const { t } = useI18n();

const route = useRoute()
const router = useRouter();

const subtitleElements = [
  'year',
  'rated',
  'released',
  'runtime',
  'genre'
] as const;
type SubtitleElement = typeof subtitleElements[number];

const descriptionElements = [
  'director',
  'writer',
  'actors',
  'language',
  'country',
  'awards',
  'metascore',
  'box_office'
] as const;
type DescriptionElement = typeof descriptionElements[number];

const errorMessage = ref<string | null>(null);
const isLoading = ref(true);
const movieInfo = ref<Movie | null>(null);

const goBack = () => {
  if (window.history.state?.back) {
    router.go(-1)
  } else {
    router.push({ name: 'Home' })
  }
}

const handlePosterError = (event: Event) => {
  const imageElement = event.currentTarget as HTMLImageElement;
  imageElement.src = POSTER_FALLBACK_URL;
};

const getDescriptionElementValue = (elementName: DescriptionElement): string | null => {
  if (!movieInfo.value) {
    return '';
  }

  return movieInfo.value[elementName];
}

const getSubtitleElementValue = (elementName: SubtitleElement): string | null => {
  if (!movieInfo.value) {
    return '';
  }

  return movieInfo.value[elementName];
}

const loadMovieDetails = async () => {
  const id = route.params.imdbId as string;
  isLoading.value = true;
  errorMessage.value = null;
  try {
    movieInfo.value = await findMovie(id);
  } catch (err) {
    if (err instanceof ApiError) {
      errorMessage.value = t(`errors.${err.code}`);
    } else {
      errorMessage.value = t('errors.unknown');
    }
  } finally {
    isLoading.value = false;
  }
}

const descriptionElementList = computed<DescriptionElement[]>(() =>
  descriptionElements.filter(name => movieInfo.value?.[name])
)

const subtitleElementList = computed<SubtitleElement[]>(() =>
  subtitleElements.filter(name => movieInfo.value?.[name])
)

onMounted(async () => {
  await loadMovieDetails();
});

watch(() => route.params.imdbId, async () => {
  await loadMovieDetails();
});
</script>

<style scoped>
.movie-details-ctn {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.header {
  display: flex;
  justify-content: start;
  align-items: center;
}

.header .action {
  margin-right: 1rem;
}

.header .action button {
  background-color: var(--color-primary);
  border-radius: .5rem;
  cursor: pointer;
  padding: .5rem;
}

.header .description {
  display: flex;
  flex-direction: column;
}

.header .description .title {
  font-size: 2rem;
  line-height: 1em;
  margin: 0;
  padding: 0;
}

.header .description .subtitle {
  color: #666;
  font-size: .6rem;
  line-height: 1em;
  margin: 0;
  padding: 0;
}

.header .description .subtitle li {
  display: inline-block;
  margin-right: .5rem;
}

.header .description .subtitle li:not(:first-of-type):before {
  content: '\ffed';
  margin-right: .75em;
}

.movie-info {
  display: flex;
  gap: .5rem;
  flex-direction: row;
}

.movie-info .poster {
  width: 100%;
}

.movie-info .description .plot {
  font-size: 1.15rem;
  line-height: 1.15em;
}

.movie-info .description h4 {
  font-size: .75rem;
  line-height: 1em;
  margin: 0;
  padding: .5rem 0 0;
}

.movie-details-error-ctn {
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