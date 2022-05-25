//vue.config.js
const Dotenv = require('dotenv-webpack');

module.exports = {
    chainWebpack: config => {
        config
            .plugin('html')
            .tap(args => {
                args[0].title = "Fair database benchmarks";
                return args;
            })
    },
    configureWebpack: {
        plugins: [
            new Dotenv()
        ]
    }
}
