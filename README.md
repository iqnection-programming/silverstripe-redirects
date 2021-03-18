# SilverStripe Redirects
Provides an interface to setup redirects from old URLs to new pages
Resolves alternate domains if you're meging sites together
Great for setting up 301 redirects when launching a new site with URL changes

## Installation
```
$ composer require iqnection/silverstripe-redirects
$ ~public_html/vendor/bin/sake dev/build
```

It's strongly suggested to set the environment variable for your main site URL
In your root .env file and add/update the following
```
SS_BASE_URL="https://www.your-domain.com"
```


## Usage
Navigate to Redirects area in the CMS to manage your redirects

Domains will automatically be matched regardless of if "www" is included, or if HTTP or HTTPS.

### Examples:
Redirect from `example.com` to somewhere
Request URLs:
- `example.com` => redirect match
- `www.example.com` => redirect match
- `subdomain.example.com` => no match
- `www.subdomain.example.com` => no match

Redirect from subdomain `anything.example.com` to somewhere (assuming anything.example.com and example.com resolve to different hosting accounts)
Request URLs:
- `example.com` => redirect match
- `www.example.com` => no match
- `subdomain.example.com` => match
- `www.subdomain.example.com` => redirect match

Redirect from alternate domain `another-example.com` to somewhere on example.com (assuming another-example.com and example.com resolve to the same hosting account)
Request URLs:
- `example.com` => redirect match
- `www.example.com` => redirect match
- `another-example.com` => redirect match
- `www.another-example.com` => redirect match

While the site is in dev mode, all redirects will be 302 regardless of their configuration. This is to keep from the redirects being cached by browsers.

## Importing
Import redirects using the CMS Bulk Import Form
Your CSV will need the following column titles
- FromPath: Relative path or full URL of the old page or file
- Destination: Relative path or full URL of the new page or file
