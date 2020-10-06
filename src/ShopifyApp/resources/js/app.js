import createApp from '@shopify/app-bridge'
import getSessionToken from '@shopify/app-bridge-utils'
import axios from 'axios'

const token = document.getElementById('token').innerText
const key = document.getElementById('shop_key').innerText
const name = document.getElementById('shop_name').innerText

const app = createApp({
  apiKey: key,
  shopOrigin: name,
  forceRedirect: true,
});

const sessionToken = getSessionToken(app).then((token) => {
  axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
})

window.axios = axios
window.app = app
