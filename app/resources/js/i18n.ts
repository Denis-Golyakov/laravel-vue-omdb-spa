import { createI18n } from 'vue-i18n'
import en from './i18n/en.json'
import lv from './i18n/lv.json'

export const i18n = createI18n({
  legacy: false,
  locale: 'en',
  fallbackLocale: 'en',
  messages: {
    en,
    lv
  }
})
