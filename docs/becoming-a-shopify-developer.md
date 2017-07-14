# Becoming a Shopify Developer

If you don't have a Shopify Partner account yet head over to [the partners page](http://shopify.com/partners) to create one, you'll need it before you can start developing apps.

Once you have a Partner account create a new application to get an API key and other API credentials. To create a development application set the `Application Callback URL` to

```bash
https://localhost:8000/
```

and the `redirect_uri` to

https://localhost:8000/authenticate

This way you'll be able to run the app on your local machine. *Note*: HTTPS is note required for Shopify apps, so you will need to use a self-signed certificate accepted by your system/browser to run apps locally. The best way to do this is through a Docker setup, or Homestead, and configuring Nginx to use your self-signed certificate.