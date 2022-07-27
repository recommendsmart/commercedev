let mix = require('laravel-mix')
let fs = require('fs-extra')

let getFiles = function (dir) {
  // get all 'files' in this directory
  // filter directories
  return fs.readdirSync(dir).filter(file => {
    if (!file.startsWith("_")) {
      return fs.statSync(`${dir}/${file}`).isFile();
    }
  });
};


/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

  // Compile all the css per directory.
  const directories = [
    'product-teaser',
    'commerce',
    'pages',
    'components'
  ];


  directories.forEach(directory => {
    getFiles('src/scss/' + directory).forEach(function (filepath) {
      mix.sass('src/scss/' + directory +'/' + filepath, 'dist/' + directory + '/')
    });
  })


  // Copy Bootstrap assets
  mix.before(() => {
    // fs.copySync('node_modules/bootstrap-icons/bootstrap-icons.svg', 'dist/images/bootstrap-icons.svg')
    // fs.copySync('node_modules/bootstrap/dist/js/bootstrap.bundle.js.map', 'dist/bootstrap.bundle.js.map')
    // fs.copySync('node_modules/bootstrap-icons/icons', 'src/icons')
    fs.copySync('node_modules/bootstrap/dist/js/bootstrap.bundle.js', 'dist/bootstrap.bundle.js');
  });


  mix
  .js('src/js/main.js', 'dist/')
  .js('src/js/product.images.js', 'dist/')
  .js('src/js/throbber.js', 'dist/')

  .sass('src/scss/main.scss', 'dist/')
  .sass('src/scss/color.scss', 'dist/')

  .options({
    processCssUrls: false,
    postCss: [
      require('postcss-inline-svg')
    ],
    autoprefixer: {}
  });

