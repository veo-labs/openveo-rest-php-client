# 2.1.0 / YYYY-MM-DD

## NEW FEATURES

- Make \OpenVeo\Client\Client.authenticate and \OpenVeo\Client\Client.isAuthenticated public

## BUG FIXES

- Fix cURL error message when using client without a certificate
- Fix remaining cookie file which wasn't removed after destructing the client

# 2.0.0 / 2017-06-14

## BREAKING CHANGES

- Prototype of the Client class constructor has changed. It now accepts the url of the web service, the client id, the client secret and an optional certificate for HTTPS support
- get / post / put / delete methods now excepts the end point instead of the full url
- Decrease curl timeout when requesting OpenVeo Web Service from 30 seconds to 10 seconds

## NEW FEATURES

- Add support for HTTPS
- Add more precise error message when request to the Web Service failed

# 1.0.2 / 2017-05-04

## BUG FIXES

- Handle expired token. Authentication wasn't automatically retried when client token expired. It now tries to authenticate again before returning an error

# 1.0.1 / 2016-01-19

Remove unwanted sleep instruction on all requests.

A debugging sleep was set on all requests made to the web service waiting for 10 long seconds before returning
the response.

# 1.0.0 / 2015-10-29

First stable version of the OpenVeo PHP REST client for OpenVeo Web Service.
