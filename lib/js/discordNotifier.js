/**
 * Send an HTTP request to any URL.
 *
 * Usage:
 *   sendRequest({}, 'https://example.com')                  // GET
 *   sendRequest({}, 'https://example.com', 'POST')         // POST via 3rd arg
 *   sendRequest({ method: 'PUT', body: {a:1} }, url)       // method inside request
 *
 * @param {Object} request  Optional: { method, headers, body, signal, ... }
 * @param {string} url      destination URL
 * @param {string} method   optional override for HTTP method
 * @returns {Promise<Object>} { ok, status, data, headers } or { ok: false, error }
 */
async function sendRequest(request = {}, url, method) {
  if (typeof url !== 'string') {
    return { ok: false, error: 'Second argument "url" must be a string' };
  }

  // Resolve method: third arg > request.method > default GET
  const resolvedMethod = (method || request.method || 'GET').toUpperCase();

  // Build headers
  const headers = new Headers(request.headers || {});

  // Prepare body
  let body = request.body ?? null;

  // If body is a plain object (not FormData/URLSearchParams/Blob), stringify to JSON
  const isFormData = (typeof FormData !== 'undefined') && (body instanceof FormData);
  const isURLSearchParams = (typeof URLSearchParams !== 'undefined') && (body instanceof URLSearchParams);
  const isBlob = (typeof Blob !== 'undefined') && (body instanceof Blob);

  if (body && typeof body === 'object' && !isFormData && !isURLSearchParams && !isBlob) {
    // default to JSON if content-type not set
    if (!headers.has('Content-Type')) {
      headers.set('Content-Type', 'application/json');
    }
    const ct = headers.get('Content-Type') || '';
    if (ct.includes('application/json')) {
      try {
        body = JSON.stringify(body);
      } catch (e) {
        return { ok: false, error: 'Failed to stringify body: ' + e.message };
      }
    }
    // otherwise leave body as-is (caller may expect different format)
  }

  // Don't send body for GET/HEAD
  const options = {
    method: resolvedMethod,
    headers,
  };
  if (!['GET', 'HEAD'].includes(resolvedMethod) && body !== null && body !== undefined) {
    options.body = body;
  }

  // propagate AbortSignal if provided
  if (request.signal) options.signal = request.signal;

  try {
    const response = await fetch(url, options);

    // attempt to parse JSON, otherwise return text
    const contentType = response.headers.get('content-type') || '';
    let data;
    if (contentType.includes('application/json')) {
      data = await response.json();
    } else {
      data = await response.text();
    }

    // Return headers as plain object for easier consumption
    const respHeaders = {};
    response.headers.forEach((v, k) => { respHeaders[k] = v; });

    return {
      ok: response.ok,
      status: response.status,
      headers: respHeaders,
      data
    };
  } catch (err) {
    // network / CORS / abort error
    return { ok: false, error: err.message || String(err) };
  }
}