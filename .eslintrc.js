module.exports = {
  ...require('@silverstripe/eslint-config/.eslintrc'),
  overrides: [
    {
      files: ['**/tests/**/*-test.js'],
      env: { jest: true },
    },
  ],
};
