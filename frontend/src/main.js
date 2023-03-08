/* Copyright (C) 2023 Manticore Software Ltd
 * You may use, distribute and modify this code under the
 * terms of the AGPLv3 license.
 *
 * You can find a copy of the AGPLv3 license here
 * https://www.gnu.org/licenses/agpl-3.0.txt
 */

import Vue from 'vue'
import App from './App.vue'
import VueLogger from 'vuejs-logger';
import VTooltip from 'v-tooltip';
import VueGtag from "vue-gtag";
import 'bootstrap'


const isProduction = process.env.NODE_ENV === 'production';

const options = {
  isEnabled: true,
  logLevel : isProduction ? 'error' : 'debug',
  stringifyArguments : false,
  showLogLevel : true,
  showMethodName : true,
  separator: '|',
  showConsoleColors: true
};

Vue.config.productionTip = false
Vue.use(VueLogger, options);
Vue.use(VTooltip);

if (process.env.VUE_APP_GA) {
  Vue.use(VueGtag, {
    config: { id: process.env.VUE_APP_GA }
  });
}

new Vue({
  render: h => h(App),
}).$mount('#app')
