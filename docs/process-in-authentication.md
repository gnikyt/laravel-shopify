# Process in Authentication

## Fresh Shop

A shop that has never used your app before, the process will be as followed:

1. Package will direct shop to `/login`
2. Shop will enter their "myshopify" domain
3. Package will redirect shop to the Shopify auth page for the app based on API key, API scopes, and redirect URL
4. Shop will accept the installation
5. Shopify will then redirect the shop back to the redirect URL with `code`, `hmac`, `timestamp`, and `shop` in the query string
6. Pacakge will take the `hmac` and verify it
7. If successfull, then we know the request is from Shopify, we then use the `code` to grab an API token for the shop
8. Package will add the shop with their domain and token to the database
9. Pacakge will redirect the shop to `/` to use the app

Essentially: Install -> Accept -> Verify -> Get Token -> Save to Database -> Show App

## Re-visiting Shop

A shop thats already setup on the system and accepted the app, the process will be as followed:

1. Shop clicks app in App section of their store
2. Package will confirm the shop accessing is the same shop
2a. If shop is not confirmed, package will direct shop to reauthenticate
3. Package will look the shop up in the database and show `/` so they can use the app

Essentially: Confirm Shop -> Update Token -> Show App
