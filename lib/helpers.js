const { API_KEY } = require( './config' )

/**
 * Parse the JSON value from Response.
 *
 * @param {Response} response
 */
const parseJSON = ( response ) => {
  return response.json()
}

/**
 * Check the Response status.
 *
 * @param {Response} response
 */
const checkStatus = ( response ) => {
  if ( response.ok ) {
    return response
  } else {
    let error = new Error( response.statusText )
    error.body = response.text()
    throw error
  }
}

/**
 * Helper to get default fetch headers.
 */
const getDefaultHeaders = () => {
  return {
    'Content-Type': 'application/json',
    'Cache-Control': 'no-store',
    'X-WP-SSR-Key': API_KEY
  }
}

/**
 * Async sleep.
 *
 * @param {int} timeout
 */
const sleep = ( timeout = 250 ) => {
  return new Promise(( resolve ) => setTimeout( resolve, timeout ))
}

module.exports = {
  parseJSON,
  checkStatus,
  getDefaultHeaders,
  sleep
}
