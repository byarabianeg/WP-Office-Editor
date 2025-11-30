const path = require('path');

module.exports = {
  entry: path.resolve(__dirname, 'src', 'ckeditor.js'),
  output: {
    filename: 'ckeditor.js',
    path: path.resolve(__dirname, 'build'),
    library: 'DecoupledEditor',
    libraryTarget: 'var'
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env']
          }
        },
        exclude: /node_modules/
      }
    ]
  },
  resolve: {
    fallback: {
      fs: false,
      path: false
    }
  }
};
