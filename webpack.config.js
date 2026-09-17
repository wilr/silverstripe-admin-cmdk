const Path = require('path');
const { JavascriptWebpackConfig, CssWebpackConfig } = require('@silverstripe/webpack-config');

const PATHS = {
  ROOT: Path.resolve(),
  SRC: Path.resolve('client/src'),
  DIST: Path.resolve('client/dist'),
};

const config = [
  new JavascriptWebpackConfig('js', PATHS, 'wilr/silverstripe-admin-cmdk')
    .setEntry({
      bundle: `${PATHS.SRC}/bundles/bundle.js`,
    })
    .getConfig(),
  new CssWebpackConfig('css', PATHS)
    .setEntry({
      bundle: `${PATHS.SRC}/styles/bundle.scss`,
    })
    .getConfig(),
];

module.exports = config;
