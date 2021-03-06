/**
 * CONFIG
 */

 require( 'dotenv' ).config()

const WP_DOMAIN = process.env.WP_DOMAIN
const API_KEY = process.env.API_KEY
const ENV = process.env.NODE_ENV
const SCHEDULE = process.env.SCHEDULE

console.log( `
Starting service with configuration:

WP_DOMAIN: ${WP_DOMAIN}
API_KEY: ${API_KEY}
ENV: ${ENV}
SCHEDULE: ${SCHEDULE}
` )

module.exports = {
  WP_DOMAIN,
  API_KEY,
  ENV,
  SCHEDULE
}
