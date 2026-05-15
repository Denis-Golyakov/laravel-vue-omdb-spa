<template>
  <div id="app-ctn" class="p-4">
    <h1 class="text-3xl font-bold mb-4">{{ t('app_header') }}</h1>
    <language-switch />
    <router-view />
  </div>
</template>

<script setup lang="ts">
import LanguageSwitch from '@/components/LanguageSwitch.vue';
import { useI18n } from 'vue-i18n'

const { t } = useI18n();
</script>

<style scoped>
#app-ctn {
  position: relative;
}
</style>