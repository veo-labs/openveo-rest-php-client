# 1.0.2 / 2017-05-04

## BUG FIXES

- Handle expired token. Authentication wasn't automatically retried when client token expired. It now tries to authenticate again before returning an error.

# 1.0.1 / 2016-01-19

Remove unwanted sleep instruction on all requests.

A debugging sleep was set on all requests made to the web service waiting for 10 long seconds before returning
the response.

# 1.0.0 / 2015-10-29

First stable version of the OpenVeo PHP REST client for OpenVeo Web Service.
