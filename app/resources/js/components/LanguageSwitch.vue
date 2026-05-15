<template>
  <div id="language-selector-ctn">
    <a v-for="locale in locales" href="#" :key="locale" role="button" tabindex="0"
      :class="{ 'active': activeLocale === locale }" @click.prevent="setLocale(locale)">{{
        locale
      }}</a>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n'
const { availableLocales, locale } = useI18n()

type Locale = typeof locale.value
const locales = availableLocales as Locale[]

const activeLocale = computed(() => locale.value)

const setLocale = (newLocale: Locale) => {
  locale.value = newLocale;
};
</script>

<style scoped>
#language-selector-ctn {
  position: absolute;
  top: 0;
  right: 0;
  margin: 1rem;
}

#language-selector-ctn a {
  cursor: pointer;
  padding-left: .5rem;
}

#language-selector-ctn a:hover {
  color: var(--color-primary-focus);
}

#language-selector-ctn a.active {
  font-weight: bold;
  text-decoration: underline;
}
</style>