const js = require("@eslint/js");

module.exports = [
  js.configs.recommended,
  {
    files: ["assets/js/**/*.js"],
    languageOptions: {
      ecmaVersion: 2021,
      sourceType: "script",
      globals: {
        window: "readonly",
        document: "readonly",
        wp: "readonly",
        tinymce: "readonly",
        ajaxurl: "readonly",
        fetch: "readonly",
        FormData: "readonly",
        Blob: "readonly",
        URL: "readonly",
        MutationObserver: "readonly",
        Event: "readonly",
        naviFaqAdminI18n: "readonly",
        naviFaqEditorSettings: "readonly",
      },
    },
  },
];
