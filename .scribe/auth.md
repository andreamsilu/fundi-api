# Authenticating requests

To authenticate requests, include an **`Authorization`** header in the form **`"Basic {credentials}"`**. 
The value of `{credentials}` should be your username/id and your password, joined with a colon (:), 
and then base64-encoded.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.


