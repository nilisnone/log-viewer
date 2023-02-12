import { createApp, markRaw } from 'vue';
import { createPinia } from 'pinia';
import Base from './base';
import axios from 'axios';
import { createRouter, createWebHistory, useRoute } from 'vue-router';
import VueJsonPretty from 'vue-json-pretty';

let token = document.head.querySelector('meta[name="csrf-token"]');
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

window.LogViewer.basePath = '/' + window.LogViewer.path;

let routerBasePath = window.LogViewer.basePath + '/';

if (window.LogViewer.path === '' || window.LogViewer.path === '/') {
  routerBasePath = '/';
  window.LogViewer.basePath = '';
}

const router = createRouter({
  routes: [{
    path: `/${LogViewer.path}`,
    name: 'home',
    component: require('./home').default,
  }],
  history: createWebHistory(),
  base: routerBasePath,
});
const route = useRoute();
const pinia = createPinia();
pinia.use(({ store }) => {
  store.$router = markRaw(router);
  store.$route = route;
})

const app = createApp({
  router,
});

app.use(router);
app.use(pinia);
app.mixin(Base);
app.component('vue-json-pretty', VueJsonPretty);
app.provide('$http', axios.create());

app.mount('#log-viewer');
