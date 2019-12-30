const autoprefixer = require('autoprefixer');
const path = require("path");

module.exports = [];
module.exports.push({
    mode: 'production',
    entry: ['./assets/app.js'],
    output: {
        filename: 'app.js',
        path: path.resolve(__dirname, 'public/'),
        libraryTarget: 'var',
        library: 'App',
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                loader: 'babel-loader'
            }
        ],
    },
});

module.exports.push({
    mode: 'production',
    entry: ['./assets/app.scss'],
    output: {
        filename: 'style.js',
        path: path.resolve(__dirname, 'public/'),
    },
    module: {
        rules: [
            {
                test: /\.scss$/,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: 'app.css',
                        },
                    },
                    {loader: 'extract-loader'},
                    {loader: 'css-loader'},
                    {
                        loader: 'postcss-loader',
                        options: {
                            plugins: () => [autoprefixer()]
                        }
                    },
                    {
                        loader: 'sass-loader',
                        options: {
                            sassOptions: {
                                includePaths: ['./node_modules'],
                            }
                        },
                    }
                ],
            }
        ],
    },
});