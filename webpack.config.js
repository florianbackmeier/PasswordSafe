module.exports = [{
    entry: './assets/app.scss',
    output: {
        // This is necessary for webpack to compile
        // But we never use style-bundle.js
        filename: 'style-bundle.js',
    },
    module: {
        rules: [{
            test: /\.scss$/,
            use: [
                {
                    loader: 'file-loader',
                    options: {
                        name: 'public/app.css',
                    },
                },
                { loader: 'extract-loader' },
                { loader: 'css-loader' },
                {
                    loader: 'sass-loader',
                    options: {
                        includePaths: ['./node_modules']
                    }
                }
            ]
        }]
    },
}];

module.exports.push({
    entry: "./assets/app.js",
    output: {
        filename: "public/app.js"
    },
    module: {
        loaders: [{
            test: /\.js$/,
            loader: 'babel-loader',
            query: {
                presets: ['es2015']
            }
        }]
    },
});
