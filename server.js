const express = require( 'express' )
const fetch = require( 'node-fetch' )
const { API_KEY, WP_DOMAIN } = require( './lib/config' )
const { parseJSON, checkStatus, getDefaultHeaders } = require( './lib/helpers' )
const { SSR } = require( './lib/render' )
const app = express()
const port = 3000

app.get( '/', ( req, res ) => {
  const requestKey = req.header( 'X-WP-SSR-Key' )

  if ( requestKey !== API_KEY ) {
    console.error( 'Unauthorized' )
    return res.status( 401 ).send( 'Not allowed' )
  }

  // Fetch the renders.
  const endpoint = `${WP_DOMAIN}/wp-json/wp-ssr/v1/renders`
  fetch( endpoint, {
    headers: getDefaultHeaders()
  })
    .then( checkStatus )
    .then( parseJSON )
    .then( SSR )
    .catch(( err ) => console.error( err ))

  return res.send( 'OK' )
})

app.listen( port, () => console.log( `Render App listening on port ${port}!` ))
