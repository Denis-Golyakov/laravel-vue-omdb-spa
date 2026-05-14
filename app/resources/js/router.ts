import { createRouter, createWebHistory } from 'vue-router'

import Home from '@/views/Home.vue'
import MovieDetails from '@/views/MovieDetails.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      name: 'Home',
      path: '/',
      component: Home
    },
    {
      name: 'MovieDetails',
      path: '/movie/:imdbId',
      component: MovieDetails
    }
  ]
})

router.afterEach((to, from) => {
  // DEBUG Logging
  // console.log('router::afterEach', from, to)
})

export default router