module.exports = {
    root: true,
    env: {
        browser: true,
        es2022: true,
        node: true,
        serviceworker: true,
    },
    extends: ['eslint:recommended', 'plugin:vue/vue3-essential'],
    parserOptions: {
        ecmaVersion: 'latest',
        sourceType: 'module',
    },
    globals: {
        Ziggy: 'readonly',
        define: 'readonly',
        route: 'readonly',
        axios: 'readonly',
    },
    rules: {
        'vue/multi-word-component-names': 'off',
        'vue/no-reserved-component-names': 'off',
        'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
    },
};
