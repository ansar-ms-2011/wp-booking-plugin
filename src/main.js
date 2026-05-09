import Vue from 'vue';
import App from './App.vue';
import store from './store';
import api from './api/client';
import * as VueGoogleMaps from 'vue2-google-maps'

Vue.prototype.$api = api;

Vue.config.productionTip = false;
Vue.use(VueGoogleMaps, {
    load: {
        key: 'AIzaSyCI3JDsXcBaCQsWVawwk2ed4SvAghkEeU8',
        libraries: 'places',
    }
})
new Vue({
    store,
    render: h => h(App)
}).$mount('#mevp-app');
