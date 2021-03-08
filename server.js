const express = require( 'express' )
const fetch = require( 'node-fetch' )
var cron = require( 'node-cron' )
const { API_KEY, WP_DOMAIN, SCHEDULE } = require( './lib/config' )
const { parseJSON, checkStatus, getDefaultHeaders } = require( './lib/helpers' )
const { SSR } = require( './lib/render' )
const app = express()
const port = 3000


if ( SCHEDULE ) {
  cron.schedule( SCHEDULE, () => {
    console.log( 'Doing render according to schedule' )
    doRender()
  })
}

app.get( '/', ( req, res ) => {
  const requestKey = req.header( 'X-WP-SSR-Key' )

  if ( requestKey !== API_KEY ) {
    console.error( 'Unauthorized' )
    return res.status( 401 ).send( 'Not allowed' )
  }

  doRender()

  return res.send( 'OK' )
})


/**
 * Run the rendering process.
 */
const doRender = () => {
  // Fetch the renders.
  const endpoint = `${WP_DOMAIN}/wp-json/wp-ssr/v1/renders`
  fetch( endpoint, {
    headers: getDefaultHeaders()
  })
    .then( checkStatus )
    .then( parseJSON )
    .then( SSR )
    .catch(( err ) => console.error( err ))
}

app.listen( port, () => console.log( `Render App listening on port ${port}!` ))
