const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

class WebpackConfig {
  constructor(mode = 'development') {
    this.mode = mode;
    this.outputPath = path.resolve(__dirname, 'public');
  }

  get isProduction() {
    return this.mode === 'production';
  }

  build() {
    return {
      entry: {
        'enhancedfields': './_dev/enhancedfields.ts',
      },
      mode: this.mode,
      output: {
        path: this.outputPath,
        filename: this.#getDestinationFilename('.js', false),
        chunkFilename: this.#getDestinationFilename('.js', true),
        pathinfo: !this.isProduction,
        clean: true
      },
      module: {
        rules: this.#buildRules(),
      },
      plugins: this.#buildPlugins(),
      devtool: this.isProduction
        // https://webpack.js.org/configuration/devtool/#for-production
        ? 'source-map'
        // https://webpack.js.org/configuration/devtool/#for-development
        : 'inline-source-map',
    }
  }

  #getDestinationFilename(append = '', contenthash = null) {
    return (this.isProduction && contenthash !== false || contenthash ? '[name].[contenthash]' : '[name]') + append
  }

  #buildRules() {
    const rules = [];
    rules.push({
      test: /\.(js|jsx|ts|tsx)?$/,
      exclude: /(node_modules)/,
      resolve: {
        fullySpecified: false,
        extensions: ['.js', '.ts'],
      },
      use: {
        loader: 'esbuild-loader',
        options: {
          loader: 'ts',
          target: 'es2015',
        },
      },
    });

    rules.push({
      test: /\.(sa|sc|c)ss$/,
      use: [
        MiniCssExtractPlugin.loader,
        {
          loader: 'css-loader',
          options: {
            sourceMap: !this.isProduction
          }
        },
        {
          loader: 'postcss-loader',
          options: {
            sourceMap: !this.isProduction
          }
        },
        {
          loader: 'sass-loader',
          options: {
            sourceMap: true,
          }
        }
      ]
    })

    return rules;
  }

  #buildPlugins() {
    const plugins = [];

    plugins.push(
      new MiniCssExtractPlugin({filename: this.#getDestinationFilename('.css', true)}),
    )

    return plugins;
  }
}

module.exports = (env, argv) => {
  return (new WebpackConfig(argv.mode)).build();
};
