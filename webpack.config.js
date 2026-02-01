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
        filename: this.#getDestinationFilename('.js'),
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

  #getDestinationFilename(append = '') {
    return (this.isProduction ? '[name].[contenthash]' : '[name]') + append
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
      new MiniCssExtractPlugin({filename: this.#getDestinationFilename('.css')}),
    )

    return plugins;
  }
}

module.exports = (env, argv) => {
  const wpc = new WebpackConfig(argv.mode);

  const config = wpc.build();
  // console.log(config);
  // process.exit();
  return config;
};
