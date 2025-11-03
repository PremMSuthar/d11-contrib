module.exports = {
  content: [
    './web/themes/**/*.html.twig',
    './web/modules/**/*.html.twig',
    './web/core/themes/**/*.html.twig',
    './web/sites/*/themes/**/*.html.twig'
  ],
  css: [
    './web/themes/custom/*/css/bootstrap.css',
    './web/libraries/bootstrap/css/bootstrap.css'
  ],
  whitelist: [
    'active',
    'show',
    'fade',
    'collapse',
    'collapsing'
  ],
  whitelistPatterns: [
    /^d-/,
    /^col-/,
    /^btn/,
    /^modal/,
    /^dropdown/,
    /^nav/,
    /^carousel/,
    /^alert/,
    /^badge/,
    /^card/,
    /^form-/,
    /^input-/,
    /^table/,
    /^text-/,
    /^bg-/,
    /^border-/,
    /^p-/,
    /^m-/,
    /^w-/,
    /^h-/
  ],
  extractors: [
    {
      extractor: content => content.match(/[\w-/:]+(?<!:)/g) || [],
      extensions: ['html', 'twig', 'php', 'js']
    }
  ]
};