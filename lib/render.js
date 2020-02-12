const puppeteer = require( 'puppeteer' )
const jsdom = require( 'jsdom' )
const fetch = require( 'node-fetch' )
const { JSDOM } = jsdom
const { WP_DOMAIN } = require( './config' )
const { checkStatus, parseJSON, getDefaultHeaders } = require( './helpers' )

/**
 * Loop the json array of renders and do the magic.
 *
 * @param {array} json
 */
const SSR = async( json ) => {
  const browser = await createBrowser()
  const promises = json.map(({ id, url, appSelector, waitForSelector }) => {
    return getHtml( browser, url, waitForSelector )
      .then(( html ) => parseApp( html, appSelector ))
      .then(( app ) => saveHtml( app, id ))
      .catch( console.error )
  })
  await Promise.all( promises )
  console.log( 'Done, closing browser.' )
  await browser.close()
}

/**
 * Create browser instance.
 */
const createBrowser = async() => {
  console.log( 'Starting browser.' )
  const browser = await puppeteer.launch({
    headless: true,
    args: [ '--no-sandbox' ]
  })
  return browser
}

/**
 * Use puppeteer to render the page html.
 * Wait for the selector to appear in the DOM.
 *
 * @param {Browser} browser
 * @param {string} url
 * @param {string} selector
 */
const getHtml = ( browser, url, selector ) => {
  return new Promise( async( resolve, reject ) => {
    try {
      console.log( `Starting to render ${url}` )
      const page = await browser.newPage()
      await page.setExtraHTTPHeaders({ 'X-WP-SSR': 'ssr' })
      await page.setViewport({ width: 1920, height: 1080 })
      await page.goto( url, { waitUntil: 'networkidle0', timeout: 5000 })
      await page.waitForSelector( selector )
      const html = await page.content()
      resolve( html )
    } catch ( error ) {
      reject( error )
    }
  })
}

/**
 * Parse the app markup from the html
 * by using the selector.
 *
 * @param {string} html
 * @param {string} selector
 */
const parseApp = ( html, selector ) => {
  return new Promise(( resolve, reject ) => {
    try {
      const dom = new JSDOM( html )
      const app = dom.window.document.querySelector( selector )
      resolve( app ? app.innerHTML : '' )
    } catch ( error ) {
      reject( error )
    }
  })
}

/**
 * Save the app markup to the render post by
 * using the post id.
 *
 * @param {string} html
 * @param {int} id
 */
const saveHtml = ( html, id ) => {
  const endpoint = `${WP_DOMAIN}/wp-json/wp-ssr/v1/save_renders`
  const body = {
    renders: [{
      id: id,
      html: html.trim()
    }]
  }
  return new Promise(( resolve, reject ) => {
    fetch( endpoint, {
      method: 'post',
      body: JSON.stringify( body ),
      headers: getDefaultHeaders()
    })
      .then( checkStatus )
      .then( parseJSON )
      .then( resolve )
      .catch( reject )
  })
}

module.exports = {
  SSR
}
