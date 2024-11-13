const path = require('path');

module.exports = {
  publicPath: process.env.NODE_ENV === 'production' ? '/event-inspector/' : '/',
  outputDir: path.resolve(__dirname, './dist'),
  filenameHashing: false,
  css: {
    extract: false,
    sourceMap: process.env.NODE_ENV === 'development'
  },
  configureWebpack: {
    output: {
      filename: 'gtm-event-inspector.js',
    },
    optimization: {
      splitChunks: false
    },
    devtool: process.env.NODE_ENV === 'development' ? 'source-map' : false,
    watch: process.env.NODE_ENV === 'development',
    watchOptions: {
      poll: true,
      ignored: /node_modules/
    }
  },
  chainWebpack: config => {
    config.optimization.delete('splitChunks')
    config.plugins.delete('prefetch')
    config.plugins.delete('preload')
  }
} 